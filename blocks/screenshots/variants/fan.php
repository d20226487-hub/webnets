<?php /** Three phones fanned out with captions as a numbered list beside them. Uses the first 3 screens. */
$screens = array_slice($props['screens'], 0, 3);
?>
<section class="<?= cx('shots', ['shots', null, 'fan']) ?>" id="screens">
  <div class="<?= cx('container', ['shots', 'inner']) ?>">
    <div class="<?= cls('shots', 'stage') ?>">
      <?php foreach ($screens as $i => $s): ?>
        <?= sx_phone(['kind' => $s['kind'] ?? 'home', 'image' => $s['image'] ?? null], cls('shots', 'p' . ($i + 1))) ?>
      <?php endforeach; ?>
    </div>
    <div class="<?= cls('shots', 'side') ?>">
      <header class="<?= cls('shots', 'head') ?>">
        <?php if ($props['eyebrow']): ?><p class="<?= cls('eyebrow') ?>"><?= e($props['eyebrow']) ?></p><?php endif; ?>
        <h2 class="<?= cls('shots', 'title') ?>"><?= e($props['title']) ?></h2>
      </header>
      <ul class="<?= cls('shots', 'list') ?>">
        <?php foreach ($screens as $s): ?><li><?= sx_icon('check') ?><span><?= e($s['caption']) ?></span></li><?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>
