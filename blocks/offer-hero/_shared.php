<?php
/** Shared prep for offer-hero variants. Defines $p (lead promo), $value, $keyTerms, $title, $termsLink. */

$p = sx_lead_promo();
if (!$p) {
    SX::$warnings[] = 'offer-hero: no active promotion for this brand and market; the hero was not rendered.';
    return false;
}
$value = sx_promo_value($p);
$keyTerms = sx_promo_key_terms($p);
$title = $props['title'] ?: tm($p['title']);
$termsLink = '<a class="' . cls('offerhero', 'terms') . '" href="' . sx_url($p['terms_url'] ?? '#terms') . '" rel="nofollow noopener" target="_blank">' . e(t('promo.full_terms')) . '</a>';
return true;
