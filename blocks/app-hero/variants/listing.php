<?php
/** Store-listing order (identity, facts, install, description) beside a phone. Our own visual language. */
include __DIR__ . '/../_shared.php';
?>
<?= sx_platform_script() ?>
<section class="<?= cx('apphero', ['apphero', null, 'listing']) ?>">
  <div class="<?= cx('container', ['apphero', 'inner']) ?>">
    <div class="<?= cls('apphero', 'copy') ?>">
      <div class="<?= cls('apphero', 'id') ?>">
        <?= sx_app_icon(cls('apphero', 'icon')) ?>
        <div>
          <p class="<?= cls('apphero', 'name') ?>"><?= e($app['name'] ?? sx_ctx('brand')['name']) ?></p>
          <p class="<?= cls('apphero', 'dev') ?>"><?= e($app['developer'] ?? '') ?></p>
          <p class="<?= cls('apphero', 'official') ?>"><?= sx_icon('shield') ?><?= e(t('app.official')) ?></p>
        </div>
      </div>
      <h1 class="<?= cls('apphero', 'title') ?>"><?= e($props['title']) ?></h1>
      <ul class="<?= cls('apphero', 'facts') ?>">
        <?php foreach ($facts as [$value, $label]): ?>
          <li><strong><?= e($value) ?></strong><span><?= e($label) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <?= sx_install_group('hero') ?>
      <?php if ($props['subtitle']): ?><p class="<?= cls('apphero', 'desc') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
    </div>
    <div class="<?= cls('apphero', 'device') ?>">
      <?= sx_phone(['kind' => $props['screen'], 'image' => $props['image'] ?: null], '', false) ?>
    </div>
  </div>
</section>
