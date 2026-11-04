<?php
/**
 * Builder JSON API. Every endpoint calls the same engine as build/build.php and the WordPress template, so the
 * preview is exactly what ships. Writes are limited to sites/<project>/<site>.json and dist/sites/<id>/.
 *
 *   GET  /api/meta                      types, blocks, markets, brands, presets, data availability, demos
 *   GET  /api/site?path=sites/p/x.json  one site config
 *   GET  /api/template?type=landing     starter config for a type
 *   POST /api/render   {config, page?, gate?}     preview HTML for one page + X-ray (trace, weights, warnings, contrast)
 *   POST /api/save     {path, config, create?}    write a site config
 *   POST /api/build    {path}                     build a saved site into dist/sites/<id>
 *   POST /api/diversity {project, path?, config?} overlap and rule check for every pair in a project
 *   POST /api/suggest  {path, config}            single changes that make the site most different from its siblings
 *   GET  /api/data/list                          shared data files (brands, studios, catalogs, offers, facts, ratings, reviews, articles)
 *   GET  /api/data/file?path=...                 one data file
 *   POST /api/data/save {path, data, create?}    write a data file (previous version kept in dist/backups/)
 *   POST /api/data/check                         build every site in memory and report which still pass
 *   GET  /api/export?path=...&kind=static|preview|wordpress   zip download
 */

ini_set('display_errors', '0');
require_once $root . '/lib/page.php';
require_once $root . '/lib/diversity.php';
require_once $root . '/lib/zip.php';

const SX_MARKET_NAMES = ['au' => 'Australia', 'kz' => 'Kazakhstan', 'br' => 'Brazil', 'xx' => 'Demo market'];
const SX_SITE_PATH = '#^sites/[a-z0-9-]+/[a-z0-9-]+\.json$#';
/** Shared data the builder may read; markets are read-only (they belong to compliance). */
const SX_DATA_PATH = '#^(brands/(operators|publishers|providers)\.json|brands/(catalog|promotions|facts|ratings|reviews)/[a-z0-9-]+\.json|content/[a-z0-9-]+/articles/[a-z0-9-]+\.json|content/[a-z0-9-]+/authors\.json|markets/[a-z]{2}\.json)$#';

$GLOBALS['php_notices'] = [];
set_error_handler(function (int $no, string $msg, string $file, int $line) {
    $GLOBALS['php_notices'][] = 'PHP: ' . $msg . ' (' . basename($file) . ":$line)";
    return true;
});

function api_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function api_fail(string $msg, int $status = 400): void
{
    api_out(['ok' => false, 'error' => $msg], $status);
}

function api_json_file(string $file): array
{
    return is_file($file) ? json_decode(file_get_contents($file), true) ?? [] : [];
}

/** Pretty JSON with two-space indent, like the hand-written configs. */
function api_pretty(array $data): string
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return preg_replace_callback('/^( +)/m', fn($m) => str_repeat(' ', intdiv(strlen($m[1]), 2)), $json) . "\n";
}

function api_site_path(?string $path): string
{
    if (!$path || !preg_match(SX_SITE_PATH, $path) || str_starts_with($path, 'sites/templates/')) {
        api_fail('Site files live at sites/<project>/<site>.json (lowercase letters, digits and dashes; not in templates).');
    }
    return $path;
}


function api_data_path(?string $path, bool $write = false): string
{
    if (!$path || !preg_match(SX_DATA_PATH, $path)) {
        api_fail('Not a data file the builder manages.');
    }
    if ($write && str_starts_with($path, 'markets/')) {
        api_fail('Market rules are read-only here: compliance owns markets/*.json.');
    }
    return $path;
}

/** Every site config with a type (templates excluded). [relative path => config]. */
function api_all_sites(string $root): array
{
    $out = [];
    foreach (glob("$root/sites/*/*.json") as $f) {
        $rel = substr($f, strlen($root) + 1);
        $c = api_json_file($f);
        if (!str_starts_with($rel, 'sites/templates/') && !empty($c['type'])) {
            $out[$rel] = $c;
        }
    }
    return $out;
}

/** Build a site in memory, isolated from the previous one. */
function api_try_build(array $cfg, string $root): array
{
    SX::$warnings = [];
    try {
        $pages = sx_build_site($cfg, $root);
        $w = [];
        foreach ($pages as $p) {
            array_push($w, ...$p['warnings']);
        }
        return ['ok' => true, 'pages' => $pages, 'warnings' => array_values(array_unique($w))];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage(), 'warnings' => []];
    }
}

/** Score a candidate against the siblings: fewer failing pairs, then lower max and mean overlap. */
function api_div_score(array $me, array $siblings): array
{
    $fail = 0; $max = 0; $sum = 0; $n = 0;
    foreach ($siblings as $sib) {
        $d = sx_diversity_pair($me, $sib);
        if (!$d['comparable']) {
            continue;
        }
        $fail += $d['ok'] ? 0 : 1; $max = max($max, $d['overlap']); $sum += $d['overlap']; $n++;
    }
    return [$fail, $max, $n ? round($sum / $n) : 0];
}

$method = $_SERVER['REQUEST_METHOD'];
$route = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 5);
$body = [];
if ($method === 'POST') {
    // A custom header cannot be sent cross-site without a CORS preflight, which this server never approves.
    if (($_SERVER['HTTP_X_SX_BUILDER'] ?? '') !== '1') {
        api_fail('Missing builder header.', 403);
    }
    $body = json_decode(file_get_contents('php://input'), true) ?? api_fail('Request body is not valid JSON.');
}

switch ("$method $route") {
    case 'GET meta':
        $types = [];
        foreach (glob("$root/blueprints/*.json") as $f) {
            $bp = sx_json($f);
            $types[$bp['type']] = $bp + ['template' => is_file("$root/sites/templates/{$bp['type']}.json")];
        }
        $blocks = [];
        foreach (glob("$root/blocks/*/block.json") as $f) {
            $name = basename(dirname($f));
            if ($name[0] === '_') {
                continue;
            }
            $b = sx_json($f);
            $blocks[$name] = ['title' => $b['title'] ?? $name, 'description' => $b['description'] ?? '', 'role' => $b['sx']['role'] ?? 'content',
                'variants' => $b['sx']['variants'] ?? [], 'guardrails' => $b['sx']['guardrails'] ?? [], 'fields' => api_json_file(dirname($f) . '/fields.json')['fields'] ?? []];
        }
        $markets = [];
        foreach (glob("$root/markets/*.json") as $f) {
            $m = sx_json($f);
            $markets[$m['id']] = ['id' => $m['id'], 'name' => SX_MARKET_NAMES[$m['id']] ?? strtoupper($m['id']), 'locales' => $m['locales'], 'default_locale' => $m['default_locale'],
                'verticals' => $m['verticals'], 'promotions_allowed' => array_keys(array_filter($m['promotions'] ?? [], fn($v) => $v === 'allowed')),
                'min_age' => $m['min_age'], 'demo_only' => !empty($m['demo_only']), 'strip' => $m['compliance']['strip'] ?? 'optional',
                'domain_suffix' => $m['compliance']['brand_domain_suffix'] ?? null, 'apps' => $m['apps'] ?? [], 'payments' => $m['payments'] ?? []];
        }
        $brands = [];
        foreach (array_diff_key(sx_json("$root/brands/operators.json"), ['_note' => 1]) as $id => $b) {
            $brands[$id] = ['id' => $id, 'name' => $b['name'], 'kind' => $b['kind'], 'verticals' => $b['verticals'], 'markets' => $b['markets'],
                'color' => $b['brand_color'], 'licensed' => !empty($b['license_no']), 'app' => $b['app']['platforms'] ?? []];
        }
        $catalogs = [];
        foreach (glob("$root/brands/catalog/*.json") as $f) {
            if (basename($f)[0] !== '_') {
                foreach (sx_json($f)['markets'] ?? [] as $m => $c) {
                    $catalogs[basename($f, '.json')][$m] = array_keys($c['providers'] ?? []);
                }
            }
        }
        $reviews = [];
        foreach (glob("$root/brands/reviews/*.json") as $f) {
            foreach (sx_json($f)['reviews'] ?? [] as $r) {
                if (($r['status'] ?? '') === 'published') {
                    $reviews[basename($f, '.json')][$r['market']] = ($reviews[basename($f, '.json')][$r['market']] ?? 0) + 1;
                }
            }
        }
        $compared = [];
        foreach (glob("$root/brands/ratings/*.json") as $f) {
            $facts = api_json_file("$root/brands/facts/" . basename($f));
            $compared[basename($f, '.json')] = array_values(array_intersect(array_keys(sx_json($f)['brands'] ?? []), array_keys($facts['brands'] ?? [])));
        }
        $articles = [];
        foreach (glob("$root/content/*/articles", GLOB_ONLYDIR) as $d) {
            $articles[substr($d, strlen($root) + 1)] = count(glob("$d/*.json"));
        }
        $presets = [];
        foreach (sx_all_presets($root) as $id => $p) {
            $presets[$id] = ['id' => $id, 'name' => $p['name'], 'mood' => $p['mood'] ?? '', 'theme' => $p['theme'], 'fits' => $p['fits'] ?? [],
                'color' => $p['color'], 'fonts' => ['display' => $p['fonts']['display']['family'], 'body' => $p['fonts']['body']['family']],
                'shape' => $p['shape'] ?? [], 'density' => $p['space']['density'] ?? 1];
        }
        $sites = [];
        foreach (glob("$root/sites/*/*.json") as $f) {
            $rel = substr($f, strlen($root) + 1);
            if (str_starts_with($rel, 'sites/templates/')) {
                continue;
            }
            $c = api_json_file($f);
            if (empty($c['type'])) {
                continue;
            }
            $id = $c['id'] ?? basename($f, '.json');
            $sites[] = ['path' => $rel, 'project' => basename(dirname($f)), 'id' => $id, 'type' => $c['type'], 'market' => $c['market'] ?? '',
                'brand' => $c['brand'] ?? null, 'publisher' => $c['publisher'] ?? null, 'preset' => $c['preset'] ?? '', 'angle' => $c['angle'] ?? '',
                'demo' => !empty($c['demo']), 'shot' => is_file("$root/dist/gallery/shots/$id-d.png") ? "/shots/$id-d.png" : null,
                'built' => is_file("$root/dist/sites/$id/index.html") ? date('Y-m-d H:i', filemtime("$root/dist/sites/$id/index.html")) : null,
                'modified' => date('Y-m-d H:i', filemtime($f))];
        }
        $providers = array_map(fn($p) => $p['name'], array_diff_key(sx_json("$root/brands/providers.json"), ['_note' => 1]));
        $publishers = array_map(fn($p) => ['name' => $p['name'], 'markets' => $p['markets'], 'verticals' => $p['verticals']], array_diff_key(sx_json("$root/brands/publishers.json"), ['_note' => 1]));
        api_out(['ok' => true, 'types' => $types, 'blocks' => $blocks, 'markets' => $markets, 'brands' => $brands, 'publishers' => $publishers,
            'providers' => $providers, 'catalogs' => $catalogs, 'reviews' => $reviews, 'compared' => $compared, 'articles' => $articles,
            'presets' => $presets, 'sites' => $sites, 'budget_kb' => SX_PAGE_BUDGET_KB]);

    case 'GET site':
        $path = api_site_path($_GET['path'] ?? null);
        is_file("$root/$path") || api_fail("No site at $path.", 404);
        api_out(['ok' => true, 'path' => $path, 'config' => sx_json("$root/$path")]);

    case 'GET template':
        $type = preg_replace('/[^a-z-]/', '', $_GET['type'] ?? '');
        $file = "$root/sites/templates/$type.json";
        is_file($file) || api_fail("No template for type '$type'.", 404);
        api_out(['ok' => true, 'config' => sx_json($file)]);

    case 'POST render':
        $cfg = $body['config'] ?? api_fail('No config.');
        $cfg['_xray'] = true;
        $cfg['urls'] = 'files'; // flat page files, so the preview can follow internal links
        unset($cfg['base']);
        try {
            $out = sx_build_site($cfg, $root);
        } catch (Throwable $e) {
            api_out(['ok' => false, 'error' => $e->getMessage(), 'trace' => SX::$trace, 'notices' => $GLOBALS['php_notices']]);
        }
        $pages = array_values(array_filter($out, fn($p) => empty($p['asset'])));
        $assets = array_column(array_values(array_filter($out, fn($p) => !empty($p['asset']))), 'html', 'file');
        $map = SX::$ctx['pages'] ?? [];
        $want = $body['page'] ?? null;
        $sel = array_values(array_filter($pages, fn($p) => $p['id'] === $want))[0] ?? $pages[0];
        $html = $sel['html'];
        $helper = (empty($body['gate']) ? '<style id="sx-builder">[class*="agegate"],[class*="age-gate"]{display:none!important}html,body{overflow:visible!important}</style>' : '')
            . '<script>document.addEventListener("click",function(e){var a=e.target.closest("a[href]");if(!a)return;var h=a.getAttribute("href");'
            . 'if(h.charAt(0)==="#")return;e.preventDefault();parent.postMessage({sxNav:h},"*");},true);'
            . 'document.addEventListener("submit",function(e){e.preventDefault();},true);</script>';
        $html = preg_replace('#</body>#', $helper . '</body>', $html, 1);
        $warnings = [];
        foreach ($pages as $p) {
            foreach ($p['warnings'] as $w) {
                $warnings[$w] = $warnings[$w] ?? ($p['id'] ?? 'page');
            }
        }
        preg_match('#<link rel="canonical" href="([^"]*)"#', $sel['html'], $canon);
        $preset = sx_merge_tokens(sx_load_preset($cfg['preset'], $root), $cfg['tokens'] ?? []);
        $m = sx_market($cfg['market'], $root);
        api_out([
            'ok' => true,
            'pages' => array_map(fn($p) => ['id' => $p['id'], 'file' => $p['file'], 'base' => $map[$p['id']]['base'] ?? ($p['id'] ?? 'index'),
                'nav' => $map[$p['id']]['nav'] ?? 'Page', 'kb' => round(strlen($p['html']) / 1024, 1),
                'title' => preg_match('#<title>(.*?)</title>#s', $p['html'], $t) ? html_entity_decode($t[1]) : ''], $pages),
            'page' => $sel['id'], 'html' => $html, 'source' => $sel['html'],
            'warnings' => array_map(fn($w, $pg) => ['text' => $w, 'page' => $pg], array_keys($warnings), $warnings),
            'notices' => array_values(array_unique($GLOBALS['php_notices'])),
            'trace' => SX::$trace, 'weights' => $sel['weights'] ?? [], 'kb' => round(strlen($sel['html']) / 1024, 1),
            'jsonld' => $sel['jsonld'] ?? [], 'prefix' => $sel['prefix'], 'canonical' => $canon[1] ?? null, 'assets' => $assets,
            'contrast' => sx_contrast_report($preset),
            'market' => ['id' => $m['id'], 'name' => SX_MARKET_NAMES[$m['id']] ?? $m['id'], 'min_age' => $m['min_age'], 'verticals' => $m['verticals'],
                'promotions_allowed' => array_keys(array_filter($m['promotions'] ?? [], fn($v) => $v === 'allowed')), 'strip' => $m['compliance']['strip'] ?? 'optional',
                'taglines' => count(tm($m['compliance']['taglines'] ?? []) ?: []), 'domain_suffix' => $m['compliance']['brand_domain_suffix'] ?? null,
                'required_blocks' => $m['compliance']['required_blocks'] ?? [], 'demo_only' => !empty($m['demo_only'])],
        ]);

    case 'POST save':
        $path = api_site_path($body['path'] ?? null);
        $cfg = $body['config'] ?? api_fail('No config.');
        if (!empty($body['create']) && is_file("$root/$path")) {
            api_fail("$path already exists. Choose another site id or project.");
        }
        unset($cfg['_xray']);
        @mkdir(dirname("$root/$path"), 0777, true);
        file_put_contents("$root/$path", api_pretty($cfg));
        api_out(['ok' => true, 'path' => $path, 'saved' => date('H:i:s')]);

    case 'POST build':
        $path = api_site_path($body['path'] ?? null);
        $cfg = sx_json("$root/$path");
        $outDir = "$root/dist/sites/{$cfg['id']}";
        try {
            $pages = sx_build_site($cfg, $root);
        } catch (Throwable $e) {
            api_out(['ok' => false, 'error' => $e->getMessage()]);
        }
        $files = [];
        $warnings = [];
        foreach ($pages as $p) {
            @mkdir(dirname("$outDir/{$p['file']}"), 0777, true);
            file_put_contents("$outDir/{$p['file']}", $p['html']);
            $files[] = ['file' => $p['file'], 'kb' => round(strlen($p['html']) / 1024, 1)];
            array_push($warnings, ...$p['warnings']);
        }
        api_out(['ok' => true, 'id' => $cfg['id'], 'files' => $files, 'warnings' => array_values(array_unique($warnings)),
            'url' => "/site/{$cfg['id']}/", 'note' => ($cfg['urls'] ?? 'pretty') === 'pretty' ? 'Folder URLs: open the site from its root.' : null]);

    case 'POST diversity':
        $project = preg_replace('/[^a-z0-9-]/', '', $body['project'] ?? '');
        $resolved = [];
        foreach (glob("$root/sites/$project/*.json") as $f) {
            $rel = substr($f, strlen($root) + 1);
            $cfg = ($body['path'] ?? null) === $rel && !empty($body['config']) ? $body['config'] : api_json_file($f);
            if (empty($cfg['type'])) {
                continue;
            }
            try {
                $resolved[$rel] = sx_site_parts($cfg, $root);
            } catch (Throwable $e) {
                $resolved[$rel] = ['id' => $cfg['id'] ?? $rel, 'type' => $cfg['type'], 'error' => $e->getMessage()];
            }
        }
        $pairs = [];
        $keys = array_keys($resolved);
        foreach ($keys as $i => $a) {
            foreach (array_slice($keys, $i + 1) as $b) {
                if (isset($resolved[$a]['error']) || isset($resolved[$b]['error'])) {
                    continue;
                }
                $d = sx_diversity_pair($resolved[$a], $resolved[$b]);
                if ($d['comparable']) {
                    $pairs[] = ['a' => $a, 'b' => $b, 'a_id' => $resolved[$a]['id'], 'b_id' => $resolved[$b]['id']] + $d;
                }
            }
        }
        api_out(['ok' => true, 'sites' => $resolved, 'pairs' => $pairs]);

    case 'POST suggest':
        $path = api_site_path($body['path'] ?? null);
        $cfg = $body['config'] ?? api_fail('No config.');
        $project = explode('/', $path)[1];
        $siblings = [];
        foreach (api_all_sites($root) as $rel => $c) {
            if ($rel !== $path && explode('/', $rel)[1] === $project && $c['type'] === $cfg['type']) {
                try { $siblings[$c['id']] = sx_site_parts($c, $root); } catch (Throwable $e) { /* a broken sibling cannot be compared */ }
            }
        }
        if (!$siblings) {
            api_out(['ok' => true, 'current' => null, 'suggestions' => [], 'note' => 'No other site of this type in the project.']);
        }
        try { $mine = sx_site_parts($cfg, $root); } catch (Throwable $e) { api_fail($e->getMessage()); }
        $current = api_div_score($mine, $siblings);
        $bp = sx_blueprint($cfg['type'], $root);
        $multi = isset($bp['pages']);
        $cands = [];
        $usedPresets = array_column($siblings, 'preset');
        foreach (array_keys(sx_all_presets($root)) as $pr) {
            if ($pr !== $cfg['preset']) {
                $cands[] = [in_array($pr, $usedPresets, true) ? "Use the $pr style" : "Use the $pr style (no sibling uses it)", ['preset' => $pr]];
            }
        }
        foreach (array_keys($bp['angles']) as $a) {
            if ($a !== $mine['angle']) {
                $cands[] = ["Lead with the " . str_replace('-', ' ', $a) . " angle", ['angle' => $a]];
            }
        }
        // One layout change per section: every layout the block has (the engine rejects ones the angle or market does not allow).
        foreach ($mine['parts'] as $key => $variant) {
            if (in_array($key, ['footer', 'age-gate'], true)) {
                continue;
            }
            [$page, $sid] = $multi ? (str_contains($key, '.') ? explode('.', $key, 2) : ['home', $key === 'hero' ? 'hero' : $key]) : ['', $key];
            if ($multi && in_array($sid, ['header', 'sticky-cta'], true)) {
                $page = '';
            }
            $secs = $multi ? ($bp['pages'][$page]['sections'] ?? []) : $bp['sections'];
            $block = array_values(array_filter($secs, fn($x) => $x['id'] === $sid))[0]['block'] ?? $sid;
            $variants = api_json_file("$root/blocks/$block/block.json")['sx']['variants'] ?? [];
            foreach ($variants as $v) {
                if ($v === $variant) {
                    continue;
                }
                $patch = $multi && $page !== '' ? ['variants' => [$page => [$sid => $v]]] : ['variants' => [$sid => $v]];
                $cands[] = ["Switch " . ($multi && $page !== '' ? "$page · " : '') . "$sid to the $v layout", $patch];
            }
        }
        $merge = function (array $a, array $b) use (&$merge): array {
            foreach ($b as $k => $v) {
                $a[$k] = is_array($v) && isset($a[$k]) && is_array($a[$k]) ? $merge($a[$k], $v) : $v;
            }
            return $a;
        };
        $results = [];
        foreach ($cands as [$label, $patch]) {
            $try = $merge($cfg, $patch);
            SX::$warnings = [];
            try {
                $parts = sx_site_parts($try, $root);
            } catch (Throwable $e) {
                continue;
            }
            // A pin the engine overrode (not allowed for this angle or market) changes nothing: skip it.
            if (isset($patch['variants'])) {
                $warned = array_filter(SX::$warnings, fn($w) => str_contains($w, 'not allowed here'));
                if ($warned) {
                    continue;
                }
            }
            $score = api_div_score($parts, $siblings);
            if ($score < $current) {
                $results[] = ['label' => $label, 'patch' => $patch, 'fail' => $score[0], 'max' => $score[1], 'mean' => $score[2]];
            }
        }
        usort($results, fn($a, $b) => [$a['fail'], $a['max'], $a['mean']] <=> [$b['fail'], $b['max'], $b['mean']]);
        api_out(['ok' => true, 'current' => ['fail' => $current[0], 'max' => $current[1], 'mean' => $current[2]], 'tried' => count($cands), 'suggestions' => array_slice($results, 0, 5)]);

    case 'GET data/list':
        $files = [];
        foreach (['brands/operators.json', 'brands/publishers.json', 'brands/providers.json', 'brands/catalog/*.json', 'brands/promotions/*.json',
            'brands/facts/*.json', 'brands/ratings/*.json', 'brands/reviews/*.json', 'content/*/articles/*.json', 'content/*/authors.json', 'markets/*.json'] as $g) {
            foreach (glob("$root/$g") as $f) {
                $rel = substr($f, strlen($root) + 1);
                if (basename($f)[0] !== '_' && preg_match(SX_DATA_PATH, $rel)) {
                    $files[] = ['path' => $rel, 'modified' => date('Y-m-d H:i', filemtime($f))];
                }
            }
        }
        api_out(['ok' => true, 'files' => $files, 'content_dirs' => array_map(fn($d) => substr($d, strlen($root) + 1), glob("$root/content/*", GLOB_ONLYDIR))]);

    case 'GET data/file':
        $path = api_data_path($_GET['path'] ?? null);
        is_file("$root/$path") || api_fail("No file at $path.", 404);
        api_out(['ok' => true, 'path' => $path, 'data' => sx_json("$root/$path"), 'readonly' => str_starts_with($path, 'markets/')]);

    case 'POST data/save':
        $path = api_data_path($body['path'] ?? null, true);
        $data = $body['data'] ?? null;
        if (!is_array($data) || array_is_list($data) && $data !== []) {
            api_fail('Data files are JSON objects.');
        }
        if (str_contains($path, '/articles/') && isset($data['slug']) && $data['slug'] !== basename($path, '.json')) {
            api_fail('An article\'s slug must match its file name (' . basename($path, '.json') . ').');
        }
        $file = "$root/$path";
        if (!empty($body['create']) && is_file($file)) {
            api_fail("$path already exists.");
        }
        if (is_file($file)) {
            // Keep the previous version: dist/backups/<path>.<time>.json
            $bak = "$root/dist/backups/" . str_replace('/', '__', $path) . '.' . date('Ymd-His') . '.json';
            @mkdir(dirname($bak), 0777, true);
            copy($file, $bak);
        }
        @mkdir(dirname($file), 0777, true);
        file_put_contents($file, api_pretty($data));
        api_out(['ok' => true, 'path' => $path, 'saved' => date('H:i:s'), 'backup' => isset($bak) ? substr($bak, strlen($root) + 1) : null]);

    case 'POST data/check':
        $report = [];
        foreach (api_all_sites($root) as $rel => $cfg) {
            $b = api_try_build($cfg, $root);
            $report[] = ['path' => $rel, 'id' => $cfg['id'] ?? $rel, 'type' => $cfg['type'], 'ok' => $b['ok'], 'error' => $b['error'] ?? null,
                'warnings' => $b['warnings'], 'pages' => $b['ok'] ? count(array_filter($b['pages'], fn($p) => empty($p['asset']))) : 0];
        }
        api_out(['ok' => true, 'sites' => $report]);

    case 'GET export':
        $path = api_site_path($_GET['path'] ?? null);
        $kind = $_GET['kind'] ?? 'static';
        $cfg = sx_json("$root/$path");
        $id = $cfg['id'];
        $files = [];
        if ($kind === 'wordpress') {
            if (!is_dir("$root/dist/wp/acf-json")) {
                ob_start();
                include "$root/wp/generate-acf-json.php";
                ob_end_clean();
            }
            foreach (glob("$root/dist/wp/acf-json/*.json") as $f) {
                $files['acf-json/' . basename($f)] = file_get_contents($f);
            }
            $preset = sx_merge_tokens(sx_load_preset($cfg['preset'], $root), $cfg['tokens'] ?? []);
            $files['theme.json'] = json_encode(sx_theme_json($preset), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $files["site-config/$id.json"] = api_pretty($cfg);
            $files['README.txt'] = "WordPress package for $id\r\n\r\n"
                . "1. Copy the satellite-system folder into your theme as /sx (once per theme) and load wp/sx-page-template.php from functions.php.\r\n"
                . "2. ACF > Tools > Import: the files in acf-json/ (block field groups), or drop them into the theme's acf-json folder.\r\n"
                . "3. Merge theme.json into the theme's theme.json for editor colours and fonts.\r\n"
                . "4. Pages > Add New > Template: \"Satellite page\" > paste site-config/$id.json into the config box"
                . (isset(sx_blueprint($cfg['type'], $root)['pages']) ? " (one WP page per site page, with its \"Site page id\"; see docs/wordpress-quick-test.md)" : '') . ".\r\n";
        } else {
            $build = $cfg;
            if ($kind === 'preview') {
                $build['urls'] = 'files'; // flat relative links: opens straight from the unzipped folder
            }
            $b = api_try_build($build, $root);
            if (!$b['ok']) {
                http_response_code(422);
                header('Content-Type: text/plain; charset=utf-8');
                exit("This site does not build: {$b['error']}");
            }
            foreach ($b['pages'] as $p) {
                $files[$p['file']] = $p['html'];
            }
            if ($b['warnings']) {
                $files['BUILD-WARNINGS.txt'] = "Not shippable until these are fixed:\r\n- " . implode("\r\n- ", $b['warnings']) . "\r\n";
            }
        }
        $zip = sx_zip($files);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $id . '-' . preg_replace('/[^a-z]/', '', $kind) . '.zip"');
        header('Content-Length: ' . strlen($zip));
        header('Cache-Control: no-store');
        echo $zip;
        exit;

    default:
        api_fail("Unknown endpoint: $method /api/$route", 404);
}
