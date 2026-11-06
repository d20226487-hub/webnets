<?php include __DIR__ . '/../_shared.php'; ?>
<header class="<?= cx('intro', ['intro', null, 'snippet']) ?>">
  <div class="<?= cx('container', ['intro', 'bar']) ?>">
    <div class="<?= cls('intro', 'text') ?>">
      <?= $crumbs ?><?= $h1 ?>
      <aside class="<?= cls('intro', 'card') ?>">
        <div class="<?= cls('intro', 'fact') ?>"><?= sx_icon('check') ?><p><?= e($props['answer']) ?></p></div>
        <?= $cta ?>
      </aside>
    </div>
    <?php if ($pic): ?><figure class="<?= cls('intro', 'fig') ?>"><?= $pic ?></figure><?php endif; ?>
  </div>
</header>
