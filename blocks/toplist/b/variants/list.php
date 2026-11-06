<?php include __DIR__ . '/../_shared.php'; ?>
<section class="<?= cx('rank', ['rank', null, 'short']) ?>" id="ranking">
  <div class="<?= cx('container', ['rank', 'wrap']) ?>"><?= $headHtml ?><ol class="<?= cls('rank', 'list') ?>"><?= $rows ?></ol></div>
</section>
