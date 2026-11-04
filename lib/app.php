<?php
/**
 * App-download helpers: which install paths a site offers, install buttons with per-platform
 * tracked links, device detection, app icon, CSS-drawn phone screens and dates.
 *
 * Platforms: "ios" (App Store), "android" (Google Play), "apk" (direct Android download).
 * Links come from the site config: links.ios, links.android, links.apk (links.qr for the QR code).
 */

require_once __DIR__ . '/qr.php';

function sx_app(): array
{
    return SX::$ctx['brand']['app'] ?? [];
}

/** Install paths for this site, in market order: market distribution ∩ brand platforms ∩ configured links. */
function sx_app_platforms(): array
{
    $apps = SX::$ctx['market']['apps'] ?? ['ios' => 'app-store', 'android' => 'play'];
    $want = [];
    if (($apps['ios'] ?? null) === 'app-store') {
        $want[] = 'ios';
    }
    $android = $apps['android'] ?? 'play';
    if ($android === 'play' || $android === 'both') {
        $want[] = 'android';
    }
    if ($android === 'apk' || $android === 'both') {
        $want[] = 'apk';
    }
    $only = SX::$ctx['platform_only'] ?? null; // a single-platform site keeps only that platform's install paths
    if ($only) {
        $want = array_values(array_intersect($want, $only === 'ios' ? ['ios'] : ['android', 'apk']));
    }
    $have = sx_app()['platforms'] ?? [];
    $out = [];
    foreach ($want as $p) {
        if (!in_array($p, $have, true)) {
            continue;
        }
        if (empty(SX::$ctx['links'][$p])) {
            SX::$warnings[] = "No links.$p in the site config; the $p install path is hidden."; // deduplicated when printed
            continue;
        }
        $out[] = $p;
    }
    if ((SX::$ctx['look'] ?? null) === 'play') { // the Google Play look leads with the Android paths (the device script still reorders on phones)
        usort($out, fn($a, $b) => ($a === 'ios') <=> ($b === 'ios'));
    }
    if (!$out) {
        SX::$warnings[] = 'No install path at all for this brand in this market.';
    } elseif (!array_intersect(['android', 'apk'], $out) && array_intersect(['android', 'apk'], $want)) {
        SX::$warnings[] = "Android visitors have no install path: market '" . SX::$ctx['market']['id'] . "' uses "
            . implode('/', array_intersect(['android', 'apk'], $want)) . ' but the brand does not offer it.';
    }
    return $out;
}

function sx_app_size(string $platform): string
{
    $mb = sx_app()['size_mb'][$platform === 'ios' ? 'ios' : 'android'] ?? null;
    return $mb ? t('app.mb', ['n' => $mb]) : '';
}

/** One install button. Badge style: small line + big line, like store badges, without store logos. */
function sx_install(string $platform, string $pos, string $extra = ''): string
{
    $href = SX::$ctx['links'][$platform];
    [$icon, $small, $big] = [
        'ios' => ['mobile', t('app.ios.small'), t('app.ios.big')],
        'android' => ['play', t('app.play.small'), t('app.play.big')],
        'apk' => ['download', t('app.apk.small', ['size' => sx_app_size('apk')]), t('app.apk.big')],
    ][$platform];
    $ext = preg_match('#^https?://#', $href) === 1;
    return '<a class="' . e(trim(cx('install', ['install', null, $platform]) . ' ' . $extra)) . '" href="' . sx_url($href) . '"'
        . ' data-cta="' . e($pos) . '" data-platform="' . e($platform) . '"' . ($ext ? ' rel="nofollow sponsored noopener" target="_blank"' : '') . '>'
        . sx_icon($icon) . '<span class="' . cls('install', 'text') . '"><small>' . e($small) . '</small><strong>' . e($big) . '</strong></span></a>';
}

/** All install buttons for the site. The platform script moves the visitor's platform first. */
function sx_install_group(string $pos, string $extra = ''): string
{
    $html = '';
    foreach (sx_app_platforms() as $p) {
        $html .= sx_install($p, $pos);
    }
    return '<div class="' . e(trim(cls('installs') . ' ' . $extra)) . '" data-installs>' . $html . '</div>';
}

/**
 * Single "Get the app" button whose link is swapped to the visitor's platform by the platform script.
 * $short uses the one-word label ("Get", "Скачать") for tight spaces: header and sticky bar.
 */
function sx_app_get(string $pos, string $classes, bool $short = false): string
{
    $ps = sx_app_platforms();
    $links = SX::$ctx['links'];
    $default = $ps ? $links[$ps[0]] : ($links['go'] ?? '#download');
    $attrs = '';
    foreach ($ps as $p) {
        $attrs .= ' data-href-' . $p . '="' . sx_url($links[$p]) . '"';
    }
    return '<a class="' . e($classes) . '" href="' . sx_url($default) . '" data-cta="' . e($pos) . '" data-app-get' . $attrs
        . ' rel="nofollow sponsored noopener" target="_blank">' . e(t($short ? 'app.get_short' : 'app.get')) . '</a>';
}

/**
 * Device detection, emitted once per page. Sets <html data-platform="ios|android|desktop">,
 * moves the matching install button first and points [data-app-get] links at the right store.
 */
function sx_platform_script(): string
{
    if (isset(SX::$once['platform'])) {
        return '';
    }
    SX::$once['platform'] = true;
    return <<<'JS'
<script>
(function () {
  var ua = navigator.userAgent, d = document.documentElement;
  var p = /iPhone|iPad|iPod/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1) ? 'ios' : (/Android/.test(ua) ? 'android' : 'desktop');
  d.setAttribute('data-platform', p);
  function run() {
    var first = p === 'android' ? ['android', 'apk'] : [p];
    document.querySelectorAll('[data-installs]').forEach(function (g) {
      first.slice().reverse().forEach(function (k) { var b = g.querySelector('[data-platform="' + k + '"]'); if (b) g.prepend(b); });
    });
    document.querySelectorAll('[data-app-get]').forEach(function (a) {
      var h = a.getAttribute('data-href-' + p) || (p === 'android' ? a.getAttribute('data-href-apk') : null);
      if (h) a.href = h;
    });
  }
  document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', run) : run();
})();
</script>
JS;
}

/** Rounded app icon: brand mark on the brand colour. */
function sx_app_icon(string $extra = ''): string
{
    $b = SX::$ctx['brand'];
    $svg = sx_logo_svg();
    $hex = ltrim($b['brand_color'], '#');
    $lum = sx_luminance_hex($hex);
    $fg = $lum > 0.45 ? '#111111' : '#FFFFFF';
    SX::$used['_phone'] = SX::$used['_phone'] ?? 1;
    return '<span class="' . e(trim(cls('appicon') . ' ' . $extra)) . '" style="background:' . e($b['brand_color']) . ';color:' . $fg . '" aria-hidden="true">' . $svg . '</span>';
}

function sx_luminance_hex(string $hex): float
{
    $rgb = array_map(fn($i) => hexdec(substr($hex, $i, 2)) / 255, [0, 2, 4]);
    return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
}

function sx_date(string $iso): string
{
    $fmt = sx_locale_format('date_format') ?? SX::$ctx['market']['date_format'] ?? 'Y-m-d';
    return date($fmt, strtotime($iso));
}

/**
 * Phone mockup. $screen: {kind: home|live|slip|wallet|casino|install, image?: url}.
 * With an image, the real screenshot is shown; without, a CSS-drawn screen in the preset's tokens.
 */
function sx_phone(array $screen, string $extra = '', bool $lazy = true): string
{
    SX::$used['_phone'] = SX::$used['_phone'] ?? 1;
    $kind = $screen['kind'] ?? 'home';
    if (empty($screen['image'])) {
        SX::$variantsUsed['_phone'][$kind] = true; // ship only this screen's CSS
    }
    $c = fn($el) => cls('phone', $el);
    if (!empty($screen['image'])) {
        $inner = '<img src="' . sx_url($screen['image']) . '" alt="" width="390" height="844"' . ($lazy ? ' loading="lazy" decoding="async"' : ' fetchpriority="high"') . '>';
    } else {
        // Compact markup: <i> = text line (data-m muted, data-a accent, data-r right), <b> = odds chip (data-on).
        $b = SX::$ctx['brand'];
        $bar = '<div class="' . $c('bar') . '"><span style="background:' . e($b['brand_color']) . '"></span><i style="width:38%"></i><i data-r style="width:16%"></i></div>';
        $line = fn($w, $mod = '') => '<i' . ($mod ? ' data-' . ['mut' => 'm', 'acc' => 'a', 'r' => 'r'][$mod] : '') . ' style="width:' . $w . '%"></i>';
        $odd = fn($v, $on = false) => '<b' . ($on ? ' data-on' : '') . '>' . $v . '</b>';
        switch ($kind) {
            case 'live':
                $body = '<div class="' . $c('score') . '"><span>54</span><i>:</i><span>47</span></div>' . '<div class="' . $c('prog') . '"><span style="width:62%"></span></div>'
                    . '<div class="' . $c('card') . '">' . $line(50) . '<div class="' . $c('odds') . '">' . $odd('1.62', true) . $odd('2.30') . '</div></div>'
                    . '<div class="' . $c('card') . '">' . $line(40) . '<div class="' . $c('odds') . '">' . $odd('1.90') . $odd('1.90') . '</div></div>'
                    . '<div class="' . $c('card') . '">' . $line(56) . '<div class="' . $c('odds') . '">' . $odd('1.87') . $odd('1.93') . '</div></div>';
                break;
            case 'slip':
                $rows = '';
                foreach (['1.62', '1.90', '3.05'] as $i => $o) {
                    $rows .= '<div class="' . $c('row') . '">' . $line([58, 46, 52][$i]) . '<b>' . $o . '</b></div>';
                }
                $body = '<div class="' . $c('card') . '">' . $line(30, 'acc') . $rows . '</div>' . '<div class="' . $c('total') . '">' . $line(30) . '<b>9.40</b></div>' . '<span class="' . $c('btn') . '"></span>';
                break;
            case 'wallet':
                $body = '<div class="' . $c('bal') . '">' . $line(30) . '<b>' . e(money(1250)) . '</b></div>'
                    . '<div class="' . $c('btns') . '"><span class="' . $c('btn') . '"></span><span class="' . $c('btn') . ' ' . $c('btn') . '--ghost"></span></div>';
                foreach ([1, 2, 3, 4] as $i) {
                    $body .= '<div class="' . $c('row') . '">' . $line([44, 36, 50, 40][$i - 1]) . $line(14, 'r') . '</div>';
                }
                break;
            case 'casino':
                $tiles = '';
                foreach (['#8C2B3A', '#1E5A44', '#2B3F8C', '#C9A45C', '#6E3BB5', '#B5482A'] as $col) {
                    $tiles .= '<span style="background:' . $col . '"></span>';
                }
                $body = $line(40, 'acc') . '<div class="' . $c('tiles') . '">' . $tiles . '</div>';
                break;
            case 'table': // live dealer table: video area, chip row, bet button
                $chips = '';
                foreach (['#D93636', '#1F6FEB', '#1F9D55', '#C9A45C', '#111111'] as $col) {
                    $chips .= '<span style="background:' . $col . '"></span>';
                }
                $body = '<div class="' . $c('video') . '"><span></span></div>' . $line(46) . $line(30, 'mut')
                    . '<div class="' . $c('chips') . '">' . $chips . '</div>' . '<span class="' . $c('btn') . '"></span>';
                break;
            case 'install':
                $body = '<div class="' . $c('dim') . '"><div class="' . $c('dialog') . '">' . $line(70) . $line(90) . $line(60)
                    . '<div class="' . $c('dbtns') . '"><span></span><span data-on></span></div></div></div>';
                break;
            default: // home
                $body = '<div class="' . $c('pills') . '"><span data-on></span><span></span><span></span></div>';
                foreach ([['1.62', '2.30'], ['1.85', '1.95'], ['2.05', '1.78'], ['1.50', '2.60']] as $i => [$a, $bb]) {
                    $body .= '<div class="' . $c('card') . '">' . $line([42, 34, 48, 38][$i]) . $line([30, 40, 26, 36][$i], 'mut')
                        . '<div class="' . $c('odds') . '">' . $odd($a, $i === 0) . $odd($bb) . '</div></div>';
                }
        }
        $inner = '<div class="' . $c('ui') . '" data-kind="' . e($kind) . '">' . $bar . $body . '</div>';
    }
    return '<div class="' . e(trim(cls('phone') . ' ' . $extra)) . '" aria-hidden="true"><div class="' . $c('screen') . '">' . $inner . '</div></div>';
}

/**
 * Store figures for one platform's listing (app.store.<ios|android>: rating, count, split %, downloads), or the other
 * platform's when this one has none. These must be copied from the real store listing (app.store.checked_at).
 */
function sx_store_figures(string $platform): ?array
{
    $s = sx_app()['store'] ?? [];
    $ps = sx_app_platforms(); // a single-platform page shows that store's figures whatever the look
    if ($platform === 'ios' && !in_array('ios', $ps, true) || $platform === 'android' && !array_intersect(['android', 'apk'], $ps)) {
        $platform = $platform === 'ios' ? 'android' : 'ios';
    }
    return $s[$platform] ?? $s[$platform === 'ios' ? 'android' : 'ios'] ?? null;
}

/** Store category label for the brand: sports or casino. */
function sx_store_category(): string
{
    return t(in_array('sports', SX::$ctx['brand']['verticals'] ?? [], true) ? 'store.cat.sports' : 'store.cat.casino');
}

/** 12400 -> "12.4K" (locale decimal mark). */
function sx_compact(int $n): string
{
    return $n >= 1000000 ? sx_number($n / 1000000, 1) . 'M' : ($n >= 1000 ? sx_number($n / 1000, $n >= 100000 ? 0 : 1) . 'K' : (string)$n);
}

