<?php
/**
 * Whole-site rendering shared by the static builder (build/build.php) and the WordPress page
 * template (wp/sx-page-template.php): site config -> context -> blueprint (if "type") -> HTML.
 */

require_once __DIR__ . '/site.php';
require_once __DIR__ . '/blueprint.php';
require_once __DIR__ . '/dialect.php';
require_once __DIR__ . '/nocomments.php';

/**
 * Render every page of a site config.
 * Returns a list of ['id', 'slug', 'file', 'html', 'warnings', 'summary', 'prefix'] plus, for multi-page
 * sites with site.url, 'asset' entries for sitemap.xml and robots.txt.
 * Multi-page sites (blueprint "pages") also run sx_site_checks(), which throws on broken internal links.
 */
function sx_build_site(array $cfg, string $root): array
{
    $bp = isset($cfg['type']) ? sx_blueprint($cfg['type'], $root) : [];
    $cfg += $bp['defaults'] ?? []; // e.g. promo pages: cta_label = "Claim offer"
    [$ctx, $preset, $warn] = sx_site_context($cfg, $root);
    sx_context($ctx);
    $ctx['dialect'] = sx_dialect_resolve($cfg['dialect'] ?? true); // seeded code dialect (lib/dialect.php); "dialect": false = off
    $ctx['icons'] = $cfg['icons'] ?? sx_pick(SX_ICON_FAMILIES, 'icons'); // icon family (sx_icon)
    $ctx['compliance_footer'] = $cfg['variants']['compliance'] ?? null; // footer compliance layout pin (panel / cards)
    $ctx['impl'] = is_string($cfg['impl'] ?? null) ? ['*' => $cfg['impl']] : ($cfg['impl'] ?? []); // block implementations (sx_impl_file)
    if (!in_array($ctx['icons'], SX_ICON_FAMILIES, true)) {
        throw new RuntimeException('icons must be one of: ' . implode(', ', SX_ICON_FAMILIES) . '.');
    }

    $summary = null;
    $map = null;
    SX::$trace = [];
    SX::$once = []; // caches (catalog, reviews, articles, brand data) belong to one site; several sites may build in one process
    $style = $cfg['urls'] ?? 'pretty';
    if ($bp) {
        SX::$warnings = [];
        sx_context($ctx);
        $verticals = sx_bp_verticals($bp, $cfg);
        foreach ($verticals as $v) {
            if (empty($ctx['market']['verticals'][$v])) {
                sx_legal_block("Site type '{$cfg['type']}' needs the '$v' vertical, which market '{$ctx['market']['id']}' does not allow. "
                    . 'This is a legal block, not a setting: use another site type there.');
            }
        }
        if (!empty($bp['compares_brands'])) {
            // "product" (casino / sports / both) picks the criteria and what "live" means; no product = the blueprint default
            $prod = isset($cfg['product']) ? $bp['products'][$cfg['product']] : [];
            $ctx['product'] = $cfg['product'] ?? null;
            $ctx['criteria'] = $prod['criteria'] ?? $bp['criteria'];
            $ctx['crit_alias'] = $prod['alias'] ?? [];
            $ctx['crit_composite'] = $bp['composite'] ?? [];
            $ctx['fact_cols'] = $prod['facts'] ?? $bp['facts'] ?? ['payouts', 'live', 'games', 'support'];
            sx_context($ctx);
            sx_casinos_gate($verticals); // throws when a brand cannot be listed
        }
        if (!empty($bp['requires_reviews'])) {
            $ctx['sample_reviews'] = sx_reviews_gate($cfg); // throws if seeded reviews would ship outside a demo
            $ctx['sample_notice'] = $ctx['sample_reviews'] ? 'reviews.sample_banner' : null;
        }
        if (!empty($bp['articles'])) {
            $ctx['articles_dir'] = $cfg['articles'] ?? null;
            sx_context($ctx);
            $ctx['sample_notice'] = sx_articles_gate($cfg) ? 'news.sample_banner' : null; // throws on unlabelled paid content
        }
        if (isset($bp['pages'])) {
            if (!in_array($style, ['pretty', 'files'], true)) {
                throw new RuntimeException("urls must be 'pretty' or 'files', not '$style'.");
            }
            [$pages, $summary, $map] = sx_expand_site($cfg, $root);
            $ctx += ['pages' => $map, 'urls' => $style, 'base' => $cfg['base'] ?? '/'];
            if (!empty($bp['compares_brands'])) {
                // Generic CTAs go to the top brand for the angle; review pages override with their own brand.
                $ctx['rank_by'] = $bp['angles'][$summary['angle']]['rank_by'] ?? null;
                $ctx['cta_brand'] = sx_casino_rank(sx_crit($ctx['rank_by']))[0];
            }
            if (empty($ctx['site']['url'])) {
                SX::$warnings[] = 'site.url is required on multi-page sites (canonical links, sitemap).';
            }
            $cfg['pages'] = $pages;
        } else {
            [$page, $summary] = sx_expand_blueprint($cfg, $root);
            if (!empty($bp['compares_brands'])) { // one-page brand comparisons: generic CTAs go to the top brand for the angle
                $ctx['rank_by'] = $bp['angles'][$summary['angle']]['rank_by'] ?? null;
                $ctx['cta_brand'] = sx_casino_rank(sx_crit($ctx['rank_by']))[0];
            }
            $cfg['pages'] = [$page];
        }
        $warn = array_merge($warn, SX::$warnings);
    }

    $out = [];
    foreach ($cfg['pages'] as $i => $page) {
        // Site-level warnings are reported once (on the first page) for multi-page sites.
        SX::$warnings = $map && $i > 0 ? [] : $warn;
        SX::$jsonld = [];
        sx_context(array_merge($ctx, $map ? ['page_id' => $page['id']] : [], array_key_exists('cta_brand', $page) && $page['item'] !== null ? ['cta_brand' => $page['cta_brand']] : [], $page['ctx'] ?? []));
        sx_reset_used();
        $html = sx_dialect_apply(sx_money_apply(sx_age_apply(sx_drop_gone_links(sx_strip_comments(sx_render_page($page, $preset, $ctx))), (int)($ctx['market']['min_age'] ?? 18)), $ctx['market']), $ctx['dialect'], $ctx['prefix']);
        $out[] = [
            'id' => $page['id'] ?? null,
            'slug' => $page['slug'] ?? 'index',
            'file' => $map ? sx_page_file($map[$page['id']], $style) : ($page['slug'] ?? 'index') . '.html',
            'html' => $html,
            'warnings' => array_values(array_unique(SX::$warnings)),
            'summary' => $summary,
            'prefix' => $ctx['prefix'],
            'dialect' => $ctx['dialect'],
            'weights' => SX::$weights,
            'jsonld' => SX::$jsonld,
            'dev' => SX::$dev,
            // review links: what the AI brief needs to know about the site and its market
            'brief' => !empty($ctx['devspec']) ? ['type' => $cfg['type'] ?? '', 'angle' => $summary['angle'] ?? '', 'site' => $ctx['site']['name'] ?? '',
                'url' => $ctx['site']['url'] ?? '', 'brand' => $ctx['brand']['name'] ?? '', 'locale' => $ctx['locale'], 'market' => $ctx['market']] : null,
            'title' => $page['title'] ?? $ctx['site']['name'],
        ];
    }
    if ($map) {
        sx_site_checks($out, $style, $ctx['base'], $bp['cta_check'] ?? true);
        foreach (sx_sitemap() ?? [] as $file => $body) {
            $out[] = ['id' => null, 'slug' => $file, 'file' => $file, 'html' => $body, 'warnings' => [], 'summary' => null, 'prefix' => $ctx['prefix'], 'asset' => true];
        }
    }
    return $out;
}

/** One page as a complete HTML document. Blocks are placed by their block.json role. */
/**
 * The sidebar's content ("nav_style": "sidebar"), or [] for other sites: multi-page sites list their pages; one-page
 * sites list the brand's sections, each a tracked link to the brand (the one CTA destination), with the CTA.
 * Returns ['title' => string, 'items' => [['label', 'url', 'current', 'cta']], 'cta' => bool].
 */
function sx_side_menu(): array
{
    $ctx = SX::$ctx;
    if (($ctx['nav_style'] ?? 'top') !== 'sidebar') {
        return [];
    }
    if (sx_pages()) {
        return ['title' => t('sidenav.title'), 'cta' => false, 'items' => array_map(fn($it) => ['label' => $it['label'], 'url' => $it['url'], 'current' => !empty($it['current']), 'cta' => false], sx_page_nav())];
    }
    if (!empty($ctx['criteria']) && function_exists('sx_casino_rank')) { // a one-page brand comparison: the ranked brands, each to its own site
        $items = [];
        foreach (array_slice(sx_casino_rank(sx_crit($ctx['rank_by'] ?? null)), 0, 6) as $n => $bid) {
            $b = sx_casino($bid);
            $items[] = ['label' => ($n + 1) . '. ' . $b['name'], 'url' => $b['link'], 'current' => false, 'cta' => true];
        }
        return ['title' => t('sidenav.top'), 'cta' => true, 'items' => $items];
    }
    $keys = [];
    foreach ($ctx['brand']['verticals'] ?? [] as $v) {
        $gate = $v === 'live-casino' ? 'casino' : ($v === 'live' ? 'sports' : $v);
        if (in_array($v, ['sports', 'live', 'casino'], true) && !empty($ctx['market']['verticals'][$gate])) {
            $keys[] = "nav.$v";
        }
    }
    $href = $ctx['links']['go'] ?? '#go';
    return ['title' => t('sidenav.links'), 'cta' => true, 'items' => array_map(fn($k) => ['label' => t($k), 'url' => $href, 'current' => false, 'cta' => true],
        array_merge($keys, ['nav.promotions', 'nav.app', 'nav.payments']))];
}

/** The sidebar's items as <li> links; tracked brand links carry data-cta and the sponsored rel. */
function sx_side_menu_items(array $side, string $pos = 'sidenav'): string
{
    $out = '';
    foreach ($side['items'] as $it) {
        $ext = $it['cta'] && preg_match('#^https?://#', $it['url']) === 1 ? ' rel="nofollow sponsored noopener" target="_blank"' : '';
        $out .= '<li><a href="' . sx_url($it['url']) . '"' . ($it['current'] ? ' aria-current="page"' : '') . ($it['cta'] ? ' data-cta="' . e($pos) . '"' : '') . $ext . '><span>' . e($it['label']) . '</span>' . sx_icon('chevron') . '</a></li>';
    }
    return $out;
}

function sx_render_page(array $page, array $preset, array $ctx): string
{
    if (!empty($page['lead'])) {
        SX::$ctx['lead_types'] = $page['lead'];
    }
    $slots = ['top' => '', 'header' => '', 'main' => '', 'footer' => '', 'overlay' => ''];
    $blockBytes = [];
    $roles = [];
    foreach ($page['blocks'] as $b) {
        $role = sx_block_meta($b['block'])['block']['sx']['role'] ?? 'content';
        $props = $b['props'] ?? [];
        if ($b['block'] === 'compliance-bar' && in_array($props['variant'] ?? '', ['strip', 'ribbon'], true)) { // top-of-page layouts
            $role = 'top';
        }
        $roles[] = $role;
        if ($b['block'] === 'footer') {
            $roles[] = 'compliance'; // the footer always embeds the compliance panel
        }
        $slot = ['top' => 'top', 'header' => 'header', 'footer' => 'footer', 'age-gate' => 'overlay', 'sticky' => 'overlay'][$role] ?? 'main';
        $html = sx_block($b['block'], $props);
        $blockBytes[] = ['block' => $b['block'], 'variant' => $props['variant'] ?? '', 'bytes' => strlen($html)];
        $slots[$slot] .= $html;
    }

    foreach ($ctx['market']['compliance']['required_blocks'] as $req) {
        $role = $req === 'compliance-bar' ? 'compliance' : $req;
        if (!in_array($role, $roles, true)) {
            SX::$warnings[] = "Page '" . ($page['slug'] ?? 'index') . "' had no $req; added automatically.";
            if ($req === 'age-gate') {
                $slots['overlay'] .= sx_block('age-gate');
            } else {
                $slots['footer'] .= '<div class="' . cls('container') . '">' . sx_block('compliance-bar', ['variant' => 'panel']) . '</div>';
            }
        }
    }

    if ($side = sx_side_menu()) { // sidebar layout: a sticky list beside the content (left, or right with nav_side); on phones the header's menu holds it
        SX::$used['_sidenav'] = 1;
        $cta = $side['cta'] ? sx_cta('sidenav', cx('btn', ['btn', null, 'primary'], ['sidenav', 'cta'])) : '';
        $inline = !empty($ctx['menu']['skip']) && !sx_pages(); // no phone menu: the sidebar shows under the content on phones
        $slots['main'] = '<div class="' . cx('container', ['sidenav', 'grid']) . ($ctx['nav_side'] === 'right' ? ' ' . cls('sidenav', 'grid', 'right') : '') . ($inline ? ' ' . cls('sidenav', 'grid', 'inline') : '')
            . '"><aside class="' . cx('sidenav', ['sidenav', null, $ctx['sidebar_look'] ?? 'panel']) . '"><p class="' . cls('sidenav', 'title') . '">' . e($side['title'])
            . '</p><nav aria-label="' . e($side['title']) . '"><ol>' . sx_side_menu_items($side) . '</ol></nav>' . $cta . '</aside><div class="' . cls('sidenav', 'main') . '">' . $slots['main'] . '</div></div>';
    }
    if (!empty($ctx['sample_notice']) && !empty($ctx['show_sample_notice'])) { // banner is opt-in ("sample_notice": true); the demo gate still applies
        // Sample (made-up) reviews or articles: a banner on every page, not removable by config (briefs 07, 09).
        SX::$used['_sample'] = 1;
        $slots['top'] = '<p class="' . cls('sample') . '" role="note">' . sx_icon('info') . e(t($ctx['sample_notice'])) . '</p>' . $slots['top'];
    }
    $css = sx_minify_css(':root { color-scheme: ' . ($preset['theme'] === 'dark' ? 'dark' : 'light') . "; }\n"
        . sx_vars_to_css(sx_token_vars($preset), ':root')) . sx_collect_css();
    $lang = sx_html_lang($ctx['locale'], $ctx['market']['id'], $ctx['market']);
    $title = e($page['title'] ?? $ctx['site']['name']);
    $desc = e($page['description'] ?? '');
    $fonts = e(sx_google_fonts_url([$preset]));
    $sprite = sx_sprite();
    SX::$weights = ['blocks' => $blockBytes, 'css' => strlen($css), 'sprite' => strlen($sprite)];
    if (!empty($ctx['xray'])) {
        // Builder only: CSS bytes per block (minified, before prefixing) so the X-ray can show what each block costs.
        $root = $ctx['root'];
        SX::$weights['css_base'] = strlen(sx_minify_css(file_get_contents("$root/tokens/base.css")));
        foreach (array_keys(SX::$used) as $n) {
            $src = is_file("$root/blocks/$n/style.css") ? file_get_contents("$root/blocks/$n/style.css") : '';
            foreach (array_keys(SX::$variantsUsed[$n] ?? []) as $v) {
                $src .= is_file("$root/blocks/$n/variants/$v.css") ? file_get_contents("$root/blocks/$n/variants/$v.css") : '';
            }
            SX::$weights['css_blocks'][$n] = strlen(sx_minify_css($src));
        }
    }
    $pid = SX::$ctx['page_id'] ?? null;
    $canonical = $pid ? sx_abs_url($pid) : ($ctx['site']['url'] ?? null);
    $canonicalTag = $canonical ? '<link rel="canonical" href="' . e($canonical) . '">' : '';
    $bodyAttr = $pid ? ' data-page="' . e($pid) . '"' : ''; // analytics: which page a data-cta click came from
    $bodyAttr .= ($ctx['rows'] ?? 'swipe') === 'stack' ? ' data-rows="stack"' : ''; // rows of cards stack instead of scrolling sideways
    $skip = '<a class="' . cls('skip') . '" href="#main">' . e(t('skip_link')) . '</a>';
    $webPage = ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => $page['title'] ?? $ctx['site']['name'], 'inLanguage' => $lang] + ($canonical ? ['url' => $canonical] : []);
    $ld = array_merge([$webPage], SX::$jsonld);
    if ($pid === 'home' && $canonical) {
        $ld[] = ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $ctx['site']['name'], 'url' => $canonical, 'inLanguage' => $lang];
    }
    $jsonld = implode("\n", array_map(fn($o) => '<script type="application/ld+json">' . json_encode($o, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>', $ld));
    // App pages: iOS Smart App Banner when the brand has an App Store ID and the site offers iOS.
    $appId = $ctx['brand']['app']['app_store_id'] ?? null;
    if (isset(SX::$used['app-hero']) && $appId && in_array('ios', sx_app_platforms(), true)) {
        $jsonld .= "\n" . '<meta name="apple-itunes-app" content="app-id=' . e($appId) . '">';
    }

    // Drop template indentation and blank lines: every "newline + indent" becomes one newline, so a
    // whitespace character still separates inline elements and rendering is unchanged. Also applied
    // inside our inline scripts (no template literals or multi-line strings there).
    foreach ($slots as $k => $html) {
        $slots[$k] = trim(preg_replace('/\n\s+/', "\n", $html));
    }

    return <<<HTML
<!doctype html>
<html lang="$lang" dir="{$ctx['market']['dir']}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>$title</title>
<meta name="description" content="$desc">
$canonicalTag
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="$fonts">
$jsonld
<style>
$css
</style>
</head>
<body$bodyAttr>
$skip
{$slots['top']}
{$slots['header']}
<main id="main">
{$slots['main']}
</main>
{$slots['footer']}
{$slots['overlay']}
$sprite
</body>
</html>

HTML;
}
