<?php
/**
 * "side-by-side": one column per install path. "tabs": one panel per path, switched with radio
 * inputs (no JS); the visitor's platform is pre-selected by the platform script when present.
 */
$app = sx_app();
$size = sx_app_size('apk');
$defaults = [
    'ios' => [t('install.ios.1'), t('install.ios.2'), t('install.open')],
    'android' => [t('install.play.1'), t('install.play.2'), t('install.open')],
    'apk' => [t('install.apk.1', ['size' => $size]), t('install.apk.2'), t('install.apk.3'), t('install.open')],
];
$labels = ['ios' => t('app.iphone'), 'android' => t('install.via_play'), 'apk' => t('install.via_apk')];
$icons = ['ios' => 'mobile', 'android' => 'play', 'apk' => 'download'];
$paths = [];
foreach (sx_app_platforms() as $p) {
    $custom = array_column($props['steps'][$p] ?? [], 'text');
    $paths[$p] = $custom ?: $defaults[$p];
}
$meta = fn($p) => t('app.version') . ' ' . ($app['version'] ?? '') . ' · ' . sx_app_size($p) . ' · ' . t('app.and_later', ['os' => $app['min_os'][$p === 'ios' ? 'ios' : 'android'] ?? '']);
$gid = sx_ctx('prefix') . 'inst';
?>
<?= sx_platform_script() ?>
<section class="<?= cx('install-guide', ['install-guide', null, $variant]) ?>" id="install">
  <div class="<?= cx('container', ['install-guide', 'inner']) ?>">
    <header class="<?= cls('install-guide', 'head') ?>">
      <?php if ($props['eyebrow']): ?><p class="<?= cls('eyebrow') ?>"><?= e($props['eyebrow']) ?></p><?php endif; ?>
      <h2 class="<?= cls('install-guide', 'title') ?>"><?= e($props['title']) ?></h2>
    </header>

    <?php if ($variant === 'tabs'): ?>
      <div class="<?= cls('install-guide', 'tabs') ?>">
        <?php $i = 0; foreach ($paths as $p => $_): ?>
          <input type="radio" name="<?= e($gid) ?>" id="<?= e("$gid-$p") ?>" data-tab="<?= e($p) ?>"<?= $i++ === 0 ? ' checked' : '' ?>>
          <label for="<?= e("$gid-$p") ?>"><?= sx_icon($icons[$p]) ?><?= e($labels[$p]) ?></label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="<?= cls('install-guide', 'paths') ?>">
      <?php foreach ($paths as $p => $steps): ?>
        <article class="<?= cx('card', ['install-guide', 'path']) ?>" data-path="<?= e($p) ?>">
          <h3 class="<?= cls('install-guide', 'name') ?>"><?= sx_icon($icons[$p]) ?><?= e($labels[$p]) ?></h3>
          <p class="<?= cls('install-guide', 'meta') ?>"><?= e($meta($p)) ?></p>
          <ol class="<?= cls('install-guide', 'steps') ?>">
            <?php foreach ($steps as $n => $s): ?><li><span class="<?= cx('num', ['install-guide', 'n']) ?>"><?= $n + 1 ?></span><span><?= e($s) ?></span></li><?php endforeach; ?>
          </ol>
          <?php if ($p === 'apk'): ?>
            <p class="<?= cls('install-guide', 'note') ?>"><?= sx_icon('info') ?><span><?= e(t('install.apk.note')) ?></span></p>
          <?php endif; ?>
          <?= sx_install($p, 'mid') ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php if ($variant === 'tabs'): ?>
<script>
(function () {
  var p = document.documentElement.getAttribute('data-platform');
  var pick = p === 'android' ? (document.getElementById(<?= json_encode("$gid-android") ?>) || document.getElementById(<?= json_encode("$gid-apk") ?>)) : document.getElementById(<?= json_encode($gid) ?> + '-' + p);
  if (pick) pick.checked = true;
})();
</script>
<?php endif; ?>
