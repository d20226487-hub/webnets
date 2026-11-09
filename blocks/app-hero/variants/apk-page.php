<?php
/**
 * APK download page: a header row (square icon, headline, developer and version) with the install buttons on the end side,
 * the first one large; under a rule, a row of five figures (rating, update date, official file, age, requirements); then
 * the short description. Our own visual language.
 */
include __DIR__ . '/../_shared.php';
$andr = (bool)array_intersect(['android', 'apk'], $ps);
$st = sx_store_figures($andr ? 'android' : 'ios');
$os = $app['min_os'][$andr ? 'android' : 'ios'] ?? null;
$figs = array_values(array_filter([
    $st ? [sx_number($st['rating'], 1) . ' ★', t('store.reviews_n', ['n' => sx_compact($st['count'])]), 'rate'] : null,
    !empty($app['updated']) ? [sx_date($app['updated']), t('app.updated'), ''] : null,
    [t('app.official_short'), $app['developer'] ?? $brand['name'], 'safe'],
    [$app['age_rating'] ?? ($market['min_age'] . '+'), t('app.age'), ''],
    $os ? [t('app.and_later', ['os' => $os]), t('app.requires'), ''] : null,
]));
?>
<?= sx_platform_script() ?>
<section class="<?= cx('apphero', ['apphero', null, 'apk-page']) ?>">
  <div class="<?= cx('container', ['apphero', 'inner']) ?>">
    <div class="<?= cls('apphero', 'top') ?>">
      <div class="<?= cls('apphero', 'id') ?>">
        <?= sx_app_icon(cls('apphero', 'icon')) ?>
        <div>
          <h1 class="<?= cls('apphero', 'title') ?>"<?= sx_fit((string)$props['title']) ?>><?= e($props['title']) ?></h1>
          <p class="<?= cls('apphero', 'dev') ?>"><?= e($app['developer'] ?? $brand['name']) ?></p>
          <?php if (!empty($app['version'])): ?><p class="<?= cls('apphero', 'ver') ?>"><?= e($app['version']) ?><?= $sizeText ? ' · ' . e($sizeText) : '' ?></p><?php endif; ?>
        </div>
      </div>
      <div class="<?= cls('apphero', 'get') ?>"><?= sx_install_group('hero') ?></div>
    </div>
    <ul class="<?= cls('apphero', 'figs') ?>">
      <?php foreach ($figs as [$v, $l, $k]): ?>
        <li<?= $k ? ' class="' . cls('apphero', 'fig', $k) . '"' : '' ?>><strong><?= $k === 'safe' ? sx_icon('shield') : '' ?><?= e($v) ?></strong><span><?= e($l) ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php if ($props['subtitle']): ?><p class="<?= cls('apphero', 'desc') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
  </div>
</section>
