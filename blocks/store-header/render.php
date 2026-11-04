<?php
/** Store-listing header. Facts come from the brand's app data; the store figures (rating, ratings, downloads) from app.store. */
$app = sx_app();
$brand = sx_ctx('brand');
$st = sx_store_figures($variant === 'play' ? 'android' : 'ios');
$c = fn($el) => cls('shead', $el);
$ps = sx_app_platforms();
$size = sx_app_size($variant === 'play' && array_intersect(['android', 'apk'], $ps) ? 'android' : (in_array('ios', $ps, true) ? 'ios' : 'android'));
$cat = sx_store_category();
sx_jsonld(['@type' => 'MobileApplication', 'name' => $app['name'] ?? $brand['name'], 'applicationCategory' => $brand['kind'] === 'casino' ? 'GameApplication' : 'SportsApplication',
    'operatingSystem' => implode(', ', array_unique(array_map(fn($p) => $p === 'ios' ? 'iOS' : 'Android', $ps))), 'softwareVersion' => $app['version'] ?? null,
    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => sx_ctx('market')['currency']['code']]]);
?>
<?= sx_platform_script() ?>
<section class="<?= cx('shead', ['shead', null, $variant]) ?>">
  <div class="<?= cx('container', ['shead', 'inner']) ?>">
    <?php if ($variant === 'play'): ?>
      <div class="<?= $c('main') ?>">
        <h1 class="<?= $c('name') ?>"><?= e($props['title']) ?></h1>
        <p class="<?= $c('dev') ?>"><?= e($app['developer'] ?? '') ?></p>
        <p class="<?= $c('note') ?>"><?= sx_icon('shield') ?><?= e(t('app.official')) ?></p>
        <ul class="<?= $c('stats') ?>">
          <?php if ($st): ?><li><strong><?= e(sx_number($st['rating'], 1)) ?> ★</strong><span><?= e(t('store.reviews_n', ['n' => sx_compact($st['count'])])) ?></span></li><?php endif; ?>
          <?php if (!empty($st['downloads'])): ?><li><strong><?= e($st['downloads']) ?></strong><span><?= e(t('store.downloads')) ?></span></li><?php endif; ?>
          <li><strong><?= e($app['age_rating'] ?? '18+') ?></strong><span><?= e(t('store.rated_for', ['age' => $app['age_rating'] ?? '18+'])) ?></span></li>
        </ul>
        <?= sx_install_group('hero') ?>
        <?php if ($props['subtitle']): ?><p class="<?= $c('sub') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
      </div>
      <div class="<?= $c('art') ?>"><?= sx_app_icon($c('icon')) ?></div>
    <?php else: ?>
      <div class="<?= $c('top') ?>">
        <?= sx_app_icon($c('icon')) ?>
        <div class="<?= $c('main') ?>">
          <h1 class="<?= $c('name') ?>"><?= e($props['title']) ?></h1>
          <?php if ($props['subtitle']): ?><p class="<?= $c('sub') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
          <p class="<?= $c('dev') ?>"><?= e($app['developer'] ?? '') ?></p>
          <?php if ($st): ?><p class="<?= $c('rating') ?>"><?= sx_stars($st['rating']) ?> <strong><?= e(sx_number($st['rating'], 1)) ?></strong> · <?= e(t('store.ratings_n', ['n' => sx_compact($st['count'])])) ?></p><?php endif; ?>
          <p class="<?= $c('price') ?>"><?= e(t('app.free')) ?></p>
          <?= sx_install_group('hero') ?>
        </div>
      </div>
      <ul class="<?= $c('strip') ?>">
        <?php if ($st): ?><li><span><?= e(t('store.ratings_n', ['n' => sx_compact($st['count'])])) ?></span><strong><?= e(sx_number($st['rating'], 1)) ?></strong><?= sx_stars($st['rating']) ?></li><?php endif; ?>
        <li><span><?= e(t('app.age')) ?></span><strong><?= e($app['age_rating'] ?? '18+') ?></strong><small><?= e(t('store.years')) ?></small></li>
        <?php if ($cat): ?><li><span><?= e(t('store.category')) ?></span><strong><?= sx_icon('bolt') ?></strong><small><?= e($cat) ?></small></li><?php endif; ?>
        <li><span><?= e(t('app.developer')) ?></span><strong><?= sx_icon('shield') ?></strong><small><?= e($app['developer'] ?? '') ?></small></li>
        <li><span><?= e(t('app.languages')) ?></span><strong><?= e(strtoupper(sx_ctx('locale'))) ?></strong><small><?= e(implode(', ', array_slice($app['languages'][sx_ctx('locale')] ?? [], 0, 2))) ?></small></li>
        <?php if ($size): ?><li><span><?= e(t('app.size')) ?></span><strong><?= e(preg_replace('/\s*MB$/', '', $size)) ?></strong><small>MB</small></li><?php endif; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
