<?php
/** "solid": full-width band in the hero treatment. "split": card with copy left and CTA right. */
$pp = sx_proof_points();
$age = sx_ctx('market')['min_age'];
$proof = [$pp['licence'], $pp['payout']];
?>
<section class="<?= cx('band', ['band', null, $variant]) ?>">
  <div class="<?= cx('container', ['band', 'inner']) ?>">
    <div class="<?= cls('band', 'copy') ?>">
      <h2 class="<?= cls('band', 'title') ?>"><?= e($props['title']) ?></h2>
      <?php if ($props['text']): ?><p class="<?= cls('band', 'text') ?>"><?= e($props['text']) ?></p><?php endif; ?>
    </div>
    <div class="<?= cls('band', 'action') ?>">
      <?php if (!empty($props['show_code'])): ?><?= sx_code_box(sx_lead_promo(), 'final', cls('band', 'code')) ?><?php endif; ?>
      <?= sx_cta('final', cx('btn', ['btn', null, 'primary'], ['band', 'cta']), true) ?>
      <ul class="<?= cls('band', 'proof') ?>">
        <li><span class="<?= cls('age') ?>"><?= e($age) ?>+</span></li>
        <?php foreach ($proof as [$icon, $value]): ?><li><?= sx_icon($icon) ?><?= e($value) ?></li><?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>
