<?php include __DIR__ . '/../_shared.php'; ?>
<section class="<?= cx('qa', ['qa', null, 'cols']) ?>" id="faq">
  <div class="<?= cx('container', ['qa', 'wrap']) ?>">
    <h2 class="<?= cls('qa', 'title') ?>"><?= e($title) ?></h2>
    <div class="<?= cls('qa', 'flow') ?>">
      <?php foreach ($props['items'] as $it): ?>
        <article class="<?= cls('qa', 'card') ?>"><h3><?= e($it['q']) ?></h3><p><?= e($it['a']) ?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
