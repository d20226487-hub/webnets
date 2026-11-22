<?php
/**
 * Brand facts: the real licence number, start year, payout times, app details and tagline of a job's brands.
 * gen_materialise_brands() fills a new brand with example data so templates look real; the facts confirmed here
 * replace it (table brand_facts, re-applied whenever the brand data is made again, e.g. after a job edit).
 * A confirmed empty value hides the fact where the page can do without it (start year, tagline); the other facts keep
 * their example value until confirmed, and the job page warns about them.
 * "Fill from the project" asks Vertex AI for the values the project information and documents state, never guesses.
 */

require_once __DIR__ . '/brands.php';

/** The facts per brand: key => [label, kind, path in the brand data, can be hidden]. Paths: op:<operators.json path> | mk:<facts/<market>.json path>. */
function gen_bfacts_schema(array $market, array $brand): array
{
    $f = [
        'license_no' => ['Licence number', 'text', 'op:license_no', false],
        'founded' => ['Operating since (year)', 'year', 'op:facts/founded', true],
    ];
    foreach ($market['payments'] ?? [] as $p) {
        $f["payout_$p"] = ['Withdrawal time, minutes: {0}', 'int', "mk:withdrawal_minutes/$p", false, sx_payment_name($p)];
    }
    foreach ($market['locales'] ?? [] as $l) {
        $f["tagline_$l"] = ['Tagline ({0})', 'text', "op:tagline/$l", true, $l];
    }
    if (!empty($brand['app'])) {
        $f += [
            'developer' => ['App developer', 'text', 'op:app/developer', false],
            'version' => ['App version', 'text', 'op:app/version', false],
            'updated' => ['App updated (YYYY-MM-DD)', 'date', 'op:app/updated', false],
            'size_ios' => ['iPhone app size, MB', 'int', 'op:app/size_mb/ios', false],
            'size_android' => ['Android app size, MB', 'int', 'op:app/size_mb/android', false],
            'min_ios' => ['Minimum iOS', 'text', 'op:app/min_os/ios', false],
            'min_android' => ['Minimum Android', 'text', 'op:app/min_os/android', false],
        ];
        foreach ($market['locales'] ?? [] as $l) {
            $f["languages_$l"] = ['App languages ({0}, comma separated)', 'list', "op:app/languages/$l", false, $l];
        }
    }
    return $f;
}

function gen_bfacts_table(PDO $db): void
{
    $db->exec('CREATE TABLE IF NOT EXISTS brand_facts (brand_id TEXT PRIMARY KEY, job_id INTEGER NOT NULL REFERENCES jobs(id) ON DELETE CASCADE, data TEXT NOT NULL, updated TEXT NOT NULL)');
}

/** The confirmed facts of a brand: [key => value] ('' = hidden). */
function gen_bfacts_saved(PDO $db, string $brandId): array
{
    gen_bfacts_table($db);
    $q = $db->prepare('SELECT data FROM brand_facts WHERE brand_id = ?');
    $q->execute([$brandId]);
    return json_decode((string)$q->fetchColumn(), true) ?: [];
}

function gen_bfacts_get(array $data, string $path)
{
    foreach (explode('/', $path) as $k) {
        if (!is_array($data) || !array_key_exists($k, $data)) {
            return null;
        }
        $data = $data[$k];
    }
    return $data;
}

function gen_bfacts_set(array $data, string $path, $v): array
{
    $ref = &$data;
    foreach (explode('/', $path) as $k) {
        if (!isset($ref[$k]) || !is_array($ref[$k])) {
            $ref[$k] = $ref[$k] ?? [];
        }
        $ref = &$ref[$k];
    }
    $ref = $v;
    unset($ref);
    return $data;
}

/** A form value as stored: trimmed, typed, '' for hidden. Throws for a value of the wrong kind. */
function gen_bfacts_clean(string $kind, $v, string $label)
{
    $v = trim((string)$v);
    if ($v === '') {
        return '';
    }
    return match ($kind) {
        'year' => preg_match('/^(19|20)\d\d$/', $v) && (int)$v <= (int)date('Y') ? (int)$v : throw new InvalidArgumentException("$label: a year, like 2019."),
        'int' => ctype_digit($v) && (int)$v > 0 ? (int)$v : throw new InvalidArgumentException("$label: a whole number."),
        'date' => preg_match('/^\d{4}-\d\d-\d\d$/', $v) && strtotime($v) ? $v : throw new InvalidArgumentException("$label: a date as YYYY-MM-DD."),
        'list' => array_values(array_filter(array_map('trim', explode(',', $v)), 'strlen')),
        default => mb_substr($v, 0, 300),
    };
}

/** The licence written in the project information of a job: ['regulator', 'no', 'short'], empty strings when not given. */
function gen_project_licence(PDO $db, int $jobId): array
{
    $q = $db->prepare('SELECT p.info FROM projects p JOIN jobs j ON j.project_id = p.id WHERE j.id = ?');
    $q->execute([$jobId]);
    $i = json_decode((string)$q->fetchColumn(), true) ?: [];
    return ['regulator' => trim((string)($i['licence_regulator'] ?? '')), 'no' => trim((string)($i['licence_no'] ?? '')), 'short' => trim((string)($i['licence_short'] ?? ''))];
}

/** Puts a brand's confirmed facts into its brand data (operators.json and the market's facts file). The project's
 *  licence number is the brand's licence unless the brand facts give its own. */
function gen_bfacts_apply(PDO $db, string $brandId, string $market): void
{
    global $GEN_ROOT;
    $saved = gen_bfacts_saved($db, $brandId);
    if (preg_match('/^u(\d+)-/', $brandId, $jm) && !isset($saved['license_no'])) {
        $lic = gen_project_licence($db, (int)$jm[1])['no'];
        if ($lic !== '') {
            $saved['license_no'] = $lic;
        }
    }
    if (!$saved) {
        return;
    }
    $m = sx_json("$GEN_ROOT/markets/$market.json");
    $op = sx_brand_data($GEN_ROOT, 'operators.json')[$brandId] ?? null;
    if (!$op) {
        return;
    }
    $schema = gen_bfacts_schema($m, $op);
    $opSet = $mkSet = [];
    foreach ($saved as $k => $v) {
        if (!isset($schema[$k])) {
            continue;
        }
        [$where, $path] = explode(':', $schema[$k][2], 2);
        if ($v === '' && !$schema[$k][3]) { // an empty fact that cannot be hidden keeps its example value
            continue;
        }
        $v = $v === '' && !str_starts_with($path, 'tagline/') ? null : $v; // a hidden tagline is empty text: missing would fall back to another language's
        $where === 'op' ? $opSet[$path] = $v : $mkSet[$path] = $v;
    }
    $mins = array_filter(array_map(fn($p, $v) => str_starts_with($p, 'withdrawal_minutes/') ? $v : null, array_keys($mkSet), $mkSet));
    if ($mins) { // the brand-wide payout figure follows the market's fastest confirmed method
        $opSet['facts/payout_minutes'] = min($mins);
    }
    gen_overlay_update('operators.json', function ($d) use ($brandId, $opSet) {
        foreach ($opSet as $path => $v) {
            $d[$brandId] = gen_bfacts_set($d[$brandId] ?? [], $path, $v);
        }
        return $d;
    });
    if ($mkSet) {
        gen_overlay_update("facts/$market.json", function ($d) use ($brandId, $mkSet) {
            foreach ($mkSet as $path => $v) {
                $d['brands'][$brandId] = gen_bfacts_set($d['brands'][$brandId] ?? [], $path, $v);
            }
            return $d;
        });
    }
}

/** The job's brands with every fact: its value now, whether it is confirmed, and what it is. */
function gen_bfacts_job(PDO $db, int $jobId): array
{
    global $GEN_ROOT;
    $job = $db->query('SELECT market, spec FROM jobs WHERE id = ' . $jobId)->fetch() ?: throw new RuntimeException('No such job.');
    $spec = json_decode($job['spec'], true) ?: [];
    $m = sx_json("$GEN_ROOT/markets/{$job['market']}.json");
    $ops = sx_brand_data($GEN_ROOT, 'operators.json');
    $mk = sx_brand_data($GEN_ROOT, "facts/{$job['market']}.json")['brands'] ?? [];
    $out = [];
    foreach ($spec['brand_ids'] ?? [] as $id) {
        if (empty($ops[$id]['user_brand'])) {
            continue;
        }
        $saved = gen_bfacts_saved($db, $id);
        $projectLicence = gen_project_licence($db, $jobId)['no'];
        $fields = [];
        foreach (gen_bfacts_schema($m, $ops[$id]) as $k => $def) {
            [$label, $kind, $path, $hide] = $def;
            [$where, $p] = explode(':', $path, 2);
            $now = gen_bfacts_get($where === 'op' ? $ops[$id] : ($mk[$id] ?? []), $p);
            $fromProject = $k === 'license_no' && !array_key_exists($k, $saved) && $projectLicence !== '';
            $fields[] = ['key' => $k, 'label' => $label, 'param' => $def[4] ?? '', 'kind' => $kind, 'hide' => $hide, 'confirmed' => array_key_exists($k, $saved) || $fromProject, 'project' => $fromProject,
                'value' => array_key_exists($k, $saved) ? (is_array($saved[$k]) ? implode(', ', $saved[$k]) : (string)$saved[$k]) : '',
                'example' => is_array($now) ? implode(', ', $now) : (string)($now ?? '')];
        }
        $out[] = ['id' => $id, 'name' => $ops[$id]['name'], 'fields' => $fields];
    }
    return $out;
}

/** Saves a brand's facts from the form (a key left out stays example data) and applies them. */
function gen_bfacts_save(PDO $db, int $jobId, string $brandId, array $in): void
{
    global $GEN_ROOT;
    $job = $db->query('SELECT market, spec FROM jobs WHERE id = ' . $jobId)->fetch() ?: throw new RuntimeException('No such job.');
    if (!in_array($brandId, json_decode($job['spec'], true)['brand_ids'] ?? [], true)) {
        throw new InvalidArgumentException('This brand is not one of the job\'s brands.');
    }
    $op = sx_brand_data($GEN_ROOT, 'operators.json')[$brandId] ?? [];
    $schema = gen_bfacts_schema(sx_json("$GEN_ROOT/markets/{$job['market']}.json"), $op);
    $data = [];
    foreach ($in as $k => $v) {
        if (isset($schema[$k]) && $v !== null) {
            $data[$k] = gen_bfacts_clean($schema[$k][1], $v, str_replace('{0}', $schema[$k][4] ?? '', $schema[$k][0]));
        }
    }
    gen_bfacts_table($db);
    $db->prepare('INSERT OR REPLACE INTO brand_facts (brand_id, job_id, data, updated) VALUES (?, ?, ?, ?)')->execute([$brandId, $jobId, json_encode($data, JSON_UNESCAPED_UNICODE), gen_now()]);
    // the brand data is made again from the example (so a fact taken back is example data again), then every confirmed fact goes on top
    $spec = json_decode($job['spec'], true);
    if (!empty($spec['brands'])) {
        gen_materialise_brands($spec['brands'], $job['market'], $jobId, $spec['copy'] ?? 'demo');
    } else {
        gen_bfacts_apply($db, $brandId, $job['market']);
    }
}

/** The facts of the job's brands that still show example data: [brand name => [[label, param]]]. */
function gen_bfacts_unconfirmed(PDO $db, int $jobId): array
{
    $out = [];
    foreach (gen_bfacts_job($db, $jobId) as $b) {
        $left = array_values(array_map(fn($f) => ['label' => $f['label'], 'param' => $f['param']], array_filter($b['fields'], fn($f) => !$f['confirmed'] && $f['example'] !== '')));
        if ($left) {
            $out[$b['name']] = $left;
        }
    }
    return $out;
}

/** Vertex AI reads the project information and documents and returns the facts they state: [key => value], nothing guessed. */
function gen_bfacts_fill(PDO $db, int $jobId, string $brandId): array
{
    require_once __DIR__ . '/content.php';
    $brands = array_values(array_filter(gen_bfacts_job($db, $jobId), fn($b) => $b['id'] === $brandId));
    if (!$brands) {
        throw new InvalidArgumentException('This brand is not one of the job\'s brands.');
    }
    $b = $brands[0];
    $q = $db->prepare('SELECT p.id, p.info FROM projects p JOIN jobs j ON j.project_id = p.id WHERE j.id = ?');
    $q->execute([$jobId]);
    $p = $q->fetch() ?: ['id' => 0, 'info' => '{}'];
    $source = gen_project_brief(['below' => 'none'] + gen_project_info(json_decode((string)$p['info'], true) ?: []), gen_pfiles_brief($db, (int)$p['id']));
    $source = trim(preg_replace('/\n### Text below sections.*$/s', '', $source));
    if ($source === '') {
        throw new InvalidArgumentException('The project has no information or documents to read the facts from. Fill in «Project information» or upload a document first.');
    }
    $keys = [];
    foreach ($b['fields'] as $f) {
        $keys[$f['key']] = str_replace('{0}', $f['param'], $f['label']) . ' (' . $f['kind'] . ')';
    }
    $prompt = "Read the project information and documents below and find these facts about the brand «{$b['name']}»:\n\n"
        . json_encode($keys, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n"
        . "Rules: take a value only when the text states it for this brand; never guess, estimate or take example values. A fact the text does not state is null."
        . " Years as 4 digits, minutes and megabytes as whole numbers, dates as YYYY-MM-DD, lists as comma-separated text, taglines in the language named in the key."
        . " For each value found, quote the words it comes from.\n\nAnswer with JSON only: {\"facts\": {\"<key>\": <value or null>}, \"quotes\": {\"<key>\": \"<quoted words>\"}}\n\n"
        . "---\n\n$source";
    $r = gen_vertex_generate($prompt, 'You extract facts from documents exactly as stated. You never invent or infer values.', true, 4096, null,
        ['project' => (int)$p['id'] ?: null, 'job' => $jobId, 'kind' => 'facts']);
    $json = trim($r['text']);
    if (preg_match('/```(?:json)?\s*(.*?)```/s', $json, $mm)) {
        $json = $mm[1];
    }
    $a = json_decode($json, true);
    $out = [];
    foreach ((array)($a['facts'] ?? []) as $k => $v) {
        $quote = trim((string)($a['quotes'][$k] ?? ''));
        if (!isset($keys[$k]) || $v === null || $v === '' || $quote === '') {
            continue;
        }
        $plain = mb_strtolower(preg_replace('/\s+/u', ' ', strip_tags($source)));
        if (!str_contains($plain, mb_strtolower(preg_replace('/\s+/u', ' ', mb_substr($quote, 0, 60))))) { // the quote must really be in the source
            continue;
        }
        $out[$k] = ['value' => is_array($v) ? implode(', ', $v) : (string)$v, 'quote' => $quote];
    }
    return $out;
}
