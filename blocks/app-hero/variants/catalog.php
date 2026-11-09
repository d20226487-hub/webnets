<?php
/**
 * Download catalog: the app's card (big icon, name, version, developer), a stats row, the headline, a large download
 * panel with the version and date, and a row of screenshots under it. Our own visual language.
 */
include __DIR__ . '/../_shared.php';
$st = sx_store_figures(array_intersect(['android', 'apk'], $ps) ? 'android' : 'ios');
$stats = array_values(array_filter([
    $st ? ['★ ' . sx_number($st['rating'], 1), t('store.reviews_n', ['n' => sx_compact($st['count'])])] : null,
    $sizeText ? [$sizeText, t('app.size')] : null,
    [$app['age_rating'] ?? ($market['min_age'] . '+'), t('app.age')],
]));
?>
<?= sx_platform_script() ?>
<section class="<?= cx('apphero', ['apphero', null, 'catalog']) ?>">
  <div class="<?= cx('container', ['apphero', 'inner']) ?>">
    <div class="<?= cls('apphero', 'card') ?>">
      <?= sx_app_icon(cls('apphero', 'icon')) ?>
      <div>
        <p class="<?= cls('apphero', 'name') ?>"><?= e($app['name'] ?? $brand['name']) ?></p>
        <?php if (!empty($app['version'])): ?><p class="<?= cls('apphero', 'ver') ?>"><?= e($app['version']) ?></p><?php endif; ?>
        <p class="<?= cls('apphero', 'dev') ?>"><?= e($app['developer'] ?? $brand['name']) ?></p>
      </div>
    </div>
    <ul class="<?= cls('apphero', 'stats') ?>">
      <?php foreach ($stats as [$v, $l]): ?><li><strong><?= e($v) ?></strong><span><?= e($l) ?></span></li><?php endforeach; ?>
      <li><strong><?= sx_icon('shield') ?></strong><span><?= e(t('app.official_file', ['dev' => $app['developer'] ?? $brand['name']])) ?></span></li>
    </ul>
    <h1 class="<?= cls('apphero', 'title') ?>"<?= sx_fit((string)$props['title']) ?>><?= e($props['title']) ?></h1>
    <div class="<?= cls('apphero', 'get') ?>">
      <?= sx_install_group('hero') ?>
      <?php if (!empty($app['version'])): ?><p class="<?= cls('apphero', 'latest') ?>"><?= e(t('app.latest', ['v' => $app['version']])) ?><?= !empty($app['updated']) ? ' · ' . e(sx_date($app['updated'])) : '' ?></p><?php endif; ?>
    </div>
    <ul class="<?= cls('apphero', 'thumbs') ?> <?= cls('swipe') ?>">
      <?php foreach ([$props['screen'], 'live', 'wallet', 'home'] as $k => $kind): ?><li><?= sx_phone(['kind' => $kind, 'image' => $k === 0 ? ($props['image'] ?: null) : null], '', $k > 0) ?></li><?php endforeach; ?>
    </ul>
  </div>
</section>
