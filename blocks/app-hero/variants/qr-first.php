<?php
/**
 * QR-first: on desktop a large QR card leads (most visitors arrive on a computer and need the
 * phone); on phones the QR is hidden and the install buttons lead.
 */
include __DIR__ . '/../_shared.php';
$links = sx_ctx('links');
$qrUrl = $links['qr'] ?? $links['go'] ?? null;
$factLine = implode(' · ', array_map(fn($f) => $f[0], $facts));
?>
<?= sx_platform_script() ?>
<section class="<?= cx('apphero', ['apphero', null, 'qr']) ?>">
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
    <div class="<?= cls('apphero', 'qrcard') ?>">
      <?= sx_phone(['kind' => $props['screen'], 'image' => $props['image'] ?: null], cls('apphero', 'peek'), false) ?>
      <?php if ($qrUrl && preg_match('#^https?://#', $qrUrl)): ?>
        <figure class="<?= cls('apphero', 'qr') ?>">
          <span class="<?= cls('apphero', 'code') ?>"><?= sx_qr_svg($qrUrl, t('app.scan')) ?></span>
          <figcaption><?= e(t('app.scan')) ?></figcaption>
        </figure>
      <?php endif; ?>
    </div>
  </div>
</section>
