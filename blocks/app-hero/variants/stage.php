<?php
/** Stage: the phone on a tinted panel on the start side, the store facts and install buttons beside it. */
include __DIR__ . '/../_shared.php';
?>
<?= sx_platform_script() ?>
<section class="<?= cx('apphero', ['apphero', null, 'stage']) ?>">
  <div class="<?= cx('container', ['apphero', 'inner']) ?>">
    <div class="<?= cls('apphero', 'device') ?>">
      <?= sx_phone(['kind' => $props['screen'], 'image' => $props['image'] ?: null], '', false) ?>
    </div>
    <div class="<?= cls('apphero', 'copy') ?>">
      <p class="<?= cls('apphero', 'official') ?>"><?= sx_icon('shield') ?><?= e(t('app.official')) ?></p>
      <h1 class="<?= cls('apphero', 'title') ?>"<?= sx_fit((string)$props['title']) ?>><?= e($props['title']) ?></h1>
      <?php if ($props['subtitle']): ?><p class="<?= cls('apphero', 'desc') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
      <?= sx_install_group('hero') ?>
      <ul class="<?= cls('apphero', 'chips') ?>">
        <?php foreach ($facts as [$value, $label]): ?>
          <li><strong><?= e($value) ?></strong> <?= e($label) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>
