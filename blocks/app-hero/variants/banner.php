<?php
/** Banner: a full-width colour band, the copy centred, one phone rising from the band's bottom edge. */
include __DIR__ . '/../_shared.php';
$factLine = implode(' · ', array_map(fn($f) => $f[0], $facts));
?>
<?= sx_platform_script() ?>
<section class="<?= cx('apphero', ['apphero', null, 'banner']) ?>">
  <div class="<?= cx('container', ['apphero', 'inner']) ?>">
    <div class="<?= cls('apphero', 'copy') ?>">
      <div class="<?= cls('apphero', 'id') ?>">
        <?= sx_app_icon(cls('apphero', 'icon')) ?>
        <div>
          <p class="<?= cls('apphero', 'name') ?>"><?= e($app['name'] ?? sx_ctx('brand')['name']) ?></p>
          <p class="<?= cls('apphero', 'official') ?>"><?= sx_icon('shield') ?><?= e(t('app.official')) ?></p>
        </div>
      </div>
      <h1 class="<?= cls('apphero', 'title') ?>"<?= sx_fit((string)$props['title']) ?>><?= e($props['title']) ?></h1>
      <?php if ($props['subtitle']): ?><p class="<?= cls('apphero', 'desc') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
      <?= sx_install_group('hero') ?>
      <p class="<?= cls('apphero', 'factline') ?>"><?= e($factLine) ?></p>
    </div>
    <div class="<?= cls('apphero', 'device') ?>">
      <?= sx_phone(['kind' => $props['screen'], 'image' => $props['image'] ?: null], '', false) ?>
    </div>
  </div>
</section>
