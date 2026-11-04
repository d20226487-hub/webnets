<?php
/** Shared prep for header variants. Defines $nav, $sticky. */

$brand = sx_ctx('brand');
$market = sx_ctx('market');

$nav = $props['nav'] ?? [];
$navStyle = sx_ctx('nav_style') ?? 'top';
if (!$nav && sx_pages() && $navStyle === 'top') {
    $nav = sx_page_nav(); // multi-page sites: one item per page, current page marked
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
