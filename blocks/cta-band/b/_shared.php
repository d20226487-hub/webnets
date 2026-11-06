<?php
/** CTA band, implementation B: flex rows, proof as inline notes. Same content as A. */
$pp = sx_proof_points();
$age = sx_ctx('market')['min_age'];
$proof = [$pp['licence'], $pp['payout']];
$copy = '<h2 class="' . cls('close', 'title') . '">' . e($props['title']) . '</h2>'
    . ($props['text'] ? '<p class="' . cls('close', 'text') . '">' . e($props['text']) . '</p>' : '');
$notes = '<p class="' . cls('close', 'notes') . '"><span class="' . cls('age') . '">' . e($age) . '+</span>';
foreach ($proof as [$icon, $value]) {
    $notes .= '<span class="' . cls('close', 'note') . '">' . sx_icon($icon) . e($value) . '</span>';
}
$notes .= '</p>';
$go = (!empty($props['show_code']) ? sx_code_box(sx_lead_promo(), 'final', cls('close', 'code')) : '')
    . sx_cta('final', cx('btn', ['btn', null, 'primary'], ['close', 'cta']), true) . $notes;
