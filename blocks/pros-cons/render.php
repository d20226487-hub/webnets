<?php
$id = sx_page_item() ?? sx_casino_ids()[0];
$r = sx_casino($id)['r'];
$c = fn($el) => cls('proscons', $el);
?>
<section class="<?= cx('proscons', ['proscons', null, $variant]) ?>">
  <div class="<?= cx('container', ['proscons', 'inner']) ?>">
    <div class="<?= $c('col') ?>"><h2><?= e(t('casinos.pros')) ?></h2><ul><?php foreach ($r['pros'] as $p): ?><li><?= sx_icon('check') ?><?= e($p) ?></li><?php endforeach; ?></ul></div>
    <div class="<?= $c('col') ?>"><h2><?= e(t('casinos.cons')) ?></h2><ul><?php foreach ($r['cons'] as $p): ?><li><span class="<?= $c('minus') ?>" aria-hidden="true">–</span><?= e($p) ?></li><?php endforeach; ?></ul></div>
  </div>
</section>
