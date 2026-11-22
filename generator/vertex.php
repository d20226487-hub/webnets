<?php
/**
 * Google Vertex AI: the model that writes site text (generator/content.php). Two ways to connect, as in ACM:
 *   - API key (Vertex Express): POST aiplatform.googleapis.com/v1/publishers/google/models/<model>:generateContent?key=…
 *     Gemini only, Express quotas.
 *   - Service account JSON + project + region: an OAuth2 token minted from the key (RS256 JWT, openssl), then the
 *     project's endpoint. Gemini (publishers/google, generateContent) and Claude (publishers/anthropic, rawPredict).
 * The settings (settings key "vertex") hold the secrets; they are never sent back to the browser, only whether they are set.
 */

const GEN_VERTEX_MODELS = ['gemini-2.5-flash', 'gemini-2.5-pro', 'claude-sonnet-4-5'];

/** The stored Vertex settings: mode (key | sa), api_key, sa_json, project_id, location, claude_location, model. */
function gen_vertex_settings(): array
{
    $q = gen_db()->query("SELECT value FROM settings WHERE key = 'vertex'");
    $v = json_decode((string)$q->fetchColumn(), true) ?: [];
    return $v + ['mode' => 'key', 'api_key' => '', 'sa_json' => '', 'project_id' => '', 'location' => 'us-central1', 'claude_location' => '', 'model' => GEN_VERTEX_MODELS[0]];
}

/** The settings for the browser: secrets replaced by whether they are set (and the service account's e-mail). */
function gen_vertex_public(): array
{
    $s = gen_vertex_settings();
    $sa = json_decode($s['sa_json'], true) ?: [];
    return ['mode' => $s['mode'], 'api_key_set' => $s['api_key'] !== '', 'api_key_end' => $s['api_key'] !== '' ? substr($s['api_key'], -4) : '',
        'sa_set' => $s['sa_json'] !== '', 'sa_email' => $sa['client_email'] ?? '', 'project_id' => $s['project_id'], 'location' => $s['location'],
        'claude_location' => $s['claude_location'], 'model' => $s['model'], 'models' => GEN_VERTEX_MODELS, 'ready' => gen_vertex_ready($s),
        'prices' => (object)gen_vertex_prices(), 'used_models' => gen_usage_models(), 'usage' => gen_usage_summary()];
}

function gen_vertex_ready(array $s): bool
{
    return $s['mode'] === 'sa' ? $s['sa_json'] !== '' && $s['project_id'] !== '' && $s['location'] !== '' : $s['api_key'] !== '';
}

/** Saves the settings from the form. An empty secret field keeps the stored secret; "clear" removes it. */
function gen_vertex_save(array $in): array
{
    $s = gen_vertex_settings();
    $s['mode'] = ($in['mode'] ?? '') === 'sa' ? 'sa' : 'key';
    if (!empty($in['clear_key'])) {
        $s['api_key'] = '';
    } elseif (trim((string)($in['api_key'] ?? '')) !== '') {
        $s['api_key'] = trim($in['api_key']);
    }
    if (!empty($in['clear_sa'])) {
        $s['sa_json'] = '';
    } elseif (trim((string)($in['sa_json'] ?? '')) !== '') {
        $sa = json_decode(trim($in['sa_json']), true);
        if (!is_array($sa) || empty($sa['private_key']) || empty($sa['client_email'])) {
            throw new InvalidArgumentException('The service account JSON is not valid: it needs client_email and private_key (download a key for the service account in Google Cloud → IAM → Service accounts → Keys).');
        }
        $s['sa_json'] = json_encode($sa, JSON_UNESCAPED_SLASHES);
        if (trim((string)($in['project_id'] ?? '')) === '' && !empty($sa['project_id'])) {
            $in['project_id'] = $sa['project_id'];
        }
    }
    foreach (['project_id', 'location', 'claude_location', 'model'] as $k) {
        if (isset($in[$k])) {
            $s[$k] = preg_replace('/[^\w.@-]/', '', trim((string)$in[$k]));
        }
    }
    $s['location'] = $s['location'] ?: 'us-central1';
    $s['model'] = $s['model'] ?: GEN_VERTEX_MODELS[0];
    gen_db()->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)')->execute(['vertex', json_encode($s, JSON_UNESCAPED_SLASHES)]);
    return gen_vertex_public();
}

function gen_vertex_host(string $location): string
{
    $l = strtolower(trim($location));
    return $l === 'global' ? 'aiplatform.googleapis.com' : "$l-aiplatform.googleapis.com";
}

function gen_b64url(string $s): string
{
    return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
}

/** An OAuth2 access token for the service account, cached in the generator's cache folder until a minute before it expires. */
function gen_vertex_token(array $sa): string
{
    $cache = gen_cache_dir('vertex') . '/token-' . md5($sa['client_email']) . '.json';
    $c = is_file($cache) ? json_decode((string)file_get_contents($cache), true) : null;
    if (!empty($c['token']) && ($c['exp'] ?? 0) > time() + 60) {
        return $c['token'];
    }
    $now = time();
    $aud = $sa['token_uri'] ?? 'https://oauth2.googleapis.com/token';
    $jwt = gen_b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' . gen_b64url(json_encode([
        'iss' => $sa['client_email'], 'scope' => 'https://www.googleapis.com/auth/cloud-platform', 'aud' => $aud, 'iat' => $now, 'exp' => $now + 3600]));
    $key = openssl_pkey_get_private($sa['private_key']);
    if (!$key || !openssl_sign($jwt, $sig, $key, OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('Vertex AI: the service account private key cannot be read.');
    }
    [$code, $body] = gen_http_post($aud, http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => "$jwt." . gen_b64url($sig)]),
        ['Content-Type: application/x-www-form-urlencoded'], 30);
    $r = json_decode($body, true);
    if ($code !== 200 || empty($r['access_token'])) {
        throw new RuntimeException('Vertex AI: Google refused the service account (' . ($r['error_description'] ?? $r['error'] ?? "HTTP $code") . ').');
    }
    @file_put_contents($cache, json_encode(['token' => $r['access_token'], 'exp' => $now + (int)($r['expires_in'] ?? 3300)]));
    @chmod($cache, 0600);
    return $r['access_token'];
}

/** POST with curl: [status, body, headers]. */
function gen_http_post(string $url, string $body, array $headers, int $timeout = 180): array
{
    $h = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_HEADERFUNCTION => function ($c, $line) use (&$h) {
            if (str_contains($line, ':')) {
                [$k, $v] = explode(':', $line, 2);
                $h[strtolower(trim($k))] = trim($v);
            }
            return strlen($line);
        }]);
    $out = curl_exec($ch);
    if ($out === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("Vertex AI: network error ($err).");
    }
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return [$code, (string)$out, $h];
}

/**
 * One generation: the model's text reply. $json asks Gemini for a JSON reply (Claude is told so in the prompt).
 * Retries rate limits and server errors (honouring Retry-After) up to 3 times. Returns ['text', 'model', 'tokens'].
 */
function gen_vertex_generate(string $prompt, string $system = '', bool $json = false, int $maxTokens = 16384, ?array $s = null, array $meta = []): array
{
    $s ??= gen_vertex_settings();
    if (!gen_vertex_ready($s)) {
        throw new RuntimeException('Vertex AI is not connected. Add an API key or a service account in Settings → Vertex AI.');
    }
    $model = $s['model'] ?: GEN_VERTEX_MODELS[0];
    $claude = str_starts_with(strtolower($model), 'claude');
    if ($claude && $s['mode'] !== 'sa') {
        throw new RuntimeException("Vertex AI: $model is a Claude model, which needs a service account (an API key reaches Gemini models only).");
    }
    if ($s['mode'] === 'sa') {
        $sa = json_decode($s['sa_json'], true);
        $loc = $claude && $s['claude_location'] !== '' ? $s['claude_location'] : $s['location'];
        $url = 'https://' . gen_vertex_host($loc) . "/v1/projects/{$s['project_id']}/locations/$loc/publishers/" . ($claude ? "anthropic/models/$model:rawPredict" : "google/models/$model:generateContent");
    } else {
        $url = "https://aiplatform.googleapis.com/v1/publishers/google/models/$model:generateContent?key=" . rawurlencode($s['api_key']);
    }
    if ($claude) {
        $body = ['anthropic_version' => 'vertex-2023-10-16', 'max_tokens' => $maxTokens, 'messages' => [['role' => 'user', 'content' => $prompt]]] + ($system !== '' ? ['system' => $system] : []);
    } else {
        $body = ['contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]], 'generationConfig' => ['maxOutputTokens' => $maxTokens, 'temperature' => 0.7] + ($json ? ['responseMimeType' => 'application/json'] : [])]
            + ($system !== '' ? ['systemInstruction' => ['parts' => [['text' => $system]]]] : []);
    }
    for ($try = 1; ; $try++) {
        $headers = ['Content-Type: application/json'];
        if ($s['mode'] === 'sa') {
            $headers[] = 'Authorization: Bearer ' . gen_vertex_token($sa);
        }
        [$code, $out, $h] = gen_http_post($url, json_encode($body, JSON_UNESCAPED_UNICODE), $headers);
        if (($code === 429 || $code >= 500) && $try < 4) {
            sleep(min(60, max((int)($h['retry-after'] ?? 0), 5 * $try)));
            continue;
        }
        break;
    }
    $r = json_decode($out, true);
    if ($code >= 400 || !is_array($r)) {
        $msg = $r['error']['message'] ?? $r[0]['error']['message'] ?? mb_substr(trim(strip_tags($out)), 0, 300);
        throw new RuntimeException("Vertex AI ($model) answered HTTP $code: $msg");
    }
    if ($claude) {
        $text = implode('', array_map(fn($b) => ($b['type'] ?? '') === 'text' ? $b['text'] : '', $r['content'] ?? []));
        $in = (int)($r['usage']['input_tokens'] ?? 0);
        $outTok = (int)($r['usage']['output_tokens'] ?? 0);
        $cut = ($r['stop_reason'] ?? '') === 'max_tokens';
    } else {
        $c = $r['candidates'][0] ?? [];
        $text = implode('', array_map(fn($p) => $p['text'] ?? '', $c['content']['parts'] ?? []));
        $u = $r['usageMetadata'] ?? [];
        $in = (int)($u['promptTokenCount'] ?? 0);
        $outTok = (int)($u['candidatesTokenCount'] ?? 0) + (int)($u['thoughtsTokenCount'] ?? 0); // thinking is billed as output
        if (!$in && !$outTok && !empty($u['totalTokenCount'])) {
            $in = (int)$u['totalTokenCount'];
        }
        $cut = ($c['finishReason'] ?? '') === 'MAX_TOKENS';
    }
    $tokens = $in + $outTok;
    gen_usage_record($meta, $model, $in, $outTok); // billed even when the answer is unusable below
    if (!$claude) {
        if ($text === '' && !empty($c['finishReason'])) {
            throw new RuntimeException("Vertex AI ($model) returned no text (finish reason {$c['finishReason']}).");
        }
    }
    if ($cut) {
        throw new RuntimeException("Vertex AI ($model): the reply was cut off at the token limit.");
    }
    return ['text' => $text, 'model' => $model, 'tokens' => $tokens, 'input' => $in, 'output' => $outTok];
}

/* ---------------------------------------------------------------- usage and cost (as in ACM: one row per call, priced when made) */

function gen_usage_table(PDO $db): void
{
    $db->exec('CREATE TABLE IF NOT EXISTS ai_usage (id INTEGER PRIMARY KEY AUTOINCREMENT, at TEXT NOT NULL, project_id INTEGER, job_id INTEGER,
        site_id TEXT, kind TEXT NOT NULL, model TEXT NOT NULL, input_tokens INTEGER NOT NULL, output_tokens INTEGER NOT NULL, cost_usd REAL)');
    $db->exec('CREATE INDEX IF NOT EXISTS ai_usage_job ON ai_usage (job_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS ai_usage_project ON ai_usage (project_id)');
}

/** Prices per model: [model => ['in' => USD per 1M input tokens, 'out' => per 1M output tokens]], set in Settings → Vertex AI. */
function gen_vertex_prices(): array
{
    $q = gen_db()->query("SELECT value FROM settings WHERE key = 'vertex_prices'");
    $v = json_decode((string)$q->fetchColumn(), true);
    return is_array($v) ? $v : [];
}

/** The cost of a call in USD, or null when the model has no price. */
function gen_usage_cost(string $model, int $in, int $out, ?array $prices = null): ?float
{
    $p = ($prices ?? gen_vertex_prices())[$model] ?? null;
    if (!$p || (!isset($p['in']) && !isset($p['out']))) {
        return null;
    }
    return round($in / 1e6 * (float)($p['in'] ?? 0) + $out / 1e6 * (float)($p['out'] ?? 0), 6);
}

/** One call: $meta = [project, job, site, kind] (kind: text | facts | test). */
function gen_usage_record(array $meta, string $model, int $in, int $out): void
{
    try {
        $db = gen_db();
        gen_usage_table($db);
        $db->prepare('INSERT INTO ai_usage (at, project_id, job_id, site_id, kind, model, input_tokens, output_tokens, cost_usd) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([gen_now(), $meta['project'] ?? null, $meta['job'] ?? null, $meta['site'] ?? null, $meta['kind'] ?? 'text', $model, $in, $out, gen_usage_cost($model, $in, $out)]);
    } catch (Throwable $e) {
        error_log('ai usage: ' . $e->getMessage()); // counting must never stop the work it counts
    }
}

/** The models that have calls, for the price table. */
function gen_usage_models(): array
{
    $db = gen_db();
    gen_usage_table($db);
    return array_column($db->query('SELECT DISTINCT model FROM ai_usage ORDER BY model')->fetchAll(), 'model');
}

/** Saves the prices; calls made without a price get one now (calls already priced keep theirs). */
function gen_vertex_prices_save(array $in): array
{
    $out = [];
    foreach ($in as $model => $p) {
        $model = preg_replace('/[^\w.@-]/', '', (string)$model);
        $i = is_numeric($p['in'] ?? null) ? max(0, (float)$p['in']) : null;
        $o = is_numeric($p['out'] ?? null) ? max(0, (float)$p['out']) : null;
        if ($model !== '' && ($i !== null || $o !== null)) {
            $out[$model] = array_filter(['in' => $i, 'out' => $o], fn($x) => $x !== null);
        }
    }
    $db = gen_db();
    $db->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)')->execute(['vertex_prices', json_encode($out)]);
    gen_usage_table($db);
    $set = $db->prepare('UPDATE ai_usage SET cost_usd = ? WHERE id = ?');
    foreach ($db->query('SELECT id, model, input_tokens, output_tokens FROM ai_usage WHERE cost_usd IS NULL')->fetchAll() as $u) {
        $c = gen_usage_cost($u['model'], (int)$u['input_tokens'], (int)$u['output_tokens'], $out);
        if ($c !== null) {
            $set->execute([$c, $u['id']]);
        }
    }
    return $out;
}

/** Totals of the calls matching $where (e.g. 'job_id = 46'): calls, tokens, cost, calls without a price, per kind and per job. */
function gen_usage_summary(string $where = '1'): array
{
    $db = gen_db();
    gen_usage_table($db);
    $t = $db->query("SELECT COUNT(*) AS calls, COALESCE(SUM(input_tokens), 0) AS input, COALESCE(SUM(output_tokens), 0) AS output,
        COALESCE(SUM(cost_usd), 0) AS cost, COALESCE(SUM(cost_usd IS NULL), 0) AS unpriced FROM ai_usage WHERE $where")->fetch();
    $by = fn(string $col) => $db->query("SELECT $col AS k, COUNT(*) AS calls, SUM(input_tokens) + SUM(output_tokens) AS tokens, COALESCE(SUM(cost_usd), 0) AS cost
        FROM ai_usage WHERE $where GROUP BY $col ORDER BY cost DESC, tokens DESC")->fetchAll();
    return ['calls' => (int)$t['calls'], 'input' => (int)$t['input'], 'output' => (int)$t['output'], 'cost' => round((float)$t['cost'], 6),
        'unpriced' => (int)$t['unpriced'], 'by_kind' => $by('kind'), 'by_job' => $by('job_id'),
        'unpriced_models' => array_column($db->query("SELECT DISTINCT model FROM ai_usage WHERE ($where) AND cost_usd IS NULL")->fetchAll(), 'model')];
}

/** Connection test: a tiny request with the saved settings. */
function gen_vertex_test(): array
{
    $t0 = microtime(true);
    $r = gen_vertex_generate('Reply with the single word OK.', '', false, 256, null, ['kind' => 'test']);
    return ['ok' => true, 'model' => $r['model'], 'reply' => mb_substr(trim($r['text']), 0, 80), 'ms' => (int)round((microtime(true) - $t0) * 1000)];
}
