<?php
/** Shared prep for header variants. Defines $nav, $sticky. */

$brand = sx_ctx('brand');
$market = sx_ctx('market');

$nav = $props['nav'] ?? [];
$navStyle = sx_ctx('nav_style') ?? 'top';
if (!$nav && sx_pages() && $navStyle === 'top') {
    $nav = sx_page_nav(); // multi-page sites: one item per page, current page marked
}
if (!$nav && !sx_pages() && !empty(sx_ctx('criteria'))) { // a one-page brand comparison: its own sections
    $nav = [['label' => t('nav.ranking'), 'url' => '#ranking'], ['label' => t('nav.compare'), 'url' => '#compare'], ['label' => t('nav.faq'), 'url' => '#faq']];
}
if (!$nav && !sx_pages()) {
    $map = ['sports' => 'nav.sports', 'live' => 'nav.live', 'casino' => 'nav.casino'];
    foreach ($brand['verticals'] as $v) {
        $gate = $v === 'live-casino' ? 'casino' : ($v === 'live' ? 'sports' : $v);
        if (isset($map[$v]) && !empty($market['verticals'][$gate])) {
            $nav[] = ['label' => t($map[$v]), 'url' => "#$v"];
        }
    }
    $nav[] = ['label' => t('nav.app'), 'url' => '#app'];
    $nav[] = ['label' => t('nav.payments'), 'url' => '#payments'];
}

$sticky = !empty($props['sticky']);

// phone menu (☰): the sidebar's content on sidebar sites, else the nav; on its side, opening as a dropdown, a drawer
// from that side, a full-width panel under the header or a full screen. One-page sidebar sites can skip it (the sidebar then shows under
// the content on phones).
$m = sx_ctx('menu') ?? ['side' => 'left', 'style' => 'dropdown', 'skip' => false];
$side = sx_side_menu();
if ($side && !empty($m['skip']) && !sx_pages()) {
    $side = [];
}
$menuItems = $side ? '' : implode('', array_map(fn($item) => '<li><a href="' . sx_url($item['url']) . '"' . (!empty($item['current']) ? ' aria-current="page"' : '') . '>' . e($item['label']) . '</a></li>', $nav));
$sideMenu = '';
$menu = '';
if ($side || $nav) {
    $label = $side['title'] ?? t('menu');
    $menu = '<details class="' . cx(['header', 'menu'], ['header', 'menu', $m['style']], ['header', 'menu', $m['side']]) . ($side ? ' ' . cls('header', 'menu', 'side') : '') . '" data-menu>'
        . '<summary aria-label="' . e($label) . '">' . sx_icon('menu') . '<span class="' . cls('header', 'close') . '" aria-hidden="true"></span></summary>'
        . ($m['style'] === 'dropdown' ? '' : '<div class="' . cls('header', 'backdrop') . '" data-menu-close></div>')
        . '<div class="' . cls('header', 'panel') . '"><p class="' . cls('header', 'paneltitle') . '">' . e($label) . '</p>'
        . '<ul>' . ($side ? sx_side_menu_items($side, 'menu') : $menuItems) . '</ul></div></details>';
    $sideMenu = $side ? $menu : '';
}
