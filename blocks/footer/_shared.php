<?php
/** Shared prep for footer variants. Defines $legal, $payments, $about, $compliance, $rights, $siteNav. */

$market = sx_ctx('market');
$brand = sx_ctx('brand');
$site = sx_ctx('site');

$payments = array_map('sx_payment_name', $market['payments']);

$pages = sx_pages();
$legal = array_merge($props['links'] ?? [], [
    ['label' => t('footer.rg'), 'url' => isset($pages['responsible']) ? sx_page_url('responsible') : '#responsible-gambling'],
    ['label' => t('footer.terms'), 'url' => '#terms'],
    ['label' => t('footer.privacy'), 'url' => '#privacy'],
    ['label' => t('footer.contact'), 'url' => '#contact'],
]);

$about = $props['about'] ?: tm($brand['tagline']);
$compliance = sx_block('compliance-bar', ['variant' => 'panel', 'show_verify' => !empty($props['show_verify'])]);
$rights = '© ' . e($site['year']) . ' ' . e($site['name']) . '. ' . e(t('footer.rights'));

// Multi-page sites: every page (home included) in the footer, so no page depends on the header menu alone.
$siteNav = [];
if ($pages) {
    SX::$used['_sitenav'] = 1; // CSS for nav states ships only on multi-page sites
}
foreach (array_filter($pages, fn($p) => empty($p['paged_of']) && empty($p['item'])) as $pid => $p) {
    $siteNav[] = ['label' => $p['nav'], 'url' => sx_page_url($pid), 'current' => $pid === sx_page_id()];
}
