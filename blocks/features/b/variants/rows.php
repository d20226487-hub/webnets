<?php include __DIR__ . '/../_shared.php'; ?>
<section class="<?= cx('perk', ['perk', null, 'lines']) ?>">
  <div class="<?= cls('container') ?>">
    <?= $head ?>
    <div class="<?= cls('perk', 'grid') ?>">
      <?php foreach ($props['items'] as $it): ?>
        <article class="<?= cls('perk', 'line') ?>">
          <i class="<?= cls('perk', 'tile') ?>" aria-hidden="true"><?= sx_icon($it['icon'] ?? 'check') ?></i>
          <div><h3><?= e($it['title']) ?></h3><p><?= e($it['text']) ?></p></div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
