<?php include __DIR__ . '/../_shared.php';
if (!$ids) {
    return;
} ?>
<nav class="<?= cx('more', ['more', null, 'rows']) ?>" id="guides" aria-label="<?= e($title) ?>">
  <div class="<?= cls('container') ?>">
    <h2 class="<?= cls('more', 'title') ?>"><?= e($title) ?></h2>
    <div class="<?= cls('more', 'set') ?>"><?= $links ?></div>
  </div>
</nav>
