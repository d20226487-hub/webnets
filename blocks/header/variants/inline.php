<?php include __DIR__ . '/../_shared.php'; ?>
<header class="<?= cx('header', ['header', null, 'inline']) ?>"<?= $sticky ? ' data-sticky' : '' ?>>
  <div class="<?= cx('container', ['header', 'inner']) ?>">
    <a class="<?= cls('header', 'brand') ?>" href="<?= sx_url(sx_home_url()) ?>"><?= sx_logo() ?></a>
    <nav class="<?= cls('header', 'nav') ?>" aria-label="Main">
      <ul>
        <?php foreach ($nav as $item): ?>
          <li><a href="<?= sx_url($item['url']) ?>"<?= !empty($item['current']) ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?= ($props['show_cta'] ?? true) ? sx_cta('header', cx('btn', ['btn', null, 'primary'], ['btn', null, 'small'], ['header', 'cta'])) : '' ?>
    <details class="<?= cls('header', 'menu') ?>">
      <summary aria-label="<?= e(t('menu')) ?>"><?= sx_icon('menu') ?></summary>
      <ul>
        <?php foreach ($nav as $item): ?>
          <li><a href="<?= sx_url($item['url']) ?>"<?= !empty($item['current']) ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </details>
  </div>
</header>
