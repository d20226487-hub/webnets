<?php include __DIR__ . '/../_shared.php';
$items = $props['items'];
$lead = array_shift($items);
?>
<section class="<?= cx('perk', ['perk', null, 'mosaic']) ?>">
  <div class="<?= cls('container') ?>">
    <?= $head ?>
    <div class="<?= cls('perk', 'mosaic') ?>">
      <?= $lead ? $card($lead, 'lead') : '' ?>
      <div class="<?= cls('perk', 'rest') ?>"><?php foreach ($items as $it) { echo $card($it); } ?></div>
    </div>
  </div>
</section>
