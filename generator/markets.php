<?php
/**
 * Markets: the market files (markets/<id>.json) and their AI notes (ai/markets/<id>.md), managed from the generator.
 * Every save keeps the previous version in data/market-history/<id>/ and a line in data/market-history/log.jsonl, so
 * any change can be seen and undone. A deleted market goes to data/market-history/_deleted/ and can be restored.
 */

const GEN_MARKET_VERTICALS = ['sports', 'casino'];
const GEN_MARKET_PROMOS = ['welcome_bonus', 'deposit_bonus', 'free_bets', 'promo_code', 'odds_boost', 'cashback', 'loyalty', 'tournaments'];
const GEN_MARKET_POLICY = ['allowed', 'restricted', 'unknown'];
const GEN_MARKET_SHARE = ['facebook', 'x', 'whatsapp', 'telegram'];
const GEN_MARKET_REQUIRED_BLOCKS = ['age-gate', 'compliance-bar'];
const GEN_MARKET_TEXTS = ['license_line', 'regulator_default', 'license_short', 'age_notice', 'self_exclusion']; // per language
const GEN_MARKET_TEXTS_REQUIRED = ['license_line', 'regulator_default', 'license_short', 'age_notice'];

function gen_market_file(string $id): string
{
    global $GEN_ROOT;
    return "$GEN_ROOT/markets/$id.json";
}

function gen_market_history_dir(string $sub = ''): string
{
    $d = gen_cache_dir('market-history') . ($sub !== '' ? "/$sub" : '');
    is_dir($d) || @mkdir($d, 0777, true);
    return $d;
}

/** Every market: id => data. */
function gen_market_files(): array
{
    global $GEN_ROOT;
    $out = [];
    foreach (glob("$GEN_ROOT/markets/*.json") as $f) {
        $out[basename($f, '.json')] = sx_json($f);
    }
    return $out;
}

/** Languages the blocks are written in (i18n/strings.json), with how complete each one is. */
function gen_market_languages(): array
{
    global $GEN_ROOT;
    $s = sx_json("$GEN_ROOT/i18n/strings.json");
    unset($s['_note']);
    $count = [];
    foreach ($s as $v) {
        foreach (is_array($v) ? array_keys($v) : [] as $l) {
            $count[$l] = ($count[$l] ?? 0) + 1;
        }
    }
    $names = ['en' => 'English', 'ru' => 'Russian', 'kk' => 'Kazakh', 'pt' => 'Portuguese'];
    $out = [];
    foreach ($count as $l => $n) {
        $out[$l] = ['name' => $names[$l] ?? $l, 'complete' => round(100 * $n / max(1, count($s)))];
    }
    return $out;
}

/** Payment methods the blocks know by name (lib/render.php sx_payment_name), plus any a market lists. */
function gen_market_payments(): array
{
    global $GEN_ROOT;
    $src = file_get_contents("$GEN_ROOT/lib/render.php");
    $ids = [];
    if (preg_match('/function sx_payment_name.*?\[(.*?)\]\[\$id\]/s', $src, $m)) {
        preg_match_all("/'([a-z0-9-]+)' =>/", $m[1], $mm);
        $ids = $mm[1];
    }
    foreach (gen_market_files() as $mk) {
        $ids = array_merge($ids, $mk['payments'] ?? []);
    }
    return array_values(array_unique($ids));
}

/** Pages a market can make mandatory: page id => the site types that have it. */
function gen_market_pages(): array
{
    global $GEN_ROOT;
    $out = [];
    foreach (glob("$GEN_ROOT/blueprints/*.json") as $f) {
        $bp = sx_json($f);
        foreach ($bp['pages'] ?? [] as $pid => $p) {
            if ($pid !== 'home' && !isset($p['generate']) && ($p['rule'] ?? 'required') === 'optional') {
                $out[$pid]['types'][] = basename($f, '.json');
                $out[$pid]['slug'] = $p['slug'] ?? $pid;
            }
        }
    }
    ksort($out);
    return $out;
}

/**
 * What each rule changes, read from the blueprints and blocks: which site types, angles, pages and section designs
 * a vertical or a promotion type switches on or off.
 */
function gen_market_effects(): array
{
    global $GEN_ROOT;
    $v = array_fill_keys(GEN_MARKET_VERTICALS, ['types' => [], 'angles' => [], 'pages' => [], 'designs' => []]);
    $promo = array_fill_keys(GEN_MARKET_PROMOS, ['angles' => []]);
    $promoPages = [];
    foreach (glob("$GEN_ROOT/blueprints/*.json") as $f) {
        $t = basename($f, '.json');
        $bp = sx_json($f);
        if (!empty($bp['requires_vertical'])) {
            $v[$bp['requires_vertical']]['types'][] = $t;
        }
        foreach ($bp['angles'] ?? [] as $a => $x) {
            if (!empty($x['requires_vertical'])) {
                $v[$x['requires_vertical']]['angles'][] = "$t: $a";
            }
            foreach ($x['requires_promo'] ?? [] as $p) {
                $promo[$p]['angles'][] = "$t: $a";
            }
        }
        foreach ($bp['pages'] ?? [] as $pid => $p) {
            if (!empty($p['requires_vertical'])) {
                $v[$p['requires_vertical']]['pages'][] = "$t: $pid";
            }
            if (!empty($p['requires_promo'])) {
                $promoPages[] = "$t: $pid";
            }
        }
    }
    foreach (glob("$GEN_ROOT/blocks/*/block.json") as $f) {
        foreach (sx_json($f)['sx']['variant_requires'] ?? [] as $design => $need) {
            $v[$need]['designs'][] = basename(dirname($f)) . ": $design";
        }
    }
    return ['verticals' => $v, 'promotions' => $promo, 'promo_pages' => $promoPages];
}

/** Who uses a market: jobs, their sites, demos, and brands licensed there. */
function gen_market_usage(PDO $db, string $id): array
{
    global $GEN_ROOT;
    $q = $db->prepare('SELECT j.id, j.name, p.name AS project, (SELECT COUNT(*) FROM sites s WHERE s.job_id = j.id) AS sites
        FROM jobs j JOIN projects p ON p.id = j.project_id WHERE j.market = ? ORDER BY j.id DESC');
    $q->execute([$id]);
    $jobs = $q->fetchAll();
    $demos = [];
    foreach (glob("$GEN_ROOT/sites/demos/*.json") as $f) {
        if ((sx_json($f)['market'] ?? '') === $id) {
            $demos[] = basename($f, '.json');
        }
    }
    $brands = [];
    foreach (sx_json("$GEN_ROOT/brands/operators.json") as $bid => $o) {
        if ($bid !== '_note' && in_array($id, $o['markets'] ?? [], true)) {
            $brands[] = $o['name'] ?? $bid;
        }
    }
    return ['jobs' => $jobs, 'sites' => array_sum(array_column($jobs, 'sites')), 'demos' => $demos, 'brands' => $brands];
}

/** Built sites of this market made before its last change (they show the old rules until they are rebuilt). */
function gen_market_stale_sites(PDO $db, string $id): int
{
    global $GEN_ROOT;
    $changed = @filemtime(gen_market_file($id)) ?: 0;
    $q = $db->prepare("SELECT s.id FROM sites s JOIN jobs j ON j.id = s.job_id WHERE j.market = ? AND s.status = 'built'");
    $q->execute([$id]);
    $n = 0;
    foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $sid) {
        $t = @filemtime("$GEN_ROOT/dist/generated/$sid/index.html");
        $n += $t && $t < $changed ? 1 : 0;
    }
    return $n;
}

/** The market's AI notes (ai/markets/<id>.md). */
function gen_market_notes(string $id): string
{
    global $GEN_ROOT;
    return is_file("$GEN_ROOT/ai/markets/$id.md") ? file_get_contents("$GEN_ROOT/ai/markets/$id.md") : '';
}

/** Checks a market before it is saved. Returns [field path => problem]. */
function gen_market_validate(array $m, array $pages): array
{
    $e = [];
    $langs = array_keys(gen_market_languages());
    $str = fn($x) => is_string($x) && trim($x) !== '';
    if (!preg_match('/^[a-z]{2,8}$/', (string)($m['id'] ?? ''))) {
        $e['id'] = 'Use 2–8 lowercase Latin letters, e.g. "de" or "ontario".';
    }
    if (!$str($m['name'] ?? null)) {
        $e['name'] = 'Give the market a name.';
    }
    $locales = is_array($m['locales'] ?? null) ? $m['locales'] : [];
    if (!$locales) {
        $e['locales'] = 'Choose at least one language.';
    } elseif ($bad = array_diff($locales, $langs)) {
        $e['locales'] = 'The blocks are not written in ' . implode(', ', $bad) . '. Available: ' . implode(', ', $langs) . '.';
    }
    if (!in_array($m['default_locale'] ?? '', $locales, true)) {
        $e['default_locale'] = 'The main language must be one of the market\'s languages.';
    }
    if (!in_array($m['dir'] ?? 'ltr', ['ltr', 'rtl'], true)) {
        $e['dir'] = 'Text direction is ltr or rtl.';
    }
    $c = $m['currency'] ?? [];
    if (!preg_match('/^[A-Z]{3}$/', (string)($c['code'] ?? ''))) {
        $e['currency.code'] = 'Use the 3-letter ISO code, e.g. EUR.';
    }
    if (!$str($c['symbol'] ?? null)) {
        $e['currency.symbol'] = 'Give the currency symbol.';
    }
    if (!in_array($c['position'] ?? '', ['before', 'after'], true)) {
        $e['currency.position'] = 'Symbol goes before or after the amount.';
    }
    foreach (['decimal' => $c['decimal'] ?? null, 'thousands' => $c['thousands'] ?? null] as $k => $x) {
        if (!is_string($x) || mb_strlen($x) > 1) {
            $e["currency.$k"] = 'One character (or none for thousands).';
        }
    }
    if (!is_int($c['decimals'] ?? null) || $c['decimals'] < 0 || $c['decimals'] > 3) {
        $e['currency.decimals'] = '0 to 3 decimals.';
    }
    if (!$str($m['date_format'] ?? null)) {
        $e['date_format'] = 'Give a date format, e.g. d.m.Y.';
    }
    if (!is_int($m['min_age'] ?? null) || $m['min_age'] < 16 || $m['min_age'] > 25) {
        $e['min_age'] = 'Minimum age is a whole number from 16 to 25.';
    }
    foreach (GEN_MARKET_PROMOS as $p) {
        if (!in_array($m['promotions'][$p] ?? 'unknown', GEN_MARKET_POLICY, true)) {
            $e["promotions.$p"] = 'Allowed, restricted or unknown.';
        }
    }
    foreach ($m['payments'] ?? [] as $p) {
        if (!preg_match('/^[a-z0-9-]+$/', (string)$p)) {
            $e['payments'] = "Payment ids are lowercase letters, digits and dashes ('$p').";
        }
    }
    if (!in_array($m['apps']['ios'] ?? 'none', ['app-store', 'none'], true)) {
        $e['apps.ios'] = 'iPhone apps: App Store or none.';
    }
    if (!in_array($m['apps']['android'] ?? 'none', ['play', 'apk', 'both', 'none'], true)) {
        $e['apps.android'] = 'Android apps: Google Play, APK, both or none.';
    }
    if (array_diff($m['share_networks'] ?? [], GEN_MARKET_SHARE)) {
        $e['share_networks'] = 'Unknown share network.';
    }
    foreach ($m['html_lang'] ?? [] as $l => $tag) {
        if (!preg_match('/^[a-zA-Z]{2,3}(-[A-Za-z0-9]{2,8})*$/', (string)$tag)) {
            $e["html_lang.$l"] = "Not a language tag ('$tag'); e.g. de-DE.";
        }
    }
    if ($bad = array_diff($m['required_pages'] ?? [], array_keys($pages))) {
        $e['required_pages'] = 'Unknown page: ' . implode(', ', $bad) . '.';
    }
    $cp = $m['compliance'] ?? [];
    foreach ($locales as $l) {
        foreach (GEN_MARKET_TEXTS_REQUIRED as $k) {
            if (!$str($cp[$k][$l] ?? null)) {
                $e["compliance.$k.$l"] = 'Needed for every language of the market.';
            }
        }
        if (isset($cp['license_line'][$l]) && !str_contains($cp['license_line'][$l], '{brand}')) {
            $e["compliance.license_line.$l"] = 'Keep {brand} in the licence line (the brand name goes there).';
        }
    }
    if (!in_array($cp['strip'] ?? 'optional', ['required', 'optional'], true)) {
        $e['compliance.strip'] = 'Required or optional.';
    }
    if (array_diff($cp['required_blocks'] ?? [], GEN_MARKET_REQUIRED_BLOCKS)) {
        $e['compliance.required_blocks'] = 'Unknown block.';
    }
    if (($cp['brand_domain_suffix'] ?? null) !== null && !preg_match('/^\.[a-z0-9.-]+$/', (string)$cp['brand_domain_suffix'])) {
        $e['compliance.brand_domain_suffix'] = 'Starts with a dot, e.g. .bet.br.';
    }
    return $e;
}

/** Changed fields between two versions of a market: ["compliance.age_notice.ru", ...]. */
function gen_market_changes(array $a, array $b, string $pre = ''): array
{
    $out = [];
    foreach (array_unique(array_merge(array_keys($a), array_keys($b))) as $k) {
        $x = $a[$k] ?? null;
        $y = $b[$k] ?? null;
        $path = $pre === '' ? (string)$k : "$pre.$k";
        if (is_array($x) && is_array($y) && !array_is_list($x) && !array_is_list($y)) {
            $out = array_merge($out, gen_market_changes($x, $y, $path));
        } elseif ($x !== $y) {
            $out[] = $path;
        }
    }
    return $out;
}

/** Writes a market file (atomic), its notes, the previous version to the history and a log line. */
function gen_market_write(string $id, ?array $data, ?string $notes, string $action): void
{
    global $GEN_ROOT;
    $file = gen_market_file($id);
    $old = is_file($file) ? sx_json($file) : null;
    $oldNotes = gen_market_notes($id);
    $stamp = date('Ymd-His') . '-' . substr(md5(uniqid('', true)), 0, 4);
    if ($old !== null) {
        file_put_contents(gen_market_history_dir($id) . "/$stamp.json", json_encode(['market' => $old, 'notes' => $oldNotes], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    if ($data === null) { // delete: the last version is kept in _deleted/ for a restore
        file_put_contents(gen_market_history_dir('_deleted') . "/$id-$stamp.json", json_encode(['market' => $old, 'notes' => $oldNotes], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        unlink($file);
        @unlink("$GEN_ROOT/ai/markets/$id.md");
        $changes = ['(market deleted)'];
    } else {
        $tmp = "$file." . getmypid() . '.tmp';
        file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        rename($tmp, $file);
        $changes = $old === null ? ['(new market)'] : gen_market_changes($old, $data);
        if ($notes !== null && $notes !== $oldNotes) {
            is_dir("$GEN_ROOT/ai/markets") || mkdir("$GEN_ROOT/ai/markets", 0777, true);
            file_put_contents("$GEN_ROOT/ai/markets/$id.md", $notes);
            $changes[] = 'ai_notes';
        }
    }
    file_put_contents(gen_market_history_dir() . '/log.jsonl', json_encode(['time' => date('c'), 'market' => $id, 'action' => $action,
        'version' => $old !== null ? $stamp : null, 'changes' => $changes], JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

/** The change log, newest first (one market, or all). */
function gen_market_log(?string $id = null): array
{
    $f = gen_market_history_dir() . '/log.jsonl';
    $out = [];
    foreach (is_file($f) ? file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [] as $line) {
        $x = json_decode($line, true);
        if ($x && ($id === null || $x['market'] === $id)) {
            $out[] = $x;
        }
    }
    return array_reverse($out);
}

/** A stored version: from a market's history, or a deleted market. */
function gen_market_version(string $id, string $version): ?array
{
    $f = gen_market_history_dir($id === '_deleted' ? '_deleted' : $id) . '/' . preg_replace('/[^A-Za-z0-9-]/', '', $version) . '.json';
    return is_file($f) ? json_decode(file_get_contents($f), true) : null;
}

/** Deleted markets that can be restored (newest copy of each id that does not exist again). */
function gen_market_deleted(): array
{
    $out = [];
    foreach (glob(gen_market_history_dir('_deleted') . '/*.json') as $f) {
        $v = basename($f, '.json');
        $id = preg_replace('/-\d{8}-\d{6}-[0-9a-f]{4}$/', '', $v);
        if (!is_file(gen_market_file($id)) && (!isset($out[$id]) || $out[$id]['version'] < $v)) {
            $m = json_decode(file_get_contents($f), true)['market'] ?? [];
            $out[$id] = ['id' => $id, 'name' => $m['name'] ?? $id, 'version' => $v];
        }
    }
    return array_values($out);
}

/** The /markets API. $seg: path segments after "markets". */
function gen_markets_api(PDO $db, array $seg, string $method): void
{
    $id = $seg[1] ?? '';
    $sub = $seg[2] ?? '';
    if ($id === '' && $method === 'GET') {
        $list = [];
        foreach (gen_market_files() as $mid => $m) {
            $u = gen_market_usage($db, $mid);
            $list[] = ['id' => $mid, 'name' => $m['name'] ?? $mid, 'demo_only' => !empty($m['demo_only']), 'locales' => $m['locales'] ?? [],
                'min_age' => $m['min_age'] ?? null, 'verticals' => $m['verticals'] ?? [], 'currency' => $m['currency']['code'] ?? '',
                'promos_allowed' => count(array_filter($m['promotions'] ?? [], fn($p) => $p === 'allowed')),
                'jobs' => count($u['jobs']), 'sites' => $u['sites'], 'demos' => count($u['demos']), 'changed' => date('Y-m-d H:i', filemtime(gen_market_file($mid)))];
        }
        gen_json(['markets' => $list, 'deleted' => gen_market_deleted(), 'log' => array_slice(gen_market_log(), 0, 30)]);
        return;
    }
    if ($id === '' && $method === 'POST') { // new market: a copy of another one, or a restored deleted one
        $in = gen_body();
        $new = strtolower(trim((string)($in['id'] ?? '')));
        if (!preg_match('/^[a-z]{2,8}$/', $new)) {
            gen_json(['error' => 'Use 2–8 lowercase Latin letters for the market id, e.g. "de".'], 422);
            return;
        }
        if (is_file(gen_market_file($new))) {
            gen_json(['error' => "Market '$new' already exists."], 409);
            return;
        }
        if (!empty($in['restore'])) {
            $v = gen_market_version('_deleted', (string)$in['restore']);
            if (!$v || ($v['market']['id'] ?? '') !== $new) {
                gen_json(['error' => 'That deleted market was not found.'], 404);
                return;
            }
            gen_market_write($new, $v['market'], $v['notes'] ?? '', 'restored');
            gen_json(['ok' => true, 'id' => $new]);
            return;
        }
        $from = (string)($in['from'] ?? '');
        if (!is_file(gen_market_file($from))) {
            gen_json(['error' => 'Choose a market to start from.'], 422);
            return;
        }
        $m = sx_json(gen_market_file($from));
        $m['id'] = $new;
        $m['name'] = trim((string)($in['name'] ?? '')) ?: strtoupper($new);
        unset($m['short_label'], $m['_purpose']);
        if (!empty($in['demo_only'])) {
            $m['demo_only'] = true;
        } else {
            unset($m['demo_only']);
        }
        gen_market_write($new, $m, gen_market_notes($from), "created from $from");
        gen_json(['ok' => true, 'id' => $new]);
        return;
    }
    if (!is_file(gen_market_file($id))) {
        gen_json(['error' => "No market '$id'."], 404);
        return;
    }
    if ($sub === '' && $method === 'GET') {
        require_once dirname(__DIR__) . "/lib/aibrief.php";
        $mk = sx_json(gen_market_file($id));
        gen_json(['market' => $mk, 'notes' => gen_market_notes($id), 'ai_facts' => sx_ai_market_facts($mk, $mk['default_locale'] ?? ($mk['locales'][0] ?? 'en')), 'usage' => gen_market_usage($db, $id),
            'stale' => gen_market_stale_sites($db, $id), 'log' => gen_market_log($id), 'languages' => gen_market_languages(),
            'payments' => gen_market_payments(), 'pages' => gen_market_pages(), 'effects' => gen_market_effects(),
            'options' => ['promotions' => GEN_MARKET_PROMOS, 'share' => GEN_MARKET_SHARE, 'required_blocks' => GEN_MARKET_REQUIRED_BLOCKS, 'texts' => GEN_MARKET_TEXTS],
            'changed' => date('Y-m-d H:i', filemtime(gen_market_file($id)))]);
        return;
    }
    if ($sub === '' && $method === 'PUT') {
        $in = gen_body();
        $m = $in['market'] ?? null;
        if (!is_array($m)) {
            gen_json(['error' => 'Nothing to save.'], 422);
            return;
        }
        $m['id'] = $id; // the id never changes: copy the market to get another id
        $old = sx_json(gen_market_file($id));
        foreach ($old as $k => $v) { // notes kept in the file (keys starting with _) stay unless replaced
            if ($k[0] === '_' && !array_key_exists($k, $m)) {
                $m[$k] = $v;
            }
        }
        if ($err = gen_market_validate($m, gen_market_pages())) {
            gen_json(['error' => 'Some fields need fixing.', 'fields' => $err], 422);
            return;
        }
        $notes = is_string($in['notes'] ?? null) ? $in['notes'] : null;
        if (!gen_market_changes($old, $m) && ($notes === null || $notes === gen_market_notes($id))) {
            gen_json(['ok' => true, 'changes' => []]); // nothing changed: no new version
            return;
        }
        gen_market_write($id, $m, $notes, 'edited');
        gen_json(['ok' => true, 'changes' => gen_market_log($id)[0]['changes'] ?? []]);
        return;
    }
    if ($sub === '' && $method === 'DELETE') {
        $u = gen_market_usage($db, $id);
        if ($u['jobs'] || $u['demos']) {
            gen_json(['error' => 'This market is in use: ' . implode(', ', array_filter([
                $u['jobs'] ? count($u['jobs']) . ' job(s)' : '', $u['demos'] ? 'demos ' . implode(', ', $u['demos']) : ''])) . '. Delete those first.'], 409);
            return;
        }
        gen_market_write($id, null, null, 'deleted');
        gen_json(['ok' => true]);
        return;
    }
    if ($sub === 'availability' && $method === 'GET') { // what can be built here, and why not (probe builds, cached)
        gen_json(['types' => gen_availability($id), 'labels' => array_map(fn($c) => $c['label'], gen_catalog())]);
        return;
    }
    if ($sub === 'version' && $method === 'GET') { // one stored version, to compare with the current one
        $v = gen_market_version($id, (string)($_GET['v'] ?? ''));
        $v ? gen_json($v + ['changes' => gen_market_changes($v['market'], sx_json(gen_market_file($id)))]) : gen_json(['error' => 'No such version.'], 404);
        return;
    }
    if ($sub === 'restore' && $method === 'POST') { // put an older version back (the current one goes to the history)
        $ver = (string)(gen_body()['version'] ?? '');
        $v = gen_market_version($id, $ver);
        if (!$v) {
            gen_json(['error' => 'No such version.'], 404);
            return;
        }
        gen_market_write($id, $v['market'], $v['notes'] ?? '', "restored version $ver");
        gen_json(['ok' => true]);
        return;
    }
    gen_json(['error' => 'Not found.'], 404);
}
