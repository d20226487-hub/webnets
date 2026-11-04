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
$dir = "$root/dist/review/{$base['id']}";
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
        $cfg = sx_var_merge($cfg, $axes[$axis][$o]);
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
    $sets[] = [$c, $pages];
}

$bar = function (array $current, string $page) use ($axes, $L, $sets, $prefix): string {
    $rows = '';
    foreach ($axes as $axis => $options) {
        $links = '';
        foreach (array_keys($options) as $o) {
            $target = array_merge($current, [$axis => $o]);
            $has = false;
            foreach ($sets as [$c, $pages]) {
                if ($c === $target) {
                    $has = isset($pages[$page]);
                }
            }
            $href = $prefix($target) . ($has ? $page : 'index.html');
            $links .= '<a href="' . $href . '"' . ($current[$axis] === $o ? ' aria-current="true"' : '') . '>' . htmlspecialchars($L[$o] ?? $o) . '</a>';
        }
        $rows .= '<div class="vbar-row"><span>' . htmlspecialchars($L[$axis] ?? $axis) . '</span><nav>' . $links . '</nav></div>';
    }
    return '<style>.vbar{position:relative;z-index:2147483000;display:flex;flex-wrap:wrap;align-items:center;gap:6px 18px;padding:8px 16px;background:#111318;color:#E8EAF0;font:500 13px/1.3 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}'
        . '.vbar b{font-weight:600;color:#9AA3B5;font-size:11px;text-transform:uppercase;letter-spacing:.08em}.vbar-row{display:flex;align-items:center;gap:8px;min-width:0}'
        . '.vbar-row>span{color:#9AA3B5;font-size:12px}.vbar nav{display:flex;gap:4px;overflow-x:auto;scrollbar-width:none}.vbar nav::-webkit-scrollbar{display:none}'
        . '.vbar a{flex:none;padding:5px 10px;border-radius:999px;color:#E8EAF0;text-decoration:none;background:#1F232C;white-space:nowrap}.vbar a:hover{background:#2C3240}'
        . '.vbar a[aria-current]{background:#E8EAF0;color:#111318}'
        . '@media(max-width:640px){.vbar{gap:6px;padding:8px 12px}.vbar b{display:none}.vbar-row{width:100%}.vbar-row>span{flex:none;width:84px}}</style>'
        . '<div class="vbar" role="region" aria-label="' . htmlspecialchars($L['_title']) . '"><b>' . htmlspecialchars($L['_title']) . '</b>' . $rows . '</div>';
};

require_once "$root/lib/aibrief.php";
foreach ($sets as [$c, $pages]) {
    $brief = null;
    if (count($pages) > 1) { // AI brief for the whole site in this combination, next to its pages
        $brief = $prefix($c) . 'ai-brief.txt';
        file_put_contents("$dir/$brief", sx_ai_brief($pages));
    }
    foreach ($pages as $page => $p) {
        $html = str_replace('</body>', sx_review_nav_script() . sx_review_device_toggle($p['file'], $locale) . sx_review_devspec($p, $pages, $locale, $p['prefix'], $brief) . '</body>', $p['html']);
        if ($axes) {
            $html = preg_replace('#<body([^>]*)>#', '<body$1>' . $bar($c, $page), $html, 1);
        }
        file_put_contents("$dir/{$p['file']}", $html);
        echo "dist/review/{$base['id']}/{$p['file']}\n";
    }
}
passthru(PHP_BINARY . ' ' . escapeshellarg("$root/build/export-review.php") . ' ' . escapeshellarg("$dir/index.html") . ' '
    . escapeshellarg("$root/dist/review/{$base['id']}.html") . ' ' . escapeshellarg($argv[2] ?? $base['site']['name'] ?? $base['id']));
