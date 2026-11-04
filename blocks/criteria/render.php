<?php
$w = sx_criteria();
$total = array_sum($w);
$unscored = array_diff(array_keys(SX::$ctx['criteria'] ?? []), array_keys($w));
$c = fn($el) => cls('crit', $el);
?>
<section class="<?= cx('crit', ['crit', null, $variant]) ?>" id="criteria">
  <div class="<?= cx('container', ['crit', 'inner']) ?>">
    <h2 class="<?= $c('title') ?>"><?= e($props['title']) ?></h2>
    <ol class="<?= $c('list') ?>">
      <?php foreach ($w as $k => $weight): $pct = round(100 * $weight / $total); ?>
        <li><p class="<?= $c('head') ?>"><strong><?= e(t("casinos.crit.$k")) ?></strong><span class="<?= cls('num') ?>"><?= $pct ?>%</span></p>
          <span class="<?= $c('bar') ?>" aria-hidden="true"><span style="width: <?= $pct * 3 ?>%"></span></span>
          <p class="<?= $c('desc') ?>"><?= e(t("casinos.crit_desc.$k")) ?></p></li>
      <?php endforeach; ?>
    </ol>
    <?php if ($unscored): ?><p class="<?= $c('note') ?>"><?= sx_icon('info') ?><?= e(t('casinos.bonus_off')) ?></p><?php endif; ?>
  </div>
</section>
