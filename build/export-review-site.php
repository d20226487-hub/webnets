<?php
/**
 * Review link for a multi-page site: builds it with "files" URLs (payments.html, relative), so the
 * navigation works when the pages are published together as one Artifact.
 *
 * A config with "variations" (axes of config overrides, as in build/variations.php) is exported once per
 * combination: the first combination as the plain files, every other one with a file prefix (e.g. with-list-*.html).
 * Every page gets a demo bar on top that opens the same page in another combination (or that combination's home
 * when it has no such page, e.g. a smaller page set).
 *
 * An option may carry "_with": {"<other axis>": {"<option>": {overrides}}}, applied when both options are chosen
 * (after every axis), e.g. rank "payouts" + product "sports" gets sportsbook titles.
 *
 * Usage: php build/export-review-site.php sites/demos/<id>.json
 * Writes dist/review/<id>.html (home, wrapper stripped) and dist/review/<id>/*.html (every page as a full document).
 * Publish <id>.html as the Artifact page and the other pages (not index.html) through `files`: the Artifact
 * serves its main page as index.html, which is where the other pages link back to.
 */

$root = dirname(__DIR__);
require "$root/lib/page.php";
require "$root/lib/devspec.php";

$path = $argv[1] ?? null;
if (!$path) {
    fwrite(STDERR, "Usage: php build/export-review-site.php <site.json>\n");
    exit(2);
}
$base = sx_json($path);
$base['urls'] = 'files';
$base['_devspec'] = true; // block markers + data for the developer skeleton view
$axes = $base['variations'] ?? [];
$extraLabels = $base['variation_labels'] ?? [];
unset($base['variations'], $base['variation_labels']);
$locale = $base['locale'] ?? 'en';
// SX_REVIEW_OUT=dist/generated (the network generator): pages go to <out>/<id>/, without the gallery wrapper copy
$out = getenv('SX_REVIEW_OUT') ?: '';
$dir = $out ? "$root/" . trim($out, '/') . "/{$base['id']}" : "$root/dist/review/{$base['id']}";
@mkdir($dir, 0777, true);
foreach (glob("$dir/*.{html,txt}", GLOB_BRACE) as $old) {
    unlink($old);
}

function sx_var_merge(array $a, array $b): array
{
    foreach ($b as $k => $v) {
        $a[$k] = is_array($v) && isset($a[$k]) && is_array($a[$k]) && !array_is_list($v) ? sx_var_merge($a[$k], $v) : $v;
    }
    return $a;
}

$combos = [[]];
foreach ($axes as $axis => $options) {
    $next = [];
    foreach ($combos as $c) {
        foreach (array_keys($options) as $o) {
            $next[] = $c + [$axis => $o];
        }
    }
    $combos = $next;
}
$prefix = fn(array $c) => $c === $combos[0] ? '' : implode('-', $c) . '-';
$L = ['_title' => ['en' => 'Template variations', 'ru' => 'Вариации шаблона', 'pt' => 'Variações do modelo'][$locale] ?? 'Template variations'];
foreach ($extraLabels as $k => $v) {
    $L[$k] = is_array($v) ? ($v[$locale] ?? reset($v)) : $v;
}

// build every combination first, so the bar knows which pages each one has
$sets = [];
foreach ($combos as $c) {
    $cfg = $base;
    foreach ($c as $axis => $o) {
        $cfg = sx_var_merge($cfg, array_diff_key($axes[$axis][$o], ['_with' => 1]));
    }
    // Cross-axis overrides: an option's "_with": {"<axis>": {"<option>": {...}}} applies when that option is chosen too
    // (e.g. rank "payouts" + product "sports" gets sportsbook titles). Applied after every axis, so they win.
    foreach ($c as $axis => $o) {
        foreach ($axes[$axis][$o]['_with'] ?? [] as $other => $byOption) {
            if (isset($c[$other], $byOption[$c[$other]])) {
                $cfg = sx_var_merge($cfg, $byOption[$c[$other]]);
            }
        }
    }
    $cfg['file_prefix'] = $prefix($c);
    $pages = [];
    foreach (sx_build_site($cfg, $root) as $p) {
        if (empty($p['asset'])) {
            $pages[substr($p['file'], strlen($cfg['file_prefix']))] = $p;
            foreach ($p['warnings'] as $w) {
                fwrite(STDERR, ($cfg['file_prefix'] ?: 'base ') . ": $w\n");
            }
        }
    }
    // Colours in the browser (a demo whose switchers already change colours keeps those): the same pages with the
    // neutral and brand looks, kept as colour overrides only
    $pal = [];
    if (!$out && !isset($axes['palette']) && !isset($axes['look']) && empty($cfg['look'])) { // gallery only: generated sites have no variations panel
        foreach (['neutral', 'brand'] as $look) {
            foreach (sx_build_site(['look' => $look] + $cfg, $root) as $ap) {
                $k = substr($ap['file'], strlen($cfg['file_prefix']));
                if (empty($ap['asset']) && isset($pages[$k])) {
                    $pal[$k][$look] = sx_review_palette($pages[$k]['html'], $ap['html']);
                }
            }
        }
    }
    $sets[] = [$c, $pages, $pal];
}

/**
 * Does a switcher option change anything? Option $o of $axis, seen from combination $c, does nothing when the target
 * combination has the same pages and every one of them is the same (its own file prefix aside). Such a chip is left out.
 */
$setOf = function (array $c) use ($sets): ?array {
    foreach ($sets as $s) {
        if ($s[0] === $c) {
            return $s;
        }
    }
    return null;
};
$changes = function (array $c, array $t) use ($setOf, $prefix): bool {
    static $memo = [];
    $key = json_encode([$c, $t]);
    if (isset($memo[$key])) {
        return $memo[$key];
    }
    [, $a] = $setOf($c);
    [, $b] = $setOf($t) ?? [null, []];
    if (array_keys($a) != array_keys($b)) {
        return $memo[$key] = true;
    }
    $own = fn(string $html, string $pfx) => $pfx === '' ? $html : str_replace(['"' . $pfx, "'" . $pfx], ['"', "'"], $html); // links to its own files
    foreach ($a as $k => $p) {
        if ($own($p['html'], $prefix($c)) !== $own($b[$k]['html'], $prefix($t))) {
            return $memo[$key] = true;
        }
    }
    return $memo[$key] = false;
};
// colour switchers (an axis offering neutral and brand colours) come after the content ones, as the live Colours row does
$isColour = fn(array $options) => isset($options['neutral'], $options['brand']);
$axes = array_filter($axes, fn($o) => !$isColour($o)) + array_filter($axes, $isColour);

$bar = function (array $current, string $page, string $live = '') use ($axes, $L, $sets, $prefix, $changes): string {
    $rows = '';
    foreach ($axes as $axis => $options) {
        $links = '';
        $shown = 0;
        foreach (array_keys($options) as $o) {
            $target = array_merge($current, [$axis => $o]);
            if ($current[$axis] !== $o && !$changes($current, $target)) {
                continue;
            }
            $shown++;
            $has = false;
            foreach ($sets as [$c, $pages, $pal]) {
                if ($c === $target) {
                    $has = isset($pages[$page]);
                }
            }
            $href = $prefix($target) . ($has ? $page : 'index.html');
            $links .= '<a href="' . $href . '"' . ($current[$axis] === $o ? ' aria-current="true"' : '') . '>' . htmlspecialchars($L[$o] ?? $o) . '</a>';
        }
        if ($shown < 2) { // only the current option is left: nothing to switch here
            continue;
        }
        $rows .='<div class="vbar-row"><span>' . htmlspecialchars($L[$axis] ?? $axis) . '</span><nav>' . $links . '</nav></div>';
    }
    return '<style>.vbar{position:relative;z-index:2147483000;display:flex;flex-wrap:wrap;align-items:center;gap:6px 18px;padding:8px 16px;background:#111318;color:#E8EAF0;font:500 13px/1.3 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}'
        . '.vbar b{font-weight:600;color:#9AA3B5;font-size:11px;text-transform:uppercase;letter-spacing:.08em}.vbar-row{display:flex;align-items:center;gap:8px;min-width:0}'
        . '.vbar-row>span{color:#9AA3B5;font-size:12px}.vbar nav{display:flex;gap:4px;overflow-x:auto;scrollbar-width:none}.vbar nav::-webkit-scrollbar{display:none}'
        . '.vbar a{flex:none;padding:5px 10px;border-radius:999px;color:#E8EAF0;text-decoration:none;background:#1F232C;white-space:nowrap}.vbar a:hover{background:#2C3240}'
        . '.vbar a[aria-current]{background:#E8EAF0;color:#111318}'
        . '@media(max-width:640px){.vbar{gap:6px;padding:8px 12px}.vbar b{display:none}.vbar-row{width:100%}.vbar-row>span{flex:none;width:84px}}</style>'
        . sx_review_phone_guard() . '<div class="vbar" role="region" aria-label="' . htmlspecialchars($L['_title']) . '"><b>' . htmlspecialchars($L['_title']) . '</b>'
        . sx_review_vbar_toggle($L['_title'], implode(' · ', array_map(fn($o) => $L[$o] ?? $o, $current))) . $rows . $live . '</div>';
};

require_once "$root/lib/aibrief.php";
foreach ($sets as [$c, $pages, $pal]) {
    // AI brief for the whole site in this combination, next to its pages (one-page sites too: it is what gets downloaded);
    // the developer panel links it only when there are several pages, a one-page site's page brief being the same thing
    $briefFile = $prefix($c) . 'ai-brief.txt';
    file_put_contents("$dir/$briefFile", sx_ai_brief($pages, null, false, true));
    $brief = count($pages) > 1 ? $briefFile : null;
    foreach ($pages as $page => $p) {
        $rowsScope = $out ? null : sx_review_rows_scope($p['html']); // Rows of cards: only on pages with card rows it changes
        $html = str_replace('</body>', sx_review_nav_script() . sx_review_device_toggle($p['file'], $locale) . sx_review_devspec($p, $pages, $locale, $p['prefix'], $brief) . '</body>', $p['html']);
        if (!$out) { // the variations panel is the gallery's; generated sites are built as they are
            $html = preg_replace('#<body([^>]*)>#', '<body$1>' . $bar($c, $page, sx_review_live_switches($pal[$page] ?? [], $locale, $rowsScope)), $html, 1);
        }
        file_put_contents("$dir/{$p['file']}", sx_strip_comments($html)); // review additions too (panel, phone view)
        echo "dist/review/{$base['id']}/{$p['file']}\n";
    }
}
if ($out) {
    exit(0);
}
passthru(PHP_BINARY . ' ' . escapeshellarg("$root/build/export-review.php") . ' ' . escapeshellarg("$dir/index.html") . ' '
    . escapeshellarg("$root/dist/review/{$base['id']}.html") . ' ' . escapeshellarg($argv[2] ?? $base['site']['name'] ?? $base['id']));
