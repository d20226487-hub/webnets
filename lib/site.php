<?php
/** Site-level helpers shared by the static builder and the style-tile generator. */

require_once __DIR__ . '/tokens.php';
require_once __DIR__ . '/render.php';

function sx_json(string $file): array
{
    // a file another process is replacing can read as missing or empty for a moment (a Docker folder shared with the
    // host does that during a rename): try again shortly before giving up
    for ($try = 1; ; $try++) {
        $raw = @file_get_contents($file);
        if ($raw !== false && $raw !== '') {
            try {
                return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                if ($try >= 4) {
                    throw $e;
                }
            }
        } elseif ($try >= 4) {
            return json_decode((string)file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        }
        usleep(50000 * $try);
    }
}

/**
 * A legal or policy block. Throws, unless the site config sets "override_blocks": true:
 * then it is recorded as a warning and the build goes on.
 */
function sx_legal_block(string $msg): void
{
    if (empty(SX::$ctx['override_blocks'])) {
        throw new RuntimeException($msg);
    }
    $w = "Override: $msg";
    if (!in_array($w, SX::$warnings, true)) {
        SX::$warnings[] = $w;
    }
}

/**
 * A block a normal build handles quietly (a section skipped, a variant swapped). Returns true when the caller should
 * render as asked anyway ("override_blocks", recorded as a warning); throws for a generator probe ("probe_blocks");
 * otherwise false: the caller keeps its usual fallback.
 */
function sx_soft_block(string $msg): bool
{
    if (!empty(SX::$ctx['override_blocks'])) {
        sx_legal_block($msg);
        return true;
    }
    if (!empty(SX::$ctx['probe_blocks'])) {
        throw new RuntimeException($msg);
    }
    return false;
}

/**
 * Brand data overlay: user brands (e.g. from the website network generator) live outside the repo,
 * in data/overlay/brands/ (or SX_BRAND_OVERLAY), with the same layout as brands/.
 */
function sx_brand_overlay(string $root): string
{
    return getenv('SX_BRAND_OVERLAY') ?: "$root/data/overlay/brands";
}

/** Path of a brand data file (logo, feed, catalog): the overlay copy when there is one. */
function sx_brand_file(string $root, string $rel): string
{
    $o = sx_brand_overlay($root) . "/$rel";
    return is_file($o) ? $o : "$root/brands/$rel";
}

/** A shared brand data file (operators, facts, ratings) with the overlay's entries merged in. */
function sx_brand_data(string $root, string $rel): array
{
    $data = is_file("$root/brands/$rel") ? sx_json("$root/brands/$rel") : [];
    $o = sx_brand_overlay($root) . "/$rel";
    if (is_file($o)) {
        foreach (sx_json($o) as $k => $v) {
            $data[$k] = $k === 'brands' && is_array($data[$k] ?? null) ? array_replace($data[$k], $v) : $v;
        }
    }
    return $data;
}

function sx_market(string $id, string $root): array
{
    return sx_json("$root/markets/$id.json");
}

function sx_brand(string $id, string $root): array
{
    $all = sx_brand_data($root, 'operators.json');
    if (!isset($all[$id])) {
        throw new RuntimeException("Unknown brand '$id'");
    }
    return $all[$id] + ['id' => $id];
}

/** Seeded class prefix such as "kv-" or "tru-". Always starts with a letter. */
function sx_auto_prefix(string $seed): string
{
    $c = 'bcdfghjklmnprstvz';
    $v = 'aeiou';
    $h = crc32("prefix|$seed");
    $len = 2 + $h % 2;
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $set = $i % 2 ? $v : $c;
        $out .= $set[($h >> ($i * 5)) % strlen($set)];
    }
    return "$out-";
}

/** HTML lang attribute for a locale within a market. */
function sx_html_lang(string $locale, string $market): string
{
    return ['en' => 'en-' . strtoupper($market), 'pt' => 'pt-BR', 'ru' => $market === 'kz' ? 'ru-KZ' : 'ru', 'kk' => 'kk-KZ'][$locale] ?? $locale;
}

/**
 * Build the render context from a site config.
 * Returns [ctx, preset (with overrides), warnings].
 */
function sx_site_context(array $cfg, string $root): array
{
    $warn = [];
    $market = sx_market($cfg['market'], $root);
    if (!empty($cfg['publisher'])) {
        // Multi-brand sites are branded as the publisher (brands/publishers.json), not as one operator.
        $pubs = sx_brand_data($root, 'publishers.json');
        $brand = ($pubs[$cfg['publisher']] ?? throw new RuntimeException("Unknown publisher '{$cfg['publisher']}' (brands/publishers.json)."))
            + ['id' => $cfg['publisher'], 'publisher' => true];
    } else {
        $brand = sx_brand($cfg['brand'], $root);
    }
    if (!empty($market['demo_only']) && empty($cfg['demo'])) {
        throw new RuntimeException("Market '{$market['id']}' is a demo-only market and cannot be used for real sites. (Demos set \"demo\": true.)");
    }
    $locale = $cfg['locale'] ?? $market['default_locale'];

    if (!in_array($market['id'], $brand['markets'], true)) {
        $warn[] = "Brand '{$brand['id']}' is not listed as licensed in market '{$market['id']}'.";
    }
    if (!in_array($locale, $market['locales'], true)) {
        $warn[] = "Locale '$locale' is not a locale of market '{$market['id']}'.";
    }
    $allowed = array_filter($brand['verticals'], function ($v) use ($market) {
        $gate = ['live' => 'sports', 'live-casino' => 'casino'][$v] ?? $v;
        return !empty($market['verticals'][$gate]);
    });
    if (!$allowed) {
        $warn[] = "None of brand '{$brand['id']}' verticals are permitted in market '{$market['id']}'.";
    }

    $suffix = $market['compliance']['brand_domain_suffix'] ?? null;
    $go = $cfg['links']['go'] ?? null;
    if ($suffix && $go && empty($brand['publisher']) && !str_ends_with((string)parse_url($go, PHP_URL_HOST), $suffix)) {
        $warn[] = "Brand link '$go' must point to a {$suffix} domain in market '{$market['id']}'.";
    }

    // Game providers (vendor pages): all known studios, plus the one this site is about.
    $providers = [];
    foreach (array_diff_key(sx_json("$root/brands/providers.json"), ['_note' => 1]) as $pid => $p) {
        $providers[$pid] = $p + ['id' => $pid];
    }
    $provider = null;
    if (!empty($cfg['provider'])) {
        $provider = $providers[$cfg['provider']] ?? throw new RuntimeException("Unknown provider '{$cfg['provider']}' (brands/providers.json).");
    }

    $seed = $cfg['seed'] ?? ($cfg['id'] ?? 'site');
    $prefix = ($cfg['prefix'] ?? 'sx-') === 'auto' ? sx_auto_prefix($seed) : ($cfg['prefix'] ?? 'sx-');
    $preset = sx_merge_tokens(sx_merge_tokens(sx_style_variant(sx_load_preset($cfg['preset'], $root), (int)($cfg['style']['hue'] ?? 0), !empty($cfg['style']['flip'])), sx_look_tokens($cfg['look'] ?? null, $brand, $root, $provider, (int)($cfg['look_variant'] ?? 0))), $cfg['tokens'] ?? []);
    if (isset($cfg['platform']) && !in_array($cfg['platform'], ['ios', 'android'], true)) {
        throw new RuntimeException("platform must be 'ios' or 'android' (omit it for a site that serves both).");
    }

    $ctx = [
        'root' => $root,
        'market' => $market,
        'locale' => $locale,
        'brand' => $brand,
        'seed' => $seed,
        'prefix' => $prefix,
        'site' => ($cfg['site'] ?? []) + ['name' => $brand['name']],
        'links' => ($cfg['links'] ?? []) + ['go' => '#go'],
        'cta_label' => $cfg['cta_label'] ?? null,
        'cta_short' => $cfg['cta_short'] ?? null,
        'today' => $cfg['today'] ?? date('Y-m-d'), // pin in demos so offer-expiry checks are reproducible
        'providers' => $providers,
        'provider' => $provider,
        'devspec' => !empty($cfg['_devspec']),  // review links: block markers + data for the developer skeleton view
        'xray' => !empty($cfg['_xray']),        // builder preview: extra measurements for the X-ray panel
        'brands_list' => $cfg['brands'] ?? [],   // multi-brand sites: compared operators
        'featured' => $cfg['featured'] ?? null,  // multi-brand sites: one labelled paid slot
        'look' => $cfg['look'] ?? null,
        'nav_style' => $cfg['nav_style'] ?? 'top', // multi-page: top menu, sidebar guide, or hub (no menu; the home page is the map)
        'nav_side' => ($cfg['nav_side'] ?? 'left') === 'right' ? 'right' : 'left', // sidebar guide: on the left or the right of the content
        // phone menu (☰): its side, how it opens (dropdown | drawer | sheet | full), and "skip" (one-page sidebar sites:
        // no menu, the sidebar shows as a panel under the content instead)
        'menu' => [
            'side' => ($cfg['menu']['side'] ?? 'left') === 'right' ? 'right' : 'left',
            'style' => in_array($cfg['menu']['style'] ?? '', ['dropdown', 'drawer', 'sheet', 'full'], true) ? $cfg['menu']['style'] : 'dropdown',
            'skip' => !empty($cfg['menu']['skip']),
        ],
        'pins_over_angle' => !empty($cfg['pins_over_angle']), // a pinned layout outside the angle's list is kept (generated sites)
        'rows' => ($cfg['rows'] ?? 'swipe') === 'stack' ? 'stack' : 'swipe',
        // affiliate disclosure of review, ranking and news sites: a strip at the top of every page, or in the footer
        'disclosure' => in_array($cfg['disclosure'] ?? 'top', ['top', 'footer', 'off'], true) ? ($cfg['disclosure'] ?? 'top') : 'top', // off: the site owner's call, with a warning // rows of cards: a swipe row, or stacked (wrapped) everywhere
        'sidebar_look' => in_array($cfg['sidebar_look'] ?? '', ['panel', 'rail', 'cards'], true) ? $cfg['sidebar_look'] : 'panel', // desktop sidebar
        'offer_mode' => in_array($cfg['offer_mode'] ?? 'mixed', ['mixed', 'banners', 'codes'], true) ? ($cfg['offer_mode'] ?? 'mixed') : throw new RuntimeException('offer_mode must be mixed, banners or codes.'),
        'vendor_ids' => $cfg['providers'] ?? (isset($cfg['provider']) ? [$cfg['provider']] : []), // vendor pages: one studio, or several
        'file_prefix' => $cfg['file_prefix'] ?? '', // review exports: a second set of pages (e.g. img-) beside the first
        'images' => !empty($cfg['images']), // news / review sites: thumbnails on cards, hero pictures on articles and pages
        'editor' => $cfg['editor'] ?? null, // review sites: the person who maintains and moderates the pages
        'demo' => !empty($cfg['demo']),
        'override_blocks' => !empty($cfg['override_blocks']), // legal and policy blocks become warnings; the build goes on
        'probe_blocks' => !empty($cfg['probe_blocks']), // generator probe: blocks that normally only drop or swap content throw, so the job form can warn
        'show_sample_notice' => !empty($cfg['sample_notice']), // visible "sample content" banner; off for the demo gallery
        'platform_only' => $cfg['platform'] ?? null, // app sites: one platform per site (ios | android)
    ];
    return [$ctx, $preset, $warn];
}
