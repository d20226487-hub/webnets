<?php
/** Hero, implementation B: flex layouts, trust facts as inline notes. Same content as A. Defines $copy, $card. */
$secondary = $props['secondary'] ?: null;
$facts = '';
if (!empty($props['show_trust'])) {
    foreach (array_slice(array_intersect_key(sx_proof_points(), array_flip(['rating', 'licence', 'payout'])), 0, 3) as $key => [$icon, $value, $label]) {
        $facts .= '<span class="' . cls('opener', 'fact') . '">' . sx_icon($icon)
            . ($key === 'licence' ? e($value) : e($label) . ' <b class="' . cls('num') . '">' . e($value) . '</b>') . '</span>';
    }
    $facts = '<p class="' . cls('opener', 'facts') . '">' . $facts . '</p>';
}
$copy = '<header class="' . cls('opener', 'copy') . '">'
    . ($props['eyebrow'] ? '<small class="' . cls('opener', 'tag') . '">' . e($props['eyebrow']) . '</small>' : '')
    . '<h1 class="' . cls('opener', 'title') . '">' . e($props['title']) . '</h1>'
    . ($props['subtitle'] ? '<p class="' . cls('opener', 'sub') . '">' . e($props['subtitle']) . '</p>' : '')
    . '<div class="' . cls('opener', 'go') . '">' . sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['opener', 'cta']), true)
    . ($secondary ? '<a class="' . cls('opener', 'alt') . '" href="' . sx_url($secondary['url']) . '">' . e($secondary['label']) . '</a>' : '')
    . '</div>' . $facts . '</header>';
$card = $props['card'] ? '<aside class="' . cls('opener', 'side') . '">' . sx_block('bonus-card', $props['card']) . '</aside>' : '';
