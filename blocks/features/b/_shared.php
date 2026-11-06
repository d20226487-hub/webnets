<?php
/** Features, implementation B: <article> cards, flex and nested-grid layouts. Same content as A. */
$head = '<header class="' . cls('perk', 'head') . '">'
    . ($props['eyebrow'] ? '<p class="' . cls('eyebrow') . '">' . e($props['eyebrow']) . '</p>' : '')
    . '<h2 class="' . cls('perk', 'title') . '">' . e($props['title']) . '</h2></header>';
$card = function (array $it, string $mod = '') {
    return '<article class="' . cx('card', ['perk', 'card']) . ($mod ? ' ' . cls('perk', 'card', $mod) : '') . '">'
        . '<i class="' . cls('perk', 'ico') . '" aria-hidden="true">' . sx_icon($it['icon'] ?? 'check') . '</i>'
        . '<h3>' . e($it['title']) . '</h3><p>' . e($it['text']) . '</p></article>';
};
