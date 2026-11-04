<?php
/**
 * Quick test mode for WordPress: a "Satellite page" template that renders a whole site type
 * from a pasted site config (the same JSON the static builder uses). No ACF needed.
 *
 * Install (in the theme's functions.php):
 *     require get_template_directory() . '/sx/wp/sx-page-template.php';
 *
 * Use: Pages → Add New → Page Attributes → Template: "Satellite page" → paste a config
 * (e.g. sites/demos/app-a-vantro-au.json) into the "Satellite site config" box → Publish → View.
 *
 * Multi-page types (e.g. "satellite"): create one WP page per site page, paste the same config into each,
 * and set "Site page id" (home, signup, payments, ...). Give the WP pages the slugs from the blueprint
 * (sign-up, payments, ...) under one parent page, and set "base" in the config to that parent's path
 * (e.g. "/vantro-guide") so the navigation links resolve. Leave "Site page id" empty for one-page types.
 *
 * The page is rendered as a standalone document (the theme's header, footer and plugin
 * scripts are not loaded), so what you see is exactly what the static build ships.
 * Logged-in editors see build warnings in a small bar at the top.
 */

defined('ABSPATH') || exit;

if (!defined('SX_ROOT')) {
    define('SX_ROOT', dirname(__DIR__));
}
require_once SX_ROOT . '/lib/page.php';

const SX_TEMPLATE = 'sx-satellite-page';
const SX_META = '_sx_site_config';
const SX_META_PAGE = '_sx_site_page';

add_filter('theme_page_templates', function (array $templates) {
    $templates[SX_TEMPLATE] = 'Satellite page';
    return $templates;
});

add_action('add_meta_boxes_page', function () {
    add_meta_box('sx-site-config', 'Satellite site config', function (WP_Post $post) {
        wp_nonce_field('sx_save_config', 'sx_config_nonce');
        $value = (string)get_post_meta($post->ID, SX_META, true);
        echo '<p>Paste a site config JSON (same format as <code>sites/demos/*.json</code>). '
            . 'Select the <strong>Satellite page</strong> template in Page Attributes.</p>';
        echo '<textarea name="sx_site_config" rows="18" style="width:100%;font-family:monospace">' . esc_textarea($value) . '</textarea>';
        echo '<p><label>Site page id (multi-page types only, e.g. <code>payments</code>): '
            . '<input name="sx_site_page" value="' . esc_attr((string)get_post_meta($post->ID, SX_META_PAGE, true)) . '"></label></p>';
        if ($value !== '' && json_decode($value) === null) {
            echo '<p style="color:#b32d2e"><strong>This is not valid JSON:</strong> ' . esc_html(json_last_error_msg()) . '</p>';
        }
    }, 'page', 'normal', 'high');
});

add_action('save_post_page', function (int $postId) {
    if (!isset($_POST['sx_config_nonce']) || !wp_verify_nonce($_POST['sx_config_nonce'], 'sx_save_config')) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_page', $postId)) {
        return;
    }
    update_post_meta($postId, SX_META, wp_unslash((string)($_POST['sx_site_config'] ?? '')));
    update_post_meta($postId, SX_META_PAGE, sanitize_key((string)($_POST['sx_site_page'] ?? '')));
});

add_filter('template_include', function (string $template) {
    if (!is_page() || get_page_template_slug() !== SX_TEMPLATE) {
        return $template;
    }
    $raw = (string)get_post_meta(get_queried_object_id(), SX_META, true);
    $cfg = json_decode($raw, true);
    if (!is_array($cfg)) {
        wp_die('This Satellite page has no valid site config. Edit the page and paste a config JSON into the "Satellite site config" box.', 'Satellite page', ['response' => 200]);
    }
    $cfg += ['id' => 'wp-' . get_queried_object_id()];

    try {
        $pages = sx_build_site($cfg, SX_ROOT);
    } catch (Throwable $e) {
        wp_die('The site config could not be rendered: ' . esc_html($e->getMessage()), 'Satellite page', ['response' => 200]);
    }
    $want = (string)get_post_meta(get_queried_object_id(), SX_META_PAGE, true);
    $match = array_values(array_filter($pages, fn($p) => empty($p['asset']) && ($want === '' || $p['id'] === $want)));
    if (!$match) {
        wp_die('This site config has no page "' . esc_html($want) . '". Check the "Site page id" box.', 'Satellite page', ['response' => 200]);
    }
    $page = $match[0];
    $html = $page['html'];

    if ($page['warnings'] && current_user_can('edit_pages')) {
        $bar = '<div style="position:sticky;top:0;z-index:9999;padding:8px 12px;background:#FFF4CE;color:#3b2f00;font:13px/1.4 system-ui,sans-serif;border-bottom:1px solid #e6cf7a">'
            . '<strong>Build warnings (visible to editors only):</strong> ' . esc_html(implode(' · ', $page['warnings'])) . '</div>';
        $html = preg_replace('/<body[^>]*>/', '$0' . $bar, $html, 1);
    }

    status_header(200);
    nocache_headers();
    echo $html;
    exit;
});
