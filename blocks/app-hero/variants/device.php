<?php
/** Device-led: copy and install buttons beside two overlapping phones. Good for live/APK angles. */
include __DIR__ . '/../_shared.php';
$factLine = implode(' · ', array_map(fn($f) => $f[0], $facts));
?>
<?= sx_platform_script() ?>
<section class="<?= cx('apphero', ['apphero', null, 'device']) ?>">
  <div class="<?= cx('container', ['apphero', 'inner']) ?>">
    <div class="<?= cls('apphero', 'copy') ?>">
      <p class="<?= cls('apphero', 'official') ?>"><?= sx_icon('shield') ?><?= e(t('app.official')) ?></p>
      <h1 class="<?= cls('apphero', 'title') ?>"><?= e($props['title']) ?></h1>
      <?php if ($props['subtitle']): ?><p class="<?= cls('apphero', 'desc') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
      <?= sx_install_group('hero') ?>
      <p class="<?= cls('apphero', 'factline') ?>"><?= e($factLine) ?></p>
    </div>
    <div class="<?= cls('apphero', 'device') ?>">
      <?= sx_phone(['kind' => $props['screen'] === 'home' ? 'wallet' : 'home'], cls('apphero', 'back')) ?>
      <?= sx_phone(['kind' => $props['screen'], 'image' => $props['image'] ?: null], cls('apphero', 'front'), false) ?>
    </div>
  </div>
</section>
