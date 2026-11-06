<?php
/** Steps, implementation B: <article> steps, numbers from a CSS counter. Same content as A. */
$cta = $props['show_cta'] ? sx_cta('mid', cx('btn', ['btn', null, 'primary'], ['how', 'cta']), true) : '';
$head = ($props['eyebrow'] ? '<p class="' . cls('eyebrow') . '">' . e($props['eyebrow']) . '</p>' : '')
    . '<h2 class="' . cls('how', 'title') . '">' . e($props['title']) . '</h2>';
$steps = '';
foreach ($props['items'] as $it) {
    $steps .= '<article class="' . cls('how', 'step') . '"><h3>' . e($it['title']) . '</h3><p>' . e($it['text']) . '</p></article>';
}
