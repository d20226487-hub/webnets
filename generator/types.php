<?php
/**
 * Site types: each type's blueprint (blueprints/<type>.json: pages, sections, rules, angles, frame) and its AI notes
 * (ai/types/<type>.md), managed from the generator. A save is test-built in the demo market before it is kept (a
 * blueprint that no longer builds is put back), and every save keeps the previous version in
 * <cache>/type-history/<type>/ with a line in type-history/log.jsonl. Copies ("based_on") can be made, hidden and deleted.
 */

const GEN_TYPE_PROBE_MARKET = 'xx'; // every vertical and promotion allowed: a type that builds anywhere builds here
const GEN_TYPE_PROBE_IDS = ['u0-probe-alpha', 'u0-probe-beta', 'u0-probe-gamma'];

function gen_type_file(string $t): string
{
    global $GEN_ROOT;
    return "$GEN_ROOT/blueprints/$t.json";
}

function gen_type_history_dir(string $sub = ''): string
{
    $d = gen_cache_dir('type-history') . ($sub !== '' ? "/$sub" : '');
    is_dir($d) || @mkdir($d, 0777, true);
    return $d;
}

function gen_type_notes(string $t): string
{
    global $GEN_ROOT;
    return is_file("$GEN_ROOT/ai/types/$t.md") ? file_get_contents("$GEN_ROOT/ai/types/$t.md") : '';
}

/** Blueprint as written: two-space indents like the hand-written files. */
function gen_type_json(array $bp): string
{
    $j = json_encode($bp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return preg_replace_callback('/^( +)/m', fn($m) => str_repeat(' ', intdiv(strlen($m[1]), 2)), $j) . "\n";
}

/** Every block: title, designs, what each design needs (a vertical), and whether it is a frame block. */
function gen_type_blocks(): array
{
    global $GEN_ROOT;
    $out = [];
    foreach (glob("$GEN_ROOT/blocks/*/block.json") as $f) {
        $b = basename(dirname($f));
        if ($b[0] === '_') {
            continue;
        }
        $j = sx_json($f);
        $out[$b] = ['title' => preg_replace('/^SX /', '', $j['title'] ?? $b), 'variants' => array_values($j['sx']['variants'] ?? []),
            'requires' => $j['sx']['variant_requires'] ?? (object)[], 'description' => $j['description'] ?? ''];
    }
    ksort($out);
    return $out;
}

/** Pages of a blueprint as [page id => page]; a one-page type is one page with the id "". */
function gen_type_pages(array $bp): array
{
    return isset($bp['pages']) ? $bp['pages'] : ['' => $bp];
}

/** How many section orders a page allows (its middle zone, under its order rules). */
function gen_type_orders(array $page): int
{
    $middle = array_column(array_filter($page['sections'] ?? [], fn($s) => ($s['zone'] ?? '') === 'middle'), 'id');
    if (count($middle) > 8) {
        return -1; // too many to count quickly
    }
    return count(array_filter(sx_permutations($middle), fn($o) => sx_order_ok($o, $page['order_rules'] ?? [])));
}

/** Checks a blueprint before it is saved. Returns [path => problem]. */
function gen_type_validate(array $bp, array $blocks): array
{
    $e = [];
    $multi = isset($bp['pages']);
    if ($multi && !isset($bp['pages']['home'])) {
        $e['pages'] = 'A multi-page type needs its home page.';
    }
    $slugs = [];
    foreach (gen_type_pages($bp) as $pid => $p) {
        $at = $multi ? "pages.$pid" : '';
        $pre = $at === '' ? '' : "$at.";
        if ($multi && $pid !== 'home') {
            if (!in_array($p['rule'] ?? '', ['required', 'optional'], true)) {
                $e["{$pre}rule"] = 'A page is required or optional.';
            }
            if (isset($p['slug']) && !preg_match('/^[a-z0-9-]+$/', $p['slug'])) {
                $e["{$pre}slug"] = 'Lowercase letters, digits and dashes.';
            } elseif (isset($p['slug']) && isset($slugs[$p['slug']])) {
                $e["{$pre}slug"] = "The slug '{$p['slug']}' is used by another page.";
            }
            $slugs[$p['slug'] ?? $pid] = 1;
        }
        $ids = [];
        if (empty($p['sections'])) {
            $e["{$pre}sections"] = 'A page needs at least one section.';
        }
        foreach ($p['sections'] ?? [] as $i => $s) {
            $sp = "{$pre}sections.$i";
            if (!preg_match('/^[a-z0-9-]+$/', (string)($s['id'] ?? ''))) {
                $e["$sp.id"] = 'Section ids are lowercase letters, digits and dashes.';
            } elseif (isset($ids[$s['id']])) {
                $e["$sp.id"] = "Two sections are called '{$s['id']}'.";
            }
            $ids[$s['id'] ?? ''] = $s;
            if (!isset($blocks[$s['block'] ?? ''])) {
                $e["$sp.block"] = "No block '" . ($s['block'] ?? '') . "'.";
                continue;
            }
            if (!in_array($s['zone'] ?? '', ['top', 'middle', 'bottom'], true)) {
                $e["$sp.zone"] = 'Zone is top, middle or bottom.';
            }
            if (!in_array($s['rule'] ?? '', ['required', 'optional'], true)) {
                $e["$sp.rule"] = 'Required or optional.';
            }
            if (isset($s['variants']) && (!$s['variants'] || array_diff($s['variants'], $blocks[$s['block']]['variants']))) {
                $e["$sp.variants"] = 'Choose at least one of the block\'s designs.';
            }
            if (isset($s['variant']) && !in_array($s['variant'], $blocks[$s['block']]['variants'], true)) {
                $e["$sp.variant"] = "The block has no design '{$s['variant']}'.";
            }
        }
        $middle = array_keys(array_filter($ids, fn($s) => ($s['zone'] ?? '') === 'middle'));
        foreach ($p['order_rules'] ?? [] as $i => $r) {
            $rp = "{$pre}order_rules.$i";
            if (!in_array($r['rule'] ?? '', ['before', 'not_first'], true)) {
                $e[$rp] = 'A rule is "before" or "not first".';
            } elseif (!in_array($r['a'] ?? '', $middle, true) || ($r['rule'] === 'before' && !in_array($r['b'] ?? '', $middle, true))) {
                $e[$rp] = 'Order rules can only name sections of the middle zone.';
            }
        }
        if (!isset($e["{$pre}sections"]) && $middle && gen_type_orders($p) === 0) {
            $e["{$pre}order_rules"] = 'No order of the sections meets all these rules.';
        }
        foreach ($p['angles'] ?? ($at === '' ? [] : []) as $a => $x) {
            gen_type_validate_angle($e, "{$pre}angles.$a", $x, $ids, $blocks, $bp);
        }
    }
    foreach ($bp['angles'] ?? [] as $a => $x) {
        $home = $multi ? ($bp['pages']['home']['sections'] ?? []) : ($bp['sections'] ?? []);
        gen_type_validate_angle($e, "angles.$a", $x, array_column($home, null, 'id'), $blocks, $bp);
    }
    if (empty($bp['angles']) && empty($bp['pages']['home']['angles'])) {
        $e['angles'] = 'Keep at least one angle.';
    }
    return $e;
}

function gen_type_validate_angle(array &$e, string $at, array $x, array $ids, array $blocks, array $bp): void
{
    if ($bad = array_diff($x['optional'] ?? [], array_keys($ids))) {
        $e["$at.optional"] = 'Unknown section: ' . implode(', ', $bad) . '.';
    }
    foreach ($x['variants'] ?? [] as $sid => $list) {
        if (!isset($ids[$sid])) {
            $e["$at.variants.$sid"] = "No section '$sid' on this page.";
        } elseif (!$list || array_diff((array)$list, $blocks[$ids[$sid]['block']]['variants'] ?? [])) {
            $e["$at.variants.$sid"] = 'Choose at least one of the block\'s designs.';
        }
    }
    if (isset($x['pages']) && ($bad = array_diff($x['pages'], array_keys($bp['pages'] ?? [])))) {
        $e["$at.pages"] = 'Unknown page: ' . implode(', ', $bad) . '.';
    }
    if (isset($x['requires_vertical']) && !in_array($x['requires_vertical'], ['sports', 'casino'], true)) {
        $e["$at.requires_vertical"] = 'Sports or casino.';
    }
}

/** Test builds of a type in the demo market: the plain build and each option of its axes (a few at a time: gen_run_probes()). */
function gen_type_probe(string $t): array
{
    global $GEN_ROOT;
    $cat = gen_catalog()[$t];
    $runs = [['', '']];
    $demo = sx_json("$GEN_ROOT/sites/demos/{$cat['source']}.json");
    foreach ($cat['axes'] as $axis => $_) {
        foreach (array_keys(gen_demo_axes($demo)[$axis] ?? []) as $o) {
            $runs[] = [$axis, (string)$o];
        }
    }
    $ran = gen_run_probes(array_map(fn($r) => [GEN_TYPE_PROBE_MARKET, $t, implode(',', GEN_TYPE_PROBE_IDS), $r[0], $r[1]], $runs));
    $out = ['base' => null, 'options' => []];
    foreach ($ran as $k => [$res, $err]) {
        $r = json_decode($res, true);
        $msg = is_array($r) ? $r['error'] : trim($err ?: $res ?: 'The test build crashed.');
        if ($runs[$k][0] === '') {
            $out['base'] = $msg;
        } elseif ($msg !== null) {
            $out['options'][$runs[$k][0]][$runs[$k][1]] = $msg;
        }
    }
    return $out;
}

/** Writes a blueprint (atomic) and its notes; the previous version to the history and a log line. */
function gen_type_write(string $t, ?array $bp, ?string $notes, string $action, bool $log = true): ?string
{
    global $GEN_ROOT;
    $file = gen_type_file($t);
    $old = is_file($file) ? sx_json($file) : null;
    $oldNotes = gen_type_notes($t);
    $stamp = date('Ymd-His') . '-' . substr(md5(uniqid('', true)), 0, 4);
    $keep = json_encode(['blueprint' => $old, 'notes' => $oldNotes], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($old !== null && $log) {
        file_put_contents(gen_type_history_dir($t) . "/$stamp.json", $keep);
    }
    if ($bp === null) {
        file_put_contents(gen_type_history_dir('_deleted') . "/$t-$stamp.json", $keep);
        unlink($file);
        @unlink("$GEN_ROOT/ai/types/$t.md");
        $changes = ['(type deleted)'];
    } else {
        $tmp = "$file." . getmypid() . '.tmp';
        file_put_contents($tmp, gen_type_json($bp));
        rename($tmp, $file);
        clearstatcache();
        $changes = $old === null ? ['(new type)'] : gen_market_changes($old, $bp);
        if ($notes !== null && $notes !== $oldNotes) {
            is_dir("$GEN_ROOT/ai/types") || mkdir("$GEN_ROOT/ai/types", 0777, true);
            file_put_contents("$GEN_ROOT/ai/types/$t.md", $notes);
            $changes[] = 'ai_notes';
        }
    }
    if ($log) {
        file_put_contents(gen_type_history_dir() . '/log.jsonl', json_encode(['time' => date('c'), 'type' => $t, 'action' => $action,
            'version' => $old !== null ? $stamp : null, 'changes' => $changes], JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    }
    return $old !== null ? $stamp : null;
}

function gen_type_log(?string $t = null): array
{
    $f = gen_type_history_dir() . '/log.jsonl';
    $out = [];
    foreach (is_file($f) ? file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [] as $line) {
        $x = json_decode($line, true);
        if ($x && ($t === null || $x['type'] === $t)) {
            $out[] = $x;
        }
    }
    return array_reverse($out);
}

function gen_type_version(string $t, string $v): ?array
{
    $f = gen_type_history_dir($t) . '/' . preg_replace('/[^A-Za-z0-9-]/', '', $v) . '.json';
    return is_file($f) ? json_decode(file_get_contents($f), true) : null;
}

/** The oldest version on record (the type as it was before the first change here), else the current one. */
function gen_type_original(string $t): array
{
    $files = glob(gen_type_history_dir($t) . '/*.json');
    sort($files);
    return $files ? (json_decode(file_get_contents($files[0]), true)['blueprint'] ?? sx_json(gen_type_file($t))) : sx_json(gen_type_file($t));
}

/** Who uses a type: jobs and their sites, and demos. */
function gen_type_usage(PDO $db, string $t): array
{
    global $GEN_ROOT;
    $q = $db->prepare('SELECT j.id, j.name, p.name AS project, COUNT(s.id) AS sites FROM sites s JOIN jobs j ON j.id = s.job_id JOIN projects p ON p.id = j.project_id
        WHERE s.type = ? GROUP BY j.id ORDER BY j.id DESC');
    $q->execute([$t]);
    $jobs = $q->fetchAll();
    $demos = [];
    foreach (glob("$GEN_ROOT/sites/demos/*.json") as $f) {
        if ((sx_json($f)['type'] ?? '') === $t) {
            $demos[] = basename($f, '.json');
        }
    }
    return ['jobs' => $jobs, 'sites' => array_sum(array_column($jobs, 'sites')), 'demos' => $demos];
}

/** Built sites of this type made before its last change. */
function gen_type_stale_sites(PDO $db, string $t): int
{
    global $GEN_ROOT;
    $changed = @filemtime(gen_type_file($t)) ?: 0;
    $q = $db->prepare("SELECT id FROM sites WHERE type = ? AND status = 'built'");
    $q->execute([$t]);
    $n = 0;
    foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $sid) {
        $m = @filemtime("$GEN_ROOT/dist/generated/$sid/index.html");
        $n += $m && $m < $changed ? 1 : 0;
    }
    return $n;
}

/** Summary of a type for the list and the editor's header. */
function gen_type_summary(string $t, array $bp): array
{
    $pages = gen_type_pages($bp);
    return ['pages' => isset($bp['pages']) ? count($pages) : 1, 'sections' => array_sum(array_map(fn($p) => count($p['sections'] ?? []), $pages)),
        'angles' => count($bp['angles'] ?? []) + array_sum(array_map(fn($p) => count($p['angles'] ?? []), $pages)),
        'requires_vertical' => $bp['requires_vertical'] ?? null];
}

/** The /types API. $seg: path segments after "types". */
function gen_types_api(PDO $db, array $seg, string $method): void
{
    global $GEN_ROOT;
    $t = $seg[1] ?? '';
    $sub = $seg[2] ?? '';
    $cat = gen_catalog();
    if ($t === '' && $method === 'GET') {
        $list = [];
        foreach ($cat as $id => $c) {
            $bp = sx_json(gen_type_file($id));
            $u = gen_type_usage($db, $id);
            $list[] = ['id' => $id, 'label' => $c['label'], 'base' => $c['base'], 'copy' => $c['base'] !== $id, 'hidden' => !empty($c['hidden']),
                'jobs' => count($u['jobs']), 'sites' => $u['sites'], 'demos' => count($u['demos']), 'changed' => date('Y-m-d H:i', filemtime(gen_type_file($id)))] + gen_type_summary($id, $bp);
        }
        gen_json(['types' => $list, 'log' => array_slice(gen_type_log(), 0, 30)]);
        return;
    }
    if ($t === '' && $method === 'POST') { // a copy of a type
        $in = gen_body();
        $new = strtolower(trim((string)($in['id'] ?? '')));
        $from = (string)($in['from'] ?? '');
        if (!preg_match('/^[a-z][a-z0-9-]{1,30}$/', $new)) {
            gen_json(['error' => 'Use lowercase Latin letters, digits and dashes for the type id (2–31, starting with a letter), e.g. "satellite-short".'], 422);
            return;
        }
        if (is_file(gen_type_file($new))) {
            gen_json(['error' => "Type '$new' already exists."], 409);
            return;
        }
        if (!isset($cat[$from])) {
            gen_json(['error' => 'Choose a type to start from.'], 422);
            return;
        }
        $bp = sx_json(gen_type_file($from));
        $bp['type'] = $new;
        $bp['based_on'] = $cat[$from]['base'];
        $bp['generator'] = ['label' => trim((string)($in['label'] ?? '')) ?: $new] + (!empty($in['hidden']) ? ['hidden' => true] : []);
        gen_type_write($new, $bp, gen_type_notes($from), "created from $from");
        gen_json(['ok' => true, 'id' => $new]);
        return;
    }
    if (!isset($cat[$t])) {
        gen_json(['error' => "No site type '$t'."], 404);
        return;
    }
    $bp = sx_json(gen_type_file($t));
    if ($sub === '' && $method === 'GET') {
        $orig = gen_type_original($t);
        $parent = $cat[$t]['base'] !== $t ? sx_json(gen_type_file($cat[$t]['base'])) : $orig;
        $orders = [];
        foreach (gen_type_pages($bp) as $pid => $p) {
            $orders[$pid] = gen_type_orders($p);
        }
        $spares = []; // sections the type had (or its base type has) that are not on the page now: they can be added back
        foreach ([$orig, $parent] as $src) {
            foreach (gen_type_pages($src) as $pid => $p) {
                $have = array_column(gen_type_pages($bp)[$pid]['sections'] ?? [], 'id');
                foreach ($p['sections'] ?? [] as $s) {
                    if (!in_array($s['id'], $have, true)) {
                        $spares[$pid][$s['id']] = $s;
                    }
                }
            }
        }
        $markets = [];
        foreach (array_keys(gen_market_files()) as $m) { // what the markets' last checks say about this type (cached; no new builds)
            $c = @json_decode((string)@file_get_contents(gen_cache_dir() . "/availability-$m.json"), true);
            $markets[$m] = $c['types'][$t] ?? null;
        }
        gen_json(['type' => $t, 'blueprint' => $bp, 'notes' => gen_type_notes($t), 'catalog' => $cat[$t], 'usage' => gen_type_usage($db, $t),
            'stale' => gen_type_stale_sites($db, $t), 'log' => gen_type_log($t), 'blocks' => gen_type_blocks(), 'orders' => $orders,
            'spares' => (object)array_map('array_values', $spares), 'markets' => $markets, 'market_pages' => array_map(fn($m) => $m['required_pages'] ?? [], gen_market_files()),
            'changed' => date('Y-m-d H:i', filemtime(gen_type_file($t)))]);
        return;
    }
    if ($sub === '' && $method === 'PUT') {
        $in = gen_body();
        $new = $in['blueprint'] ?? null;
        if (!is_array($new)) {
            gen_json(['error' => 'Nothing to save.'], 422);
            return;
        }
        $new['type'] = $t; // the id never changes
        if ($cat[$t]['base'] !== $t) {
            $new['based_on'] = $cat[$t]['base'];
        }
        $notes = is_string($in['notes'] ?? null) ? $in['notes'] : null;
        if (!gen_market_changes($bp, $new) && ($notes === null || $notes === gen_type_notes($t))) {
            gen_json(['ok' => true, 'changes' => []]);
            return;
        }
        if ($err = gen_type_validate($new, gen_type_blocks())) {
            gen_json(['error' => 'Some fields need fixing.', 'fields' => $err], 422);
            return;
        }
        // try it: write, test-build, and put the old one back if the type no longer builds
        $lock = fopen(gen_type_history_dir() . '/.lock', 'c');
        flock($lock, LOCK_EX);
        try {
            $before = gen_type_probe($t)['base'];
            gen_type_write($t, $new, null, 'test', false);
            $probe = gen_type_probe($t);
            if ($probe['base'] !== null && $before === null) {
                gen_type_write($t, $bp, null, 'test', false);
                gen_json(['error' => 'Not saved: with these changes the type no longer builds. ' . $probe['base'], 'probe' => $probe], 422);
                return;
            }
            gen_type_write($t, $bp, null, 'test', false); // back to the old file, then the real save (history and log)
            gen_type_write($t, $new, $notes, 'edited');
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        gen_json(['ok' => true, 'changes' => gen_type_log($t)[0]['changes'] ?? [], 'probe' => $probe]);
        return;
    }
    if ($sub === 'visibility' && $method === 'PUT') { // shown in the job form or not
        $hidden = !empty(gen_body()['hidden']);
        $bp['generator'] = array_filter(['hidden' => $hidden] + ($bp['generator'] ?? []));
        if ($hidden) {
            $bp['generator']['hidden'] = true;
        } else {
            unset($bp['generator']['hidden']);
        }
        if (!$bp['generator']) {
            unset($bp['generator']);
        }
        gen_type_write($t, $bp, null, $hidden ? 'hidden from the job form' : 'shown in the job form');
        gen_json(['ok' => true]);
        return;
    }
    if ($sub === '' && $method === 'DELETE') {
        if ($cat[$t]['base'] === $t) {
            gen_json(['error' => 'Built-in types cannot be deleted (the engine and the demos use them). Hide it from the job form instead.'], 409);
            return;
        }
        $u = gen_type_usage($db, $t);
        if ($u['jobs'] || $u['demos']) {
            gen_json(['error' => 'This type is in use by ' . count($u['jobs']) . ' job(s). Delete those first.'], 409);
            return;
        }
        gen_type_write($t, null, null, 'deleted');
        gen_json(['ok' => true]);
        return;
    }
    if ($sub === 'test' && $method === 'POST') { // test-build the saved type now
        gen_json(gen_type_probe($t));
        return;
    }
    if ($sub === 'version' && $method === 'GET') {
        $v = gen_type_version($t, (string)($_GET['v'] ?? ''));
        $v ? gen_json($v + ['changes' => gen_market_changes($v['blueprint'] ?? [], $bp)]) : gen_json(['error' => 'No such version.'], 404);
        return;
    }
    if ($sub === 'restore' && $method === 'POST') {
        $ver = (string)(gen_body()['version'] ?? '');
        $v = gen_type_version($t, $ver);
        if (!$v || !$v['blueprint']) {
            gen_json(['error' => 'No such version.'], 404);
            return;
        }
        gen_type_write($t, $v['blueprint'], $v['notes'] ?? '', "restored version $ver");
        gen_json(['ok' => true]);
        return;
    }
    gen_json(['error' => 'Not found.'], 404);
}
