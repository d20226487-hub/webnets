<?php
$id = sx_page_item() ?? sx_casino_ids()[0];
$b = sx_casino($id);
$w = sx_criteria();
$total = array_sum($w);
$c = fn($el) => cls('sbox', $el);
$unscored = array_diff(array_keys(SX::$ctx['criteria'] ?? []), array_keys($w));
?>
<section class="<?= cx('sbox', ['sbox', null, $variant]) ?>" id="score">
  <div class="<?= cx('container', ['sbox', 'inner']) ?>">
    <h2 class="<?= $c('title') ?>"><?= e($props['title'] ?: t('casinos.breakdown', ['name' => $b['name']])) ?></h2>
    <ul class="<?= $c('list') ?>">
      <?php foreach ($w as $crit => $weight): $s = $b['r']['scores'][$crit]; ?>
        <li>
          <p class="<?= $c('name') ?>"><strong><?= e(t("casinos.crit.$crit")) ?></strong><small><?= e(t('casinos.weight', ['n' => round(100 * $weight / $total)])) ?></small></p>
          <span class="<?= $c('bar') ?>" aria-hidden="true"><span style="width: <?= $s['score'] * 10 ?>%"></span></span>
          <strong class="<?= cx('num', ['sbox', 'val']) ?>"><?= e(sx_score10($s['score'])) ?></strong>
          <p class="<?= $c('reason') ?>"><?= e($s['reason']) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
    <p class="<?= $c('overall') ?>"><?= e(t('casinos.overall')) ?>: <strong><?= e(sx_score10(sx_casino_score($id))) ?>/10</strong></p>
    <?php if ($unscored): ?><p class="<?= $c('note') ?>"><?= sx_icon('info') ?><?= e(t('casinos.bonus_off')) ?></p><?php endif; ?>
    <?= sx_cta('mid', cx('btn', ['btn', null, 'primary'], ['sbox', 'cta']), true) ?>
  </div>
</section>
