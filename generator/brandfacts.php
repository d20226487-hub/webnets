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
    $f['min_deposit'] = ['Minimum deposit ({0})', 'int', 'mk:min_deposit', false, $market['currency']['code'] ?? ''];
    if (in_array('casino', array_keys(array_filter($market['verticals'] ?? [])), true) && array_intersect(['casino', 'live-casino'], $brand['verticals'] ?? ['casino'])) {
        $f['games'] = ['Casino games', 'int', 'mk:games', false];
        $f['live_tables'] = ['Live tables', 'int', 'mk:live_tables', false];
    }
    foreach ($market['payments'] ?? [] as $p) {
        $f["payout_$p"] = ['Withdrawal time, minutes: {0}', 'int', "mk:withdrawal_minutes/$p", false, sx_payment_name($p)];
    }
    $f['payments'] = ['Payment methods', 'list', 'mk:payments', false];
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
        'long' => mb_substr(preg_replace('/\s*\n\s*/u', ' ', $v), 0, 1000),
        'lines' => array_slice(array_values(array_filter(array_map(fn($x) => mb_substr(trim(preg_replace('/^\s*[-*•–—]\s*/u', '', $x)), 0, 160), preg_split('/\R/u', $v)), 'strlen')), 0, 8),
        default => mb_substr($v, 0, 300),
    };
}

/** The licence written in the project information of a job: ['regulator', 'no', 'short'], empty strings when not given. */
function gen_project_licence(PDO $db, int $jobId): array
{
    $q = $db->prepare('SELECT p.info FROM projects p JOIN jobs j ON j.project_id = p.id WHERE j.id = ?');
    $q->execute([$jobId]);
    $i = json_decode((string)$q->fetchColumn(), true) ?: [];
    return gen_pfacts_licence($i);
}

/** The licence a project's information gives (Факты бренда, or the fields of projects saved before it moved there):
 *  ['regulator', 'no', 'short'], empty strings when not given. */
function gen_pfacts_licence(array $info): array
{
    $f = [];
    foreach ((array)($info['brand_facts'] ?? []) as $r) {
        if (in_array($r['key'] ?? '', ['licence_regulator', 'licence_no', 'licence_short'], true)) {
            $f[$r['key']] = trim((string)($r['value'] ?? ''));
        }
    }
    $get = fn($k) => ($f[$k] ?? '') !== '' ? $f[$k] : trim((string)($info[$k] ?? ''));
    return ['regulator' => $get('licence_regulator'), 'no' => $get('licence_no'), 'short' => $get('licence_short')];
}

/** Puts a brand's confirmed facts into its brand data (operators.json and the market's facts file). The project's
 *  licence number is the brand's licence unless the brand facts give its own. */
function gen_bfacts_apply(PDO $db, string $brandId, string $market): void
{
    global $GEN_ROOT;
    $m0 = sx_json("$GEN_ROOT/markets/$market.json");
    $saved = gen_bfacts_saved($db, $brandId) + gen_pfacts_for_brand($db, $brandId, $m0); // facts confirmed for this job's brand (older page) over the project's
    if (preg_match('/^u(\d+)-/', $brandId, $jm) && !isset($saved['license_no']) && !gen_brand_is_sample($brandId)) {
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

/** The facts of the job's brands that still show example data: [brand name => [[label, param]]] (from the project's facts). */
function gen_bfacts_unconfirmed(PDO $db, int $jobId): array
{
    $q = $db->prepare('SELECT p.info FROM projects p JOIN jobs j ON j.project_id = p.id WHERE j.id = ?');
    $q->execute([$jobId]);
    $info = json_decode((string)$q->fetchColumn(), true) ?: [];
    $missing = gen_pfacts_missing($info);
    $out = [];
    foreach (gen_bfacts_job($db, $jobId) as $b) {
        $saved = gen_bfacts_saved($db, $b['id']); // a fact confirmed on the older per-job page counts too
        $left = array_values(array_filter($missing, fn($f) => !(($f['label'] === 'Licence number' && isset($saved['license_no'])) || ($f['label'] === GEN_PFACTS['founded'][0] && isset($saved['founded'])))));
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
    $r = gen_vertex_generate($prompt, 'You extract facts from documents exactly as stated. You never invent or infer values.', true, gen_vertex_max('tok_facts'), null,
        ['project' => (int)$p['id'] ?: null, 'job' => $jobId, 'kind' => 'facts', 'limit' => 'tok_facts']);
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

/* ---------------------------------------------------------------- project brand facts (Информация о проекте → Факты бренда) */

/** The brand facts a project can give: key => [label, kind]. They replace the example data of the project's job brands. */
const GEN_PFACTS = [ // in the form's order: the most important first, the app's last
    'licence_regulator' => ['Licence issued by', 'text'],
    'licence_no' => ['Licence number', 'text'],
    'licence_short' => ['Licence badge', 'text'],
    'founded' => ['Operating since (year)', 'year'],
    'min_deposit' => ['Minimum deposit (in the market currency)', 'int'],
    'min_withdrawal' => ['Minimum withdrawal (in the market currency)', 'int'],
    'payments' => ['Payment methods (comma separated)', 'list'],
    'payout_minutes' => ['Fastest withdrawal, minutes', 'int'],
    'payment_times' => ['Deposit and withdrawal times', 'long'],
    'games' => ['Casino games', 'int'],
    'live_tables' => ['Live tables', 'int'],
    'providers' => ['Top game providers (comma separated)', 'list'],
    'game_types' => ['Game types (comma separated)', 'list'],
    'live_games' => ['Top live casino games (comma separated)', 'list'],
    'bet_types' => ['Bet types (comma separated)', 'list'],
    'min_bet' => ['Minimum bet (in the market currency)', 'int'],
    'support' => ['Support (channels, hours)', 'text'],
    'support_email' => ['Support email', 'text'],
    'verification' => ['Verification', 'long'],
    'site_languages' => ['Site languages (comma separated)', 'list'],
    'tagline' => ['Tagline', 'text'],
    'summary' => ['Short description (in rankings)', 'long'],
    'verdict' => ['Verdict', 'long'],
    'pros' => ['Pros (one per line)', 'lines'],
    'cons' => ['Cons (one per line)', 'lines'],
    'developer' => ['App developer', 'text'],
    'version' => ['App version', 'text'],
    'updated' => ['App updated (YYYY-MM-DD)', 'date'],
    'size_ios' => ['iPhone app size, MB', 'int'],
    'size_android' => ['Android app size, MB', 'int'],
    'min_ios' => ['Minimum iOS', 'text'],
    'min_android' => ['Minimum Android', 'text'],
    'languages' => ['App languages (comma separated)', 'list'],
];

/** The app's facts: last in the form, after the project's own fields. */
const GEN_PFACTS_APP = ['developer', 'version', 'updated', 'size_ios', 'size_android', 'min_ios', 'min_android', 'languages'];

/** Own fields that are a known fact under another name («Платежные методы»): the label that names it, per fact, in the
 *  order they are tried. Such a field becomes that fact (when its value fits and the fact is not given already). */
const GEN_PFACTS_MATCH = [
    'licence_no' => '/(номер|№|number|n[úu]mero).*лиценз|licen[cs]e\s*(no|number|#)/iu',
    'support_email' => '/(e-?mail|почт).*(поддерж|support|suporte)|(поддерж|support|suporte).*(e-?mail|почт)/iu',
    'min_withdrawal' => '/минимальн\p{L}*\s+вывод|min\p{L}*\s+withdraw|saque\s+m[ií]nimo/iu',
    'min_bet' => '/минимальн\p{L}*\s+ставк|min\p{L}*\s+bet|aposta\s+m[ií]nima/iu',
    'payment_times' => '/врем\p{L}*.*(депозит|вывод|выплат)|(deposit|withdraw\p{L}*|payout).*time|prazo/iu',
    'payments' => '/платеж|платёж|оплат|payment|pagamento/iu',
    'providers' => '/провайдер|provider|provedor/iu',
    'live_games' => '/(live|лайв).*(игр|game|jogo)|(игр|game|jogo).*(live|лайв)/iu',
    'game_types' => '/вид\p{L}*\s+игр|game\s+types|tipos\s+de\s+jogo/iu',
    'bet_types' => '/вид\p{L}*\s+ставок|bet\s+types|tipos\s+de\s+aposta/iu',
    'verification' => '/верификац|verification|kyc|verifica/iu',
    'site_languages' => '/язык\p{L}*\s+сайт|site\s+languages|idiomas\s+do\s+site/iu',
    'support' => '/служб\p{L}*\s+поддерж|^поддержк|^support|^suporte/iu',
];

/** The facts that are the brand's own rating texts (rankings, review page), not figures. */
const GEN_PTEXT = ['summary', 'verdict', 'pros', 'cons'];

/** A project's facts list as stored: [{key, label (own fields only), value}], known keys or own fields "c<n>", 60 at most,
 *  in the form's order (known facts by importance, then own fields, then the app's). An own field named like a known
 *  fact becomes it. */
function gen_pfacts_clean($in): array
{
    $out = [];
    foreach (is_array($in) ? array_slice($in, 0, 60) : [] as $r) {
        if (!is_array($r)) {
            continue;
        }
        $k = (string)($r['key'] ?? '');
        $v = mb_substr(trim(str_replace("\r", '', (string)($r['value'] ?? ''))), 0, 2000);
        if (isset(GEN_PFACTS[$k]) && (!isset($out[$k]) || $out[$k]['value'] === '')) {
            $out[$k] = ['key' => $k, 'value' => $v];
        } elseif (preg_match('/^c\d{1,4}$/', $k) && !isset($out[$k]) && trim((string)($r['label'] ?? '')) !== '') {
            $out[$k] = ['key' => $k, 'label' => mb_substr(trim((string)$r['label']), 0, 100), 'value' => $v];
        }
    }
    foreach ($out as $k => $f) { // own fields named like a known fact become that fact
        if (!isset($f['label'])) {
            continue;
        }
        foreach (GEN_PFACTS_MATCH as $known => $re) {
            if (!preg_match($re, $f['label'])) {
                continue;
            }
            if ((!isset($out[$known]) || $out[$known]['value'] === '') && gen_pfacts_fits($known, $f['value'])) {
                $out[$known] = ['key' => $known, 'value' => $f['value']];
                unset($out[$k]);
            }
            break;
        }
    }
    $rank = array_flip(array_keys(GEN_PFACTS));
    $place = fn($k) => isset($rank[$k]) ? (in_array($k, GEN_PFACTS_APP, true) ? 2000 + $rank[$k] : $rank[$k]) : 1000;
    $order = array_flip(array_keys($out));
    uksort($out, fn($a, $b) => [$place($a), $order[$a]] <=> [$place($b), $order[$b]]);
    return array_values($out);
}

/** Whether a value fits a known fact's kind (an own field's value moving into it). */
function gen_pfacts_fits(string $k, string $v): bool
{
    try {
        gen_bfacts_clean(GEN_PFACTS[$k][1], $v, $k);
        return true;
    } catch (InvalidArgumentException $e) {
        return false;
    }
}

/** The starting list of a new project: every known fact, empty. */
function gen_pfacts_default(): array
{
    return array_map(fn($k) => ['key' => $k, 'value' => ''], array_keys(GEN_PFACTS));
}

/** Checks the values of known facts (a year, a whole number, a date); returns the error messages. */
function gen_pfacts_errors(array $facts): array
{
    $e = [];
    foreach ($facts as $f) {
        if ($f['value'] === '' || !isset(GEN_PFACTS[$f['key']])) {
            continue;
        }
        try {
            gen_bfacts_clean(GEN_PFACTS[$f['key']][1], $f['value'], GEN_PFACTS[$f['key']][0]);
        } catch (InvalidArgumentException $x) {
            $e[] = $x->getMessage();
        }
    }
    return $e;
}

/** A made-up rival of a job's brands (sample brands in rankings): the project's facts and offers are not its own. */
function gen_brand_is_sample(string $brandId): bool
{
    global $GEN_ROOT;
    return !empty(sx_brand_data($GEN_ROOT, 'operators.json')[$brandId]['sample']);
}

/** The project's facts of a job brand ("u<job>-…"), as brand-facts keys of that market: [schema key => value]. */
function gen_pfacts_for_brand(PDO $db, string $brandId, array $market): array
{
    if (!preg_match('/^u(\d+)-/', $brandId, $jm) || gen_brand_is_sample($brandId)) {
        return [];
    }
    $q = $db->prepare('SELECT p.info FROM projects p JOIN jobs j ON j.project_id = p.id WHERE j.id = ?');
    $q->execute([(int)$jm[1]]);
    $info = json_decode((string)$q->fetchColumn(), true) ?: [];
    $out = [];
    $facts = $info['brand_facts'] ?? [];
    if (!array_filter($facts, fn($f) => ($f['key'] ?? '') === 'payments')) { // an own field naming the payment methods counts as the fact
        foreach ($facts as $f) {
            if (!empty($f['label']) && preg_match('/платеж|платёж|оплат|payment|pagamento/iu', $f['label'])) {
                $facts[] = ['key' => 'payments', 'value' => $f['value'] ?? ''];
                break;
            }
        }
    }
    $pays = null;
    foreach ($facts as $f) {
        if (($f['key'] ?? '') === 'payments' && trim((string)($f['value'] ?? '')) !== '') {
            $pays = gen_pfacts_payments((string)$f['value'], $market['payments'] ?? []);
        }
    }
    if ($pays) {
        $out['payments'] = $pays;
    }
    foreach ($facts as $f) {
        $k = $f['key'] ?? '';
        $v = trim((string)($f['value'] ?? ''));
        if ($v === '' || !isset(GEN_PFACTS[$k]) || $k === 'payments' || in_array($k, GEN_PTEXT, true)) {
            continue;
        }
        try {
            $v = gen_bfacts_clean(GEN_PFACTS[$k][1], $v, GEN_PFACTS[$k][0]);
        } catch (InvalidArgumentException $x) {
            continue;
        }
        if ($k === 'payout_minutes') { // one figure: every payment method the brand takes (all of the market's when not given)
            foreach ($pays ?: ($market['payments'] ?? []) as $p) {
                $out["payout_$p"] = $v;
            }
        } elseif ($k === 'tagline' || $k === 'languages') { // one text: every language of the market
            foreach ($market['locales'] ?? [] as $l) {
                $out[$k . "_$l"] = $v;
            }
        } else {
            $out[$k] = $v;
        }
    }
    return $out;
}

/** The market's payment methods a project's list names ("Visa, Kaspi", "Банковская карта" → visa, mastercard), in the
 *  market's order. Names without a market method (crypto, e-wallets) are left to the text. */
function gen_pfacts_payments(string $list, array $market): array
{
    $want = [];
    foreach (array_filter(array_map('trim', preg_split('/[,;
]/u', $list))) as $name) {
        $n = mb_strtolower($name);
        foreach ($market as $id) {
            if ($id !== 'bank' && (str_contains($n, mb_strtolower(sx_payment_name($id))) || str_contains($n, $id))) {
                $want[$id] = true;
            }
        }
        if (preg_match('/карт|card|cartão|cartao/iu', $n)) {
            foreach (['visa', 'mastercard', 'visa-debit', 'mastercard-debit', 'debit'] as $id) {
                $want[$id] = true;
            }
        }
        if (preg_match('/банк|bank|transfer|перевод|ted/iu', $n) && !preg_match('/карт|card/iu', $n)) {
            foreach (['bank', 'ted', 'bpay', 'payid'] as $id) {
                $want[$id] = true;
            }
        }
    }
    return array_values(array_filter($market, fn($id) => isset($want[$id])));
}

/** Labels of the known facts a project has not given while its job brands show example data for them. */
function gen_pfacts_missing(array $info): array
{
    $given = [];
    foreach ($info['brand_facts'] ?? [] as $f) {
        if (trim((string)($f['value'] ?? '')) !== '') {
            $given[$f['key']] = true;
        }
    }
    $out = [];
    if (gen_pfacts_licence($info)['no'] === '') {
        $out[] = ['label' => 'Licence number', 'param' => ''];
    }
    foreach (['founded', 'min_deposit', 'payout_minutes'] as $k) { // the ones every site shows
        if (empty($given[$k])) {
            $out[] = ['label' => GEN_PFACTS[$k][0], 'param' => ''];
        }
    }
    return $out;
}

/** Vertex AI reads the project information, facts text and documents and returns the facts asked for: [key => {value, quote}]. */
function gen_pfacts_fill(PDO $db, int $projectId, array $fields): array
{
    require_once __DIR__ . '/content.php';
    $q = $db->prepare('SELECT info FROM projects WHERE id = ?');
    $q->execute([$projectId]);
    $info = gen_project_info(json_decode((string)$q->fetchColumn(), true) ?: []);
    $info['brand_facts'] = []; // read from the texts, not from the values being filled
    $docs = gen_pfiles_brief($db, $projectId);
    $source = gen_project_brief(['below' => 'none'] + $info, $docs);
    $source = trim(preg_replace('/\n### Text below sections.*$/s', '', $source));
    if ($docs === '' && trim($info['about'] . $info['facts'] . $info['audience'] . $info['keywords']) === '') { // nothing to read the facts from
        throw new InvalidArgumentException('The project has no information or documents to read the facts from. Fill in «Project information» or upload a document first.');
    }
    $keys = [];
    foreach ($fields as $f) {
        $k = (string)($f['key'] ?? '');
        if (isset(GEN_PFACTS[$k])) {
            $keys[$k] = GEN_PFACTS[$k][0] . ' (' . GEN_PFACTS[$k][1] . ')';
        } elseif (preg_match('/^c\d{1,4}$/', $k) && trim((string)($f['label'] ?? '')) !== '') {
            $keys[$k] = mb_substr(trim((string)$f['label']), 0, 100) . ' (text)';
        }
    }
    if (!$keys) {
        return [];
    }
    $prompt = "Read the project information and documents below and find these facts about the project's brand:\n\n"
        . json_encode($keys, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n"
        . "Rules: take a value only when the text states it; never guess, estimate or take example values. A fact the text does not state is null."
        . " Years as 4 digits, minutes, amounts and megabytes as whole numbers, dates as YYYY-MM-DD, lists as comma-separated text."
        . " The short description (one sentence, up to 140 characters), verdict (2–3 sentences), pros and cons (short items, one per line) are written in the documents' language from what the text says about the brand, with no claim or figure the text does not support; null when the text says nothing to base them on."
        . " For each value found, quote the words it comes from.\n\nAnswer with JSON only: {\"facts\": {\"<key>\": <value or null>}, \"quotes\": {\"<key>\": \"<quoted words>\"}}\n\n"
        . "---\n\n$source";
    $r = gen_vertex_generate($prompt, 'You extract facts from documents exactly as stated. You never invent or infer values.', true, gen_vertex_max('tok_facts'), null,
        ['project' => $projectId, 'kind' => 'facts', 'limit' => 'tok_facts']);
    $json = trim($r['text']);
    if (preg_match('/```(?:json)?\s*(.*?)```/s', $json, $mm)) {
        $json = $mm[1];
    }
    $a = json_decode($json, true);
    $plain = mb_strtolower(preg_replace('/\s+/u', ' ', strip_tags($source)));
    $out = [];
    foreach ((array)($a['facts'] ?? []) as $k => $v) {
        $quote = trim((string)($a['quotes'][$k] ?? ''));
        if (!isset($keys[$k]) || $v === null || $v === '' || $quote === '') {
            continue;
        }
        if (!str_contains($plain, mb_strtolower(preg_replace('/\s+/u', ' ', mb_substr($quote, 0, 60))))) { // the quote must really be in the source
            continue;
        }
        $text = in_array($k, GEN_PTEXT, true);
        $v = is_array($v) ? implode($text ? "\n" : ', ', $v) : (string)$v;
        if ($text && gen_content_bad_figures($v, gen_content_numbers($source))) { // a written text states only the documents' figures
            continue;
        }
        $out[$k] = ['value' => $v, 'quote' => $quote];
    }
    return $out;
}

/* ---------------------------------------------------------------- the brand's own rating texts (summary, verdict, pros, cons) */

function gen_btexts_table(PDO $db): void
{
    $db->exec('CREATE TABLE IF NOT EXISTS brand_texts (brand_id TEXT PRIMARY KEY, job_id INTEGER NOT NULL REFERENCES jobs(id) ON DELETE CASCADE, data TEXT NOT NULL, updated TEXT NOT NULL)');
}

/** The texts the AI text step wrote for a job brand: [lang => [key => text | list]]. */
function gen_btexts_saved(PDO $db, string $id): array
{
    gen_btexts_table($db);
    $q = $db->prepare('SELECT data FROM brand_texts WHERE brand_id = ?');
    $q->execute([$id]);
    return json_decode((string)$q->fetchColumn(), true) ?: [];
}

/** The texts the project gives for a job brand (Факты бренда): [key => text | list]. */
function gen_btexts_project(PDO $db, string $id): array
{
    if (!preg_match('/^u(\d+)-/', $id, $jm) || gen_brand_is_sample($id)) {
        return [];
    }
    $q = $db->prepare('SELECT p.info FROM projects p JOIN jobs j ON j.project_id = p.id WHERE j.id = ?');
    $q->execute([(int)$jm[1]]);
    $out = [];
    foreach ((json_decode((string)$q->fetchColumn(), true) ?: [])['brand_facts'] ?? [] as $f) {
        $k = $f['key'] ?? '';
        if (in_array($k, GEN_PTEXT, true) && ($v = gen_bfacts_clean(GEN_PFACTS[$k][1], $f['value'] ?? '', $k)) !== '' && $v !== []) {
            $out[$k] = $v;
        }
    }
    return $out;
}

/** The rating files of a market, per language: [file => lang]. */
function gen_rating_files(array $m): array
{
    $out = [];
    foreach ($m['locales'] ?? [] as $l) {
        $out[$l === $m['default_locale'] ? "ratings/{$m['id']}.json" : "ratings/{$m['id']}.$l.json"] = $l;
    }
    return $out;
}

/** A job brand's rating texts replaced by its own: the project's, else the ones the AI text step wrote, in every
 *  product view (casino, sports, both). Example texts stay where neither gives one. */
function gen_btexts_apply(PDO $db, string $id, string $market): void
{
    global $GEN_ROOT;
    $proj = gen_btexts_project($db, $id);
    $ai = gen_btexts_saved($db, $id);
    if (!$proj && !$ai) {
        return;
    }
    foreach (gen_rating_files(sx_json("$GEN_ROOT/markets/$market.json")) as $rf => $l) {
        $t = $proj + ($ai[$l] ?? []);
        if (!$t || !is_array(sx_brand_data($GEN_ROOT, $rf)['brands'][$id] ?? null)) {
            continue;
        }
        gen_overlay_update($rf, function ($d) use ($id, $t) {
            $set = function (array $e) use ($t): array {
                foreach ($t as $k => $v) {
                    if ($v !== '' && $v !== []) {
                        $e[$k] = $v;
                    }
                }
                return $e;
            };
            $r = $set($d['brands'][$id]);
            foreach ($r['products'] ?? [] as $p => $pr) {
                if (is_array($pr)) {
                    $r['products'][$p] = $set($pr);
                }
            }
            $d['brands'][$id] = $r;
            return $d;
        });
    }
}

/**
 * The AI text step's part for the brands: for each of the job's own brands, the rating texts the project does not give
 * (summary, verdict, pros, cons) written from the project information and the brand's facts, in each language the
 * market has rating text in. Figures not in the data are taken out; the texts are stored (brand_texts), applied, and
 * kept until the next run. A failure leaves the example texts.
 */
function gen_btexts_write(PDO $db, int $jobId, string $projectPart): void
{
    global $GEN_ROOT;
    require_once __DIR__ . '/content.php';
    $job = $db->query('SELECT market, spec, project_id FROM jobs WHERE id = ' . $jobId)->fetch() ?: [];
    if (!$job) {
        return;
    }
    $m = sx_json("$GEN_ROOT/markets/{$job['market']}.json");
    $ops = sx_brand_data($GEN_ROOT, 'operators.json');
    gen_btexts_table($db);
    foreach (json_decode($job['spec'], true)['brand_ids'] ?? [] as $id) {
        if (empty($ops[$id]['user_brand']) || !empty($ops[$id]['sample'])) {
            continue;
        }
        $want = array_values(array_diff(GEN_PTEXT, array_keys(gen_btexts_project($db, $id))));
        if (!$want) {
            $db->prepare('DELETE FROM brand_texts WHERE brand_id = ?')->execute([$id]);
            continue;
        }
        $v = $ops[$id]['verticals'] ?? [];
        $slot = in_array('sports', $v, true) ? (array_intersect(['casino', 'live-casino'], $v) ? 'both' : 'sports') : null;
        $cur = [];
        foreach (gen_rating_files($m) as $rf => $l) {
            $e = sx_brand_data($GEN_ROOT, $rf)['brands'][$id] ?? null;
            if (is_array($e)) {
                $e = $slot && is_array($e['products'][$slot] ?? null) ? array_merge($e, $e['products'][$slot]) : $e;
                $cur[$l] = array_intersect_key($e, array_flip($want));
            }
        }
        if (!$cur) {
            continue;
        }
        $name = $ops[$id]['name'];
        $facts = gen_content_brand_facts(['_id' => $id] + $ops[$id], $job['market'], $m);
        $flines = implode("\n", array_map(fn($k, $x) => "- $k: $x", array_keys($facts), $facts));
        $offers = $slot === null ? 'casino and live casino' : ($slot === 'sports' ? 'sports betting' : 'sports betting and casino');
        $P = [];
        $P[] = "Write the editorial rating texts of the brand $name (it offers $offers) for a review and comparison site: " . implode(', ', $want) . '.';
        $P[] = '';
        $P[] = "- summary: one sentence, up to 140 characters, shown on ranking cards: the brand's main strength and one weakness.";
        $P[] = "- verdict: 2–3 sentences, up to 350 characters, the review's conclusion: who the brand suits, what is good and what is weaker.";
        $P[] = '- pros: 3 short items, up to 60 characters each.';
        $P[] = '- Write amounts and counts as the market writes them (thousands separated by a space: 15 700, 2 000 ₸).';
        $P[] = '- cons: 2–3 short items, up to 60 characters each: real limits the facts or project information show (for example no phone line, few live tables). Never invent problems, and always give at least one. The payment methods in the figures are only the ones the site lists; the project information may name more, so never call the payment choice limited unless it says so.';
        $P[] = '';
        $P[] = "Rules: write the brand exactly as $name. Use a figure only when it is in the brand figures or the project information below, exactly as given; otherwise write without a figure."
            . ' No absolute or guaranteed claims, no promises of winnings, and no comparisons with other operators («fastest», «best in the list») unless the project information states them. Follow the market rules.';
        $P[] = '';
        $P[] = "## $name figures";
        $P[] = $flines ?: '- none given';
        $P[] = '';
        $P[] = sx_ai_market_facts($m, $m['default_locale'] ?? 'en', []);
        $P[] = '';
        $P[] = $projectPart !== '' ? $projectPart : "## Project information\n\nNone given.";
        $P[] = '';
        $P[] = "The current texts are example copy from another brand (their claims and figures are not this brand's), given only for the length and tone:";
        $P[] = '```json';
        $P[] = json_encode($cur, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $P[] = '```';
        $P[] = '';
        $P[] = 'Write each language given above, in that language: ' . implode(', ', array_map(fn($l) => sx_brief_lang($l) . " ($l)", array_keys($cur))) . '.';
        $P[] = 'Answer with JSON only, in this shape: {"<lang>": {"summary": "…", "verdict": "…", "pros": ["…"], "cons": ["…"]}} (only the texts asked for).';
        $allowed = gen_content_numbers($flines . "\n" . $projectPart);
        foreach ([24, 7, 365, (int)date('Y')] as $n) {
            $allowed['any'][(string)$n] = true;
        }
        try {
            $r = gen_vertex_generate(implode("\n", $P), 'You write short, honest editorial texts about gambling operators from the data given. You never invent facts or figures.', true, gen_vertex_max('tok_brand'), null,
                ['project' => (int)$job['project_id'] ?: null, 'job' => $jobId, 'kind' => 'text', 'limit' => 'tok_brand']);
            $json = trim($r['text']);
            if (preg_match('/```(?:json)?\s*(.*?)```/s', $json, $mm)) {
                $json = $mm[1];
            }
            $a = json_decode($json, true);
            $out = [];
            foreach (array_keys($cur) as $l) {
                $x = is_array($a[$l] ?? null) ? $a[$l] : [];
                $t = [];
                foreach (['summary' => 220, 'verdict' => 520] as $k => $max) {
                    if (in_array($k, $want, true) && is_string($x[$k] ?? null) && ($s = gen_content_drop_figures(trim(strip_tags($x[$k])), $allowed)) !== null && mb_strlen($s) <= $max) {
                        $t[$k] = gen_content_brand('', $s, $name);
                    }
                }
                foreach (['pros', 'cons'] as $k) {
                    if (in_array($k, $want, true) && is_array($x[$k] ?? null)) {
                        $items = array_values(array_filter(array_map(fn($i) => is_string($i) ? trim(strip_tags($i)) : '', $x[$k]),
                            fn($i) => $i !== '' && mb_strlen($i) <= 90 && !gen_content_bad_figures($i, $allowed)));
                        if ($items) {
                            $t[$k] = array_slice($items, 0, 4);
                        }
                    }
                }
                if ($t) {
                    $out[$l] = $t;
                }
            }
            if (!$out) {
                throw new RuntimeException('the answer had no usable text');
            }
            $db->prepare('INSERT OR REPLACE INTO brand_texts (brand_id, job_id, data, updated) VALUES (?, ?, ?, ?)')->execute([$id, $jobId, json_encode($out, JSON_UNESCAPED_UNICODE), gen_now()]);
            gen_btexts_apply($db, $id, $job['market']);
        } catch (Throwable $e) {
            fwrite(STDERR, date('c') . " brand texts of $id: " . $e->getMessage() . "\n");
        }
    }
}

/* ---------------------------------------------------------------- example reviews follow the brand's facts */

/** An amount in the market's currency format ("2 000 ₸", "R$ 10"). */
function gen_market_money(array $m, $n): string
{
    $c = $m['currency'] ?? [];
    $num = number_format((float)$n, 0, $c['decimal'] ?? '.', ($c['thousands'] ?? ',') === ' ' ? "\u{202F}" : ($c['thousands'] ?? ','));
    $gap = !empty($c['space']) ? "\u{00A0}" : '';
    return ($c['position'] ?? 'after') === 'before' ? ($c['symbol'] ?? '') . $gap . $num : $num . $gap . ($c['symbol'] ?? '');
}

/** A time in a review's words, to read after "за" / "in": "10 минут", "2 часа". */
function gen_review_time(int $min, string $l): string
{
    $ru = fn($n, $a, $b, $c) => $n % 10 === 1 && $n % 100 !== 11 ? $a : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? $b : $c);
    if ($min < 90) {
        return ['ru' => "$min " . $ru($min, 'минуту', 'минуты', 'минут'), 'pt' => "$min minutos", 'kk' => "$min минут"][$l] ?? "$min minutes";
    }
    $h = (int)round($min / 60);
    return ['ru' => "$h " . $ru($h, 'час', 'часа', 'часов'), 'pt' => "$h horas", 'kk' => "$h сағат"][$l] ?? "$h hours";
}

/**
 * A job brand's example reviews (reviews/<id>.json, this market) made consistent with its facts, without touching a
 * review's rating, topics or tone: payment methods the brand does not take become ones it takes, app versions become
 * the app's version, withdrawal times written in figures become the brand's time for that method, and minimum deposit
 * or withdrawal amounts become the brand's (the withdrawal minimum from a project field so named, when there is one).
 */
function gen_reviews_sync(PDO $db, string $id, string $market): void
{
    global $GEN_ROOT;
    $m = sx_json("$GEN_ROOT/markets/$market.json");
    $f = sx_brand_data($GEN_ROOT, "facts/$market.json")['brands'][$id] ?? null;
    $op = sx_brand_data($GEN_ROOT, 'operators.json')[$id] ?? null;
    if (!$f || !$op) {
        return;
    }
    $minOut = null;
    if (preg_match('/^u(\d+)-/', $id, $jm)) {
        $q = $db->prepare('SELECT p.info FROM projects p JOIN jobs j ON j.project_id = p.id WHERE j.id = ?');
        $q->execute([(int)$jm[1]]);
        foreach ((json_decode((string)$q->fetchColumn(), true) ?: [])['brand_facts'] ?? [] as $pf) {
            if ((($pf['key'] ?? '') === 'min_withdrawal' || preg_match(GEN_PFACTS_MATCH['min_withdrawal'], (string)($pf['label'] ?? ''))) && preg_match('/\d[\d\s.,]*/u', (string)($pf['value'] ?? ''), $nm)) {
                $minOut = (int)preg_replace('/\D/', '', $nm[0]);
            }
        }
    }
    $all = $m['payments'] ?? [];
    $own = array_values(array_intersect($all, $f['payments'] ?? [])) ?: $all;
    $wd = $f['withdrawal_minutes'] ?? [];
    $wdOwn = array_intersect_key($wd, array_flip($own)) ?: $wd;
    $name = fn($p) => $p === 'bank' ? 'bank' : sx_payment_name($p);
    $named = fn($p, $t) => (bool)preg_match('/(?<!\p{L})' . preg_quote($name($p), '/') . '(?!\p{L})/iu', $t);
    $version = $op['app']['version'] ?? null;
    $swaps = function (string $t) use ($all, $own, $named): array { // a method the brand does not take: one it takes, not already named in the review
        $map = [];
        $taken = array_values(array_filter($own, fn($o) => $named($o, $t)));
        foreach (array_diff($all, $own) as $p) {
            if ($p === 'bank' || !$named($p, $t)) {
                continue;
            }
            $use = $own[0];
            foreach ($own as $o) {
                if (!in_array($o, $taken, true)) {
                    $use = $o;
                    break;
                }
            }
            $taken[] = $use;
            $map[$p] = $use;
        }
        return $map;
    };
    $fix = function (string $t, string $l, array $map) use ($wdOwn, $name, $named, $version, $f, $m, $minOut): string {
        foreach ($map as $p => $use) {
            $t = preg_replace('/(?<!\p{L})' . preg_quote($name($p), '/') . '(?!\p{L})/iu', $name($use), $t);
        }
        if ($version) {
            $t = preg_replace('/((?:верси\p{L}*|version|versão|нұсқа\p{L}*)\s+)\d+(?:\.\d+)+/iu', '${1}' . $version, $t);
        }
        $sent = preg_split('/(?<=[.!?])\s+/u', $t);
        foreach ($sent as $i => $s) {
            if ($wdOwn && preg_match('/вывод|вывож|выплат|приход|приш|withdraw|cash(?:ed)? out|payout|saque|saquei|chega|шығар/iu', $s)) {
                $min = min($wdOwn);
                foreach ($wdOwn as $p => $v) {
                    if ($named($p, $s)) {
                        $min = $v;
                        break;
                    }
                }
                $s = preg_replace('/\d+(?:\s?[–-]\s?\d+)?\s?(?:минут\p{L}*|мин\.?(?!\p{L})|minutes?|mins?(?!\p{L})|minutos?)/iu', gen_review_time((int)$min, $l), $s);
            }
            if (preg_match('/минимальн|minimum|mínim|ең аз/iu', $s)) {
                $amt = null;
                if (preg_match('/депозит|пополн|deposit|depósito/iu', $s) && isset($f['min_deposit'])) {
                    $amt = $f['min_deposit'];
                } elseif (preg_match('/вывод|withdraw|saque/iu', $s) && $minOut) {
                    $amt = $minOut;
                }
                if ($amt !== null) {
                    $s = preg_replace('/(?:(?:[₸€$£]|R\$|A\$)\s?)?\d{1,3}(?:[ \x{00A0}\x{202F}.,]\d{3})*(?:\s?(?:₸|тг|тенге|€|reais))?/u', gen_market_money($m, $amt), $s, 1);
                }
            }
            $sent[$i] = $s;
        }
        return implode(' ', $sent);
    };
    gen_overlay_update("reviews/$id.json", function ($d) use ($market, $fix, $swaps, $m) {
        foreach ($d['reviews'] ?? [] as $k => $r) {
            if (($r['market'] ?? '') !== $market) {
                continue;
            }
            $l = $r['locale'] ?? ($m['default_locale'] ?? 'en');
            $map = $swaps(implode(' ', array_filter([$r['title'] ?? '', $r['text'] ?? '', $r['reply']['text'] ?? ''], 'is_string')));
            foreach (['title', 'text'] as $x) {
                if (is_string($r[$x] ?? null)) {
                    $d['reviews'][$k][$x] = $fix($r[$x], $l, $map);
                }
            }
            if (is_string($r['reply']['text'] ?? null)) {
                $d['reviews'][$k]['reply']['text'] = $fix($r['reply']['text'], $l, $map);
            }
        }
        return $d;
    });
}

/* ---------------------------------------------------------------- project offers (Информация о проекте → Акции) */

const GEN_POFFER_TYPES = ['welcome_bonus', 'deposit_bonus', 'free_bets', 'promo_code', 'odds_boost', 'cashback', 'loyalty', 'tournaments'];
/** Value kinds and the figures each takes. */
const GEN_POFFER_KINDS = ['match' => ['percent', 'max'], 'boost' => ['percent'], 'cashback' => ['percent', 'max'], 'free_bets' => ['amount'], 'free_spins' => ['count'], 'prize_pool' => ['amount']];
const GEN_POFFER_INTS = ['percent', 'max', 'amount', 'count', 'wagering', 'min_deposit', 'max_bet', 'expiry_days'];

/** A project's offers as stored: [{type, kind, title, figures…, code, min_odds, valid_to, conditions}], 20 at most;
 *  rows without a title are dropped. */
function gen_poffers_clean($in): array
{
    $out = [];
    foreach (is_array($in) ? array_slice($in, 0, 20) : [] as $r) {
        if (!is_array($r) || ($title = mb_substr(trim((string)($r['title'] ?? '')), 0, 200)) === '') {
            continue;
        }
        $o = [
            'type' => in_array($r['type'] ?? '', GEN_POFFER_TYPES, true) ? $r['type'] : 'welcome_bonus',
            'kind' => isset(GEN_POFFER_KINDS[$r['kind'] ?? '']) ? $r['kind'] : 'match',
            'title' => $title,
        ];
        foreach (GEN_POFFER_INTS as $k) {
            $v = preg_replace('/[\s\x{00A0}\x{202F}]/u', '', (string)($r[$k] ?? ''));
            $o[$k] = $v === '' ? '' : $v;
        }
        $o['code'] = mb_substr(trim((string)($r['code'] ?? '')), 0, 40);
        $o['min_odds'] = str_replace(',', '.', trim((string)($r['min_odds'] ?? '')));
        $o['valid_to'] = trim((string)($r['valid_to'] ?? ''));
        $o['conditions'] = mb_substr(trim(str_replace("\r", '', (string)($r['conditions'] ?? ''))), 0, 600);
        $out[] = $o;
    }
    return $out;
}

/** Checks the offers' figures (whole numbers, odds, a date); returns the error messages. */
function gen_poffers_errors(array $offers): array
{
    $e = [];
    foreach ($offers as $i => $o) {
        $n = '«' . $o['title'] . '»';
        foreach (GEN_POFFER_INTS as $k) {
            if ($o[$k] !== '' && (!ctype_digit($o[$k]) || (int)$o[$k] <= 0)) {
                $e[] = "$n: $k must be a whole number.";
            }
        }
        if ($o['min_odds'] !== '' && !preg_match('/^\d{1,3}(\.\d{1,2})?$/', $o['min_odds'])) {
            $e[] = "$n: min odds like 1.50.";
        }
        if ($o['valid_to'] !== '' && !(preg_match('/^\d{4}-\d\d-\d\d$/', $o['valid_to']) && strtotime($o['valid_to']))) {
            $e[] = "$n: valid until as YYYY-MM-DD.";
        }
    }
    return $e;
}

/** The project's offers replace the example offers of a job brand ("u<job>-…", not a sample rival) in this market. */
function gen_poffers_apply(PDO $db, string $id, string $market): void
{
    global $GEN_ROOT;
    if (!preg_match('/^u(\d+)-/', $id, $jm) || gen_brand_is_sample($id)) {
        return;
    }
    $q = $db->prepare('SELECT p.info FROM projects p JOIN jobs j ON j.project_id = p.id WHERE j.id = ?');
    $q->execute([(int)$jm[1]]);
    $offers = gen_poffers_clean((json_decode((string)$q->fetchColumn(), true) ?: [])['offers'] ?? []);
    if (!$offers) {
        return;
    }
    $m = sx_json("$GEN_ROOT/markets/$market.json");
    $sq = $db->prepare('SELECT spec FROM jobs WHERE id = ?');
    $sq->execute([(int)$jm[1]]);
    $jl = gen_job_langs(json_decode((string)$sq->fetchColumn(), true) ?: []); // a multi-language job: its languages, the main one first
    $langs = array_values(array_unique(array_merge(array_keys($jl), $m['locales'] ?? [], ['en'])));
    $main = $jl ? array_key_first($jl) : null;
    // the project's wording in every language; in a multi-language job's other languages its translation (once translated)
    $all = function (string $t) use ($langs, $main, $db) {
        $out = [];
        foreach ($langs as $l) {
            $out[$l] = $main !== null && $l !== $main ? (gen_tr_cached($db, $l, $t) ?? $t) : $t;
        }
        return $out;
    };
    $num = fn($v) => $v === '' ? null : (int)$v;
    $host = sx_brand_data($GEN_ROOT, 'operators.json')[$id]['domain'] ?? null;
    $feed = [];
    foreach ($offers as $i => $o) {
        $value = ['kind' => $o['kind']];
        foreach (GEN_POFFER_KINDS[$o['kind']] as $k) {
            $value[$k] = $num($o[$k]);
        }
        $feed[] = [
            'id' => "$id-o" . ($i + 1),
            'type' => $o['type'],
            'markets' => [$market],
            'title' => $all($o['title']),
            'value' => $value,
            'code' => $o['code'] !== '' ? $o['code'] : null,
            'code_scope' => 'public',
            'terms' => [
                'min_deposit' => $num($o['min_deposit']),
                'wagering' => $num($o['wagering']),
                'min_odds' => $o['min_odds'] !== '' ? $o['min_odds'] : null,
                'max_bet' => $num($o['max_bet']),
                'expiry_days' => $num($o['expiry_days']),
                'eligible' => $o['conditions'] !== '' ? $all($o['conditions']) : null,
                'excluded_payments' => null,
                'games' => null,
            ],
            'valid_to' => $o['valid_to'] !== '' ? $o['valid_to'] : date('Y-m-d', strtotime('+1 year')),
            'verified_at' => date('Y-m-d'),
            'terms_url' => $host ? "https://$host/promotions/terms" : null,
            'project' => true,
        ];
    }
    gen_overlay_update("promotions/$id.json", fn($d) => ['promotions' => array_merge(array_values(array_filter($d['promotions'] ?? [], fn($p) => !in_array($market, $p['markets'] ?? [], true))), $feed)]);
}

/** Vertex AI reads the project information and documents and lists the offers they describe (suggestions, not saved). */
function gen_poffers_fill(PDO $db, int $projectId): array
{
    require_once __DIR__ . '/content.php';
    $q = $db->prepare('SELECT info FROM projects WHERE id = ?');
    $q->execute([$projectId]);
    $info = gen_project_info(json_decode((string)$q->fetchColumn(), true) ?: []);
    $info['offers'] = [];
    $source = gen_project_brief(['below' => 'none'] + $info, gen_pfiles_brief($db, $projectId));
    $source = trim(preg_replace('/\n### Text below sections.*$/s', '', $source));
    if ($source === '') {
        throw new InvalidArgumentException('The project has no information or documents to read the offers from. Fill in «Project information» or upload a document first.');
    }
    $prompt = "Read the project information and documents below and list the brand's offers (bonuses, promotions).\n\n"
        . "For each offer give: type (one of " . implode(', ', GEN_POFFER_TYPES) . "), kind (one of " . implode(', ', array_keys(GEN_POFFER_KINDS)) . "), title (short, as the documents name it, in their language),"
        . " percent, max (largest bonus amount), amount, count (free spins), code, wagering (multiple), min_deposit, max_bet, min_odds, expiry_days, conditions (one sentence in the language of the documents: who can claim, main terms), and quote (up to 12 words of the document, copied exactly, that name the offer)."
        . " Amounts as whole numbers in the market currency. Take a value only when the text states it; a value the text does not state is null. Never invent offers or figures. At most 20 offers.\n\n"
        . "Answer with JSON only: {\"offers\": [{…}]}\n\n---\n\n$source";
    $r = gen_vertex_generate($prompt, 'You extract facts from documents exactly as stated. You never invent or infer values.', true, gen_vertex_max('tok_offers'), null, ['project' => $projectId, 'kind' => 'offers', 'limit' => 'tok_offers']);
    $json = trim($r['text']);
    if (preg_match('/```(?:json)?\s*(.*?)```/s', $json, $mm)) {
        $json = $mm[1];
    }
    $a = json_decode($json, true);
    $plain = mb_strtolower(preg_replace('/\s+/u', ' ', strip_tags($source)));
    $rows = [];
    foreach ((array)($a['offers'] ?? []) as $o) {
        $quote = trim((string)($o['quote'] ?? ''));
        if (!is_array($o) || $quote === '' || !str_contains($plain, mb_strtolower(preg_replace('/\s+/u', ' ', mb_substr($quote, 0, 60))))) {
            continue;
        }
        foreach ($o as $k => $v) {
            $o[$k] = $v === null ? '' : (is_scalar($v) ? (string)$v : '');
        }
        $rows[] = $o;
    }
    $out = gen_poffers_clean($rows);
    foreach ($out as $i => $o) {
        foreach (GEN_POFFER_INTS as $k) {
            if ($o[$k] !== '' && !ctype_digit($o[$k])) {
                $out[$i][$k] = ctype_digit((string)(int)(float)$o[$k]) && (float)$o[$k] > 0 ? (string)(int)round((float)$o[$k]) : '';
            }
        }
    }
    return $out;
}
