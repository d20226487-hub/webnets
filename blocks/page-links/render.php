<?php
$all = sx_pages();
$here = sx_page_id();
$ids = array_filter(array_map('trim', explode(',', (string)($props['pages'] ?? ''))));
$base = $all[$here]['paged_of'] ?? $here;
$ids = $ids ?: array_keys(array_filter(array_diff_key($all, [$here => 1, $base => 1, 'home' => 1]), fn($p) => empty($p['paged_of']) && empty($p['item'])));
$ids = array_values(array_filter($ids, fn($id) => isset($all[$id]) && $id !== $here));
if (!$ids) {
    return;
}
$title = $props['title'] ?: t($here === 'home' ? 'pages.title' : 'pages.related');
?>
<section class="<?= cx('plinks2', ['plinks2', null, $variant]) ?>" id="guides">
  <div class="<?= cx('container', ['plinks2', 'inner']) ?>">
    <h2 class="<?= cls('plinks2', 'title') ?>"><?= e($title) ?></h2>
    <ul class="<?= cls('plinks2', 'list') ?>">
      <?php foreach ($ids as $id): $p = $all[$id]; ?>
        <li><a href="<?= sx_url(sx_page_url($id)) ?>"><?= sx_images_on() ? sx_picture(['slug' => $id, 'title' => $p['nav'] . ' ' . $id], '16/9', cls('plinks2', 'pic')) : '' ?><strong><?= e($p['nav']) ?></strong><?php if ($p['blurb']): ?><span><?= e($p['blurb']) ?></span><?php endif; ?><?= sx_icon('arrow') ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
