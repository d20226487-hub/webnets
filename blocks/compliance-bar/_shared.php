<?php
/** Resolves market compliance copy for the current locale. Defines $c (array of strings/null). */

$market = sx_ctx('market');
$brand = sx_ctx('brand');
$m = $market['compliance'];

$regulator = $props['regulator'] ?: tm($m['regulator_default']);
$taglines = tm($m['taglines']) ?: [];

$c = [
    'age' => $market['min_age'] . '+',
    'age_notice' => tm($m['age_notice']),
    'license' => !empty($brand['publisher'])
        ? t('casinos.licence_line', ['regulator' => $regulator])
        : tm($m['license_line'], [
            'regulator' => $regulator,
            'license_no' => $props['license_no'] ?: ($brand['license_no'] ?? '—'),
        ]),
    'tagline' => $taglines ? sx_pick($taglines, 'tagline') : null,
    'self_exclusion' => $m['self_exclusion'] ? tm($m['self_exclusion']) : null,
    'helpline' => $m['helpline'] ? tm($m['helpline']) : null,
    'verify' => !empty($props['show_verify']) ? $m['_verify'] : null,
];
