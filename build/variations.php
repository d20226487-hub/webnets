<?php
/**
 * Variation set for a one-page demo: every combination of the config's "variations" axes, each built as its own
 * page, with a demo bar on top that links the variations together. For showing how far one template stretches.
 *
 *   "variations": {
 *     "platform": { "both": {}, "ios": { "platform": "ios", "content": {...} }, "android": { "platform": "android", ... } },
 *     "look":     { "preset": {}, "neutral": { "look": "neutral" }, "brand": { "look": "brand" }, ... }
 *   }
 * Each option is a config override (objects merge, lists replace). The first option of every axis is the main page.
 *
 * Usage: php build/variations.php sites/demos/<id>.json
 * Writes dist/review/<id>.html (main variation, wrapper stripped) and dist/review/<id>/<option>-<option>.html.
 * Publish <id>.html as the Artifact page and the other files through `files`.
 *
 * An option may carry "_with": {"<other axis>": {"<option>": {overrides}}}, applied when both options are chosen
 * (after every axis), e.g. rank "payouts" + product "sports" gets sportsbook titles.
 */

$root = dirname(__DIR__);
require "$root/lib/page.php";
require "$root/lib/devspec.php";

$path = $argv[1] ?? null;
if (!$path) {
    fwrite(STDERR, "Usage: php build/variations.php <site.json>\n");
    exit(2);
}
$base = sx_json($path);
$axes = $base['variations'] ?? throw new RuntimeException('The config has no "variations".');
unset($base['variations']);
$extraLabels = $base['variation_labels'] ?? [];
unset($base['variation_labels']);
$base['urls'] = 'files';
$base['_devspec'] = true; // block markers + data for the developer skeleton view

function sx_var_merge(array $a, array $b): array
{
    foreach ($b as $k => $v) {
        $a[$k] = is_array($v) && isset($a[$k]) && is_array($a[$k]) && !array_is_list($v) ? sx_var_merge($a[$k], $v) : $v;
    }
    return $a;
}

// every combination, in axis order; the first one is the main page (index.html in the Artifact)
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
$file = fn(array $c) => $c === $combos[0] ? 'index.html' : implode('-', $c) . '.html';

$labels = [
    'en' => ['_title' => 'Template variations', 'platform' => 'Platform', 'look' => 'Page look', 'palette' => 'Colours', 'both' => 'iOS + Android', 'ios' => 'iOS only', 'android' => 'Android only',
        'preset' => 'Site style', 'neutral' => 'Neutral', 'brand' => 'Brand colours', 'play' => 'Google Play style', 'appstore' => 'App Store style'],
    'ru' => ['_title' => 'Вариации шаблона', 'platform' => 'Платформа', 'look' => 'Вид страницы', 'palette' => 'Цвета', 'both' => 'iOS + Android', 'ios' => 'Только iOS', 'android' => 'Только Android',
        'preset' => 'Стиль сайта', 'neutral' => 'Нейтральный', 'brand' => 'Цвета бренда', 'play' => 'Стиль Google Play', 'appstore' => 'Стиль App Store'],
    'pt' => ['_title' => 'Variações do modelo', 'platform' => 'Plataforma', 'look' => 'Visual da página', 'palette' => 'Cores', 'both' => 'iOS + Android', 'ios' => 'Só iOS', 'android' => 'Só Android',
        'preset' => 'Estilo do site', 'neutral' => 'Neutro', 'brand' => 'Cores da marca', 'play' => 'Estilo Google Play', 'appstore' => 'Estilo App Store'],
];
$L = $labels[$base['locale'] ?? 'en'] ?? $labels['en'];
foreach ($extraLabels as $k => $v) { // per-demo labels: {"key": {"en": ..., "pt": ...}}
    $L[$k] = is_array($v) ? ($v[$base['locale'] ?? 'en'] ?? reset($v)) : $v;
}

// colour switchers (an axis offering neutral and brand colours) come after the content ones, as the live Colours row does
$isColour = fn(array $options) => isset($options['neutral'], $options['brand']);
$shownAxes = array_filter($axes, fn($o) => !$isColour($o)) + array_filter($axes, $isColour);
$built = []; // file => page html, filled before the bars are made

/**
 * The demo bar: not part of the site, so it carries its own scoped styles and stays out of the site's tokens.
 * A chip whose page is the same as the current one (the option changes nothing here) is left out, and so is a row
 * with nothing left to switch to.
 */
$bar = function (array $current, string $live = '') use ($shownAxes, $L, $file, &$built): string {
    $rows = '';
    foreach ($shownAxes as $axis => $options) {
        $links = '';
        $shown = 0;
        foreach (array_keys($options) as $o) {
            $target = array_merge($current, [$axis => $o]);
            $on = $current[$axis] === $o;
            if (!$on && ($built[$file($target)] ?? null) === $built[$file($current)]) {
                continue;
            }
            $shown++;
            $links .= '<a href="' . $file($target) . '"' . ($on ? ' aria-current="true"' : '') . '>' . htmlspecialchars($L[$o] ?? $o) . '</a>';
        }
        if ($shown < 2) {
            continue;
        }
        $rows .='<div class="vbar-row"><span>' . htmlspecialchars($L[$axis] ?? $axis) . '</span><nav>' . $links . '</nav></div>';
    }
    return '<style>.vbar{position:relative;z-index:2147483000;display:flex;flex-wrap:wrap;align-items:center;gap:6px 18px;padding:8px 16px;background:#111318;color:#E8EAF0;font:500 13px/1.3 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}'
        . '.vbar b{font-weight:600;color:#9AA3B5;font-size:11px;text-transform:uppercase;letter-spacing:.08em}.vbar-row{display:flex;align-items:center;gap:8px;min-width:0}'
        . '.vbar-row>span{color:#9AA3B5;font-size:12px}.vbar nav{display:flex;gap:4px;overflow-x:auto;scrollbar-width:none}.vbar nav::-webkit-scrollbar{display:none}'
        . '.vbar a{flex:none;padding:5px 10px;border-radius:999px;color:#E8EAF0;text-decoration:none;background:#1F232C;white-space:nowrap}.vbar a:hover{background:#2C3240}'
        . '.vbar a[aria-current]{background:#E8EAF0;color:#111318}'
        . '@media(max-width:640px){.vbar{gap:6px;padding:8px 12px}.vbar b{display:none}.vbar-row{width:100%}.vbar-row>span{flex:none;width:72px}}</style>'
        . sx_review_phone_guard() . '<div class="vbar" role="region" aria-label="' . htmlspecialchars($L['_title']) . '"><b>' . htmlspecialchars($L['_title']) . '</b>'
        . sx_review_vbar_toggle($L['_title'], implode(' · ', array_map(fn($o) => $L[$o] ?? $o, $current))) . $rows . $live . '</div>';
};

$dir = "$root/dist/review/{$base['id']}";
@mkdir($dir, 0777, true);
foreach (glob("$dir/*.html") as $old) {
    unlink($old);
}
$made = [];
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
    $page = array_values(array_filter(sx_build_site($cfg, $root), fn($p) => empty($p['asset'])))[0];
    if (SX::$warnings) {
        fwrite(STDERR, implode('-', $c) . ': ' . implode(' | ', array_unique(SX::$warnings)) . "\n");
    }
    $page['file'] = $file($c);
    // Colours in the browser (demos whose switchers already change colours keep those): neutral and brand overrides
    $pal = [];
    if (!isset($axes['palette']) && !isset($axes['look']) && empty($cfg['look'])) {
        foreach (['neutral', 'brand'] as $look) {
            $alt = array_values(array_filter(sx_build_site(['look' => $look] + $cfg, $root), fn($p) => empty($p['asset'])))[0];
            $pal[$look] = sx_review_palette($page['html'], $alt['html']);
        }
    }
    $built[$file($c)] = $page['html'];
    $made[] = [$c, $page, $pal];
}
foreach ($made as [$c, $page, $pal]) {
    $live = sx_review_live_switches($pal, $base['locale'] ?? 'en', sx_review_rows_scope($page['html']));
    $html = str_replace('</body>', sx_review_nav_script() . sx_review_device_toggle($file($c), $base['locale'] ?? 'en') . sx_review_devspec($page, [$page], $base['locale'] ?? 'en', $page['prefix']) . '</body>', preg_replace('#<body([^>]*)>#', '<body$1>' . $bar($c, $live), $page['html'], 1));
    file_put_contents("$dir/" . $file($c), sx_strip_comments($html));
    echo "dist/review/{$base['id']}/" . $file($c) . "\n";
}
passthru(PHP_BINARY . ' ' . escapeshellarg("$root/build/export-review.php") . ' ' . escapeshellarg("$dir/index.html") . ' '
    . escapeshellarg("$root/dist/review/{$base['id']}.html") . ' ' . escapeshellarg($base['site']['name'] ?? $base['id']));
