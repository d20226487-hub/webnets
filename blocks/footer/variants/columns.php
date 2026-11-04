<?php include __DIR__ . '/../_shared.php'; ?>
<footer class="<?= cx('footer', ['footer', null, 'columns']) ?>">
  <div class="<?= cx('container', ['footer', 'inner']) ?>">
    <div class="<?= cls('footer', 'grid') ?>">
      <div class="<?= cls('footer', 'about') ?>">
        <?= sx_logo('mono') ?>
        <p><?= e($about) ?></p>
      </div>
      <?php if ($siteNav): ?>
        <nav class="<?= cls('footer', 'site') ?>" aria-label="<?= e(sx_ctx('site')['name']) ?>">
          <ul class="<?= cls('footer', 'links') ?>">
            <?php foreach ($siteNav as $l): ?><li><a href="<?= sx_url($l['url']) ?>"<?= $l['current'] ? ' aria-current="page"' : '' ?>><?= e($l['label']) ?></a></li><?php endforeach; ?>
          </ul>
        </nav>
      <?php endif; ?>
      <nav aria-label="<?= e(t('footer.rg')) ?>">
        <ul class="<?= cls('footer', 'links') ?>">
          <?php foreach ($legal as $l): ?><li><a href="<?= sx_url($l['url']) ?>"><?= e($l['label']) ?></a></li><?php endforeach; ?>
        </ul>
      </nav>
      <div>
        <p class="<?= cls('eyebrow') ?>"><?= e(t('footer.payments')) ?></p>
        <ul class="<?= cls('footer', 'pay') ?>">
          <?php foreach ($payments as $p): ?><li><?= e($p) ?></li><?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?= $compliance ?>
    <p class="<?= cls('footer', 'rights') ?>"><?= $rights ?></p>
  </div>
</footer>
