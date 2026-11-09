<?php
/**
 * Download hub: on the start side the header (icon, headline, "free · rating · platform" line, an official-version chip
 * and the version) and the description under a rule; on the end side the install buttons, the first one large, over a
 * specifications box with a screenshot on top and the facts stacked label over value. Our own visual language.
 */
include __DIR__ . '/../_shared.php';
$andr = (bool)array_intersect(['android', 'apk'], $ps);
$st = sx_store_figures($andr ? 'android' : 'ios');
$langs = $app['languages'][sx_ctx('locale')] ?? $app['languages']['en'] ?? [];
$plats = implode(', ', array_filter([in_array('ios', $ps, true) ? t('app.iphone') : null, $andr ? t('app.android') : null]));
$os = array_filter([in_array('ios', $ps, true) ? ($app['min_os']['ios'] ?? null) : null, $andr ? ($app['min_os']['android'] ?? null) : null]);
$specs = array_values(array_filter([
    [t('app.developer'), $app['developer'] ?? $brand['name']],
    [t('app.license'), t('app.free')],
    !empty($app['version']) ? [t('app.version'), $app['version']] : null,
    !empty($app['updated']) ? [t('app.updated'), sx_date($app['updated'])] : null,
    $plats ? [t('app.platform'), $plats] : null,
    $os ? [t('app.requires'), implode(' · ', array_map(fn($o) => t('app.and_later', ['os' => $o]), $os))] : null,
    $langs ? [t('app.languages'), implode(', ', $langs)] : null,
    $sizeText ? [t('app.size'), $sizeText] : null,
]));
?>
<?= sx_platform_script() ?>
<section class="<?= cx('apphero', ['apphero', null, 'hub']) ?>">
  <div class="<?= cx('container', ['apphero', 'inner']) ?>">
    <div class="<?= cls('apphero', 'copy') ?>">
      <div class="<?= cls('apphero', 'id') ?>">
        <?= sx_app_icon(cls('apphero', 'icon')) ?>
        <div class="<?= cls('apphero', 'idtext') ?>">
          <h1 class="<?= cls('apphero', 'title') ?>"<?= sx_fit((string)$props['title']) ?>><?= e($props['title']) ?></h1>
          <p class="<?= cls('apphero', 'meta') ?>"><span class="<?= cls('apphero', 'free') ?>"><?= e(t('app.free')) ?></span><?php if ($st): ?><span>★ <?= e(sx_number($st['rating'], 1)) ?> (<?= e(sx_compact($st['count'])) ?>)</span><?php endif; ?><?php if ($plats): ?><span><?= e($plats) ?></span><?php endif; ?></p>
          <p class="<?= cls('apphero', 'chips') ?>"><span class="<?= cls('apphero', 'chip') ?>"><?= sx_icon('shield') ?><?= e(t('app.official_short')) ?></span><?php if (!empty($app['version'])): ?><span class="<?= cls('apphero', 'v') ?>">V <?= e($app['version']) ?></span><?php endif; ?></p>
        </div>
      </div>
      <?php if ($props['subtitle']): ?><p class="<?= cls('apphero', 'desc') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
      <p class="<?= cls('apphero', 'official') ?>"><?= e(t('app.official_file', ['dev' => $app['developer'] ?? $brand['name']])) ?></p>
    </div>
    <div class="<?= cls('apphero', 'side') ?>">
      <?= sx_install_group('hero') ?>
      <aside class="<?= cls('apphero', 'specs') ?>">
        <div class="<?= cls('apphero', 'shot') ?>"><?= sx_phone(['kind' => $props['screen'], 'image' => $props['image'] ?: null], '', false) ?></div>
        <p class="<?= cls('apphero', 'specstitle') ?>"><?= e(t('app.specs')) ?></p>
        <dl>
          <?php foreach ($specs as [$k, $v]): ?><div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div><?php endforeach; ?>
        </dl>
      </aside>
    </div>
  </div>
</section>
