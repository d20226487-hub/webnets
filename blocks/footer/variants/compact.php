<?php include __DIR__ . '/../_shared.php'; ?>
<footer class="<?= cx('footer', ['footer', null, 'compact']) ?>">
  <div class="<?= cx('container', ['footer', 'inner']) ?>">
    <div class="<?= cls('footer', 'row') ?>">
      <?= sx_logo('mono') ?>
      <ul class="<?= cls('footer', 'links') ?>">
        <?php foreach ($legal as $l): ?><li><a href="<?= sx_url($l['url']) ?>"><?= e($l['label']) ?></a></li><?php endforeach; ?>
      </ul>
    </div>
    <?php if ($siteNav): ?>
      <nav class="<?= cls('footer', 'site') ?>" aria-label="<?= e(sx_ctx('site')['name']) ?>">
        <ul>
          <?php foreach ($siteNav as $l): ?><li><a href="<?= sx_url($l['url']) ?>"<?= $l['current'] ? ' aria-current="page"' : '' ?>><?= e($l['label']) ?></a></li><?php endforeach; ?>
        </ul>
      </nav>
    <?php endif; ?>
    <ul class="<?= cls('footer', 'pay') ?>" aria-label="<?= e(t('footer.payments')) ?>">
      <?php foreach ($payments as $p): ?><li><?= e($p) ?></li><?php endforeach; ?>
    </ul>
    <?= $compliance ?>
    <p class="<?= cls('footer', 'rights') ?>"><?= $rights ?></p>
  </div>
</footer>
