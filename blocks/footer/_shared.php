<?php
/** Shared prep for footer variants. Defines $legal, $payments, $about, $compliance, $rights, $siteNav. */

$market = sx_ctx('market');
$brand = sx_ctx('brand');
$site = sx_ctx('site');

$payments = array_map('sx_payment_name', $market['payments']);

$pages = sx_pages();
// Only links to something that exists: the responsible-gambling page, else the panel in this footer. Terms, privacy and
// contact link only to pages the site has (none of the blueprints has them yet).
$legal = array_merge($props['links'] ?? [], [
    ['label' => t('footer.rg'), 'url' => isset($pages['responsible']) ? sx_page_url('responsible') : '#responsible-gambling'],
], array_values(array_filter(array_map(fn($k) => isset($pages[$k]) ? ['label' => t("footer.$k"), 'url' => sx_page_url($k)] : null, ['terms', 'privacy', 'contact']))));

$about = $props['about'] ?: tm($brand['tagline']);
// compliance inside the footer: panel or cards, per site (seed; pin with "variants": {"compliance": "cards"})
$cv = in_array(sx_ctx('compliance_footer'), ['panel', 'cards'], true) ? sx_ctx('compliance_footer') : sx_pick(['panel', 'cards'], 'compliance-footer');
// the affiliate disclosure, when the site shows it in the footer instead of a strip at the top of the page
$disclose = is_array($props['disclosure'] ?? null) ? sx_block('disclosure', ['variant' => 'line'] + $props['disclosure']) : '';
$compliance = $disclose . '<div id="responsible-gambling">' . sx_block('compliance-bar', ['variant' => $cv, 'show_verify' => !empty($props['show_verify'])]) . '</div>'; // the footer's "responsible gambling" link lands here
$rights = '© ' . e($site['year']) . ' ' . e($site['name']) . '. ' . e(t('footer.rights'));

// Multi-page sites: every page (home included) in the footer, so no page depends on the header menu alone.
$siteNav = [];
if ($pages) {
    SX::$used['_sitenav'] = 1; // CSS for nav states ships only on multi-page sites
}
foreach (array_filter($pages, fn($p) => empty($p['paged_of']) && empty($p['item'])) as $pid => $p) {
    $siteNav[] = ['label' => $p['nav'], 'url' => sx_page_url($pid), 'current' => $pid === sx_page_id()];
}
