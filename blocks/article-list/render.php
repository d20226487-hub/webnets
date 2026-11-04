<?php
$arts = sx_article_list_items($props);
if (!empty($props['exclude_current']) && ($here = sx_page_item())) {
    unset($arts[$here]);
}
$pageNo = (int)($props['page_no'] ?? 1);
$pageCount = (int)($props['page_count'] ?? 1);
if ($pageCount > 1) {
    $arts = array_slice($arts, ($pageNo - 1) * (int)$props['per_page'], (int)$props['per_page'], true);
} elseif ((int)$props['limit'] > 0) {
    $arts = array_slice($arts, 0, (int)$props['limit'], true);
}
if (!$arts) {
    return;
}
$h = $props['heading'] === 'h1' ? 'h1' : 'h2';
$c = fn($el) => cls('alist', $el);
$more = $props['more'] && isset(sx_pages()[$props['more']]) ? sx_page_url($props['more']) : null;
?>
<section class="<?= cx('alist', ['alist', null, $variant]) ?>">
  <div class="<?= cx('container', ['alist', 'inner']) ?>">
    <?php if ($h === 'h1'): ?><?= sx_breadcrumbs(cls('alist', 'crumbs')) ?><?php endif; ?>
    <header class="<?= $c('head') ?>">
      <<?= $h ?> class="<?= $c('title') ?>"><?php if (($props['kind'] ?? '') === 'pr'): ?><?= sx_sponsored_badge() ?> <?php endif; ?><?= e($props['title']) ?></<?= $h ?>>
      <?php if ($more): ?><a class="<?= $c('more') ?>" href="<?= sx_url($more) ?>"><?= e(t('news.more')) ?><?= sx_icon('arrow') ?></a><?php endif; ?>
    </header>
    <?php if ($props['intro']): ?><p class="<?= $c('intro') ?>"><?= e($props['intro']) ?></p><?php endif; ?>
    <ul class="<?= $c('list') ?>">
      <?php foreach ($arts as $a): ?><li><?= sx_article_card($a, $variant !== 'rail') ?></li><?php endforeach; ?>
    </ul>
    <?= $pageCount > 1 ? sx_pagination($props['page_base'] ?? sx_page_id(), $pageNo, $pageCount, $c('pages')) : '' ?>
  </div>
</section>
