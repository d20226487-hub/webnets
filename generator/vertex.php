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
        'prices' => (object)gen_vertex_prices(), 'used_models' => gen_usage_models(), 'usage' => gen_usage_summary(),
        'limits' => gen_vertex_limits(), 'limits_default' => GEN_VERTEX_LIMITS];
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

/** Request limits for the text step, as in ACM (Settings → Vertex AI → Limits). */
const GEN_VERTEX_LIMITS = ['rpm' => 60, 'concurrency' => 4, 'batch' => 10, 'delay_ms' => 0, 'retries' => 3, 'backoff_ms' => 1000, 'jitter_ms' => 250, 'retry_after' => true,
    'tok_text' => 32768, 'tok_facts' => 16384, 'tok_brand' => 8192, 'tok_offers' => 32768, 'tok_translate' => 32768, 'tok_reviews' => 32768];

/** The output token limits per task (thinking models spend part of it on thinking): key => the setting's name. */
const GEN_VERTEX_TOKENS = ['tok_text' => 'Max output tokens: site texts', 'tok_facts' => 'Max output tokens: «Fill from the project» (master review)',
    'tok_brand' => 'Max output tokens: brand texts (summary, verdict)', 'tok_offers' => 'Max output tokens: «Fill from the project» (offers)',
    'tok_translate' => 'Max output tokens: translations', 'tok_reviews' => 'Max output tokens: sample reviews'];

/** A task's output token limit from the settings (tok_text, tok_facts, tok_brand, tok_offers, tok_translate). */
function gen_vertex_max(string $key): int
{
    return (int)(gen_vertex_limits()[$key] ?? GEN_VERTEX_LIMITS[$key]);
}

function gen_vertex_limits(): array
{
    $q = gen_db()->query("SELECT value FROM settings WHERE key = 'vertex_limits'");
    $v = json_decode((string)$q->fetchColumn(), true);
    return (is_array($v) ? array_intersect_key($v, GEN_VERTEX_LIMITS) : []) + GEN_VERTEX_LIMITS;
}

function gen_vertex_limits_save(array $in): array
{
    $int = fn($k, $min, $max) => max($min, min($max, (int)($in[$k] ?? GEN_VERTEX_LIMITS[$k])));
    $v = ['rpm' => $int('rpm', 1, 6000), 'concurrency' => $int('concurrency', 1, 32), 'batch' => $int('batch', 1, 200), 'delay_ms' => $int('delay_ms', 0, 600000),
        'retries' => $int('retries', 0, 10), 'backoff_ms' => $int('backoff_ms', 0, 600000), 'jitter_ms' => $int('jitter_ms', 0, 60000), 'retry_after' => !empty($in['retry_after'])];
    foreach (array_keys(GEN_VERTEX_TOKENS) as $k) {
        $v[$k] = $int($k, 1024, 65536);
    }
    gen_db()->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)')->execute(['vertex_limits', json_encode($v)]);
    return $v;
}

/** Everything about a request except the access token: url, body, model, whether it is Claude. */
function gen_vertex_prepare(string $prompt, string $system, bool $json, int $maxTokens, array $s): array
{
    if (!gen_vertex_ready($s)) {
        throw new RuntimeException('Vertex AI is not connected. Add an API key or a service account in Settings → Vertex AI.');
    }
    $model = $s['model'] ?: GEN_VERTEX_MODELS[0];
    $claude = str_starts_with(strtolower($model), 'claude');
    if ($claude && $s['mode'] !== 'sa') {
        throw new RuntimeException("Vertex AI: $model is a Claude model, which needs a service account (an API key reaches Gemini models only).");
    }
    if ($s['mode'] === 'sa') {
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
    return ['url' => $url, 'body' => json_encode($body, JSON_UNESCAPED_UNICODE), 'model' => $model, 'claude' => $claude, 'max' => $maxTokens];
}

/** A finished response as the result, recording its usage; throws for an error or an unusable answer. */
function gen_vertex_parse(int $code, string $out, array $prep, array $meta): array
{
    $model = $prep['model'];
    $r = json_decode($out, true);
    if ($code >= 400 || !is_array($r)) {
        $msg = $r['error']['message'] ?? $r[0]['error']['message'] ?? mb_substr(trim(strip_tags($out)), 0, 300);
        throw new RuntimeException("Vertex AI ($model) answered HTTP $code: $msg");
    }
    if ($prep['claude']) {
        $text = implode('', array_map(fn($b) => ($b['type'] ?? '') === 'text' ? $b['text'] : '', $r['content'] ?? []));
        $in = (int)($r['usage']['input_tokens'] ?? 0);
        $outTok = (int)($r['usage']['output_tokens'] ?? 0);
        $cut = ($r['stop_reason'] ?? '') === 'max_tokens';
        $finish = '';
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
        $finish = (string)($c['finishReason'] ?? '');
    }
    gen_usage_record($meta, $model, $in, $outTok); // billed even when the answer is unusable below
    if ($text === '' && $finish !== '') {
        throw new RuntimeException("Vertex AI ($model) returned no text (finish reason $finish).");
    }
    if ($cut) {
        $set = GEN_VERTEX_TOKENS[$meta['limit'] ?? ''] ?? null;
        throw new RuntimeException("Vertex AI ($model): the reply was cut off at the limit of " . ($prep['max'] ?? '?') . ' output tokens.'
            . ($set ? " Raise «{$set}» in Settings → Vertex AI → Limits." : ''));
    }
    return ['text' => $text, 'model' => $model, 'tokens' => $in + $outTok, 'input' => $in, 'output' => $outTok];
}

/**
 * Several generations at once, within the limits (Settings → Vertex AI → Limits): at most «concurrency» requests in
 * flight, starts spaced by the requests-per-minute rate and the delay, and a rate limit (429), a server error (5xx)
 * or a network error retried up to «retries» times after backoff × 2^(try−1) plus a random spread (or the server's
 * Retry-After). $calls: [key => ['prompt', 'system', 'json', 'max', 'meta']]. Returns [key => result array | Throwable].
 */
function gen_vertex_many(array $calls, ?array $s = null, ?array $lim = null): array
{
    $s ??= gen_vertex_settings();
    $lim ??= gen_vertex_limits();
    $results = [];
    $queue = [];
    foreach ($calls as $key => $c) {
        try {
            $queue[] = ['key' => $key, 'prep' => gen_vertex_prepare($c['prompt'], $c['system'] ?? '', !empty($c['json']), (int)($c['max'] ?? 16384), $s), 'meta' => $c['meta'] ?? [], 'try' => 1, 'at' => 0.0];
        } catch (Throwable $e) {
            $results[$key] = $e;
        }
    }
    $gap = max(60.0 / max(1, $lim['rpm']), $lim['delay_ms'] / 1000); // seconds between two starts
    $sa = $s['mode'] === 'sa' ? json_decode($s['sa_json'], true) : null;
    $mh = curl_multi_init();
    $live = [];
    $lastStart = 0.0;
    while ($queue || $live) {
        $now = microtime(true);
        foreach ($queue as $i => $q) { // start what the limits allow
            if (count($live) >= $lim['concurrency'] || $now - $lastStart < $gap) {
                break;
            }
            if ($q['at'] > $now) {
                continue;
            }
            unset($queue[$i]);
            $headers = ['Content-Type: application/json'];
            try {
                if ($sa) {
                    $headers[] = 'Authorization: Bearer ' . gen_vertex_token($sa);
                }
            } catch (Throwable $e) {
                $results[$q['key']] = $e;
                continue;
            }
            $hdr = [];
            $ch = curl_init($q['prep']['url']);
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $q['prep']['body'], CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 300, CURLOPT_CONNECTTIMEOUT => 15]);
            $q['hdr'] = new ArrayObject();
            $box = $q['hdr'];
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($c, $line) use ($box) {
                if (str_contains($line, ':')) {
                    [$k, $v] = explode(':', $line, 2);
                    $box[strtolower(trim($k))] = trim($v);
                }
                return strlen($line);
            });
            curl_multi_add_handle($mh, $ch);
            $live[spl_object_id($ch)] = ['ch' => $ch] + $q;
            $lastStart = $now = microtime(true);
        }
        $queue = array_values($queue);
        if ($live) {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 0.2);
            curl_multi_exec($mh, $running);
            while ($info = curl_multi_info_read($mh)) {
                $ch = $info['handle'];
                $q = $live[spl_object_id($ch)];
                unset($live[spl_object_id($ch)]);
                $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                $out = (string)curl_multi_getcontent($ch);
                $err = $info['result'] !== CURLE_OK ? curl_error($ch) : '';
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
                $retryable = $err !== '' || $code === 429 || $code >= 500;
                if ($retryable && $q['try'] <= $lim['retries']) {
                    $wait = $lim['backoff_ms'] / 1000 * 2 ** ($q['try'] - 1) + mt_rand(0, max(0, $lim['jitter_ms'])) / 1000;
                    $ra = $q['hdr']['retry-after'] ?? null;
                    if ($lim['retry_after'] && $ra !== null && is_numeric($ra)) {
                        $wait = max($wait, (float)$ra);
                    }
                    $queue[] = ['try' => $q['try'] + 1, 'at' => microtime(true) + min(300, $wait)] + $q;
                    continue;
                }
                try {
                    if ($err !== '') {
                        throw new RuntimeException("Vertex AI: network error ($err).");
                    }
                    $results[$q['key']] = gen_vertex_parse($code, $out, $q['prep'], $q['meta']);
                } catch (Throwable $e) {
                    $results[$q['key']] = $e;
                }
            }
        } elseif ($queue) { // nothing in flight: wait for the next start the limits allow
            $next = min(array_column($queue, 'at'));
            $wait = max($next - microtime(true), $lastStart + $gap - microtime(true), 0.05);
            usleep((int)(min($wait, 5) * 1e6));
        }
    }
    curl_multi_close($mh);
    return $results;
}

/** One generation: the model's text reply ($json asks Gemini for JSON). Within the same limits and retries as gen_vertex_many(). */
function gen_vertex_generate(string $prompt, string $system = '', bool $json = false, int $maxTokens = 16384, ?array $s = null, array $meta = []): array
{
    $r = gen_vertex_many(['one' => ['prompt' => $prompt, 'system' => $system, 'json' => $json, 'max' => $maxTokens, 'meta' => $meta]], $s)['one'];
    if ($r instanceof Throwable) {
        throw $r;
    }
    return $r;
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
    $r = gen_vertex_generate('Reply with the single word OK.', '', false, 1024, null, ['kind' => 'test']);
    return ['ok' => true, 'model' => $r['model'], 'reply' => mb_substr(trim($r['text']), 0, 80), 'ms' => (int)round((microtime(true) - $t0) * 1000)];
}
