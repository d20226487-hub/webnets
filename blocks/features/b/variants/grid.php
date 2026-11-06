<?php include __DIR__ . '/../_shared.php'; ?>
<section class="<?= cx('perk', ['perk', null, 'tiles']) ?>">
  <div class="<?= cls('container') ?>">
    <?= $head ?>
    <div class="<?= cls('perk', 'wrap') ?>"><?php foreach ($props['items'] as $it) { echo $card($it); } ?></div>
  </div>
</section>
