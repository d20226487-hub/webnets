<?php
/**
 * Block runtime shared by the static builder (build/build.php) and the WP adapter (wp/sx-blocks.php).
 *
 * Templates never call get_field() or read globals directly. They receive $props (array) and
 * $variant (string), and use the helpers below for escaping, strings, money and class names.
 */

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/promo.php';
require_once __DIR__ . '/vendor.php';
require_once __DIR__ . '/pages.php';
require_once __DIR__ . '/social.php';
require_once __DIR__ . '/art.php';
require_once __DIR__ . '/reviews.php';
require_once __DIR__ . '/casinos.php';
require_once __DIR__ . '/news.php';

final class SX
{
    /** @var array{root:string,market:array,locale:string,brand:array,site:array,seed:string,prefix:string} */
    public static array $ctx = [];
    public static array $used = [];
    /** [block => [variant => true]] so only CSS for rendered variants ships. */
    public static array $variantsUsed = [];
    /** Implementation rendered per block: [block => ['a' => [variant => true], 'b' => [...]]] (see sx_impl_file). */
    public static array $implUsed = [];
    public static array $warnings = [];
    /** JSON-LD objects blocks want in <head> (e.g. FAQPage). */
    public static array $jsonld = [];
    /** SVG symbols used on the page: [id => [viewBox, inner]], emitted once by sx_sprite(). */
    public static array $symbols = [];
    /** Per-page "emit once" flags (e.g. the platform script). */
    public static array $once = [];
    /** Builder X-ray: why pages and sections are on or off and how variants and order were chosen, by page id ('' = one-page site). */
    public static array $trace = [];
    /** Review links only (ctx devspec): every block rendered on the page, in order, for the developer skeleton view. */
    public static array $dev = [];
    public static int $devDepth = 0;
    /** Builder X-ray: bytes per rendered block and per CSS part of the current page. */
    public static array $weights = [];
}

/** Set the render context. Call once per page (or per locale, for previews). */
function sx_context(array $ctx): void
{
    $root = $ctx['root'];
    SX::$ctx = $ctx + ['seed' => 'default', 'prefix' => 'sx-', 'site' => [], 'links' => ['go' => '#go'], 'cta_label' => null];
    SX::$ctx['site'] += ['name' => SX::$ctx['brand']['name'] ?? 'Site', 'year' => date('Y')];
    if (!isset(SX::$ctx['strings'])) {
        SX::$ctx['strings'] = json_decode(file_get_contents("$root/i18n/strings.json"), true);
    }
}

function sx_ctx(?string $key = null)
{
    return $key === null ? SX::$ctx : (SX::$ctx[$key] ?? null);
}

/** Escape for HTML text and attributes. */
function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sx_fill(string $s, array $vars = []): string
{
    $vars += [
        'brand' => SX::$ctx['brand']['name'] ?? '',
        'age' => SX::$ctx['market']['min_age'] ?? 18,
        'year' => SX::$ctx['site']['year'] ?? date('Y'),
        // only when used: the several-studios label is itself built from a translated string
        'provider' => str_contains($s, '{provider}') && function_exists('sx_vendor_multi') && sx_vendor_multi() ? sx_vendor_label() : (SX::$ctx['provider']['name'] ?? ''),
    ];
    return preg_replace_callback('/\{(\w+)\}/', fn($m) => array_key_exists($m[1], $vars) ? (string)$vars[$m[1]] : $m[0], $s);
}

/** UI string from i18n/strings.json in the current locale (falls back to English). */
function t(string $key, array $vars = []): string
{
    // Product-specific wording ("key@sports", "key@both") wins on sites with a "product" (casino reviews).
    $row = (!empty(SX::$ctx['product']) ? SX::$ctx['strings']["$key@" . SX::$ctx['product']] ?? null : null)
        ?? SX::$ctx['strings'][$key] ?? null;
    if ($row === null) {
        SX::$warnings[] = "Missing string '$key'";
        return $key;
    }
    return sx_fill($row[SX::$ctx['locale']] ?? $row['en'] ?? $key, $vars);
}

/** Pick the current locale from a {locale: value} map (market compliance copy, brand taglines). */
function tm($map, array $vars = [])
{
    if (!is_array($map)) {
        return $map;
    }
    $v = $map[SX::$ctx['locale']] ?? reset($map);
    return is_string($v) ? sx_fill($v, $vars) : $v;
}

/**
 * Per-locale overrides from the market (`locale_formats`: {ru: {decimal, thousands, date_format}}), for a market
 * that serves several languages. $key null returns the whole override map for the site's locale.
 */
function sx_locale_format(?string $key)
{
    $f = SX::$ctx['market']['locale_formats'][SX::$ctx['locale'] ?? ''] ?? [];
    return $key === null ? array_intersect_key($f, ['decimal' => 1, 'thousands' => 1]) : ($f[$key] ?? null);
}

/** How a market is named in short copy ("Average RTP at Nyxa, BR"): a per-locale label, else the market code. */
function sx_market_label(): string
{
    $m = SX::$ctx['market'];
    return $m['short_label'][SX::$ctx['locale'] ?? ''] ?? strtoupper($m['id']);
}

function sx_number($n, int $decimals = 0): string
{
    $c = sx_locale_format(null) + (SX::$ctx['market']['currency'] ?? ['decimal' => '.', 'thousands' => ',']);
    $thousands = $c['thousands'] === ' ' ? "\u{202F}" : $c['thousands'];
    return number_format((float)$n, $decimals, $c['decimal'], $thousands);
}

function money($amount): string
{
    $c = SX::$ctx['market']['currency'];
    $num = sx_number($amount, $c['decimals']);
    $gap = $c['space'] ? "\u{00A0}" : '';
    return $c['position'] === 'before' ? $c['symbol'] . $gap . $num : $num . $gap . $c['symbol'];
}

/**
 * BEM class with the site's prefix. Authoring prefix is "sx-"; the build swaps it per site so
 * class names differ between sites (footprint reduction).
 *   cls('hero') => "sx-hero"   cls('hero', 'title') => "sx-hero__title"   cls('hero', null, 'split') => "sx-hero--split"
 */
function cls(string $block, ?string $el = null, ?string $mod = null): string
{
    $name = SX::$ctx['prefix'] . $block . ($el ? "__$el" : '') . ($mod ? "--$mod" : '');
    return $name;
}

/** Several classes at once: cx('btn', ['btn', null, 'primary']) */
function cx(...$parts): string
{
    return implode(' ', array_map(fn($p) => is_array($p) ? cls(...$p) : cls($p), $parts));
}

/** Deterministic choice from $options for this site's seed. Same seed + salt => same pick. */
function sx_pick(array $options, string $salt)
{
    $options = array_values($options);
    $i = crc32(SX::$ctx['seed'] . '|' . $salt) % count($options);
    return $options[$i];
}

function sx_block_meta(string $name): array
{
    static $cache = [];
    if (!isset($cache[$name])) {
        $dir = SX::$ctx['root'] . "/blocks/$name";
        if (!is_dir($dir)) {
            throw new RuntimeException("Unknown block '$name'");
        }
        $meta = json_decode(file_get_contents("$dir/block.json"), true, 512, JSON_THROW_ON_ERROR);
        $fields = is_file("$dir/fields.json") ? json_decode(file_get_contents("$dir/fields.json"), true, 512, JSON_THROW_ON_ERROR) : ['fields' => []];
        $cache[$name] = ['dir' => $dir, 'block' => $meta, 'fields' => $fields['fields']];
    }
    return $cache[$name];
}

/** Defaults declared in fields.json, applied under the given props. */
function sx_defaults(array $fields): array
{
    $out = [];
    foreach ($fields as $f) {
        if (array_key_exists('default', $f)) {
            $out[$f['name']] = $f['default'];
        }
    }
    return $out;
}

/** Variants of a block that the current market allows (sx.variant_requires: {variant: vertical}). */
function sx_market_variants(string $name): array
{
    $sx = sx_block_meta($name)['block']['sx'] ?? [];
    $req = $sx['variant_requires'] ?? [];
    $verticals = SX::$ctx['market']['verticals'] ?? [];
    return array_values(array_filter($sx['variants'] ?? ['default'], fn($v) => !isset($req[$v]) || !empty($verticals[$req[$v]])));
}

/**
 * Render a block to a string.
 * Variant resolution: $props['variant'] if set and not "auto", else a seeded pick from block.json sx.variants.
 * Market gating: blocks that declare sx.requires.vertical are skipped where the market disallows it.
 */
function sx_block(string $name, array $props = []): string
{
    $m = sx_block_meta($name);
    $sx = $m['block']['sx'] ?? [];
    $props += sx_defaults($m['fields']);

    $vertical = $sx['requires']['vertical'] ?? null;
    if ($vertical && empty(SX::$ctx['market']['verticals'][$vertical])) {
        SX::$warnings[] = "Block '$name' skipped: '$vertical' not allowed in market " . SX::$ctx['market']['id'];
        return '';
    }

    $variants = sx_market_variants($name);
    $variant = $props['variant'] ?? 'auto';
    if ($variant !== 'auto' && in_array($variant, $sx['variants'] ?? [], true) && !in_array($variant, $variants, true)) {
        SX::$warnings[] = "Block '$name': variant '$variant' needs a vertical this market does not allow; picking another.";
        $variant = 'auto';
    }
    if ($variant === 'auto' || !in_array($variant, $variants, true)) {
        if ($variant !== 'auto') {
            SX::$warnings[] = "Block '$name': unknown variant '$variant', picking one";
        }
        $variant = sx_pick($variants, "variant:$name:" . (SX::$used[$name] ?? 0));
    }
    SX::$used[$name] = (SX::$used[$name] ?? 0) + 1;
    SX::$variantsUsed[$name][$variant] = true;

    $file = "$m[dir]/variants/$variant.php";
    if (!is_file($file)) {
        $file = "$m[dir]/render.php";
    }
    $impl = 'a';
    if ($b = sx_impl_file($name, $variant, $m['dir'])) {
        [$file, $impl] = [$b, 'b'];
    }
    SX::$implUsed[$name][$impl][$variant] = true;
    $dev = !empty(SX::$ctx['devspec']) ? count(SX::$dev) : null;
    if ($dev !== null) {
        SX::$dev[] = ['b' => $name, 'v' => $variant, 'i' => $impl, 'n' => SX::$devDepth++, 'p' => ['variant' => $variant] + sx_dev_props($m['fields'], $props)];
    }
    ob_start();
    (static function (string $__file, array $props, string $variant) {
        include $__file;
    })($file, $props, $variant);
    $html = ob_get_clean();
    if ($dev === null) {
        return $html;
    }
    SX::$devDepth--;
    return trim($html) === '' ? $html : "<!--sx:$dev-->" . $html; // the skeleton view finds the block's element after this marker
}

/**
 * Implementation B of a block variant, if this site uses it: blocks/<name>/b/variants/<variant>.php.
 * B is the same design built independently (other element structure, other CSS technique), with its own class
 * roots and CSS in blocks/<name>/b/. Per site and block by seed; "impl" in the site config pins it:
 * "impl": "b" (every block that has a B), "impl": {"hero": "a", "faq": "b"}.
 */
function sx_impl_file(string $name, string $variant, string $dir): ?string
{
    $f = "$dir/b/variants/$variant.php";
    if (!is_file($f)) {
        return null;
    }
    $pin = SX::$ctx['impl'][$name] ?? SX::$ctx['impl']['*'] ?? null;
    $use = in_array($pin, ['a', 'b'], true) ? $pin : sx_pick(['a', 'b'], "impl|$name");
    return $use === 'b' ? $f : null;
}

/** Field values a block was rendered with (non-empty), for the developer skeleton view and the AI brief. */
function sx_dev_props(array $fields, array $props): array
{
    $out = [];
    foreach ($fields as $f) {
        $v = $props[$f['name']] ?? null;
        if ($v === null || $v === '' || $v === []) {
            continue;
        }
        $out[$f['name']] = $v; // full values; sx_dev_trim() shortens them for the page data
    }
    return $out;
}

/**
 * Compiled CSS for the base layer plus every block rendered so far, with the site's class prefix.
 * Per-variant CSS (blocks/<name>/variants/<v>.css) ships only for variants that were rendered;
 * $extraBlocks (used by WP and previews) includes a block with all of its variant CSS.
 */
function sx_collect_css(array $extraBlocks = []): string
{
    $root = SX::$ctx['root'];
    $css = file_get_contents("$root/tokens/base.css");
    $names = array_unique(array_merge(array_keys(SX::$used), $extraBlocks));
    sort($names);
    foreach ($names as $n) {
        $all = in_array($n, $extraBlocks, true);
        $used = SX::$implUsed[$n] ?? null; // null: not rendered through sx_block (e.g. _sitenav): implementation A only
        foreach (['a' => "$root/blocks/$n", 'b' => "$root/blocks/$n/b"] as $impl => $dir) {
            if (!$all && ($used === null ? $impl === 'b' : empty($used[$impl]))) {
                continue;
            }
            if (is_file("$dir/style.css")) {
                $css .= "\n" . file_get_contents("$dir/style.css");
            }
            foreach (glob("$dir/variants/*.css") as $vf) {
                $v = basename($vf, '.css');
                if ($all || ($used === null ? isset(SX::$variantsUsed[$n][$v]) : isset($used[$impl][$v]))) {
                    $css .= "\n" . file_get_contents($vf);
                }
            }
        }
    }
    $look = SX::$ctx['look'] ?? null; // a look may add a few rules beyond its tokens (tokens/looks/<look>.css), last so they win
    if ($look && is_file("$root/tokens/looks/$look.css")) {
        $css .= "
" . file_get_contents("$root/tokens/looks/$look.css");
    }
    return sx_prefix_css(sx_minify_css($css), SX::$ctx['prefix']);
}

/**
 * Strip comments so shipped CSS carries no system fingerprints (block names, authoring notes),
 * then a conservative minify: collapse whitespace and drop it around { } ; , only.
 * Colons and combinators are left alone so selectors like ".a :focus" keep their meaning.
 */
function sx_minify_css(string $css): string
{
    $css = preg_replace('#/\*.*?\*/#s', '', $css);
    $css = preg_replace('/\s+/', ' ', $css);
    $css = preg_replace('/\s*([{};,])\s*/', '$1', $css);
    return trim(str_replace(';}', '}', $css));
}

function sx_prefix_css(string $css, string $prefix): string
{
    return $prefix === 'sx-' ? $css : preg_replace('/(?<=\.)sx-(?=[a-z])/', $prefix, $css);
}

function sx_reset_used(): void
{
    SX::$used = [];
    SX::$variantsUsed = [];
    SX::$implUsed = [];
    SX::$symbols = [];
    SX::$once = [];
    SX::$dev = [];
    SX::$devDepth = 0;
}

/** Brand mark (inline SVG) + wordmark. Variant "mono" uses currentColor instead of brand colour. */
function sx_logo(string $mode = 'brand'): string
{
    $b = SX::$ctx['brand'];
    $svg = file_get_contents(SX::$ctx['root'] . '/brands/' . $b['logo']);
    $style = $mode === 'brand' ? ' style="color:' . e($b['brand_color']) . '"' : '';
    return '<span class="' . cls('logo') . '"><span class="' . cls('logo', 'mark') . "\"$style>" . sx_logo_svg() . '</span>'
        . '<span class="' . cls('logo', 'word') . '">' . e($b['name']) . '</span></span>';
}

/** Brand mark as a sprite reference (defined once per page). */
function sx_logo_svg(): string
{
    $file = SX::$ctx['root'] . '/brands/' . SX::$ctx['brand']['logo'];
    return sx_use('l-' . basename($file, '.svg'), fn() => file_get_contents($file));
}

/**
 * Reference a symbol in the page sprite, registering it on first use. $svg is a callable returning
 * a full <svg> element (viewBox + content). Repeated icons and logos then cost a few bytes each.
 */
function sx_use(string $key, callable $svg, string $class = ''): string
{
    $id = SX::$ctx['prefix'] . $key;
    if (!isset(SX::$symbols[$id])) {
        $src = $svg();
        preg_match('/viewBox="([^"]+)"/', $src, $vb);
        preg_match('#<svg[^>]*>(.*)</svg>#s', $src, $inner);
        SX::$symbols[$id] = [$vb[1] ?? '0 0 24 24', trim($inner[1] ?? '')];
    }
    return '<svg' . ($class ? ' class="' . $class . '"' : '') . ' viewBox="' . SX::$symbols[$id][0] . '" aria-hidden="true"><use href="#' . e($id) . '"/></svg>';
}

/** Hidden sprite with every symbol used on the page. Builders print it once, before </body>. */
function sx_sprite(): string
{
    if (!SX::$symbols) {
        return '';
    }
    $out = '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute" aria-hidden="true">';
    foreach (SX::$symbols as $id => [$vb, $inner]) {
        $out .= '<symbol id="' . e($id) . '" viewBox="' . $vb . '">' . $inner . '</symbol>';
    }
    return $out . '</svg>';
}

/**
 * The site's one CTA label. Every brand CTA uses it (guardrail: same label, same destination).
 * Set "cta_label" in the site config to a strings.json key or literal text; default depends on brand kind.
 */
function sx_cta_label(bool $short = false): string
{
    $l = ($short ? SX::$ctx['cta_short'] ?? null : null) ?? SX::$ctx['cta_label'] ?? null;
    if ($l) {
        return isset(SX::$ctx['strings'][$l]) ? t($l) : $l;
    }
    return t((SX::$ctx['brand']['kind'] ?? '') === 'casino' ? 'cta.play' : 'cta.bet');
}

/**
 * Brand CTA link. All CTAs go to links.go from the site config and carry data-cta=<position>
 * (header | hero | card | mid | final | sticky) for analytics. Header and sticky bar use cta_short when a
 * type sets one; long labels ("Play Emberforge at Marlowe Club") get btn--wrap so they break instead of overflowing.
 */
function sx_cta(string $pos, string $classes, bool $arrow = false): string
{
    if (!empty(SX::$ctx['cta_brand'])) {
        return sx_brand_cta(SX::$ctx['cta_brand'], $pos, $classes, $arrow);
    }
    $href = SX::$ctx['links']['go'] ?? '#go';
    $ext = preg_match('#^https?://#', $href) === 1;
    $label = sx_cta_label(in_array($pos, ['header', 'sticky'], true));
    if (mb_strlen($label) > 22) {
        $classes .= ' ' . cls('btn', null, 'wrap');
    }
    return '<a class="' . e($classes) . '" href="' . sx_url($href) . '" data-cta="' . e($pos) . '"'
        . ($ext ? ' rel="nofollow sponsored noopener" target="_blank"' : '') . '>'
        . e($label) . ($arrow ? sx_icon('arrow') : '') . '</a>';
}

function sx_payment_name(string $id): string
{
    return [
        'kaspi' => 'Kaspi', 'halyk' => 'Halyk', 'visa' => 'Visa', 'mastercard' => 'Mastercard',
        'payid' => 'PayID', 'bpay' => 'BPAY', 'visa-debit' => 'Visa Debit', 'mastercard-debit' => 'Mastercard Debit',
        'apple-pay' => 'Apple Pay', 'pix' => 'PIX', 'ted' => 'TED', 'debit' => 'Débito', 'bank' => t('pay.bank'),
    ][$id] ?? $id;
}

/** "~15 min", or "~4 h" above 90 minutes. */
function sx_minutes_label(int $n): string
{
    return $n > 90 ? t('trust.hours', ['n' => round($n / 60)]) : t('trust.minutes', ['n' => $n]);
}

/**
 * Brand proof points from brand facts + market, keyed by type. Ratings appear only when the brand
 * declares facts.rating_source (guardrail: no unsourced ratings).
 * Each item: [icon, value, label].
 */
function sx_proof_points(): array
{
    $b = SX::$ctx['brand'];
    $m = SX::$ctx['market'];
    $f = $b['facts'];
    if (!empty(SX::$ctx['cta_brand'])) {
        // Multi-brand sites: proof points describe the brand the CTA leads to, with this market's facts.
        $cb = sx_casino(SX::$ctx['cta_brand']);
        $f = ($cb['f'] ? ['payout_minutes' => min($cb['f']['withdrawal_minutes'])] : []) + $cb['facts'];
    } elseif (!empty($b['id']) && function_exists('sx_casino') && !empty(sx_casino($b['id'])['f']['withdrawal_minutes'])) {
        // This market's own facts (brands/facts/<market>.json) win over the brand-wide figure.
        $f = ['payout_minutes' => min(sx_casino($b['id'])['f']['withdrawal_minutes'])] + $f;
    }
    $p = [];
    if (!empty($f['rating_source'])) {
        $p['rating'] = ['star', number_format($f['rating'], 1), tm($f['rating_source'])];
    }
    $p['licence'] = ['shield', tm($m['compliance']['license_short']), t('trust.licensed')];
    if (isset($f['payout_minutes'])) {
        $p['payout'] = ['bolt', sx_minutes_label($f['payout_minutes']), t('trust.payout')];
    }
    if (!empty($f['founded'])) {
        $p['since'] = ['clock', (string)$f['founded'], t('trust.since')];
    }
    $p['payments'] = ['wallet', implode(' · ', array_map('sx_payment_name', array_slice($m['payments'], 0, 3))), t('footer.payments')];
    return $p;
}

/** Register a JSON-LD object for the page <head>. */
function sx_jsonld(array $data): void
{
    SX::$jsonld[] = ['@context' => 'https://schema.org'] + $data;
}

/** Icon families a site can use: picked per site by seed, or "icons" in the site config. */
const SX_ICON_FAMILIES = ['outline', 'filled', 'rounded'];

/**
 * Small inline icon set (currentColor), in the site's icon family:
 *   outline  thin strokes (1.8), round caps
 *   filled   solid glyphs with cut-outs (evenodd), square-capped heavy lines where a glyph is a line
 *   rounded  thick soft strokes (2.2) with tinted fills, softer and partly redrawn shapes
 */
function sx_icon(string $name): string
{
    $family = SX::$ctx['icons'] ?? 'outline';
    if ($family === 'filled') {
        $line = fn($d) => '<path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="square"/>';
        $paths = [
            'star' => '<path d="m12 2.6 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.4l-5.8 3.1 1.1-6.5-4.7-4.6 6.5-.9z"/>',
            'shield' => '<path fill-rule="evenodd" d="M12 2.5 20 5.7v5.8c0 5-3.4 9.1-8 10.3-4.6-1.2-8-5.3-8-10.3V5.7zM8.1 12.6l1.4-1.4 1.6 1.6 3.8-3.9 1.4 1.4-5.2 5.3z"/>',
            'bolt' => '<path d="M13.6 1.8 4 14.2h6.6L9.6 22.2l10-12.9h-6.8z"/>',
            'chat' => '<path d="M5 3.5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H9.2L3 21.6V5.5a2 2 0 0 1 2-2z"/>',
            'user' => '<circle cx="12" cy="7.8" r="4.6"/><path d="M3.4 21.5a8.6 8.6 0 0 1 17.2 0z"/>',
            'clock' => '<path fill-rule="evenodd" d="M12 2.8a9.2 9.2 0 1 0 0 18.4 9.2 9.2 0 0 0 0-18.4zM10.9 6.5h2.2v5l3.4 2.1-1.1 1.8-4.5-2.8z"/>',
            'arrow' => $line('M4 12h15m-5.6-6 6 6-6 6'),
            'menu' => '<rect x="3" y="4.8" width="18" height="2.8" rx="1.4"/><rect x="3" y="10.6" width="18" height="2.8" rx="1.4"/><rect x="3" y="16.4" width="18" height="2.8" rx="1.4"/>',
            'phone' => '<path d="M6.2 2.8h3.6l1.8 4.6-2.3 1.7a10.6 10.6 0 0 0 5.6 5.6l1.7-2.3 4.6 1.8v3.6a2.4 2.4 0 0 1-2.4 2.4A17.4 17.4 0 0 1 3.8 5.2a2.4 2.4 0 0 1 2.4-2.4z"/>',
            'info' => '<path fill-rule="evenodd" d="M12 2.8a9.2 9.2 0 1 0 0 18.4 9.2 9.2 0 0 0 0-18.4zm-1.2 7.6h2.4v7.2h-2.4zm0-3.8h2.4V9h-2.4z"/>',
            'layers' => '<path d="m12 2.4 9.6 5.3L12 13 2.4 7.7z"/><path d="M4.3 12.5 12 16.8l7.7-4.3 1.9 1.1L12 19 2.4 13.6z"/>',
            'play' => '<path fill-rule="evenodd" d="M5 4.4h14a2.6 2.6 0 0 1 2.6 2.6v10a2.6 2.6 0 0 1-2.6 2.6H5A2.6 2.6 0 0 1 2.4 17V7A2.6 2.6 0 0 1 5 4.4zm4.9 4.5v6.2l5.3-3.1z"/>',
            'wallet' => '<path fill-rule="evenodd" d="M5.2 4.6h12.6v2.6H6.1a.8.8 0 0 0 0 1.6h13a1.6 1.6 0 0 1 1.6 1.6v7.4a2.2 2.2 0 0 1-2.2 2.2H5.2A2.2 2.2 0 0 1 3 17.8V6.8a2.2 2.2 0 0 1 2.2-2.2zm10.9 8.5a1.3 1.3 0 1 0 0 2.6 1.3 1.3 0 0 0 0-2.6z"/>',
            'gauge' => '<path fill-rule="evenodd" d="M2.8 17a9.2 9.2 0 0 1 18.4 0zm8.4-1.4 1.5 1.3 3.7-4.9-1.3-1z"/><rect x="3" y="18.8" width="18" height="2" rx="1"/>',
            'chart' => '<rect x="3.6" y="12" width="4" height="8.8" rx="1"/><rect x="10" y="7" width="4" height="13.8" rx="1"/><rect x="16.4" y="3" width="4" height="17.8" rx="1"/>',
            'mobile' => '<path fill-rule="evenodd" d="M8.6 1.8h6.8a2.7 2.7 0 0 1 2.7 2.7v15a2.7 2.7 0 0 1-2.7 2.7H8.6a2.7 2.7 0 0 1-2.7-2.7v-15a2.7 2.7 0 0 1 2.7-2.7zm2.3 16.2v1.6h2.2V18z"/>',
            'chevron' => '<path d="M5.2 8.2 12 15l6.8-6.8 1.5 1.5-8.3 8.3-8.3-8.3z"/>',
            'check' => '<path d="m3.8 12.2 1.7-1.7 4 4 9-9 1.7 1.7L9.5 17.9z"/>',
            'download' => '<path d="M10.8 3h2.4v9l3.2-3.2 1.7 1.7L12 16.6l-6.1-6.1 1.7-1.7 3.2 3.2zM4.4 18.4h15.2v2.4H4.4z"/>',
        ];
        return sx_use('i-' . $name, fn() => '<svg viewBox="0 0 24 24"><g fill="currentColor" stroke="none">' . ($paths[$name] ?? '') . '</g></svg>', cls('icon'));
    }
    if ($family === 'rounded') {
        $tint = ' fill="currentColor" fill-opacity=".18"';
        $paths = [
            'star' => '<path d="m12 3.5 2.6 5.3 5.8.8-4.2 4.1 1 5.8L12 16.8l-5.2 2.7 1-5.8-4.2-4.1 5.8-.8z" fill="currentColor"/>',
            'shield' => '<path d="M12 3.2c2.6 1.6 5 2.3 7.5 2.5v5.6c0 4.6-3 8.3-7.5 9.5-4.5-1.2-7.5-4.9-7.5-9.5V5.7c2.5-.2 4.9-.9 7.5-2.5z"' . $tint . '/><path d="m8.8 12 2.3 2.3 4.1-4.3"/>',
            'bolt' => '<path d="M13.2 2.8 5.4 13h6.1l-.8 8.2 7.9-10.6h-6z"' . $tint . '/>',
            'chat' => '<path d="M6 4.5h12a2.5 2.5 0 0 1 2.5 2.5v7a2.5 2.5 0 0 1-2.5 2.5h-6.5L7 20v-3.5H6A2.5 2.5 0 0 1 3.5 14V7A2.5 2.5 0 0 1 6 4.5z"' . $tint . '/>',
            'user' => '<circle cx="12" cy="8" r="3.8"' . $tint . '/><path d="M4.8 20.2c.9-3.6 3.8-5.7 7.2-5.7s6.3 2.1 7.2 5.7"/>',
            'clock' => '<circle cx="12" cy="12" r="8.8"' . $tint . '/><path d="M12 7.2V12l3.2 2.1"/>',
            'arrow' => '<circle cx="12" cy="12" r="9.2" fill="currentColor" fill-opacity=".16" stroke="none"/><path d="M8 12h8m-3.4-3.6L16.2 12l-3.6 3.6"/>',
            'menu' => '<path d="M4.5 7h15M4.5 12h10M4.5 17h15"/>',
            'phone' => '<path d="M7 3.8h2.6l1.6 4.1-2 1.6a11 11 0 0 0 5.3 5.3l1.6-2 4.1 1.6V17a2.6 2.6 0 0 1-2.8 2.6A16 16 0 0 1 4.4 6.6 2.6 2.6 0 0 1 7 3.8z"' . $tint . '/>',
            'info' => '<circle cx="12" cy="12" r="8.8"' . $tint . '/><path d="M12 11.2v5M12 7.8v.1"/>',
            'layers' => '<path d="m12 3.5 8.5 4.6L12 12.7 3.5 8.1z"' . $tint . '/><path d="m3.5 12.4 8.5 4.6 8.5-4.6M3.5 16.4 12 21l8.5-4.6"/>',
            'play' => '<rect x="2.8" y="4.8" width="18.4" height="14.4" rx="4"' . $tint . '/><path d="M10.2 9.3v5.4l4.6-2.7z" fill="currentColor"/>',
            'wallet' => '<rect x="3.5" y="6" width="17" height="13.5" rx="3.5"' . $tint . '/><path d="M7 6v-.8A1.7 1.7 0 0 1 9 3.5l8 1.6"/><path d="M16 12.8h.1"/>',
            'gauge' => '<path d="M4 16.5a8 8 0 0 1 16 0"' . $tint . '/><path d="m12 16.5 3-4"/><circle cx="12" cy="16.5" r="1.2" fill="currentColor"/>',
            'chart' => '<rect x="4" y="11" width="4" height="9" rx="1.6"' . $tint . '/><rect x="10" y="7" width="4" height="13" rx="1.6"' . $tint . '/><rect x="16" y="4" width="4" height="16" rx="1.6"' . $tint . '/>',
            'mobile' => '<rect x="6.5" y="2.5" width="11" height="19" rx="3.2"' . $tint . '/><path d="M10.8 18.2h2.4"/>',
            'chevron' => '<path d="m7 9.5 5 5 5-5"/>',
            'check' => '<circle cx="12" cy="12" r="9.2" fill="currentColor" fill-opacity=".16" stroke="none"/><path d="m8 12.3 2.8 2.8 5.4-5.5"/>',
            'download' => '<path d="M12 4.5v10m-4-4 4 4 4-4"/><rect x="4.5" y="17.5" width="15" height="3" rx="1.5"' . $tint . '/>',
        ];
        return sx_use('i-' . $name, fn() => '<svg viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">'
            . ($paths[$name] ?? '') . '</g></svg>', cls('icon'));
    }
    $paths = [
        'star' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z" fill="currentColor" stroke="none"/>',
        'shield' => '<path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.4 7.5 9.5 4.3-1.1 7.5-4.9 7.5-9.5V6z"/><path d="m8.8 12.2 2.3 2.3 4.2-4.5"/>',
        'bolt' => '<path d="M13 2 4.5 13.5H11L10 22l8.5-11.5H12z"/>',
        'chat' => '<path d="M4 5h16v11H9l-5 4z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0z"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'arrow' => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'phone' => '<path d="M6.5 3.5h3l1.5 4-2 1.5a11 11 0 0 0 6 6l1.5-2 4 1.5v3a2 2 0 0 1-2 2A16.5 16.5 0 0 1 4.5 5.5a2 2 0 0 1 2-2z"/>',
        'info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5M12 7.6v.4"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/>',
        'play' => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m10 9.5 5 2.5-5 2.5z" fill="currentColor"/>',
        'wallet' => '<path d="M4 7.5A2.5 2.5 0 0 1 6.5 5H18v3"/><rect x="4" y="8" width="16" height="11" rx="2.5"/><path d="M16 13.5h.01"/>',
        'gauge' => '<path d="M4.5 16a7.5 7.5 0 1 1 15 0"/><path d="m12 16 3.5-4.5"/><path d="M4 19.5h16"/>',
        'chart' => '<path d="M4 20V4m0 16h16"/><path d="m7 15 4-4 3 3 5-6"/>',
        'mobile' => '<rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'download' => '<path d="M12 4v11m-4.5-4.5L12 15l4.5-4.5"/><path d="M5 19.5h14"/>',
    ];
    // Stroke styling lives on a <g> inside the symbol so it survives <use> cloning.
    return sx_use('i-' . $name, fn() => '<svg viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'
        . ($paths[$name] ?? '') . '</g></svg>', cls('icon'));
}

/** Escaped URL; anything other than http(s), relative or fragment becomes "#". */
function sx_url($url): string
{
    $url = (string)$url;
    return preg_match('#^(https?://|/|\#|\?)#', $url) || preg_match('#^[a-z0-9][a-z0-9/_.-]*$#i', $url) ? e($url) : '#';
}

/**
 * Count phrase with plural forms: uses "<key>.one" / "<key>.few" when they exist, else "<key>".
 * Russian: one (1, 21), few (2-4, 22-24), many; other locales: one (1) / many.
 */
function sx_plural(string $key, int $n): string
{
    $form = '';
    if ((SX::$ctx['locale'] ?? '') === 'ru') {
        $form = $n % 10 === 1 && $n % 100 !== 11 ? '.one' : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 12 || $n % 100 > 14) ? '.few' : '');
    } elseif ($n === 1) {
        $form = '.one';
    }
    return t(isset(SX::$ctx['strings'][$key . $form]) ? $key . $form : $key, ['n' => $n]);
}
