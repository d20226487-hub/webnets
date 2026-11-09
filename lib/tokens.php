<?php
/**
 * Token compiler: preset JSON -> CSS custom properties, Google Fonts URL, WP theme.json.
 * Used by tokens/build.php (all presets) and build/build.php (one preset + site overrides).
 */

const SX_SPACE_STEPS = [4, 8, 12, 16, 24, 32, 48, 64, 96];

function sx_load_preset(string $id, string $root): array
{
    $file = "$root/tokens/presets/$id.json";
    if (!is_file($file)) {
        throw new RuntimeException("Unknown preset '$id' ($file)");
    }
    return json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
}

function sx_all_presets(string $root): array
{
    $out = [];
    foreach (glob("$root/tokens/presets/*.json") as $f) {
        $p = json_decode(file_get_contents($f), true, 512, JSON_THROW_ON_ERROR);
        $out[$p['id']] = $p;
    }
    ksort($out);
    return $out;
}

/** Deep-merge site-level overrides (e.g. brand colour) onto a preset. */
/**
 * A look: named token overrides on top of the preset (tokens/looks.json), or "brand", built from the brand colour.
 * Same layout and blocks; colour, type and shape change.
 */
function sx_look_tokens(?string $look, array $brand, string $root, ?array $provider = null, int $variant = 0): array
{
    if (!$look || $look === 'preset') {
        return [];
    }
    if ($look === 'brand' || $look === 'provider') {
        if ($look === 'provider' && empty($provider['colors'][0])) {
            throw new RuntimeException('look "provider" needs a site with a provider that has colors (brands/providers.json).');
        }
        $c = $look === 'provider' ? $provider['colors'][0] : ($brand['brand_color'] ?? '#2457FF'); // the studio's own colours, or the casino brand's
        $hex = ltrim($c, '#');
        $dark = '#' . implode('', array_map(fn($i) => str_pad(dechex((int) (hexdec(substr($hex, $i, 2)) * 0.55)), 2, '0', STR_PAD_LEFT), [0, 2, 4]));
        $on = sx_luminance_hex($hex) > 0.55 ? '#111111' : '#FFFFFF';
        $deep = fn(float $f) => '#' . implode('', array_map(fn($i) => str_pad(dechex((int) (hexdec(substr($hex, $i, 2)) * $f)), 2, '0', STR_PAD_LEFT), [0, 2, 4]));
        if ($variant % 5) {
            return sx_look_palette($c, $variant % 5);
        }
        // hero in a deep shade of the brand so the brand-coloured install button stands out on it
        return ['theme' => 'light', 'color' => ['accent' => $c, 'onAccent' => $on, 'accent2' => $dark, 'heroText' => '#FFFFFF', 'heroMuted' => 'rgba(255,255,255,.78)'],
            'decor' => ['heroBg' => 'radial-gradient(120% 90% at 85% 10%, ' . $deep(0.62) . ' 0%, ' . $deep(0.3) . ' 55%, ' . $deep(0.18) . ' 100%)']];
    }
    $looks = json_decode(file_get_contents("$root/tokens/looks.json"), true);
    return $looks[$look] ?? throw new RuntimeException("Unknown look '$look' (tokens/looks.json: neutral, play, appstore; or brand, preset).");
}

function sx_merge_tokens(array $preset, array $override): array
{
    foreach ($override as $k => $v) {
        $preset[$k] = (is_array($v) && isset($preset[$k]) && is_array($preset[$k]))
            ? sx_merge_tokens($preset[$k], $v)
            : $v;
    }
    return $preset;
}

function sx_px(float $v): string
{
    return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') . 'px';
}

/** Fluid size between 360px and 1200px viewport. */
function sx_fluid(float $min, float $max): string
{
    if (abs($max - $min) < 0.5) {
        return sx_px($max);
    }
    $slope = ($max - $min) / 840;
    $intercept = $min - $slope * 360;
    return sprintf('clamp(%s, %s + %svw, %s)', sx_px($min), sx_px($intercept), round($slope * 100, 3), sx_px($max));
}

function sx_font_stack(string $family, string $role): string
{
    $fallback = [
        'display' => 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
        'body'    => 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
        'mono'    => 'ui-monospace, "SFMono-Regular", Consolas, monospace',
    ][$role];
    $serifs = ['Cormorant Garamond', 'Lora', 'PT Serif', 'Prata'];
    if (in_array($family, $serifs, true)) {
        $fallback = 'Georgia, "Times New Roman", serif';
    }
    return "\"$family\", $fallback";
}

/** Returns [cssVarName => value]. */
function sx_token_vars(array $p): array
{
    $c = $p['color'];
    $t = $p['type'];
    $d = (float)($p['space']['density'] ?? 1);
    $base = (float)$t['base'];
    $scale = (float)$t['scale'];

    $v = [
        '--c-bg' => $c['bg'],
        '--c-surface' => $c['surface'],
        '--c-surface-2' => $c['surface2'],
        '--c-text' => $c['text'],
        '--c-muted' => $c['muted'],
        '--c-border' => $c['border'],
        '--c-accent' => $c['accent'],
        '--c-on-accent' => $c['onAccent'],
        '--c-accent-2' => $c['accent2'],
        '--c-success' => $c['success'] ?? '#1F9D55',
        '--c-warning' => $c['warning'] ?? '#D98B00',
        '--c-danger' => $c['danger'] ?? '#D93636',
        '--c-hero-text' => $c['heroText'] ?? $c['text'],
        '--c-hero-muted' => $c['heroMuted'] ?? $c['muted'],
        '--c-hero-accent' => $p['decor']['heroAccent'] ?? $c['accent'],
        '--c-hero-on-accent' => $p['decor']['heroOnAccent'] ?? $c['onAccent'],
        '--hero-bg' => $p['decor']['heroBg'] ?? $c['bg'],

        '--f-display' => sx_font_stack($p['fonts']['display']['family'], 'display'),
        '--f-body' => sx_font_stack($p['fonts']['body']['family'], 'body'),
        '--f-mono' => sx_font_stack($p['fonts']['mono']['family'] ?? $p['fonts']['body']['family'], isset($p['fonts']['mono']) ? 'mono' : 'body'),
        '--fw-heading' => (string)$t['headingWeight'],
        '--fw-body' => (string)$t['bodyWeight'],
        '--fw-strong' => (string)max($p['fonts']['body']['weights']),
        '--tt-heading' => $t['headingCase'],
        '--ls-heading' => $t['headingTracking'],
        '--lh-body' => (string)$t['lineHeight'],
        '--lh-heading' => $t['headingCase'] === 'uppercase' ? '1.08' : '1.15',

        '--r-sm' => $p['shape']['radiusSm'],
        '--r' => $p['shape']['radius'],
        '--r-lg' => $p['shape']['radiusLg'],
        '--r-btn' => $p['shape']['buttonRadius'],
        '--bw' => $p['shape']['borderWidth'],
        '--density' => (string)$d,
    ];

    // Type scale: fs-0 small, fs-1 body, fs-2..fs-6 headings (fs-3+ fluid).
    $v['--fs-0'] = sx_px($base / $scale);
    $v['--fs-1'] = sx_px($base);
    for ($n = 2; $n <= 6; $n++) {
        $max = $base * $scale ** $n;
        $v["--fs-$n"] = $n >= 3 ? sx_fluid(max($base * 1.15, $max * 0.68), $max) : sx_px($max);
    }

    foreach (SX_SPACE_STEPS as $i => $step) {
        $v['--sp-' . ($i + 1)] = sx_px($step * $d);
    }

    // Surface treatment drives every "card" in the library.
    $style = $p['surface']['style'];
    $v['--card-bg'] = $c['surface'];
    $v['--card-border'] = $style === 'outlined' ? $c['border'] : ($style === 'flat' ? 'transparent' : 'transparent');
    $v['--card-shadow'] = $style === 'elevated' ? $p['surface']['shadow'] : 'none';
    $v['--surface-style'] = $style;

    return $v;
}

function sx_vars_to_css(array $vars, string $selector): string
{
    $lines = [];
    foreach ($vars as $k => $val) {
        $lines[] = "  $k: $val;";
    }
    return "$selector {\n" . implode("\n", $lines) . "\n}\n";
}

/** Google Fonts css2 URL for one or more presets (families merged, weights unioned). */
function sx_google_fonts_url(array $presets): string
{
    $fam = [];
    foreach ($presets as $p) {
        foreach ($p['fonts'] as $f) {
            $fam[$f['family']] = array_unique(array_merge($fam[$f['family']] ?? [], $f['weights']));
        }
    }
    ksort($fam);
    $parts = [];
    foreach ($fam as $name => $w) {
        sort($w);
        $parts[] = 'family=' . str_replace(' ', '+', $name) . ':wght@' . implode(';', $w);
    }
    return 'https://fonts.googleapis.com/css2?' . implode('&', $parts) . '&display=swap';
}

/** WP theme.json (v3) generated from the same preset, for the ACF custom theme. */
function sx_theme_json(array $p): array
{
    $v = sx_token_vars($p);
    $palette = [];
    foreach (['bg', 'surface', 'surface2', 'text', 'muted', 'border', 'accent', 'onAccent', 'accent2'] as $k) {
        $palette[] = ['slug' => strtolower(preg_replace('/([A-Z])/', '-$1', $k)), 'name' => ucfirst($k), 'color' => $p['color'][$k]];
    }
    $sizes = [];
    for ($n = 0; $n <= 6; $n++) {
        $sizes[] = ['slug' => "fs-$n", 'name' => "Step $n", 'size' => $v["--fs-$n"]];
    }
    $spacing = [];
    foreach (SX_SPACE_STEPS as $i => $_) {
        $spacing[] = ['slug' => 'sp-' . ($i + 1), 'name' => 'Space ' . ($i + 1), 'size' => $v['--sp-' . ($i + 1)]];
    }
    $custom = [];
    foreach ($v as $k => $val) {
        if (str_starts_with($k, '--r') || str_starts_with($k, '--card') || in_array($k, ['--bw', '--hero-bg', '--tt-heading', '--ls-heading', '--fw-heading'], true)) {
            $custom[ltrim($k, '-')] = $val;
        }
    }
    return [
        '$schema' => 'https://schemas.wp.org/trunk/theme.json',
        'version' => 3,
        'settings' => [
            'appearanceTools' => true,
            'color' => ['palette' => $palette, 'custom' => false, 'defaultPalette' => false],
            'typography' => [
                'fontFamilies' => [
                    ['slug' => 'display', 'name' => $p['fonts']['display']['family'], 'fontFamily' => $v['--f-display']],
                    ['slug' => 'body', 'name' => $p['fonts']['body']['family'], 'fontFamily' => $v['--f-body']],
                ],
                'fontSizes' => $sizes,
                'defaultFontSizes' => false,
            ],
            'spacing' => ['spacingSizes' => $spacing, 'defaultSpacingSizes' => false],
            'custom' => $custom,
        ],
        'styles' => [
            'color' => ['background' => $p['color']['bg'], 'text' => $p['color']['text']],
            'typography' => ['fontFamily' => 'var(--wp--preset--font-family--body)', 'lineHeight' => (string)$p['type']['lineHeight']],
        ],
    ];
}

/* ---------- WCAG contrast ---------- */

function sx_luminance(string $hex): float
{
    $hex = ltrim($hex, '#');
    $rgb = array_map(fn($i) => hexdec(substr($hex, $i, 2)) / 255, [0, 2, 4]);
    $lin = array_map(fn($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4, $rgb);
    return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
}

function sx_contrast(string $a, string $b): float
{
    $l1 = sx_luminance($a);
    $l2 = sx_luminance($b);
    return round((max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05), 2);
}

/** Pairs every preset must pass. [label, fg, bg, minimum]. */
function sx_contrast_report(array $p): array
{
    $c = $p['color'];
    $pairs = [
        ['text on bg', $c['text'], $c['bg'], 4.5],
        ['muted on bg', $c['muted'], $c['bg'], 4.5],
        ['text on surface', $c['text'], $c['surface'], 4.5],
        ['muted on surface', $c['muted'], $c['surface'], 4.5],
        ['button label on accent', $c['onAccent'], $c['accent'], 4.5],
        ['hero button label', $p['decor']['heroOnAccent'] ?? $c['onAccent'], $p['decor']['heroAccent'] ?? $c['accent'], 4.5],
    ];
    $out = [];
    foreach ($pairs as [$label, $fg, $bg, $min]) {
        $ratio = sx_contrast($fg, $bg);
        $out[] = ['pair' => $label, 'ratio' => $ratio, 'min' => $min, 'pass' => $ratio >= $min];
    }
    return $out;
}

/**
 * Palettes built around one colour (look "brand" / "provider", "look_variant" 1-4): the colour stays the accent, so
 * the site still looks like the brand, while background, surfaces, second colour and hero change.
 *   1 night: dark theme tinted with the colour, hero a deep gradient towards a hue 60° away
 *   2 pastel: light theme, light tinted hero with dark text
 *   3 contrast: light theme, complementary second colour, hero in the complementary hues
 *   4 dusk: dark theme, neutral surfaces, hero a glow in a hue 120° away
 */
function sx_look_palette(string $c, int $variant): array
{
    [$h, $s, $l] = sx_hex_hsl($c);
    $on = sx_hsl_luminance($c) > 0.55 ? '#111111' : '#FFFFFF';
    $hx = fn(float $hh, float $ss, float $ll) => sx_hsl_hex($hh, $ss, $ll);
    return match ($variant) {
        1 => ['theme' => 'dark', 'color' => ['bg' => $hx($h, min($s, .35), .07), 'surface' => $hx($h, min($s, .3), .11), 'surface2' => $hx($h, min($s, .28), .15),
            'text' => '#F3F4F8', 'muted' => $hx($h, .12, .72), 'border' => $hx($h, .25, .21), 'accent' => $c, 'onAccent' => $on, 'accent2' => $hx($h + 30, max($s, .5), .64),
            'heroText' => '#FFFFFF', 'heroMuted' => 'rgba(255,255,255,.76)'],
            'decor' => ['heroBg' => 'linear-gradient(160deg, ' . $hx($h, .45, .1) . ' 0%, ' . $hx($h + 60, .55, .22) . ' 100%)']],
        2 => ['theme' => 'light', 'color' => ['bg' => '#FFFFFF', 'surface' => $hx($h, .4, .97), 'surface2' => $hx($h, .35, .93), 'text' => $hx($h, .3, .1),
            'muted' => $hx($h, .12, .38), 'border' => $hx($h, .25, .87), 'accent' => $c, 'onAccent' => $on, 'accent2' => $hx($h - 30, max($s, .5), .42),
            'heroText' => $hx($h, .4, .12), 'heroMuted' => $hx($h, .15, .34)],
            'decor' => ['heroBg' => 'linear-gradient(180deg, ' . $hx($h, .7, .93) . ' 0%, ' . $hx($h, .6, .985) . ' 100%)']],
        3 => ['theme' => 'light', 'color' => ['bg' => '#FFFFFF', 'surface' => '#F6F7F9', 'surface2' => '#ECEEF2', 'text' => '#14161B', 'muted' => '#555B66',
            'border' => '#E2E5EA', 'accent' => $c, 'onAccent' => $on, 'accent2' => $hx($h + 180, max($s, .55), .45), 'heroText' => '#FFFFFF', 'heroMuted' => 'rgba(255,255,255,.8)'],
            'decor' => ['heroBg' => 'linear-gradient(120deg, ' . $hx($h + 165, .55, .2) . ' 0%, ' . $hx($h + 200, .6, .3) . ' 100%)']],
        default => ['theme' => 'dark', 'color' => ['bg' => '#101114', 'surface' => '#17191E', 'surface2' => '#1F2229', 'text' => '#F2F3F5', 'muted' => '#A0A5B0',
            'border' => '#2A2E37', 'accent' => $c, 'onAccent' => $on, 'accent2' => $hx($h - 150, max($s, .55), .62), 'heroText' => '#FFFFFF', 'heroMuted' => 'rgba(255,255,255,.75)'],
            'decor' => ['heroBg' => 'radial-gradient(120% 100% at 85% 0%, ' . $hx($h - 120, .6, .3) . ' 0%, #101114 75%)']],
    };
}

/** '#RRGGBB' -> [hue 0-360, saturation 0-1, lightness 0-1]. */
function sx_hex_hsl(string $hex): array
{
    [$r, $g, $b] = array_map(fn($i) => hexdec(substr(ltrim($hex, '#'), $i, 2)) / 255, [0, 2, 4]);
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $l = ($max + $min) / 2;
    $d = $max - $min;
    if ($d == 0) {
        return [0.0, 0.0, $l];
    }
    $s = $d / (1 - abs(2 * $l - 1));
    $h = match ($max) {
        $r => 60 * fmod(($g - $b) / $d + 6, 6),
        $g => 60 * (($b - $r) / $d + 2),
        default => 60 * (($r - $g) / $d + 4),
    };
    return [$h, $s, $l];
}

function sx_hsl_hex(float $h, float $s, float $l): string
{
    $h = fmod(fmod($h, 360) + 360, 360);
    $s = max(0, min(1, $s));
    $l = max(0, min(1, $l));
    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m = $l - $c / 2;
    [$r, $g, $b] = match (intdiv((int)$h, 60)) {
        0 => [$c, $x, 0], 1 => [$x, $c, 0], 2 => [0, $c, $x], 3 => [0, $x, $c], 4 => [$x, 0, $c], default => [$c, 0, $x],
    };
    return sprintf('#%02X%02X%02X', (int)round(($r + $m) * 255), (int)round(($g + $m) * 255), (int)round(($b + $m) * 255));
}

function sx_hsl_luminance(string $hex): float
{
    $rgb = array_map(fn($i) => hexdec(substr(ltrim($hex, '#'), $i, 2)) / 255, [0, 2, 4]);
    return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
}

/**
 * A style variant of a preset (site config "style": {"hue": 0-359, "flip": bool}): every colour turned by "hue" degrees
 * around the colour wheel, and with "flip" the theme swapped by role: backgrounds, surfaces, borders and text trade light
 * for dark while the accents keep their lightness. Contrast is restored afterwards (text 4.5:1 and more, button labels
 * 4.5:1, links and accents 3:1 on the background), so every variant passes the same checks as the presets.
 */
function sx_style_variant(array $p, int $hue = 0, bool $flip = false): array
{
    if (!$hue && !$flip) {
        return $p;
    }
    $neutral = ['bg', 'surface', 'surface2', 'border', 'text', 'muted', 'heroText', 'heroMuted'];
    $turn = fn(string $hex) => sx_style_turn($hex, $hue, false);
    foreach ($p['color'] as $k => $v) {
        if (is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
            $p['color'][$k] = sx_style_turn($v, $hue, $flip && in_array($k, $neutral, true));
        }
    }
    foreach ($p['decor'] ?? [] as $k => $v) {
        if (is_string($v)) {
            $p['decor'][$k] = sx_style_each_hex($v, $k === 'heroBg' ? fn($h) => sx_style_turn($h, $hue, $flip) : $turn);
        }
    }
    if ($flip) {
        $p['theme'] = ($p['theme'] ?? 'light') === 'dark' ? 'light' : 'dark';
    }
    $c = &$p['color'];
    preg_match('/#[0-9a-fA-F]{6}/', $p['decor']['heroBg'] ?? '', $m);
    $hero = $m[0] ?? $c['bg'];
    $c['text'] = sx_style_contrast(sx_style_contrast($c['text'], $c['bg'], 7), $c['surface'], 4.5);
    $c['muted'] = sx_style_contrast(sx_style_contrast($c['muted'], $c['bg'], 4.5), $c['surface'], 4.5);
    $c['heroText'] = sx_style_contrast($c['heroText'] ?? $c['text'], $hero, 7);
    $c['heroMuted'] = sx_style_contrast($c['heroMuted'] ?? $c['muted'], $hero, 4.5);
    $c['accent'] = sx_style_contrast($c['accent'], $c['bg'], 3);
    $c['accent2'] = sx_style_contrast($c['accent2'], $c['bg'], 3);
    $on = fn(string $bg) => sx_contrast('#FFFFFF', $bg) >= sx_contrast('#111111', $bg) ? '#FFFFFF' : '#111111';
    $c['onAccent'] = $on($c['accent']);
    $c['accent'] = sx_style_contrast($c['accent'], $c['onAccent'], 4.5); // a mid-tone button: its colour moves until the label passes
    if (isset($p['decor']['heroAccent'])) {
        $p['decor']['heroOnAccent'] = $on($p['decor']['heroAccent']);
        $p['decor']['heroAccent'] = sx_style_contrast($p['decor']['heroAccent'], $p['decor']['heroOnAccent'], 4.5);
    }
    unset($c);
    return $p;
}

/** One colour of a variant: turned by $hue degrees; with $flip its lightness mirrored (light <-> dark). */
function sx_style_turn(string $hex, int $hue, bool $flip): string
{
    [$h, $s, $l] = sx_hex_hsl($hex);
    if ($flip) {
        $l = 1 - $l;
        $l = $l > 0.5 ? 0.88 + ($l - 0.5) * 0.24 : 0.04 + $l * 0.4;
        $s = min($s, 0.6);
    }
    return sx_hsl_hex($h + $hue, $s, $l);
}

/** Apply $f to every #RRGGBB and rgba() colour inside a CSS value (gradients included). */
function sx_style_each_hex(string $v, callable $f): string
{
    $v = preg_replace_callback('/#([0-9a-fA-F]{6})\b/', fn($m) => $f('#' . $m[1]), $v);
    return preg_replace_callback('/rgba\((\d+),\s*(\d+),\s*(\d+),/', function ($m) use ($f) {
        $hx = $f(sprintf('#%02X%02X%02X', $m[1], $m[2], $m[3]));
        return 'rgba(' . hexdec(substr($hx, 1, 2)) . ',' . hexdec(substr($hx, 3, 2)) . ',' . hexdec(substr($hx, 5, 2)) . ',';
    }, $v);
}

/** Move $fg's lightness away from $bg until their contrast reaches $min. */
function sx_style_contrast(string $fg, string $bg, float $min): string
{
    [$h, $s, $l] = sx_hex_hsl($fg);
    $up = sx_luminance($bg) < 0.18;
    for ($i = 0; $i < 40 && sx_contrast($fg, $bg) < $min; $i++) {
        $l = max(0, min(1, $l + ($up ? 0.03 : -0.03)));
        $fg = sx_hsl_hex($h, $s, $l);
    }
    return $fg;
}
