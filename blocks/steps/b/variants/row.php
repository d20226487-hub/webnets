<?php include __DIR__ . '/../_shared.php'; ?>
<section class="<?= cx('how', ['how', null, 'cols']) ?>" id="how">
  <div class="<?= cls('container') ?>">
    <header class="<?= cls('how', 'head') ?>"><?= $head ?></header>
    <div class="<?= cls('how', 'track') ?>"><?= $steps ?></div>
    <?php if ($cta): ?><footer class="<?= cls('how', 'foot') ?>"><?= $cta ?></footer><?php endif; ?>
  </div>
</section>
