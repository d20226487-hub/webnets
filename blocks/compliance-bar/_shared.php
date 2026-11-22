<?php
/** Resolves market compliance copy for the current locale. Defines $c (array of strings/null). */

$market = sx_ctx('market');
$brand = sx_ctx('brand');
$m = $market['compliance'];

$regulator = $props['regulator'] ?: sx_licence_regulator();
// sites that list several operators say only that they list licensed ones (no one licence): casinos, betting sites or both,
// by the site's product, else by what the market allows
$listedProduct = sx_ctx('product') ?: (!empty($market['verticals']['sports']) ? (!empty($market['verticals']['casino']) ? 'both' : 'sports') : 'casino');
$listed = ['sports' => '@sports', 'both' => '@both'][$listedProduct] ?? '';
$taglines = tm($m['taglines']) ?: [];

$c = [
    'age' => $market['min_age'] . '+',
    'age_notice' => tm($m['age_notice']),
    'license' => !empty($brand['publisher'])
        ? t('casinos.licence_line' . $listed)
        : tm($m['license_line'], [
            'regulator' => $regulator,
            'license_no' => $props['license_no'] ?: ($brand['license_no'] ?? '—'),
        ]),
    'tagline' => $taglines ? sx_pick($taglines, 'tagline') : null,
    'self_exclusion' => $m['self_exclusion'] ? tm($m['self_exclusion']) : null,
    'helpline' => $m['helpline'] ? tm($m['helpline']) : null,
    'verify' => !empty($props['show_verify']) ? $m['_verify'] : null,
];
