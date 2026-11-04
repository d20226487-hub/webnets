<?php
/**
 * WP adapter for the custom theme (ACF PRO 6+).
 *
 * Registers every /blocks/<name>/block.json as an ACF block, maps ACF field values to the
 * same $props array the static builder uses, and enqueues the compiled CSS for the site's preset.
 *
 * Install: copy satellite-system/ into the theme as /sx and add to functions.php:
 *     require get_template_directory() . '/sx/wp/sx-blocks.php';
 * Field groups: run `php wp/generate-acf-json.php` and copy dist/wp/acf-json/*.json into the
 * theme's acf-json/ folder (ACF local JSON), or point ACF's load_json filter at that folder.
 *
 * Site settings (market, locale, brand, preset, seed, class prefix) come from the `sx_site`
 * option (JSON) and can be overridden with the `sx_site_config` filter.
 */

defined('ABSPATH') || exit;

if (!defined('SX_ROOT')) {
    define('SX_ROOT', dirname(__DIR__));
}
require_once SX_ROOT . '/lib/render.php';
require_once SX_ROOT . '/lib/tokens.php';

add_action('init', function () {
    foreach (glob(SX_ROOT . '/blocks/*/block.json') as $json) {
        register_block_type(dirname($json));
    }
});

add_filter('block_categories_all', function (array $cats) {
    $cats[] = ['slug' => 'sx-conversion', 'title' => 'SX: Conversion'];
    $cats[] = ['slug' => 'sx-shared', 'title' => 'SX: Shared'];
    return $cats;
});

add_filter('acf/settings/load_json', function (array $paths) {
    $paths[] = SX_ROOT . '/dist/wp/acf-json';
    return $paths;
});

function sx_wp_site_config(): array
{
    $saved = json_decode((string)get_option('sx_site', ''), true) ?: [];
    $cfg = $saved + [
        'market' => 'br',
        'locale' => null,
        'brand' => 'vantro',
        'preset' => 'clean-sportsbook',
        'seed' => (string)wp_parse_url(home_url(), PHP_URL_HOST),
        'prefix' => 'sx-',
        'site_name' => get_bloginfo('name'),
    ];
    return apply_filters('sx_site_config', $cfg);
}

function sx_wp_context(): void
{
    if (SX::$ctx) {
        return;
    }
    $cfg = sx_wp_site_config();
    $market = json_decode(file_get_contents(SX_ROOT . "/markets/{$cfg['market']}.json"), true);
    $brands = json_decode(file_get_contents(SX_ROOT . '/brands/operators.json'), true);
    sx_context([
        'root' => SX_ROOT,
        'market' => $market,
        'locale' => $cfg['locale'] ?: $market['default_locale'],
        'brand' => $brands[$cfg['brand']],
        'seed' => $cfg['seed'],
        'prefix' => $cfg['prefix'],
        'site' => ['name' => $cfg['site_name']],
    ]);
}

/** Convert ACF values to block props. Empty values are dropped so fields.json defaults apply. */
function sx_acf_props(array $fields, array $values): array
{
    $props = [];
    foreach ($fields as $f) {
        $name = $f['name'];
        if (!array_key_exists($name, $values)) {
            continue;
        }
        $v = $values[$name];
        switch ($f['type']) {
            case 'link':
                $v = !empty($v['url']) ? ['label' => $v['title'] ?? '', 'url' => $v['url']] : null;
                break;
            case 'repeater':
                $v = array_map(fn($row) => sx_acf_props($f['fields'] ?? [], (array)$row), $v ?: []);
                break;
            case 'group':
                $v = $v ? sx_acf_props($f['fields'] ?? [], (array)$v) : null;
                break;
            case 'true_false':
                $v = (bool)$v;
                break;
        }
        if ($v === '' || $v === null || $v === []) {
            continue;
        }
        $props[$name] = $v;
    }
    return $props;
}

/** ACF renderCallback declared in every block.json. */
function sx_acf_render(array $block, string $content = '', bool $is_preview = false, $post_id = 0): void
{
    sx_wp_context();
    $name = preg_replace('#^sx/#', '', $block['name']);
    $meta = sx_block_meta($name);
    $props = sx_acf_props($meta['fields'], get_fields() ?: []);
    if ($is_preview && $name === 'age-gate') {
        $props['preview'] = true;
    }
    echo sx_block($name, $props);
}

/** Tokens for the site's preset + CSS for every block, prefixed, cached as a file in uploads. */
add_action('wp_enqueue_scripts', function () {
    sx_wp_context();
    $cfg = sx_wp_site_config();
    $preset = sx_load_preset($cfg['preset'], SX_ROOT);

    $blocks = array_map(fn($j) => basename(dirname($j)), glob(SX_ROOT . '/blocks/*/block.json'));
    $version = substr(md5($cfg['preset'] . $cfg['prefix'] . implode(',', array_map('filemtime', glob(SX_ROOT . '/{tokens,blocks/*}/*.{css,json}', GLOB_BRACE)))), 0, 10);
    $upload = wp_upload_dir();
    $file = "{$upload['basedir']}/sx/site-$version.css";
    if (!is_file($file)) {
        wp_mkdir_p(dirname($file));
        $css = sx_vars_to_css(sx_token_vars($preset), ':root') . sx_collect_css($blocks);
        file_put_contents($file, $css);
    }
    wp_enqueue_style('sx-fonts', sx_google_fonts_url([$preset]), [], null);
    wp_enqueue_style('sx-site', "{$upload['baseurl']}/sx/site-$version.css", ['sx-fonts'], null);
});

/** Print the SVG sprite (icons, logos) once per page, after all blocks have rendered. */
add_action('wp_footer', function () {
    echo sx_sprite();
}, 5);
