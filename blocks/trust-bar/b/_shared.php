<?php
/** Trust bar, implementation B: a description list (label/value pairs). Same items as A. */
$pp = sx_proof_points();
$items = [];
foreach ((array)$props['items'] as $key) {
    if (isset($pp[$key])) {
        $items[$key] = $pp[$key];
    }
}
$items = array_slice($items, 0, 4, true);
$cells = '';
foreach ($items as $key => [$icon, $value, $label]) {
    // "Operating since 2019" reads label first; the licence line needs no visible label
    $lab = '<dt class="' . cls('proof', 'k') . ($key === 'licence' ? ' ' . cls('visually-hidden') : '') . '">' . e($label) . '</dt>';
    $val = '<dd class="' . cls('proof', 'v') . '">' . e($value) . '</dd>';
    $cells .= '<div class="' . cx(['proof', 'cell'], ['proof', 'cell', $key === 'since' ? 'label-first' : 'value-first']) . '">'
        . sx_icon($icon) . $lab . $val . '</div>';
}
