<?php
$app = sx_app();
$ps = $app['platforms'] ?? [];
$hasIos = in_array('ios', $ps, true);
$hasAndroid = (bool)array_intersect(['android', 'apk'], $ps);
$per = function (string $key, callable $fmt) use ($app, $hasIos, $hasAndroid) {
    $out = [];
    if ($hasIos && isset($app[$key]['ios'])) {
        $out[] = t('app.iphone') . ': ' . $fmt($app[$key]['ios']);
    }
    if ($hasAndroid && isset($app[$key]['android'])) {
        $out[] = t('app.android') . ': ' . $fmt($app[$key]['android']);
    }
    return implode(' · ', $out);
};
$rows = array_filter([
    t('app.developer') => $app['developer'] ?? '',
    t('app.version') => $app['version'] ?? '',
    t('app.updated') => isset($app['updated']) ? sx_date($app['updated']) : '',
    t('app.size') => $per('size_mb', fn($n) => t('app.mb', ['n' => $n])),
    t('app.requires') => $per('min_os', fn($os) => t('app.and_later', ['os' => $os])),
    t('app.languages') => implode(', ', tm($app['languages'] ?? []) ?: []),
    t('app.age') => $app['age_rating'] ?? '',
    t('app.price') => t('app.free'),
    t('trust.licensed') => tm(sx_ctx('market')['compliance']['license_short']),
]);
?>
<section class="<?= cx('appinfo', ['appinfo', null, $variant]) ?>" id="info">
  <div class="<?= cx('container', ['appinfo', 'inner']) ?>">
    <h2 class="<?= cls('appinfo', 'title') ?>"><?= e($props['title'] ?: t('app.info_title')) ?></h2>
    <dl class="<?= cls('appinfo', 'list') ?>">
      <?php foreach ($rows as $label => $value): ?>
        <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
      <?php endforeach; ?>
    </dl>
  </div>
</section>
