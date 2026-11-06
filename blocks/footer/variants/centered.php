<?php include __DIR__ . '/../_shared.php'; ?>
<footer class="<?= cx('footer', ['footer', null, 'centered']) ?>">
  <div class="<?= cx('container', ['footer', 'inner']) ?>">
    <div class="<?= cls('footer', 'brand') ?>">
      <?= sx_logo('mono') ?>
      <?php if ($about): ?><p class="<?= cls('footer', 'about') ?>"><?= e($about) ?></p><?php endif; ?>
    </div>
    <?php if ($siteNav): ?>
      <nav class="<?= cls('footer', 'site') ?>" aria-label="<?= e(sx_ctx('site')['name']) ?>">
        <ul>
          <?php foreach ($siteNav as $l): ?><li><a href="<?= sx_url($l['url']) ?>"<?= $l['current'] ? ' aria-current="page"' : '' ?>><?= e($l['label']) ?></a></li><?php endforeach; ?>
        </ul>
      </nav>
    <?php endif; ?>
    <?= $compliance ?>
    <ul class="<?= cls('footer', 'pay') ?>" aria-label="<?= e(t('footer.payments')) ?>">
      <?php foreach ($payments as $p): ?><li><?= e($p) ?></li><?php endforeach; ?>
    </ul>
    <div class="<?= cls('footer', 'base') ?>">
      <ul class="<?= cls('footer', 'links') ?>">
        <?php foreach ($legal as $l): ?><li><a href="<?= sx_url($l['url']) ?>"><?= e($l['label']) ?></a></li><?php endforeach; ?>
      </ul>
      <p class="<?= cls('footer', 'rights') ?>"><?= $rights ?></p>
    </div>
  </div>
</footer>
