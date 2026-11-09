<?php /** App pages: app icon + app name + "official" note + one Get button for the visitor's platform. No nav (sidebar sites: the phone menu holds the sidebar). */ ?>
<?php include __DIR__ . '/../_shared.php'; ?>
<header class="<?= cx('header', ['header', null, 'app']) ?>">
  <div class="<?= cx('container', ['header', 'inner']) ?>">
    <?= $sideMenu ?>
    <a class="<?= cls('header', 'brand') ?>" href="<?= sx_url(sx_home_url()) ?>">
      <?= sx_app_icon(cls('header', 'appicon')) ?>
      <span class="<?= cls('header', 'appname') ?>"><strong><?= e(sx_app()['name'] ?? sx_ctx('brand')['name']) ?></strong><small><?= e(t('app.official')) ?></small></span>
    </a>
    <?= sx_app_get('header', cx('btn', ['btn', null, 'primary'], ['btn', null, 'small'], ['header', 'cta']), true) ?>
  </div>
</header>
<?php if ($sideMenu !== '') include __DIR__ . '/../_menu.php'; ?>
