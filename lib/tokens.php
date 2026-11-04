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
function sx_look_tokens(?string $look, array $brand, string $root, ?array $provider = null): array
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
