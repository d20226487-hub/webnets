<?php
/** Studio identity + lobby CTA on one side; the newest game added to the brand, as a spotlight card, on the other. */
$v = sx_vendor();
$games = sx_vendor_games();
$new = sx_vendor_new_games();
$spot = $new[0] ?? ($games[0] ?? null);
?>
<section class="<?= cx('vhero', ['vhero', null, 'spotlight']) ?>">
  <div class="<?= cx('container', ['vhero', 'inner']) ?>">
    <div class="<?= cls('vhero', 'copy') ?>">
      <?php if (sx_vendor_multi()): ?>
        <p class="<?= cls('vhero', 'studios') ?>"><?php foreach (sx_vendor_ids() as $sid): $sp = sx_ctx('providers')[$sid]; ?><span class="<?= cls('vhero', 'studio') ?>" style="--p1: <?= e($sp['colors'][0]) ?>"><span aria-hidden="true"></span><?= e($sp['name']) ?></span><?php endforeach; ?></p>
      <?php else: ?>
      <p class="<?= cls('vhero', 'studio') ?>" style="--p1: <?= e($v['colors'][0]) ?>"><span aria-hidden="true"></span><?= e($v['name']) ?></p>
      <?php endif; ?>
      <h1 class="<?= cls('vhero', 'title') ?>"><?= e($props['title']) ?></h1>
      <?php if ($props['subtitle']): ?><p class="<?= cls('vhero', 'subtitle') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
      <ul class="<?= cls('vhero', 'facts') ?>">
        <li><strong class="<?= cls('num') ?>"><?= count($games) ?></strong><span><?= e(t('vendor.games_at')) ?></span></li>
        <?php if (sx_vendor_multi()): ?>
        <li><strong class="<?= cls('num') ?>"><?= count(sx_vendor_ids()) ?></strong><span><?= e(t('vendor.studios')) ?></span></li>
        <li class="<?= cls('vhero', 'sig') ?>"><strong><?= e(count(sx_vendor_new_games())) ?></strong><span><?= e(t('vendor.new_60')) ?></span></li>
        <?php else: ?>
        <li><strong class="<?= cls('num') ?>"><?= e($v['founded']) ?></strong><span><?= e(t('vendor.founded')) ?></span></li>
        <li class="<?= cls('vhero', 'sig') ?>"><strong><?= e(tm($v['signature'])) ?></strong><span><?= e(t('vendor.signature')) ?></span></li>
        <?php endif; ?>
      </ul>
      <?= sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['vhero', 'cta']), true) ?>
    </div>
    <?php if ($spot): ?>
      <aside class="<?= cx('card', ['vhero', 'spot']) ?>">
        <p class="<?= cls('vhero', 'spotlabel') ?>"><?= e($spot['is_new'] ? t('vendor.new_on') : t('vendor.popular')) ?></p>
        <?= sx_game_card($spot, 'game-hero') ?>
        <?php if ($spot['added']): ?><p class="<?= cls('vhero', 'added') ?>"><?= e(t('vendor.added', ['date' => sx_date($spot['added'])])) ?></p><?php endif; ?>
      </aside>
    <?php endif; ?>
  </div>
</section>
