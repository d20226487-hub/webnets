<?php
/** Fixed, translated explanation + a four-step volatility scale marking where this studio's games sit. */
$levels = ['low', 'medium', 'high', 'very-high'];
$counts = array_fill_keys($levels, 0);
foreach (sx_vendor_games() as $g) {
    $counts[$g['volatility']] = ($counts[$g['volatility']] ?? 0) + 1;
}
?>
<section class="<?= cx('rtpx', ['rtpx', null, $variant]) ?>" id="rtp">
  <div class="<?= cx('container', ['rtpx', 'inner']) ?>">
    <h2 class="<?= cls('rtpx', 'title') ?>"><?= e(t('vendor.explain.title')) ?></h2>
    <div class="<?= cls('rtpx', 'cols') ?>">
      <p><strong><?= e(t('vendor.rtp')) ?>.</strong> <?= e(t('vendor.explain.rtp')) ?></p>
      <p><strong><?= e(t('vendor.volatility')) ?>.</strong> <?= e(t('vendor.explain.vol')) ?></p>
      <p><strong><?= e(t('vendor.max_win')) ?>.</strong> <?= e(t('vendor.explain.max')) ?></p>
    </div>
    <div class="<?= cls('rtpx', 'scale') ?>">
      <ol>
        <?php foreach ($levels as $l): ?>
          <li data-level="<?= e($l) ?>"><span><?= e(sx_volatility_label($l)) ?></span><small><?= e(sx_n_games($counts[$l])) ?></small></li>
        <?php endforeach; ?>
      </ol>
      <p class="<?= cls('rtpx', 'ends') ?>"><span><?= e(t('vendor.explain.vol_low')) ?></span><span><?= e(t('vendor.explain.vol_high')) ?></span></p>
    </div>
  </div>
</section>
