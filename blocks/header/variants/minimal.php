<?php include __DIR__ . '/../_shared.php'; ?>
<header class="<?= cx('header', ['header', null, 'minimal']) ?>"<?= $sticky ? ' data-sticky' : '' ?>>
  <div class="<?= cx('container', ['header', 'inner']) ?>">
    <?= $sideMenu ?>
    <a class="<?= cls('header', 'brand') ?>" href="<?= sx_url(sx_home_url()) ?>"><?= sx_logo() ?></a>
    <div class="<?= cls('header', 'aside') ?>">
      <?php if (!empty($props['note'])): ?>
        <span class="<?= cls('header', 'note') ?>"><?= sx_icon('shield') ?><?= e($props['note']) ?></span>
      <?php endif; ?>
      <?= ($props['show_cta'] ?? true) ? sx_cta('header', cx('btn', ['btn', null, 'primary'], ['btn', null, 'small'], ['header', 'cta'])) : '' ?>
    </div>
  </div>
</header>
<?php if ($sideMenu !== '') include __DIR__ . '/../_menu.php'; ?>
