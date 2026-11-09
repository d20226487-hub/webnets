<?php
/** Page hero, implementation B: a flex column header; the picture is its own column in image mode. Same content as A. */
$crumbs = sx_breadcrumbs(cls('intro', 'crumbs'));
$showCta = $props['show_cta'] ?? true;
$cta = $showCta ? sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['intro', 'cta']), true) : '';
$pic = sx_images_on() ? sx_picture(['slug' => sx_page_id(), 'title' => $props['title'] . ' ' . sx_page_id()], '4/3', cls('intro', 'pic')) : '';
$h1 = '<h1 class="' . cls('intro', 'title') . '"' . sx_fit((string)$props['title']) . '>' . e($props['title']) . '</h1>';
