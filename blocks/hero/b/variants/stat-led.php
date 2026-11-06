<?php
/** Hero led by one big number, implementation B: the number is a <figure> with a <data> value. */
include __DIR__ . '/../_shared.php';
$s = $props['stat'] ?? null;
?>
<section class="<?= cx('opener', ['opener', null, 'num']) ?>">
  <div class="<?= cx('container', ['opener', 'frame']) ?>">
    <?php if ($s): ?>
      <figure class="<?= cls('opener', 'stat') ?>">
        <div class="<?= cls('opener', 'big') ?>"><data value="<?= e($s['value']) ?>"><?= e($s['value']) ?></data> <span><?= e($s['unit']) ?></span></div>
        <figcaption><?= e($s['caption']) ?></figcaption>
      </figure>
    <?php endif; ?>
    <?= $copy ?>
  </div>
</section>
