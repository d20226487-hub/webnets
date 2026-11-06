<?php include __DIR__ . '/../_shared.php'; ?>
<section class="<?= cx('qa', ['qa', null, 'fold']) ?>" id="faq">
  <header class="<?= cx('container', ['qa', 'head']) ?>"><h2 class="<?= cls('qa', 'title') ?>"><?= e($title) ?></h2></header>
  <div class="<?= cx('container', ['qa', 'rows']) ?>">
    <?php foreach ($props['items'] as $i => $it): ?>
      <details class="<?= cls('qa', 'row') ?>"<?= $i === 0 ? ' open' : '' ?>>
        <summary class="<?= cls('qa', 'q') ?>"><h3><?= e($it['q']) ?></h3><i class="<?= cls('qa', 'mark') ?>" aria-hidden="true"></i></summary>
        <div class="<?= cls('qa', 'a') ?>"><p><?= e($it['a']) ?></p></div>
      </details>
    <?php endforeach; ?>
  </div>
</section>
