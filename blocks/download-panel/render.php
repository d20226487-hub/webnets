<?php
$app = sx_app();
$links = sx_ctx('links');
$qrUrl = $links['qr'] ?? $links['go'] ?? null;
$facts = array_filter([
    isset($app['version']) ? t('app.version') . ' ' . $app['version'] : '',
    $app['age_rating'] ?? '',
    tm(sx_ctx('market')['compliance']['license_short']),
]);
?>
<?= sx_platform_script() ?>
<section class="<?= cx('dl', ['dl', null, $variant]) ?>" id="download">
  <div class="<?= cx('container', ['dl', 'inner']) ?>">
    <div class="<?= cls('dl', 'card') ?>">
      <div class="<?= cls('dl', 'copy') ?>">
        <?= sx_app_icon(cls('dl', 'icon')) ?>
        <h2 class="<?= cls('dl', 'title') ?>"><?= e($props['title']) ?></h2>
        <?php if ($props['text']): ?><p class="<?= cls('dl', 'text') ?>"><?= e($props['text']) ?></p><?php endif; ?>
        <?= sx_install_group('final') ?>
        <p class="<?= cls('dl', 'facts') ?>"><?= e(implode(' · ', $facts)) ?></p>
      </div>
      <?php if ($qrUrl && preg_match('#^https?://#', $qrUrl)): ?>
        <figure class="<?= cls('dl', 'qr') ?>">
          <span class="<?= cls('dl', 'code') ?>"><?= sx_qr_svg($qrUrl, t('app.scan')) ?></span>
          <figcaption><?= e(t('app.scan')) ?></figcaption>
        </figure>
      <?php endif; ?>
    </div>
  </div>
</section>
