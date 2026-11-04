<?php
/** Numbers first: games at the brand, the brand's average RTP (known values only), newest addition. Cover mosaic beside. */
$v = sx_vendor();
$games = sx_vendor_games();
$known = array_filter(array_column($games, 'rtp'), fn($r) => $r !== null);
$newest = $games;
usort($newest, fn($a, $b) => strcmp($b['added'] ?? '', $a['added'] ?? ''));
$newest = $newest[0] ?? null;
?>
<section class="<?= cx('vhero', ['vhero', null, 'stats']) ?>">
  <div class="<?= cx('container', ['vhero', 'inner']) ?>">
    <div class="<?= cls('vhero', 'copy') ?>">
      <?php if (sx_vendor_multi()): ?>
        <p class="<?= cls('vhero', 'studios') ?>"><?php foreach (sx_vendor_ids() as $sid): $sp = sx_ctx('providers')[$sid]; ?><span class="<?= cls('vhero', 'studio') ?>" style="--p1: <?= e($sp['colors'][0]) ?>"><span aria-hidden="true"></span><?= e($sp['name']) ?></span><?php endforeach; ?></p>
      <?php else: ?>
      <p class="<?= cls('vhero', 'studio') ?>" style="--p1: <?= e($v['colors'][0]) ?>"><span aria-hidden="true"></span><?= e($v['name']) ?></p>
      <?php endif; ?>
      <h1 class="<?= cls('vhero', 'title') ?>"><?= e($props['title']) ?></h1>
      <?php if ($props['subtitle']): ?><p class="<?= cls('vhero', 'subtitle') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
      <dl class="<?= cls('vhero', 'stats') ?>">
        <div><dt><?= e(t('vendor.games_at')) ?></dt><dd class="<?= cls('num') ?>"><?= count($games) ?></dd></div>
        <?php if ($known): ?>
          <div><dt><?= e(t('vendor.avg_rtp', ['market' => sx_market_label()])) ?></dt><dd class="<?= cls('num') ?>"><?= e(sx_number(array_sum($known) / count($known), 1)) ?>%</dd></div>
        <?php endif; ?>
        <?php if ($newest): ?>
          <div><dt><?= e(t('vendor.newest')) ?></dt><dd><?= e($newest['name']) ?><small><?= e(sx_date($newest['added'])) ?></small></dd></div>
        <?php endif; ?>
      </dl>
      <?= sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['vhero', 'cta']), true) ?>
    </div>
    <div class="<?= cls('vhero', 'mosaic') ?>" aria-hidden="true">
      <?php foreach (array_slice($games, 0, 4) as $g): ?><?= sx_game_cover($g) ?><?php endforeach; ?>
    </div>
  </div>
</section>
