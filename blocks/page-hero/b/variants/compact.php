<?php include __DIR__ . '/../_shared.php'; ?>
<header class="<?= cx('intro', ['intro', null, 'plain']) ?>">
  <div class="<?= cx('container', ['intro', 'bar']) ?>">
    <div class="<?= cls('intro', 'text') ?>">
      <?= $crumbs ?><?= $h1 ?>
      <p class="<?= cls('intro', 'lede') ?>"><?= e($props['answer']) ?></p>
      <?= $cta ?>
    </div>
    <?php if ($pic): ?><figure class="<?= cls('intro', 'fig') ?>"><?= $pic ?></figure><?php endif; ?>
  </div>
</header>
