<?php include __DIR__ . '/../_shared.php'; ?>
<header class="<?= cx('header', ['header', null, 'centered']) ?>"<?= $sticky ? ' data-sticky' : '' ?>>
  <div class="<?= cx('container', ['header', 'inner']) ?>">
    <?= $menu ?>
    <a class="<?= cls('header', 'brand') ?>" href="<?= sx_url(sx_home_url()) ?>"><?= sx_logo() ?></a>
    <?= ($props['show_cta'] ?? true) ? sx_cta('header', cx('btn', ['btn', null, 'primary'], ['btn', null, 'small'], ['header', 'cta'])) : '' ?>
  </div>
  <nav class="<?= cls('header', 'nav') ?>" aria-label="Main">
    <ul class="<?= cls('container') ?>">
      <?php foreach ($nav as $item): ?>
        <li><a href="<?= sx_url($item['url']) ?>"<?= !empty($item['current']) ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </nav>
</header>
<?php if ($menu !== '') include __DIR__ . '/../_menu.php'; ?>
