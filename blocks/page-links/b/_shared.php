<?php
/** Page links, implementation B: a labelled <nav> of plain links (no list). Same pages as A. */
$all = sx_pages();
$here = sx_page_id();
$ids = array_filter(array_map('trim', explode(',', (string)($props['pages'] ?? ''))));
$base = $all[$here]['paged_of'] ?? $here;
$ids = $ids ?: array_keys(array_filter(array_diff_key($all, [$here => 1, $base => 1, 'home' => 1]), fn($p) => empty($p['paged_of']) && empty($p['item'])));
$ids = array_values(array_filter($ids, fn($id) => isset($all[$id]) && $id !== $here));
$title = $props['title'] ?: t($here === 'home' ? 'pages.title' : 'pages.related');
$links = '';
foreach ($ids as $id) {
    $p = $all[$id];
    $pic = sx_images_on() ? sx_picture(['slug' => $id, 'title' => $p['nav'] . ' ' . $id], '16/9', cls('more', 'pic')) : '';
    $links .= '<a class="' . cls('more', 'link') . ($pic ? ' ' . cls('more', 'link', 'pic') : '') . '" href="' . sx_url(sx_page_url($id)) . '">' . $pic
        . '<span class="' . cls('more', 'txt') . '"><b class="' . cls('more', 'name') . '">' . e($p['nav']) . '</b>'
        . ($p['blurb'] ? '<small>' . e($p['blurb']) . '</small>' : '') . '</span>' . sx_icon('arrow') . '</a>';
}
