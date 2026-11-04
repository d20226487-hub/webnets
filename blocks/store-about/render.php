<?php
$app = sx_app();
$c = fn($el) => cls('sabout', $el);
$st = sx_store_figures('android');
$tags = array_filter(array_map('trim', explode(',', (string)$props['tags'])));
$paras = array_filter(array_map('trim', preg_split('/\n\s*\n/', (string)$props['text'])));
$facts = array_filter([
    t('app.updated') => !empty($app['updated']) ? sx_date($app['updated']) : null,
    t('app.version') => $app['version'] ?? null,
    t('app.requires') => in_array('ios', sx_app_platforms(), true) && $variant === 'appstore' ? ($app['min_os']['ios'] ?? null) : ($app['min_os']['android'] ?? null),
    t('store.downloads') => $variant === 'play' ? ($st['downloads'] ?? null) : null,
    t('app.age') => $app['age_rating'] ?? null,
    t('store.released_by') => $app['developer'] ?? null,
]);
?>
<section class="<?= cx('sabout', ['sabout', null, $variant]) ?>" id="about">
  <div class="<?= cx('container', ['sabout', 'inner']) ?>">
    <h2 class="<?= $c('title') ?>"><?= e($props['title'] ?: t($variant === 'play' ? 'store.about' : 'store.description')) ?></h2>
    <div class="<?= $c('text') ?>"><?php foreach ($paras as $p): ?><p><?= e($p) ?></p><?php endforeach; ?></div>
    <?php if ($variant === 'play'): ?>
      <?php if ($tags): ?><ul class="<?= $c('tags') ?>"><?php foreach ($tags as $tg): ?><li><?= e($tg) ?></li><?php endforeach; ?></ul><?php endif; ?>
      <dl class="<?= $c('facts') ?>"><?php foreach ($facts as $k => $v): ?><div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div><?php endforeach; ?></dl>
    <?php else: ?>
      <p class="<?= $c('dev') ?>"><span><?= e($app['developer'] ?? '') ?></span><small><?= e(t('app.developer')) ?></small></p>
    <?php endif; ?>
  </div>
</section>
