<?php
/**
 * Review links only: the "Developer skeleton" view. A floating button opens a panel over the page that shows
 * how the page is put together, for a developer rebuilding it (e.g. in WordPress):
 *   - every section outlined on the page with its block name and variant, optionally as a grey wireframe;
 *   - Page sections: the WordPress block (sx/<name>), variant, role, guardrails and the field values it was rendered with;
 *   - Site sections: every page of the site with its sections; a section opens that page scrolled to it;
 *   - Styles: design tokens (CSS custom properties) and fonts;
 *   - AI brief: lib/aibrief.php's prompt for rebuilding this page, and a link to the one for the whole site;
 *   - WordPress: block markup for the page (ACF blocks from wp/sx-blocks.php) and the full spec as JSON.
 * Needs a build with ctx devspec (cfg "_devspec": true), which marks each block's element with data-sxdev="N". Not part of the site.
 */

/**
 * @param array  $page  one sx_build_site() page (needs 'dev', 'file', 'title')
 * @param array  $pages every page of the same build, for the Site tab
 * @param string|null $siteBrief file name of the whole-site AI brief published next to the pages
 */
function sx_review_devspec(array $page, array $pages, string $locale, string $prefix, ?string $siteBrief = null): string
{
    require_once __DIR__ . '/aibrief.php';
    static $names = null;
    $names ??= json_decode(file_get_contents(__DIR__ . '/devspec-names.json'), true);
    $li = ['en' => 0, 'ru' => 1, 'pt' => 2][$locale] ?? 0;
    $blocks = [];
    foreach ($pages as $p) {
        foreach ($p['dev'] ?? [] as $d) {
            if (isset($blocks[$d['b']])) {
                continue;
            }
            $m = sx_block_meta($d['b']);
            $blocks[$d['b']] = [
                'w' => $m['block']['name'] ?? "sx/{$d['b']}",
                't' => $m['block']['title'] ?? $d['b'],
                's' => $names[$d['b']][$li] ?? $names[$d['b']][0] ?? $d['b'], // short section name in the page's language
                'd' => $m['block']['description'] ?? '',
                'r' => $m['block']['sx']['role'] ?? 'content',
                'g' => $m['block']['sx']['guardrails'] ?? [],
                'vs' => $m['block']['sx']['variants'] ?? [],
                'f' => array_map(fn($f) => [$f['name'], $f['type'], $f['label'] ?? $f['name'], !empty($f['required']) ? 1 : 0, $f['default'] ?? null], $m['fields']),
            ];
        }
    }
    $data = [
        'l' => $locale,
        'file' => $page['file'],
        'prefix' => $prefix,
        'dialect' => $page['dialect'] ?? null, // code dialect of this site (lib/dialect.php)
        'blocks' => $blocks,
        'items' => array_map(fn($d) => ['p' => array_map('sx_dev_trim', $d['p'])] + $d, $page['dev'] ?? []),
        'brief' => sx_ai_brief($pages, $page['file']),
        'system' => sx_ai_system($pages, $page['file']),
        'siteBrief' => $siteBrief, // published file with the brief for every page of this build
        'pages' => array_map(fn($p) => [
            'f' => $p['file'],
            'i' => $p['id'] ?? null,
            't' => $p['title'] ?? '',
            'b' => array_values(array_map(fn($d) => $d['b'] . ':' . $d['v'], array_filter($p['dev'] ?? [], fn($d) => $d['n'] === 0))),
        ], array_values($pages)),
    ];
    $dir = __DIR__;
    $label = ['ru' => 'Каркас для разработчика', 'pt' => 'Esqueleto para devs', 'kk' => 'Әзірлеуші қаңқасы'][$locale] ?? 'Developer skeleton';
    return '<style>' . file_get_contents("$dir/devspec.css") . '</style>'
        . '<button type="button" id="sx-skeltoggle" aria-haspopup="dialog"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7l-5 5 5 5M16 7l5 5-5 5M14 4l-4 16"/></svg></span>'
        . htmlspecialchars($label) . '</button>'
        . '<script type="application/json" id="sx-devdata">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>'
        . '<script>' . file_get_contents("$dir/devspec.js") . '</script>';
}
