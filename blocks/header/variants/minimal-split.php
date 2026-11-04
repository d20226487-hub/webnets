<?php include __DIR__ . '/../_shared.php'; ?>
<header class="<?= cx('header', ['header', null, 'split']) ?>"<?= $sticky ? ' data-sticky' : '' ?>>
  <div class="<?= cx('container', ['header', 'inner']) ?>">
    <span class="<?= cls('header', 'note') ?>"><?= sx_icon('shield') ?><?= e($props['note'] ?: tm(sx_ctx('market')['compliance']['license_short'])) ?></span>
    <a class="<?= cls('header', 'brand') ?>" href="<?= sx_url(sx_home_url()) ?>"><?= sx_logo() ?></a>
    <?= ($props['show_cta'] ?? true) ? sx_cta('header', cx('btn', ['btn', null, 'primary'], ['btn', null, 'small'], ['header', 'cta'])) : '' ?>
  </div>
</header>
