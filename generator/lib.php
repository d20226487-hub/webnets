<?php
/**
 * Website network generator: projects -> jobs -> sites, stored in SQLite (gen_db_file(): the generator-db volume in
 * Docker, else data/generator.sqlite).
 *
 * A job asks for N sites of one or more types in one market, for chosen brands, with chosen meaningful variations
 * (casino vs sport, iOS vs Android, ...). Each site is built from the type's switcher demo (sites/demos), with the
 * chosen and the automatic variation options merged in, its copy replaced by placeholders, its own seed, preset and
 * colours, and exported to dist/generated/<site>/ like a review link (developer panel, phone view, AI brief) but
 * without the variations bar. Fingerprints of each home page are kept to compare sites inside the job, the project
 * and every earlier job.
 */

$GEN_ROOT = dirname(__DIR__);
require_once "$GEN_ROOT/lib/page.php";
require_once __DIR__ . '/brands.php';
require_once __DIR__ . '/edits.php';
require_once __DIR__ . '/markets.php';
require_once __DIR__ . '/types.php';

const GEN_PLACEHOLDER = 'Placeholder copy for this section of the site to be written later with real content';

/**
 * Site types: the built-in ones (GEN_TYPES), with what the Site types page changed (each blueprint's "generator" entry:
 * label, hidden), and the copies made there (a blueprint with "based_on": a built-in type). A copy works like the type it
 * is based on (same source demo, axes and generator behaviour) with its own blueprint: sections, pages, rules, angles.
 */
function gen_catalog(): array
{
    global $GEN_ROOT;
    static $cache = [];
    $files = glob("$GEN_ROOT/blueprints/*.json");
    $sig = md5(implode('|', array_map(fn($f) => $f . filemtime($f), $files)));
    if (isset($cache[$sig])) {
        return $cache[$sig];
    }
    $out = [];
    $copies = [];
    foreach (GEN_TYPES as $t => $e) {
        $g = is_file("$GEN_ROOT/blueprints/$t.json") ? (sx_json("$GEN_ROOT/blueprints/$t.json")['generator'] ?? []) : [];
        $out[$t] = ['base' => $t] + array_merge($e, array_intersect_key($g, ['label' => 1, 'hidden' => 1]));
    }
    foreach ($files as $f) {
        $bp = sx_json($f);
        $t = basename($f, '.json');
        if (!isset($out[$t]) && isset($out[$bp['based_on'] ?? ''])) {
            $g = $bp['generator'] ?? [];
            $copies[$bp['based_on']][$t] = ['base' => $bp['based_on']] + array_merge($out[$bp['based_on']], ['label' => $g['label'] ?? $t, 'hidden' => !empty($g['hidden'])], ['base' => $bp['based_on']]);
        }
    }
    $all = [];
    foreach ($out as $t => $e) { // each copy right after the type it is based on
        $all[$t] = $e;
        foreach ($copies[$t] ?? [] as $ct => $ce) {
            $all[$ct] = $ce;
        }
    }
    return $cache[$sig] = $all;
}

/** The built-in type a type works like: itself, or for a copy the type it was copied from. */
function gen_type_base(string $type): string
{
    return gen_catalog()[$type]['base'] ?? $type;
}

/** Meaningful variations per built-in type: from which switcher demo, which axes the job form offers, which the generator picks. */
const GEN_TYPES = [
    'landing' => ['label' => 'Branded landing', 'source' => 'b-nyxa-xx', 'axes' => ['product' => 'Product', 'palette' => 'Colours'], 'auto' => ['length']],
    'app' => ['label' => 'App page (landing)', 'source' => 'app-b-nyxa-kz', 'axes' => ['platform' => 'Platform', 'palette' => 'Colours', 'look' => 'Design'], 'auto' => []],
    'app-multi' => ['label' => 'App site (multi-page)', 'source' => 'appm-b-nyxa-kz', 'axes' => ['platform' => 'Platform', 'palette' => 'Colours', 'look' => 'Design'], 'auto' => []],
    'promo' => ['label' => 'Bonuses and promo codes', 'source' => 'promo-b-nyxa-xx', 'axes' => ['offers' => 'Offers', 'palette' => 'Colours'], 'auto' => []],
    'vendor' => ['label' => 'Game provider overview', 'source' => 'vendor-b-nyxa-xx', 'axes' => ['studios' => 'Providers', 'look' => 'Colours'], 'auto' => []],
    'satellite' => ['label' => 'Multi-page satellite', 'source' => 'sat-b-nyxa-xx', 'axes' => ['product' => 'Product', 'palette' => 'Colours'], 'auto' => ['size', 'nav']],
    'casinos' => ['label' => 'Casino reviews (several brands)', 'source' => 'casinos-b-xx', 'axes' => ['product' => 'Product', 'layout' => 'Design', 'palette' => 'Colours'], 'auto' => ['rank']],
    'casinos-one' => ['label' => 'Casino reviews, one page (several brands)', 'source' => 'casinos1-b-xx', 'axes' => ['product' => 'Product', 'layout' => 'Design', 'palette' => 'Colours'], 'auto' => ['rank']],
    'news' => ['label' => 'Gambling news', 'source' => 'news-c-kz', 'axes' => ['topic' => 'News', 'pr' => 'Paid articles', 'palette' => 'Colours'], 'auto' => ['images']],
    'reviews' => ['label' => 'Brand reviews (user reviews)', 'source' => 'reviews-a-nyxa-kz', 'axes' => ['palette' => 'Colours'], 'auto' => ['images']],
];

/**
 * Source demo for a site: a switcher demo of the type written for this market (its copy, payments and figures fit),
 * when it has every option the site uses; else the type's default demo.
 */
function gen_source(string $type, string $market, array $choice): string
{
    global $GEN_ROOT;
    $default = gen_catalog()[$type]['source'];
    foreach (glob("$GEN_ROOT/sites/demos/*.json") as $f) {
        $d = sx_json($f);
        if (($d['type'] ?? '') !== $type || ($d['market'] ?? '') !== $market || empty($d['variations']) || basename($f, '.json') === $default) {
            continue;
        }
        $ok = true;
        $base = sx_json("$GEN_ROOT/sites/demos/$default.json")['variations'];
        foreach ($choice as $axis => $o) { // an axis the market demo lacks is fine at its first (default) option
            $missing = !isset($d['variations'][$axis]) ? $o !== array_key_first($base[$axis] ?? [$o => 1]) : !isset($d['variations'][$axis][$o]);
            if (isset($base[$axis]) && $missing) {
                $ok = false;
            }
        }
        if ($ok) {
            return basename($f, '.json');
        }
    }
    return $default;
}

/**
 * Axes the generator offers on types whose demo has no such switcher: Colours (the site style, neutral, or a palette
 * around the brand's colour: the job brand's on single-brand sites, the site's own on multi-brand ones).
 */
const GEN_GENERIC_AXES = ['palette' => ['preset' => [], 'neutral' => ['look' => 'neutral'], 'brand' => ['look' => 'brand']]];

/** A demo's switcher axes, plus the generic ones it does not have. */
function gen_demo_axes(array $demo): array
{
    return ($demo['variations'] ?? []) + GEN_GENERIC_AXES;
}

/** Raised when thumbnails were retaken without a rebuild, so browsers drop the cached ones (2: retaken after a kept attempt). */
const GEN_THUMB_REV = 2;

/** One line under an axis in the job form, where its options need a word of explanation. */
const GEN_AXIS_HINTS = [
    'app' => ['look' => 'The first screen. Google Play and App Store looks bring their own colours, so Colours does not change them.'],
    'app-multi' => ['look' => 'The first screen. Google Play and App Store looks bring their own colours, so Colours does not change them.'],
    'casinos' => ['layout' => 'The ranking on the first screen.', 'palette' => 'Brand colours: the colour of the site itself, as it lists several brands.'],
    'casinos-one' => ['layout' => 'The ranking on the first screen.', 'palette' => 'Brand colours: the colour of the site itself, as it lists several brands.'],
    'news' => ['palette' => 'Brand colours: the colour of the site itself, as it lists several brands.'],
];

/** The catalog with each axis's options and their labels, from the source demo. */
function gen_catalog_full(): array
{
    global $GEN_ROOT;
    $out = [];
    foreach (gen_catalog() as $type => $c) {
        $demo = sx_json("$GEN_ROOT/sites/demos/{$c['source']}.json");
        $labels = $demo['variation_labels'] ?? [];
        $lab = fn($k) => is_array($labels[$k] ?? null) ? ($labels[$k]['en'] ?? $labels[$k]['ru'] ?? reset($labels[$k])) : ($labels[$k] ?? $k);
        $axes = [];
        foreach ($c['axes'] as $axis => $name) {
            $opts = [];
            foreach (array_keys(gen_demo_axes($demo)[$axis] ?? []) as $o) {
                $opts[$o] = gen_option_label($axis, $o, $lab($o));
            }
            $axes[$axis] = ['label' => $name, 'options' => $opts] + (($h = GEN_AXIS_HINTS[gen_type_base($type)][$axis] ?? null) ? ['hint' => $h] : []);
        }
        $out[$type] = ['label' => $c['label'], 'axes' => $axes, 'auto' => $c['auto'], 'base' => $c['base'], 'hidden' => !empty($c['hidden'])];
    }
    return $out;
}

/** English labels for the job form (the demos label their switchers in Russian). */
function gen_option_label(string $axis, string $o, string $fallback): string
{
    $map = [
        'product' => ['both' => 'Casino + sport', 'casino' => 'Casino', 'sports' => 'Sport', 'live' => 'Live betting'],
        'platform' => ['both' => 'iOS + Android', 'ios' => 'iOS', 'android' => 'Android'],
        'palette' => ['preset' => 'Random style', 'neutral' => 'Neutral', 'brand' => 'Brand colours'],
        'look' => ['preset' => 'Random style', 'neutral' => 'Neutral', 'brand' => 'Brand colours', 'provider' => 'Provider colours', 'auto' => 'Any (each design in turn)',
            'catalog' => 'Download catalog (like uptodown)', 'apk-page' => 'APK page (like apkpure)', 'hub' => 'Download hub (like softonic)', 'play' => 'Google Play look', 'appstore' => 'App Store look'],
        'layout' => ['auto' => 'Any (each design in turn)', 'table' => 'Comparison table', 'cards' => 'Cards', 'podium' => 'Podium', 'logo-grid' => 'Logo grid (like casino.ru)',
            'offer-cards' => 'Offer cards (like liga.net)', 'portal' => 'Portal (like affgambler.ru)', 'claim-cards' => 'Bonus cards (like slotcatalog)'],
        'offers' => ['mixed' => 'Banners + promo codes', 'banners' => 'Bonus banners', 'codes' => 'Promo codes'],
        'studios' => ['one' => 'One provider', 'several' => 'Several providers'],
        'topic' => ['general' => 'General iGaming news', 'brand' => 'Brand news'],
        'pr' => ['block' => 'As a separate block', 'feed' => 'In the news feed'],
    ];
    return $map[$axis][$o] ?? $fallback;
}

/* ---------------------------------------------------------------- database */

function gen_db(): PDO
{
    global $GEN_ROOT;
    static $db = null;
    if ($db) {
        return $db;
    }
    @mkdir("$GEN_ROOT/data", 0777, true);
    $file = gen_db_file();
    is_dir(dirname($file)) || mkdir(dirname($file), 0777, true);
    $db = new PDO('sqlite:' . $file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_TIMEOUT, 15);
    $db->exec('PRAGMA busy_timeout = 15000');
    // WAL lets the page read while the worker writes, but needs shared memory, which a host folder mounted into Docker
    // does not give reliably: on Docker's own disk (SX_GEN_DB) WAL, in data/ the rollback journal
    $mode = getenv('SX_GEN_DB') ? 'wal' : 'delete';
    if ($db->query('PRAGMA journal_mode')->fetchColumn() !== $mode) {
        $db->exec("PRAGMA journal_mode = $mode");
    }
    $db->exec('PRAGMA foreign_keys = ON');
    $db->exec('CREATE TABLE IF NOT EXISTS projects (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, created TEXT NOT NULL)');
    $db->exec('CREATE TABLE IF NOT EXISTS jobs (id INTEGER PRIMARY KEY AUTOINCREMENT, project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
        name TEXT NOT NULL, market TEXT NOT NULL, spec TEXT NOT NULL, status TEXT NOT NULL, log TEXT DEFAULT \'\', created TEXT NOT NULL)');
    $db->exec('CREATE TABLE IF NOT EXISTS sites (id TEXT PRIMARY KEY, job_id INTEGER NOT NULL REFERENCES jobs(id) ON DELETE CASCADE,
        n INTEGER NOT NULL, type TEXT NOT NULL, brand TEXT, choice TEXT NOT NULL, plan TEXT NOT NULL, status TEXT NOT NULL,
        warnings TEXT DEFAULT \'[]\', error TEXT, pages INTEGER DEFAULT 0, mobile TEXT, checks TEXT, created TEXT NOT NULL, fingerprint TEXT, visual TEXT)');
    $db->exec('CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT NOT NULL)');
    if (!in_array('compare', array_column($db->query('PRAGMA table_info(jobs)')->fetchAll(), 'name'), true)) { // 0 = a draft: other jobs are not compared with it
        $db->exec('ALTER TABLE jobs ADD COLUMN compare INTEGER NOT NULL DEFAULT 1');
    }
    if (!in_array('queued_at', array_column($db->query('PRAGMA table_info(jobs)')->fetchAll(), 'name'), true)) { // when the job joined the worker queue
        $db->exec('ALTER TABLE jobs ADD COLUMN queued_at TEXT');
    }
    $cols = array_column($db->query('PRAGMA table_info(sites)')->fetchAll(), 'name');
    if (!in_array('checks', $cols, true) || array_search('fingerprint', $cols, true) < array_search('checks', $cols, true)) {
        // the code fingerprint and the screenshot print are large: they go last in each row, so reading the other
        // columns (the job page, the planner) does not read through them
        $db->exec('PRAGMA foreign_keys = OFF');
        $db->exec('BEGIN IMMEDIATE'); // one process migrates; a second one waits here and then finds it done
        $cols = array_column($db->query('PRAGMA table_info(sites)')->fetchAll(), 'name');
        if (in_array('checks', $cols, true) && array_search('fingerprint', $cols, true) > array_search('checks', $cols, true)) {
            $db->exec('COMMIT');
            $db->exec('PRAGMA foreign_keys = ON');
            return $db;
        }
        $db->exec('CREATE TABLE sites_new (id TEXT PRIMARY KEY, job_id INTEGER NOT NULL REFERENCES jobs(id) ON DELETE CASCADE,
            n INTEGER NOT NULL, type TEXT NOT NULL, brand TEXT, choice TEXT NOT NULL, plan TEXT NOT NULL, status TEXT NOT NULL,
            warnings TEXT DEFAULT \'[]\', error TEXT, pages INTEGER DEFAULT 0, mobile TEXT, checks TEXT, created TEXT NOT NULL, fingerprint TEXT, visual TEXT)');
        $has = fn($c) => in_array($c, $cols, true) ? $c : 'NULL';
        $db->exec('INSERT INTO sites_new (id, job_id, n, type, brand, choice, plan, status, warnings, error, pages, mobile, checks, created, fingerprint, visual)
            SELECT id, job_id, n, type, brand, choice, plan, status, warnings, error, pages, mobile, ' . $has('checks') . ', created, fingerprint, ' . $has('visual') . ' FROM sites');
        $db->exec('DROP TABLE sites');
        $db->exec('ALTER TABLE sites_new RENAME TO sites');
        $db->exec('COMMIT');
        $db->exec('PRAGMA foreign_keys = ON');
    }
    return $db;
}

/** Difference thresholds and how far the generator goes to meet them. */
const GEN_SETTINGS = ['code_min' => 50, 'visual_min' => 25, 'scope' => 'all', 'rebuilds' => 2, 'visual' => true, 'variants' => true];

/** The settings, plus 'attempts' (builds per check: the first build and the rebuilds) for the worker. */
function gen_settings(): array
{
    $out = GEN_SETTINGS;
    $stored = array_column(gen_db()->query('SELECT key, value FROM settings')->fetchAll(), 'value', 'key');
    if (!isset($stored['rebuilds']) && isset($stored['attempts'])) { // stored before "rebuilds": attempts counted the first build
        $stored['rebuilds'] = json_encode(max(0, (int)json_decode($stored['attempts']) - 1));
    }
    foreach ($stored as $k => $v) {
        if (array_key_exists($k, $out)) {
            $out[$k] = json_decode($v, true);
        }
    }
    return $out + ['attempts' => $out['rebuilds'] + 1];
}

/** Store settings from the settings form; unknown keys are ignored, values are clamped. Returns the stored settings. */
function gen_save_settings(array $in): array
{
    $clean = [
        'code_min' => max(0, min(100, (int)($in['code_min'] ?? GEN_SETTINGS['code_min']))),
        'visual_min' => max(0, min(100, (int)($in['visual_min'] ?? GEN_SETTINGS['visual_min']))),
        'scope' => in_array($in['scope'] ?? '', ['job', 'project', 'all'], true) ? $in['scope'] : GEN_SETTINGS['scope'],
        'rebuilds' => max(0, min(5, (int)($in['rebuilds'] ?? GEN_SETTINGS['rebuilds']))),
        'visual' => !empty($in['visual']),
        'variants' => !empty($in['variants']),
    ];
    $st = gen_db()->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
    foreach ($clean as $k => $v) {
        $st->execute([$k, json_encode($v)]);
    }
    gen_db()->exec("DELETE FROM settings WHERE key = 'attempts'");
    return gen_settings();
}

/** The database file: SX_GEN_DB (Docker: the generator-db volume, on Docker's own disk), else data/generator.sqlite. */
function gen_db_file(): string
{
    global $GEN_ROOT;
    return getenv('SX_GEN_DB') ?: "$GEN_ROOT/data/generator.sqlite";
}

/** Folder for caches (market checks, difference tables): SX_GEN_CACHE (Docker: on the volume), else data/. */
function gen_cache_dir(string $sub = ''): string
{
    global $GEN_ROOT;
    $d = rtrim(getenv('SX_GEN_CACHE') ?: "$GEN_ROOT/data", '/') . ($sub !== '' ? "/$sub" : '');
    is_dir($d) || @mkdir($d, 0777, true);
    return $d;
}

function gen_now(): string
{
    return gmdate('Y-m-d H:i:s');
}

/* ---------------------------------------------------------------- availability */

/**
 * What the market's rules say about each type and option, as warnings for the job form. Every type is tried with
 * placeholder probe brands and the market's blocks enforced; nothing here stops the user from building it anyway
 * (generated sites build with "override_blocks", which turns blocks into warnings on the site).
 */
function gen_availability(string $market): array
{
    global $GEN_ROOT;
    $cache = gen_cache_dir() . "/availability-$market.json";
    $sig = gen_availability_sig($market);
    $read = function () use ($cache, $sig) {
        $c = is_file($cache) ? json_decode((string)file_get_contents($cache), true) : null;
        return ($c['sig'] ?? '') === $sig ? $c['types'] : null;
    };
    if (($types = $read()) !== null) {
        return $types;
    }
    $lock = fopen("$cache.lock", 'c');
    flock($lock, LOCK_EX);
    try {
        if (($types = $read()) !== null) {
            return $types;
        }
        $probe = gen_probe_brands($market);
        $runs = [];
        foreach (gen_catalog() as $type => $cat) {
            $runs[] = [$type, '', ''];
            $demo = sx_json("$GEN_ROOT/sites/demos/{$cat['source']}.json");
            foreach ($cat['axes'] as $axis => $_) {
                foreach (array_keys(gen_demo_axes($demo)[$axis] ?? []) as $o) {
                    $runs[] = [$type, $axis, (string)$o];
                }
            }
        }
        $procs = [];
        foreach ($runs as $k => [$type, $axis, $o]) {
            $procs[$k] = [proc_open([PHP_BINARY, __DIR__ . '/probe.php', $market, $type, implode(',', $probe), $axis, $o], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes), $pipes];
        }
        $res = [];
        foreach ($procs as $k => [$p, $pipes]) {
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($p);
            $r = json_decode($out, true);
            if (!is_array($r)) {
                throw new RuntimeException("Probe of '{$runs[$k][0]}' in '$market' failed: " . trim($err ?: $out));
            }
            $res[$k] = $r['error'];
        }
        $types = [];
        foreach ($runs as $k => [$type, $axis, $o]) {
            if ($axis === '') {
                $base = $res[$k];
                $types[$type] = ['ok' => $base === null, 'reason' => $base, 'options' => [], 'clean' => []];
            } elseif ($res[$k] !== null && $res[$k] !== $base) {
                $types[$type]['options'][$axis][$o] = $res[$k];
            } elseif ($res[$k] === null && $base !== null) {
                $types[$type]['clean'][] = $o;
            }
        }
        @file_put_contents($cache, json_encode(['sig' => $sig, 'types' => $types]));
        return $types;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/** What a market's cached availability depends on: brand data, the engine, blueprints, the market and the generator. */
function gen_availability_sig(string $market): string
{
    global $GEN_ROOT;
    return md5(json_encode(array_map(fn($f) => filemtime($f), array_merge(glob("$GEN_ROOT/brands/*.json"), glob("$GEN_ROOT/brands/*/*.json"),
        glob("$GEN_ROOT/lib/*.php"), glob("$GEN_ROOT/blueprints/*.json"), ["$GEN_ROOT/markets/$market.json", __FILE__, __DIR__ . '/brands.php', __DIR__ . '/probe.php']))));
}

/** Every market whose cached availability is out of date. */
function gen_stale_markets(): array
{
    global $GEN_ROOT;
    $stale = [];
    foreach (glob("$GEN_ROOT/markets/*.json") as $f) {
        $m = basename($f, '.json');
        $c = @json_decode((string)@file_get_contents(gen_cache_dir() . "/availability-$m.json"), true);
        if (($c['sig'] ?? '') !== gen_availability_sig($m)) {
            $stale[] = $m;
        }
    }
    return $stale;
}

/** Work out availability for every out-of-date market in the background, so the job form opens at once. */
function gen_warm_availability(): void
{
    global $GEN_ROOT;
    if (!gen_stale_markets()) {
        return;
    }
    @mkdir("$GEN_ROOT/data/logs", 0777, true);
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/probe.php') . ' warm >> ' . escapeshellarg("$GEN_ROOT/data/logs/availability.log") . ' 2>&1';
    if (PHP_OS_FAMILY === 'Windows') {
        pclose(popen('start "" /B cmd /c "' . $cmd . '"', 'r'));
    } else {
        exec($cmd . ' &');
    }
}

/** Build a site in memory; null when it builds, else the reason. PHP notices count as failures (they would break pages). */
function gen_try_build(string $type, string $market, ?string $brand, array $choice, array $brands = [], string $id = 'probe-00'): ?string
{
    global $GEN_ROOT;
    set_error_handler(function ($no, $msg, $file, $line) {
        throw new ErrorException($msg . ' (' . basename($file) . ":$line)", 0, $no, $file, $line);
    });
    ob_start();
    try {
        sx_build_site(['override_blocks' => false, 'probe_blocks' => true] + gen_site_config($type, $market, $brand, $choice, $id, 'probe-' . $market, null, [], $brands), $GEN_ROOT);
        return null;
    } catch (Throwable $e) {
        return $e->getMessage();
    } finally {
        ob_end_clean();
        restore_error_handler();
    }
}

/* ---------------------------------------------------------------- site config */

function gen_merge(array $a, array $b): array
{
    foreach ($b as $k => $v) {
        $a[$k] = is_array($v) && isset($a[$k]) && is_array($a[$k]) && !array_is_list($v) ? gen_merge($a[$k], $v) : $v;
    }
    return $a;
}

/**
 * The full config of one generated site.
 * $choice: [axis => option] for the meaningful and the automatic axes. $plan: seed, preset, colours.
 * $brand: the user brand of a single-brand site; $brands: all the job's user brands (listed by comparison and news sites).
 */
function gen_site_config(string $type, string $market, ?string $brand, array $choice, string $id, string $seed, ?string $preset, array $colors, array $brands = [], ?int $slot = null, string $copy = 'demo', int $level = 0, ?int $palette = null, ?string $nav = null, array $style = [], array $menu = []): array
{
    global $GEN_ROOT;
    $cfg = sx_json("$GEN_ROOT/sites/demos/" . gen_source($type, $market, $choice) . '.json');
    $axes = gen_demo_axes($cfg);
    if (!isset($choice['layout']) && isset($choice['design'])) { // sites planned when casino Design was an axis of its own
        $choice['layout'] = $choice['design'];
    }
    unset($cfg['variations'], $cfg['variation_labels'], $cfg['_demo']);
    $cfg['type'] = $type; // a copied type builds from its own blueprint, with the source demo of the type it is based on
    // Below the first screen the engine picks the order and the designs by its rules (no two conversion blocks or tinted
    // bands in a row, one pattern per site: lib/blueprint.php), so the demo's hand-picked order and section designs are
    // dropped; the header, footer and other frame pins stay, and the chosen options add the pins they need.
    unset($cfg['order']);
    foreach ($cfg['variants'] ?? [] as $k => $v) {
        if (is_array($v) || !in_array($k, ['header', 'footer', 'sticky-cta', 'age-gate', 'compliance-bar', 'disclosure', 'compliance'], true)) {
            unset($cfg['variants'][$k]);
        }
    }
    if ($level >= 2) { // re-planned to differ more: the frame's layouts too
        unset($cfg['variants']);
    }
    foreach ($axes as $axis => $opts) { // same merge as build/export-review-site.php, incl. cross-axis "_with"
        $o = $choice[$axis] ?? array_key_first($opts);
        $cfg = gen_merge($cfg, array_diff_key($opts[$o] ?? [], ['_with' => 1]));
    }
    foreach ($axes as $axis => $opts) {
        $o = $choice[$axis] ?? array_key_first($opts);
        foreach ($opts[$o]['_with'] ?? [] as $other => $byOpt) {
            $oo = $choice[$other] ?? array_key_first($axes[$other] ?? []);
            if (isset($byOpt[$oo])) {
                $cfg = gen_merge($cfg, $byOpt[$oo]);
            }
        }
    }
    // the demo pins one first-screen layout (and its options pin others): instead, sites of a type take turns through
    // every layout of the first-screen block (the market allowing), times implementation A / B, so they do not open the
    // same way. A pin outside the angle's list is kept (pins_over_angle).
    $cfg['pins_over_angle'] = true;
    $want = $cfg['first_screen'] ?? null; // a first-screen design the user chose in the form (Design)
    unset($cfg['first_screen']);
    if ($fs = gen_first_screen($type)) {
        unset($cfg['variants'][$fs['id']]);
        foreach ($cfg['variants'] ?? [] as $k => $v) {
            if (is_array($v)) {
                unset($cfg['variants'][$k][$fs['id']]);
            }
        }
    }
    if ($combos = gen_hero_combos($type, $cfg, $market)) {
        $combos = array_values(array_filter($combos, fn($cb) => $cb[0] === $want)) ?: $combos;
        [$hv, $hi, $multi] = $combos[($slot ?? crc32($id)) % count($combos)];
        if ($multi) {
            $cfg['variants']['home'][$fs['id']] = $hv;
        } else {
            $cfg['variants'][$fs['id']] = $hv;
        }
        $cfg['impl'][$combos[0][3]] = $hi;
    }
    $m = sx_json("$GEN_ROOT/markets/$market.json");
    $cfg['id'] = $id;
    $cfg['seed'] = $seed;
    $cfg['prefix'] = 'auto';
    $cfg['market'] = $market;
    $cfg['demo'] = true; // generated copy is placeholder copy; seeded reviews and sample articles are allowed
    $cfg['override_blocks'] = true; // the user decides: market blocks become warnings on the site, never a refusal
    unset($cfg['slugs']); // the blueprint's slugs for the site's language apply
    if ($brand && isset($cfg['brand'])) {
        $cfg['brand'] = $brand;
    }
    if ($brands && isset($cfg['brands'])) { // comparison sites list the job's brands, one of them featured
        $cfg['brands'] = $brands;
        $cfg['featured'] = $brands[crc32($id) % count($brands)];
        $cfg['links']['brands'] = array_fill_keys($brands, 'x');
    }
    $demoLocale = $cfg['locale'] ?? null;
    $cfg['locale'] = in_array($cfg['locale'] ?? '', $m['locales'], true) ? $cfg['locale'] : $m['default_locale'];
    // demo copy when the demo is written in the site's language (brand names swapped for the user's), else placeholders
    $useDemo = $copy === 'demo' && $demoLocale === $cfg['locale'];
    $names = gen_name_map($useDemo ? ($brand && isset($cfg['brand']) ? [$brand] : $brands) : []);
    if ($useDemo) { // payment methods named in the demo copy: the market's own (Apple Pay -> Kaspi)
        $names += gen_payment_map($m)['names'];
    }
    if (!empty($cfg['articles'])) { // news: the demo's articles, about the job's brands
        $cfg['articles'] = gen_site_articles($cfg['articles'], $id, $brands, $useDemo ? $names : null);
        if ($brands) {
            $cfg['links']['brands'] = array_fill_keys($brands, 'x');
        }
    }
    if (isset($cfg['publisher'])) { // comparison and news sites: a publisher registered for this market, varied per site
        $pubs = array_filter(sx_json("$GEN_ROOT/brands/publishers.json"), fn($p, $k) => $k !== '_note' && in_array($market, $p['markets'] ?? [], true), ARRAY_FILTER_USE_BOTH);
        if ($pubs && !isset($pubs[$cfg['publisher']])) {
            $keys = array_keys($pubs);
            $cfg['publisher'] = $keys[crc32($id) % count($keys)];
        } elseif ($pubs && count($pubs) > 1) {
            $keys = array_keys($pubs);
            $cfg['publisher'] = $keys[crc32($id) % count($keys)];
        }
    }
    $ops = sx_brand_data($GEN_ROOT, 'operators.json');
    $tld = $m['compliance']['brand_domain_suffix'] ?? '.example'; // e.g. Brazil: brand links must be on .bet.br
    $host = fn($b) => $ops[$b]['domain'] ?? ($b . (str_starts_with($tld, '.') ? $tld : ".$tld"));
    if (in_array(gen_type_base($type), ['app', 'app-multi', 'satellite'], true)) { // store links where the source demo installs by APK
        $cfg['links'] += ['ios' => 'x', 'android' => 'x'];
    }
    foreach ($cfg['links'] ?? [] as $k => $url) { // every tracked link (go, ios, android, apk...) on the brand's own domain
        if (is_string($url)) {
            $cfg['links'][$k] = 'https://' . $host($brand ?: ($cfg['brand'] ?? 'site')) . '/' . ($k === 'go' ? 'join' : "app/$k") . "?src=$id";
        }
    }
    foreach (array_keys($cfg['links']['brands'] ?? []) as $b) {
        $cfg['links']['brands'][$b] = 'https://' . $host($b) . "/join?src=$id";
    }
    $cfg['site']['name'] = gen_site_name($type, $brand && isset($cfg['brand']) ? ($ops[$brand]['name'] ?? $brand) : ($cfg['publisher'] ?? ''), $id);
    $cfg['site']['url'] = "https://$id.example";
    if ($preset) {
        $cfg['preset'] = $preset;
        unset($cfg['tokens']);
    }
    // phone menu and desktop sidebar look (the plan's, else derived from the site id, so older plans vary too)
    $h = crc32($id . '|menu');
    $cfg['menu'] = ['side' => $menu['side'] ?? (['left', 'right'][$h % 2]), 'style' => $menu['style'] ?? GEN_MENU_STYLES[intdiv($h, 2) % 4],
        'skip' => (bool)($menu['skip'] ?? (!gen_multi_page($type) && intdiv($h, 8) % 3 === 0))];
    $cfg['sidebar_look'] = $menu['look'] ?? GEN_SIDE_LOOKS[intdiv($h, 24) % 3];
    if (gen_multi_page($type)) { // multi-page sites always have their menu
        $cfg['menu']['skip'] = false;
    }
    unset($cfg['style']);
    if (!empty($style['hue']) || !empty($style['flip'])) { // a variant of the preset: rotated colours and / or light and dark swapped
        $cfg['style'] = ['hue' => (int)($style['hue'] ?? 0), 'flip' => !empty($style['flip'])];
    }
    $tok = [];
    if (!empty($colors['primary'])) {
        $tok['color']['accent'] = $colors['primary'];
    }
    if (!empty($colors['secondary'])) {
        $tok['color']['accent2'] = $colors['secondary'];
    }
    if (!empty($colors['accent'])) {
        $tok['decor']['heroBg'] = $colors['accent'];
    }
    if ($nav !== null && gen_navs($type)) { // multi-page: top menu, or the page list in a sidebar on the left or the right
        if (isset($axes['nav'])) { // satellites choose top / sidebar / hub by their own variation; the plan picks the sidebar's side
            if (($cfg['nav_style'] ?? 'top') === 'sidebar') {
                $cfg['nav_side'] = $nav === 'right' ? 'right' : 'left';
            }
        } elseif ($nav === 'top') {
            unset($cfg['nav_style'], $cfg['nav_side']);
        } else {
            $cfg['nav_style'] = 'sidebar';
            $cfg['nav_side'] = $nav === 'right' ? 'right' : 'left';
        }
    }
    if (in_array($cfg['look'] ?? '', ['brand', 'provider'], true)) { // a palette per site around the brand's colour, so brand-coloured sites differ
        $cfg['look_variant'] = ($palette ?? $slot ?? crc32($id)) % 5;
    }
    if ($tok) {
        $cfg['tokens'] = gen_merge($cfg['tokens'] ?? [], $tok);
    }
    if (isset($cfg['content'])) { // payment rows only list the market's own methods, or the table renders empty
        $cfg['content'] = gen_fit_payments($cfg['content'], $m, $cfg['locale'], $useDemo);
    }
    foreach (['content', 'meta', 'page', 'editor', 'cta_label'] as $k) {
        if (isset($cfg[$k])) {
            $cfg[$k] = $useDemo ? gen_swap_names($cfg[$k], $names) : gen_placeholders($cfg[$k], $k, $k);
        }
    }
    return $cfg;
}

/** First-screen layouts a type never takes (they do not fit it); filled in from test builds. */
const GEN_HERO_SKIP = [];

/**
 * A type's first-screen section on its (home) page: "hero", else the first top-zone section that is not an offers
 * strip, trust bar or notice (e.g. the rating summary of review sites, the lead stories of news sites).
 * Returns the blueprint section with "multi", or null.
 */
function gen_first_screen(string $type): ?array
{
    global $GEN_ROOT;
    static $c = [];
    if (!array_key_exists($type, $c)) {
        $bp = sx_json("$GEN_ROOT/blueprints/$type.json");
        $multi = isset($bp['pages']);
        $secs = $multi ? ($bp['pages']['home']['sections'] ?? []) : ($bp['sections'] ?? []);
        $hero = null;
        foreach ($secs as $s) {
            if ($s['id'] === 'hero') {
                $hero = $s;
                break;
            }
        }
        foreach ($hero ? [] : $secs as $s) {
            if ($s['zone'] === 'top' && !in_array($s['block'], ['promo-list', 'trust-bar', 'compliance-bar', 'disclosure'], true)) {
                $hero = $s;
                break;
            }
        }
        $c[$type] = $hero && empty($hero['variant']) ? $hero + ['multi' => $multi] : null;
    }
    return $c[$type];
}

/** First-screen layouts a site can take: [variant, implementation, multi-page, block] for every layout of the block. */
function gen_hero_combos(string $type, array $cfg, string $market): array
{
    global $GEN_ROOT;
    $hero = gen_first_screen($type);
    if (!$hero) {
        return [];
    }
    $multi = $hero['multi'];
    $sx = sx_json("$GEN_ROOT/blocks/{$hero['block']}/block.json")['sx'] ?? [];
    $verticals = sx_json("$GEN_ROOT/markets/$market.json")['verticals'];
    $list = $hero['variants'] ?? ($sx['variants'] ?? []); // a list on the blueprint's section itself is a hard limit
    $list = array_values(array_filter($list, fn($v) => in_array($v, $sx['variants'] ?? [], true) && !in_array($v, GEN_HERO_SKIP[gen_type_base($type)] ?? [], true)
        && (!isset($sx['variant_requires'][$v]) || !empty($verticals[$sx['variant_requires'][$v]]))));
    $out = [];
    foreach (['a', 'b'] as $impl) {
        foreach ($list as $v) {
            if ($impl === 'a' || is_file("$GEN_ROOT/blocks/{$hero['block']}/b/variants/$v.php")) {
                $out[] = [$v, $impl, $multi, $hero['block']];
            }
        }
    }
    return $out;
}

function gen_site_name(string $type, string $brand, string $id): string
{
    $n = (int)substr($id, strrpos($id, '-') + 1);
    $base = ucfirst($brand ?: 'Site');
    return trim($base . ' ' . ['landing' => 'Hub', 'app' => 'App', 'app-multi' => 'App Guide', 'promo' => 'Bonus', 'vendor' => 'Games',
        'satellite' => 'Guide', 'casinos' => 'Casino Ranking', 'casinos-one' => 'Casino Top', 'news' => 'News', 'reviews' => 'Reviews'][gen_type_base($type)] . ' ' . ($n ?: ''));
}

/**
 * Demo brand names (and their ids in domains and slugs) -> the user's brands. One user brand takes every demo
 * brand's name; several take them in turn, in the demo brands' order.
 */
function gen_name_map(array $brandIds): array
{
    global $GEN_ROOT;
    if (!$brandIds) {
        return [];
    }
    $ops = sx_brand_data($GEN_ROOT, 'operators.json');
    $map = [];
    $k = 0;
    foreach (sx_json("$GEN_ROOT/brands/operators.json") as $id => $o) {
        if ($id === '_note') {
            continue;
        }
        $u = $brandIds[$k++ % count($brandIds)];
        $name = $ops[$u]['name'] ?? $u;
        $map[$o['name']] = $name;
        $map[$id] = gen_slug($name);
    }
    return $map;
}

/** Swap demo brand names in every copy string (whole words only), keep settings. */
/**
 * Payment methods for a market: every method another market (or the demo data) uses, mapped to one this market has.
 * The same card where the market has it (Visa Debit -> Visa), a card for "debit", else the market's first non-card
 * method (Apple Pay, PIX or PayID -> Kaspi). Returns ['ids' => [from => to], 'names' => [from name => to name]]; the
 * names map leaves generic words (bank transfer, debit) alone, as the copy around them reads fine either way.
 */
function gen_payment_map(array $m): array
{
    global $GEN_ROOT;
    static $all = null;
    if ($all === null) {
        $all = ['apple-pay', 'visa-debit', 'mastercard-debit'];
        foreach (glob("$GEN_ROOT/markets/*.json") as $f) {
            $all = array_merge($all, sx_json($f)['payments'] ?? []);
        }
        $all = array_values(array_unique($all));
    }
    $own = $m['payments'] ?? [];
    if (!$own) {
        return ['ids' => [], 'names' => []];
    }
    $card = fn(string $p) => (bool)preg_match('/^(visa|mastercard)(-debit)?$|^debit$/', $p);
    $cards = array_values(array_filter($own, $card));
    $other = array_values(array_filter($own, fn($p) => !$card($p)));
    $ids = $names = [];
    foreach ($all as $p) {
        if (in_array($p, $own, true)) {
            continue;
        }
        $to = match (true) {
            in_array(preg_replace('/-debit$/', '', $p), $own, true) => preg_replace('/-debit$/', '', $p),
            in_array("$p-debit", $own, true) => "$p-debit",
            $card($p) && (bool)$cards => $cards[0],
            default => $other[0] ?? $own[0],
        };
        $ids[$p] = $to;
        if (!in_array($p, ['bank', 'debit'], true) && !in_array($to, ['bank', 'debit'], true) && !str_starts_with(sx_payment_name($to), sx_payment_name($p))) { // "Visa" -> "Visa Debit" would double up an existing "Visa Debit"
            $names[sx_payment_name($p)] = sx_payment_name($to);
        }
    }
    uksort($names, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a)); // "Visa Debit" before "Visa"
    return ['ids' => $ids, 'names' => $names];
}

/** Brand facts with the market's own payment methods: payments and withdrawal times keyed by gen_payment_map() ids. */
function gen_fit_fact_payments(array $facts, array $map): array
{
    if (isset($facts['payments']) && is_array($facts['payments'])) {
        $facts['payments'] = array_values(array_unique(array_map(fn($p) => $map[$p] ?? $p, $facts['payments'])));
    }
    if (isset($facts['withdrawal_minutes']) && is_array($facts['withdrawal_minutes'])) {
        $w = [];
        foreach ($facts['withdrawal_minutes'] as $p => $min) {
            $to = $map[$p] ?? $p;
            $w[$to] = isset($w[$to]) ? min($w[$to], $min) : $min;
        }
        $facts['withdrawal_minutes'] = $w;
    }
    return $facts;
}

function gen_swap_names($v, array $map)
{
    if (is_array($v)) {
        return array_map(fn($x) => gen_swap_names($x, $map), $v);
    }
    if (!is_string($v) || !$map) {
        return $v;
    }
    foreach ($map as $from => $to) {
        $v = preg_replace('/(?<![\p{L}\d])' . preg_quote($from, '/') . '(?![\p{L}\d])/u', $to, $v);
    }
    return $v;
}

/** Payment sections ({rows: [{method, ...}]}) in any demo for this market, best first: same language, then any. */
function gen_market_payment_sections(string $market, string $locale): array
{
    global $GEN_ROOT;
    static $cache = [];
    if (isset($cache["$market|$locale"])) {
        return $cache["$market|$locale"];
    }
    $allowed = sx_json("$GEN_ROOT/markets/$market.json")['payments'] ?? [];
    $found = [[], []];
    $walk = function ($v, bool $same) use (&$walk, &$found, $allowed) {
        if (!is_array($v)) {
            return;
        }
        if (isset($v['rows']) && is_array($v['rows']) && $v['rows'] && isset($v['rows'][0]['method'])
            && !array_diff(array_column($v['rows'], 'method'), $allowed)) {
            $found[$same ? 0 : 1][] = ['node' => $v, 'same' => $same];
            return;
        }
        foreach ($v as $x) {
            $walk($x, $same);
        }
    };
    foreach (glob("$GEN_ROOT/sites/demos/*.json") as $f) {
        $d = sx_json($f);
        if (($d['market'] ?? '') === $market) {
            $walk($d['content'] ?? [], ($d['locale'] ?? sx_json("$GEN_ROOT/markets/$market.json")['default_locale']) === $locale);
        }
    }
    return $cache["$market|$locale"] = array_merge($found[0], $found[1]);
}

/**
 * Payment sections whose methods the market does not have: take a payment section written for this market (its copy
 * too, when it is in the site's language and demo copy is on), else rows for the market's own methods with the
 * demo's figures.
 */
function gen_fit_payments(array $content, array $m, string $locale, bool $useDemo)
{
    $fix = function ($v) use (&$fix, $m, $locale, $useDemo) {
        if (!is_array($v)) {
            return $v;
        }
        if (isset($v['rows']) && is_array($v['rows']) && isset($v['rows'][0]['method'])) {
            $allowed = $m['payments'] ?? [];
            if (!array_diff(array_column($v['rows'], 'method'), $allowed)) {
                return $v;
            }
            $donors = gen_market_payment_sections($m['id'], $locale);
            if ($donors) {
                $d = $donors[0]; // same language first
                return $useDemo && $d['same'] ? $d['node'] + $v : ['rows' => $d['node']['rows']] + $v;
            }
            $rows = [];
            foreach ($allowed as $i => $method) {
                $rows[] = ['method' => $method] + array_diff_key($v['rows'][$i % count($v['rows'])], ['method' => 1, 'label' => 1]);
            }
            return ['rows' => $rows] + $v;
        }
        return array_map($fix, $v);
    };
    return $fix($content);
}

/** Keys whose string values are settings, not copy. */
const GEN_KEEP = ['variant', 'rank_by', 'icon', 'url', 'href', 'link', 'link_page', 'pages', 'types', 'kind', 'slug', 'item', 'mobile', 'src',
    'value', 'unit', 'code', 'id', 'type', 'platform', 'look', 'lead', 'topic', 'brand', 'image', 'art', 'theme', 'heading', 'items_source', 'mode'];

/** Replace copy with placeholder text of about the same length (keeps layouts realistic), leave settings alone. */
function gen_placeholders($v, string $key, string $path)
{
    if (is_array($v)) {
        foreach ($v as $k => $x) {
            $v[$k] = gen_placeholders($x, is_int($k) ? $key : (string)$k, "$path.$k");
        }
        return $v;
    }
    if (!is_string($v) || in_array($key, GEN_KEEP, true) || str_starts_with($key, 'show_') || preg_match('~^(https?:|#|/|page:)~', $v)
        || !preg_match('/\s/u', trim($v))) { // one word: an id, a payment method, a code; copy has spaces
        return $v;
    }
    if (preg_match('/<[a-z][^>]*>/i', $v)) { // rich text: keep the tags, replace every text node
        $n = 0;
        $r = preg_replace_callback('/>([^<]+)</u', function ($m) use ($path, &$n) {
            return '>' . (trim($m[1]) === '' ? $m[1] : gen_filler(mb_strlen(trim($m[1])), $path . '#' . $n++)) . '<';
        }, ">$v<");
        return $r === null ? $v : substr($r, 1, -1);
    }
    $len = mb_strlen($v);
    // page titles and descriptions start with the page's name, so every page of a site keeps its own title
    if (preg_match('/^meta\.([\w-]+)\.(title|description)$/', $path, $pm)) {
        $label = ucfirst(str_replace('-', ' ', $pm[1])) . ' · ';
        return $label . (str_contains($v, '{item}') ? '{item} ' : '') . gen_filler(max(12, $len - mb_strlen($label)), $path);
    }
    return str_contains($v, '{item}') ? '{item} ' . gen_filler(max(8, $len - 7), $path) : gen_filler($len, $path);
}

/** Placeholder text of about $len characters; where it starts in the filler depends on $path, so texts differ. */
function gen_filler(int $len, string $path = ''): string
{
    $words = explode(' ', GEN_PLACEHOLDER);
    $start = $path === '' ? 0 : crc32($path) % count($words);
    $out = '';
    for ($i = $start; ; $i++) { // whole words only, up to about $len characters
        $w = $words[$i % count($words)];
        $next = $out === '' ? ucfirst($w) : "$out $w";
        if ($out !== '' && mb_strlen($next) > $len) {
            return $out;
        }
        $out = $next;
    }
}

/* ---------------------------------------------------------------- planning */

/**
 * Plan every site of a job: which brand, which option per meaningful axis (spread over the user's picks), which option
 * per automatic axis (spread over all options), and a preset, avoiding presets already common in the project.
 */
function gen_plan_job(int $jobId, array $spec): array
{
    global $GEN_ROOT;
    $db = gen_db();
    $project = (int)$db->query("SELECT project_id FROM jobs WHERE id = $jobId")->fetchColumn();
    $scope = gen_settings()['scope'];
    $cat = gen_catalog();
    $sites = [];
    foreach ($spec['types'] as $type => $t) {
        $count = max(0, min(50, (int)($t['count'] ?? 0)));
        if (!$count || !isset($cat[$type])) {
            continue;
        }
        $demo = sx_json("$GEN_ROOT/sites/demos/{$cat[$type]['source']}.json");
        $brands = $spec['brand_ids'] ?? [];
        $use = []; // styles of this type in the other compared jobs; the job's own sites are added as they are planned
        foreach (gen_style_usage($jobId, $type, $scope) as $k => $v) {
            $use[$k] = $v;
        }
        $q = $db->prepare('SELECT plan FROM sites WHERE job_id = ? AND type = ?');
        $q->execute([$jobId, $type]);
        foreach ($q->fetchAll() as $r) { // a re-planned job's old sites are replaced, so they do not count
            $p = json_decode($r['plan'], true);
            foreach ([gen_style_key($p), substr(gen_style_key($p), 0, strrpos(gen_style_key($p), '|'))] as $k) {
                $use[$k] = max(0, ($use[$k] ?? 0) - 1);
            }
        }
        // combinations of the user's picks per meaningful axis, cycled through
        $combos = [[]];
        foreach ($cat[$type]['axes'] as $axis => $_) {
            $all = array_keys(gen_demo_axes($demo)[$axis] ?? []);
            // the user's picks, blocked in this market or not (blocks become warnings on the site); none ticked = the first option
            $mine = $t['variations'][$axis] ?? [];
            if ($axis === 'layout' && !isset($t['variations']['layout']) && isset($t['variations']['design'])) { // casino Design picks of an older job
                $mine = $t['variations']['design'];
            }
            if ($axis === 'palette' && !isset($t['variations']['palette'])) { // a job from before Colours and Design were split
                $mine = array_values(array_intersect($t['variations']['look'] ?? [], ['preset', 'neutral', 'brand']));
            }
            $picks = array_values(array_intersect($all, $mine)) ?: [$all[0] ?? ''];
            $next = [];
            foreach ($combos as $cmb) {
                foreach ($picks as $p) {
                    $next[] = $cmb + [$axis => $p];
                }
            }
            $combos = $next;
        }
        $use = [];
        for ($i = 0; $i < $count; $i++) {
            // the combination whose options were used least so far, so every ticked option shows up as early as possible
            $best = null;
            foreach ($combos as $k => $cmb) {
                $score = 0;
                foreach ($cmb as $axis => $o) {
                    $score += ($use["$axis=$o"] ?? 0) * 10;
                }
                $score += $use['#' . $k] ?? 0;
                if ($best === null || $score < $best[0]) {
                    $best = [$score, $k];
                }
            }
            $choice = $combos[$best[1]];
            $use['#' . $best[1]] = ($use['#' . $best[1]] ?? 0) + 1;
            foreach ($choice as $axis => $o) {
                $use["$axis=$o"] = ($use["$axis=$o"] ?? 0) + 1;
            }
            foreach ($cat[$type]['auto'] as $k => $axis) { // automatic differences, offset per axis so they do not move in lockstep
                $opts = array_keys($demo['variations'][$axis] ?? []);
                if ($opts) {
                    $choice[$axis] = $opts[($i + $k * 2 + $jobId) % count($opts)];
                }
            }
            $style = gen_pick_style($use);
            $navs = gen_navs($type);
            $sites[] = ['type' => $type, 'n' => $i + 1, 'brand' => $brands && !gen_multi_brand($type) ? $brands[$i % count($brands)] : null, 'choice' => $choice,
                'plan' => gen_set_style(['seed' => bin2hex(random_bytes(6)), 'colors' => $spec['colors'] ?? [], 'slot' => $i + $jobId, 'palette' => $i + $jobId, 'copy' => $spec['copy'] ?? 'demo', 'menu' => gen_menu_plan($type, $i + $jobId), 'rows' => gen_rows_plan($spec['rows'] ?? 'both', $i + $jobId)] + (gen_type_base($type) === 'satellite' ? ['offers' => ($i + $jobId) % 3 === 0] : []) + ($navs ? ['nav' => $navs[($i + 2 * $jobId) % count($navs)]] : []), $style)];
            gen_style_count($use, $sites[count($sites) - 1]['plan']);
        }
    }
    return $sites;
}

/* ---------------------------------------------------------------- build */

function gen_site_id(int $jobId, string $type, int $n): string
{
    return sprintf('j%d-%s-%02d', $jobId, $type, $n);
}

/** Build and export one site. Returns [ok, warnings, error, pages]. */
function gen_build_site(array $site, string $market, array $brands = []): array
{
    global $GEN_ROOT;
    $plan = is_array($site['plan']) ? $site['plan'] : json_decode($site['plan'], true);
    $q = gen_db()->prepare('SELECT spec FROM jobs WHERE id = ?');
    $q->execute([(int)$site['job_id']]);
    $spec = json_decode((string)$q->fetchColumn(), true) ?: [];
    $own = $brands;
    if (!empty(sx_json("$GEN_ROOT/blueprints/{$site['type']}.json")['compares_brands'])) { // rankings list the sample rivals too
        $brands = array_values(array_unique(array_merge($brands, $spec['sample_ids'] ?? [])));
    }
    $cfg = gen_site_config($site['type'], $market, $site['brand'], json_decode($site['choice'], true), $site['id'], $plan['seed'], $plan['preset'], $plan['colors'] ?? [], $brands, $plan['slot'] ?? $site['n'] - 1 + (int)$site['job_id'], $plan['copy'] ?? 'demo', (int)($plan['level'] ?? 0), isset($plan['palette']) ? (int)$plan['palette'] : null, $plan['nav'] ?? null, ['hue' => (int)($plan['hue'] ?? 0), 'flip' => !empty($plan['flip'])], $plan['menu'] ?? []);
    if (gen_type_base($site['type']) === 'satellite') { // bonuses before the hero on some satellites, like the full-length landings (older plans: from the site id)
        $cfg['sections']['home']['offers'] = (bool)($plan['offers'] ?? crc32($site['id'] . '|offers') % 3 === 0);
    }
    $cfg['rows'] = ($plan['rows'] ?? 'swipe') === 'stack' ? 'stack' : 'swipe';
    $cfg['disclosure'] = in_array($spec['disclosure'] ?? 'top', ['top', 'footer', 'off'], true) ? $spec['disclosure'] : 'top'; // affiliate disclosure: top strip, footer or none
    if (isset($cfg['featured']) && $own && $brands !== $own) { // the paid featured slot is always one of the job's own brands
        $cfg['featured'] = $own[crc32($site['id']) % count($own)];
    }
    $cfg = gen_apply_edits($cfg, $site['type'], gen_site_edits($spec, $site['type'], $plan)); // the user's edits (type, then site) go on top of the plan
    @mkdir("$GEN_ROOT/data/configs", 0777, true);
    $file = "$GEN_ROOT/data/configs/{$site['id']}.json";
    file_put_contents($file, json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $cmd = 'SX_REVIEW_OUT=dist/generated ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$GEN_ROOT/build/export-review-site.php") . ' ' . escapeshellarg($file) . ' 2>&1';
    if (PHP_OS_FAMILY === 'Windows') {
        $cmd = 'set SX_REVIEW_OUT=dist/generated&& ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$GEN_ROOT/build/export-review-site.php") . ' ' . escapeshellarg($file) . ' 2>&1';
    }
    exec($cmd, $out, $code);
    $dir = "$GEN_ROOT/dist/generated/{$site['id']}";
    $pages = count(glob("$dir/*.html"));
    $warnings = array_values(array_filter($out, fn($l) => !str_starts_with($l, 'dist/') && trim($l) !== ''));
    if ($code !== 0 || !$pages) {
        return [false, [], trim(implode("\n", array_slice($warnings, -3))) ?: 'Build failed.', 0];
    }
    return [true, $warnings, null, $pages];
}

/* ---------------------------------------------------------------- fingerprints and differences */

/** Code fingerprint of a site's home page: CSS rules, markup and DOM skeleton as hashed 6-tag sequences. */
function gen_fingerprint(string $html): array
{
    $dev = preg_match('/<script[^>]*id="sx-devdata"[^>]*>(.*?)<\/script>/s', $html, $m) ? json_decode($m[1], true) : [];
    $c = preg_replace('/<script[^>]*id="sx-devdata".*?<\/script>/s', '', $html);
    $c = preg_split('/<button type="button" id="sx-(skel|dev)toggle"/', $c)[0];
    if (!empty($dev['prefix'])) {
        $c = str_replace($dev['prefix'], 'P-', $c);
    }
    $c = preg_replace('/(?<![a-z0-9-])u\d+-(?=[a-z0-9])/', 'u-', $c); // job brand ids (u<job>-<brand>): the same brand in every job
    preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $c, $st);
    $rules = [];
    preg_match_all('/([^{}]+)\{([^{}]*)\}/', preg_replace('/@media[^{]+\{/', '', implode(' ', $st[1])), $rm, PREG_SET_ORDER);
    foreach ($rm as [$_, $sel, $body]) {
        $d = array_filter(array_map('trim', preg_split('/;(?![^(]*\))/', $body)));
        sort($d);
        $rules[] = crc32(preg_replace('/\s+/', ' ', trim($sel)) . '{' . implode(';', $d) . '}');
    }
    preg_match_all('/<(\/?)([a-zA-Z][\w:-]*)([^>]*)>/', preg_replace('/<style.*?<\/style>|<script.*?<\/script>/s', ' ', $c), $tm, PREG_SET_ORDER);
    $toks = $names = [];
    foreach ($tm as [$_, $sl, $n, $at]) {
        preg_match_all('/[^\s=]+(?:="[^"]*")?/', $at, $am);
        $a = array_map(fn($x) => preg_replace('/"[^"]{40,}"/', '"…"', $x), $am[0]);
        sort($a);
        $toks[] = "<$sl$n " . implode(' ', $a) . '>';
        $names[] = $sl . strtolower($n);
    }
    $sh = function (array $t) {
        $o = [];
        for ($i = 0; $i + 6 <= count($t); $i++) {
            $o[crc32(implode('|', array_slice($t, $i, 6)))] = 1;
        }
        return array_keys($o);
    };
    return ['css' => array_values(array_unique($rules)), 'markup' => $sh($toks), 'skeleton' => $sh($names)];
}

function gen_jaccard(array $a, array $b): float
{
    if (!$a && !$b) {
        return 0.0;
    }
    $fa = array_flip($a);
    $inter = 0;
    foreach ($b as $x) {
        $inter += isset($fa[$x]) ? 1 : 0;
    }
    return 1 - $inter / (count($a) + count($b) - $inter);
}

/** Difference in % between two fingerprints: code (mean of CSS and markup) and DOM skeleton. */
/** A fingerprint prepared for many comparisons: each hash list as a set (gen_diff_sets()). */
function gen_diff_prep(array $fp): array
{
    return array_map(fn($l) => array_flip($l), array_intersect_key($fp, ['css' => 1, 'markup' => 1, 'skeleton' => 1]));
}

/** gen_diff() on prepared fingerprints, the same result without rebuilding the sets for every pair. */
function gen_diff_sets(array $a, array $b): array
{
    $j = function (array $x, array $y): float {
        if (!$x && !$y) {
            return 0.0;
        }
        $inter = count(count($x) < count($y) ? array_intersect_key($x, $y) : array_intersect_key($y, $x));
        return 1 - $inter / (count($x) + count($y) - $inter);
    };
    return ['code' => round(50 * ($j($a['css'] ?? [], $b['css'] ?? []) + $j($a['markup'] ?? [], $b['markup'] ?? []))), 'skeleton' => round(100 * $j($a['skeleton'] ?? [], $b['skeleton'] ?? []))];
}

/** PHP for background workers, with the JIT on: the visual and code comparisons are tight loops. */
function gen_php_cmd(): string
{
    return escapeshellarg(PHP_BINARY) . ' -d opcache.enable_cli=1 -d opcache.jit_buffer_size=64M -d opcache.jit=tracing';
}

function gen_diff(array $a, array $b): array
{
    $css = gen_jaccard($a['css'], $b['css']);
    $mk = gen_jaccard($a['markup'], $b['markup']);
    return ['code' => round(50 * ($css + $mk)), 'skeleton' => round(100 * gen_jaccard($a['skeleton'], $b['skeleton']))];
}

/* ---------------------------------------------------------------- worker queue */

/**
 * Start the worker for a job. One job builds at a time (gen_lock_file()): a worker that finds another one running
 * queues its job and exits; each worker starts the next queued job when it is done. The worker runs at a lower CPU
 * priority, so the app stays responsive while it builds.
 */
function gen_spawn(int $jobId): void
{
    global $GEN_ROOT;
    @mkdir("$GEN_ROOT/data/logs", 0777, true);
    $log = "$GEN_ROOT/data/logs/job-$jobId.log";
    $cmd = gen_php_cmd() . ' ' . escapeshellarg(__DIR__ . '/run.php') . ' ' . $jobId . ' >> ' . escapeshellarg($log) . ' 2>&1';
    if (PHP_OS_FAMILY === 'Windows') {
        pclose(popen('start "" /B /BELOWNORMAL cmd /c "' . $cmd . '"', 'r'));
    } else {
        exec('nice -n 10 ' . $cmd . ' &');
    }
}

/** The worker lock, on the container's own disk: locks on the host folder mounted as data/ are not reliable. */
function gen_lock_file(): string
{
    return sys_get_temp_dir() . '/satellite-generator-worker.lock';
}

/** Whether a worker is building right now (it holds the worker lock). */
function gen_worker_busy(): bool
{
    global $GEN_ROOT;
    $f = fopen(gen_lock_file(), 'c');
    $free = flock($f, LOCK_EX | LOCK_NB);
    if ($free) {
        flock($f, LOCK_UN);
    }
    fclose($f);
    return !$free;
}

/** The job being built (running or checking), if any: ['id', 'name']. */
function gen_busy_job(): ?array
{
    $r = gen_db()->query("SELECT id, name FROM jobs WHERE status IN ('running', 'checking') ORDER BY id LIMIT 1")->fetchAll();
    return $r[0] ?? null;
}

/**
 * Bring a job's status in line with the worker: a job shown as building while no worker runs was stopped (the worker
 * was killed or the container restarted); a queued job with no worker running starts now. Returns the status.
 */
function gen_heal_job(int $jobId, string $status): string
{
    if (!in_array($status, ['running', 'checking', 'queued'], true) || gen_worker_busy()) {
        return $status;
    }
    $db = gen_db();
    if ($status === 'queued') {
        gen_spawn($jobId);
        return $status;
    }
    gen_stop_jobs([$jobId]);
    $next = $db->query("SELECT id FROM jobs WHERE status = 'queued' ORDER BY queued_at, id LIMIT 1")->fetchColumn();
    if ($next) {
        gen_spawn((int)$next);
    }
    return 'stopped';
}

/** Mark jobs whose worker died as stopped; their half-built sites go back to the queue of the job. */
function gen_stop_jobs(array $ids): void
{
    $db = gen_db();
    foreach ($ids as $id) {
        $db->prepare("UPDATE jobs SET status = 'stopped' WHERE id = ? AND status IN ('running', 'checking')")->execute([(int)$id]);
        $db->prepare("UPDATE sites SET status = 'planned' WHERE job_id = ? AND status = 'building'")->execute([(int)$id]);
    }
}

/* ---------------------------------------------------------------- thresholds */

/**
 * Sites a site is compared with: same type, built, in the settings' scope (this job, this project or every job).
 * Inside the site's own job only the sites before it count, so of two sites that are too alike the later one changes.
 * Jobs left out of comparisons (drafts) are skipped; a draft's own sites are still checked against the others.
 */
function gen_compare_set(array $site, string $scope): array
{
    $db = gen_db();
    $job = $db->query('SELECT id, project_id FROM jobs WHERE id = ' . (int)$site['job_id'])->fetchAll()[0];
    $q = $db->prepare("SELECT s.id, s.job_id, s.plan, s.fingerprint, s.visual, j.project_id, j.compare FROM sites s JOIN jobs j ON j.id = s.job_id
        WHERE s.type = ? AND s.id <> ? AND s.status = 'built' AND s.fingerprint IS NOT NULL");
    $q->execute([$site['type'], $site['id']]);
    return array_values(array_filter($q->fetchAll(), fn($r) => match (true) {
        (int)$r['job_id'] === (int)$job['id'] => $r['id'] < $site['id'],
        $scope === 'job', !(int)$r['compare'] => false,
        $scope === 'project' => (int)$r['project_id'] === (int)$job['project_id'],
        default => true,
    }));
}

/** Lowest code difference between a fingerprint and the comparison set: ['min' => %, 'site' => id, 'preset' => style], or null. */
function gen_code_check(array $fp, array $set): ?array
{
    $best = null;
    foreach ($set as $r) {
        $d = gen_diff($fp, json_decode($r['fingerprint'], true))['code'];
        if ($best === null || $d < $best['min']) {
            $best = ['min' => $d, 'site' => $r['id'], 'preset' => json_decode($r['plan'], true)['preset'] ?? null, 'style' => gen_style_key(json_decode($r['plan'], true))];
        }
    }
    return $best;
}

/** Lowest visual difference between a visual print and the comparison set's prints. */
function gen_visual_check(string $print, array $set): ?array
{
    $best = null;
    foreach ($set as $r) {
        if (!$r['visual']) {
            continue;
        }
        $d = gen_visual_diff($print, $r['visual']);
        if ($best === null || $d < $best['min']) {
            $best = ['min' => $d, 'site' => $r['id'], 'preset' => json_decode($r['plan'], true)['preset'] ?? null, 'style' => gen_style_key(json_decode($r['plan'], true))];
        }
    }
    return $best;
}

/**
 * Visual difference in %: the share of the first screen that visibly changes, desktop and phone averaged. A print is
 * JSON {"d": base64, "p": base64} of 8x8 px cells (average RGB, at half scale); a cell counts when its colour moves by
 * more than 40 (RGB distance).
 */
function gen_visual_diff(string $a, string $b): int
{
    return gen_visual_diff_raw(gen_visual_raw($a), gen_visual_raw($b));
}

/** A stored visual print, decoded once: ['d' => desktop cells, 'p' => phone cells] as lists of R, G, B values. */
function gen_visual_raw(string $print): array
{
    $j = json_decode($print, true) ?: [];
    $cells = fn(string $b64) => ($bin = base64_decode($b64)) === '' ? [] : array_values(unpack('C*', $bin));
    return ['d' => $cells($j['d'] ?? ''), 'p' => $cells($j['p'] ?? '')];
}

/** gen_visual_diff() on decoded prints (comparing many pairs decodes each print once). */
function gen_visual_diff_raw(array $a, array $b): int
{
    $parts = [];
    foreach (['d', 'p'] as $k) {
        $x = $a[$k];
        $y = $b[$k];
        $n = intdiv(min(count($x), count($y)), 3);
        if (!$n) {
            continue;
        }
        $changed = 0;
        for ($i = 0, $m = $n * 3; $i < $m; $i += 3) { // a tight integer loop: fast with the JIT on (gen_php_cmd())
            $dr = $x[$i] - $y[$i];
            $dg = $x[$i + 1] - $y[$i + 1];
            $db = $x[$i + 2] - $y[$i + 2];
            if ($dr * $dr + $dg * $dg + $db * $db > 1600) {
                $changed++;
            }
        }
        $parts[] = $changed / $n;
    }
    return $parts ? (int)round(100 * array_sum($parts) / count($parts)) : 0;
}

/**
 * A new plan for a site that is too alike another. $flipOnly (a site that only looks too alike, first try): the same
 * plan with light and dark swapped, the change that moves the page most. Otherwise: new seed, the next hero layout, a
 * higher level (1: section order left to the seed; 2: every layout too), and the least-used style (gen_pick_style()) that
 * is neither its own nor the closest site's light / dark version of a preset.
 * $type: gen_type_siblings() of the site; $avoid: the closest site's style key.
 */
function gen_replan(array $plan, int $attempt, ?string $avoid, array $type = [], bool $flipOnly = false): array
{
    if ($flipOnly && gen_settings()['variants']) {
        $plan['flip'] = empty($plan['flip']);
        return $plan;
    }
    $palettes = $type['palettes'] ?? [];
    $not = [substr(gen_style_key($plan), 0, strrpos(gen_style_key($plan), '|'))];
    if ($avoid) {
        $not[] = substr($avoid, 0, strrpos($avoid, '|') ?: strlen($avoid));
    }
    $plan['seed'] = bin2hex(random_bytes(6));
    $plan = gen_set_style($plan, gen_pick_style($type['styles'] ?? [], $not));
    $plan['slot'] = (int)($plan['slot'] ?? 0) + 1 + $attempt;
    $next = (int)($plan['palette'] ?? $plan['slot']) + 1;
    for ($k = 0; $k < 5 && in_array($next % 5, $palettes, true); $k++) { // a palette the other sites of its type do not use
        $next++;
    }
    $plan['palette'] = $next;
    $plan['level'] = min(2, $attempt);
    $plan['menu'] = gen_next_menu($plan['menu'] ?? [], $type['type'] ?? '');
    if ($navs = gen_navs($type['type'] ?? '')) { // the next navigation, one the type's other sites in the job use least
        $i = array_search($plan['nav'] ?? '', $navs, true);
        $order = array_merge(array_slice($navs, $i === false ? 0 : $i + 1), array_slice($navs, 0, $i === false ? 0 : $i + 1));
        $count = array_count_values($type['navs'] ?? []);
        usort($order, fn($a, $b) => ($count[$a] ?? 0) <=> ($count[$b] ?? 0));
        $plan['nav'] = $order[0];
    }
    return $plan;
}

/**
 * Navigations a type's sites take turns through: a top menu (header + footer) or a sidebar on the left or the right.
 * Multi-page sidebars list the pages; one-page sidebars list the brand's sections as tracked links to the brand, with
 * the CTA. Satellites choose top / sidebar / hub by their own variation, and the plan picks the sidebar's side.
 */
function gen_navs(string $type): array
{
    global $GEN_ROOT;
    static $cache = [];
    if (!isset($cache[$type])) {
        $f = "$GEN_ROOT/blueprints/$type.json";
        $cache[$type] = $type === '' || !is_file($f) ? [] : (in_array('nav', gen_catalog()[$type]['auto'] ?? [], true) ? ['left', 'right'] : ['top', 'left', 'right']);
    }
    return $cache[$type];
}

/**
 * New looks for every site of a job, variations kept: new seed, the next hero layout and palette, and a style the
 * other sites of its type do not use (the least-used styles in the project first).
 */
function gen_restyle_job(int $jobId): void
{
    global $GEN_ROOT;
    $db = gen_db();
    $scope = gen_settings()['scope'];
    $st = $db->prepare('UPDATE sites SET plan = ? WHERE id = ?');
    $use = [];
    $byType = $pals = [];
    foreach ($db->query("SELECT id, type, n, plan FROM sites WHERE job_id = $jobId ORDER BY type, n")->fetchAll() as $r) {
        $plan = json_decode($r['plan'], true);
        if (!isset($use[$r['type']])) { // the other compared jobs' styles of this type; the job's sites are added as they get new ones
            $use[$r['type']] = array_filter(gen_style_usage($jobId, $r['type'], $scope), fn($v) => true);
            $q = $db->prepare('SELECT plan FROM sites WHERE job_id = ? AND type = ?');
            $q->execute([$jobId, $r['type']]);
            foreach ($q->fetchAll() as $o) {
                $k = gen_style_key(json_decode($o['plan'], true));
                foreach ([$k, substr($k, 0, strrpos($k, '|'))] as $kk) {
                    $use[$r['type']][$kk] = max(0, ($use[$r['type']][$kk] ?? 0) - 1);
                }
            }
        }
        $plan = gen_set_style($plan, gen_pick_style($use[$r['type']], [gen_style_key($plan)]));
        $plan['menu'] = gen_next_menu($plan['menu'] ?? [], $r['type']);
        gen_style_count($use[$r['type']], $plan);
        $plan['seed'] = bin2hex(random_bytes(6));
        $plan['slot'] = (int)($plan['slot'] ?? 0) + 1;
        $next = (int)($plan['palette'] ?? $plan['slot']) + 1;
        for ($k = 0; $k < 5 && in_array($next % 5, $pals[$r['type']] ?? [], true); $k++) { // palettes spread over the type's sites
            $next++;
        }
        $plan['palette'] = $next;
        $pals[$r['type']][] = $next % 5;
        if ($navs = gen_navs($r['type'])) { // navigations in turn over the type's sites, moved on by one
            $i = array_search($plan['nav'] ?? '', $navs, true);
            $plan['nav'] = $navs[($i === false ? (int)$r['n'] + $jobId : $i + 1) % count($navs)];
        }
        $st->execute([json_encode($plan), $r['id']]);
    }
}

/**
 * What the other sites of a site's type use: palettes (0-4) and styles in its job, and how often each style appears
 * among the sites it is compared with.
 */
function gen_type_siblings(array $site, string $scope): array
{
    $q = gen_db()->prepare('SELECT plan FROM sites WHERE job_id = ? AND type = ? AND id <> ?');
    $q->execute([$site['job_id'], $site['type'], $site['id']]);
    $plans = array_map(fn($r) => json_decode($r['plan'], true), $q->fetchAll());
    $usage = [];
    foreach (gen_compare_set($site, $scope) as $r) {
        $p = json_decode($r['plan'], true)['preset'] ?? '';
        $usage[$p] = ($usage[$p] ?? 0) + 1;
    }
    return ['type' => $site['type'], 'palettes' => array_map(fn($p) => (int)($p['palette'] ?? 0) % 5, $plans), 'presets' => array_values(array_filter(array_column($plans, 'preset'))),
        'navs' => array_values(array_filter(array_column($plans, 'nav'))), 'usage' => $usage, 'styles' => gen_style_usage((int)$site['job_id'], $site['type'], $scope, $site['id'])];
}


/* ---------------------------------------------------------------- menus */

/** Rows of cards on a site: "swipe" (sideways-scrolling rows), "stack" (wrapped), or "both" (every other site). */
function gen_rows_plan(string $mode, int $k): string
{
    return $mode === 'both' ? ['swipe', 'stack'][$k % 2] : ($mode === 'stack' ? 'stack' : 'swipe');
}

/** How the phone menu opens: a dropdown under the ☰, a drawer from its side, a full-width panel under the header, the full screen. */
const GEN_MENU_STYLES = ['dropdown', 'drawer', 'sheet', 'full'];
/** Desktop sidebar looks: a solid panel, a rail with large type, a stack of cards. */
const GEN_SIDE_LOOKS = ['panel', 'rail', 'cards'];

/** Types that list several brands (casino reviews, news): no single brand per site. */
function gen_multi_brand(string $type): bool
{
    global $GEN_ROOT;
    return gen_type_base($type) === 'news' || !empty(sx_json("$GEN_ROOT/blueprints/$type.json")['compares_brands']);
}

function gen_multi_page(string $type): bool
{
    global $GEN_ROOT;
    static $c = [];
    return $c[$type] ??= is_file("$GEN_ROOT/blueprints/$type.json") && isset(sx_json("$GEN_ROOT/blueprints/$type.json")['pages']);
}

/**
 * A site's phone menu and sidebar look, the $k-th of its type: side, opening and look in turn with different strides
 * (so they combine in many ways), and one-page sites without a menu every third time (multi-page sites always have one).
 */
function gen_menu_plan(string $type, int $k): array
{
    return ['side' => ['left', 'right'][$k % 2], 'style' => GEN_MENU_STYLES[intdiv($k, 2) % 4], 'look' => GEN_SIDE_LOOKS[$k % 3],
        'skip' => !gen_multi_page($type) && $k % 3 === 1];
}

/** The next menu for a re-planned or restyled site: the other side, the next opening and look. */
function gen_next_menu(array $menu, string $type): array
{
    $style = array_search($menu['style'] ?? 'dropdown', GEN_MENU_STYLES, true);
    $look = array_search($menu['look'] ?? 'panel', GEN_SIDE_LOOKS, true);
    return ['side' => ($menu['side'] ?? 'left') === 'left' ? 'right' : 'left', 'style' => GEN_MENU_STYLES[((int)$style + 1) % 4],
        'look' => GEN_SIDE_LOOKS[((int)$look + 1) % 3], 'skip' => !gen_multi_page($type) && empty($menu['skip']) && mt_rand(0, 2) === 0];
}

/* ---------------------------------------------------------------- styles */

/** Colour rotations a style variant can take (degrees); with light / dark, 10 presets give 120 styles. */
const GEN_HUES = [0, 60, 120, 180, 240, 300];

/** A plan's style: "preset|flip|hue", e.g. "arena|1|120". */
function gen_style_key(array $plan): string
{
    return ($plan['preset'] ?? '') . '|' . (empty($plan['flip']) ? 0 : 1) . '|' . (int)($plan['hue'] ?? 0);
}

/** "arena · light · +120°": the preset, its theme when flipped, its colour rotation. */
function gen_style_label(array $plan): string
{
    global $GEN_ROOT;
    static $themes = [];
    $p = $plan['preset'] ?? '';
    $themes[$p] ??= is_file("$GEN_ROOT/tokens/presets/$p.json") ? (sx_json("$GEN_ROOT/tokens/presets/$p.json")['theme'] ?? 'light') : 'light';
    $theme = empty($plan['flip']) ? $themes[$p] : ($themes[$p] === 'dark' ? 'light' : 'dark');
    return $p . ' · ' . $theme . (empty($plan['hue']) ? '' : ' · +' . (int)$plan['hue'] . '°');
}

/**
 * How often each style is used by sites of a type: every other job in the settings' scope (jobs left out of
 * comparisons do not count), plus the given job's own sites. Keys: "preset|flip|hue" and "preset|flip".
 */
function gen_style_usage(int $jobId, string $type, string $scope, ?string $exceptSite = null): array
{
    $db = gen_db();
    $project = (int)$db->query("SELECT project_id FROM jobs WHERE id = $jobId")->fetchColumn();
    $q = $db->prepare("SELECT s.id, s.job_id, s.plan, j.project_id, j.compare FROM sites s JOIN jobs j ON j.id = s.job_id WHERE s.type = ?");
    $q->execute([$type]);
    $use = [];
    foreach ($q->fetchAll() as $r) {
        $own = (int)$r['job_id'] === $jobId;
        if ($r['id'] === $exceptSite || (!$own && (!(int)$r['compare'] || $scope === 'job' || ($scope === 'project' && (int)$r['project_id'] !== $project)))) {
            continue;
        }
        gen_style_count($use, json_decode($r['plan'], true));
    }
    return $use;
}

function gen_style_count(array &$use, array $plan): void
{
    $k = gen_style_key($plan);
    $use[$k] = ($use[$k] ?? 0) + 1;
    $pk = substr($k, 0, strrpos($k, '|'));
    $use[$pk] = ($use[$pk] ?? 0) + 1;
}

/**
 * The least-used style: the light / dark version of a preset used least first (it changes the page most), then the
 * colour rotation used least within it. $not: style keys, or "preset|flip" pairs, to avoid. Returns [preset, flip, hue].
 */
function gen_pick_style(array $use, array $not = []): array
{
    global $GEN_ROOT;
    static $variants = null;
    $variants ??= gen_settings()['variants']; // off: the presets as they are (no light / dark swap, no colour rotation)
    $best = [];
    $score = null;
    foreach (glob("$GEN_ROOT/tokens/presets/*.json") as $f) {
        $p = basename($f, '.json');
        foreach ($variants ? [0, 1] : [0] as $flip) {
            if (in_array("$p|$flip", $not, true)) {
                continue;
            }
            foreach ($variants ? GEN_HUES : [0] as $hue) {
                if (in_array("$p|$flip|$hue", $not, true)) {
                    continue;
                }
                $sc = ($use["$p|$flip"] ?? 0) * 100 + ($use["$p|$flip|$hue"] ?? 0);
                if ($score === null || $sc < $score) {
                    [$score, $best] = [$sc, [[$p, $flip, $hue]]];
                } elseif ($sc === $score) {
                    $best[] = [$p, $flip, $hue];
                }
            }
        }
    }
    return $best ? $best[random_int(0, count($best) - 1)] : ['fresh-minimal', 0, 0];
}

/** Set a plan's style. */
function gen_set_style(array $plan, array $style): array
{
    [$plan['preset'], $flip, $hue] = $style;
    $plan['flip'] = (bool)$flip;
    $plan['hue'] = (int)$hue;
    return $plan;
}

/** First-screen visual prints of built sites (desktop and phone), taken with headless Chromium. Returns {id: print}. */
function gen_shoot(array $ids): array
{
    if (!$ids) {
        return [];
    }
    $cmd = 'python3 ' . escapeshellarg(__DIR__ . '/visual.py') . ' ' . implode(' ', array_map('escapeshellarg', $ids)) . ' 2>&1';
    exec($cmd, $out, $code);
    $json = json_decode((string)end($out), true);
    if ($code !== 0 || !is_array($json)) {
        throw new RuntimeException('Screenshots failed: ' . trim(implode("\n", array_slice($out, -3))));
    }
    return $json;
}
