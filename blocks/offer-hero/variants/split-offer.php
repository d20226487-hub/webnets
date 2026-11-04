<?php
/**
 * Split: headline + code/Claim on one side, a value card (big value + key terms + verified) on the other.
 * Phone order: headline, value card, action, so value and terms come before the button.
 */
if (!(include __DIR__ . '/../_shared.php')) {
    return;
}
?>
<section class="<?= cx('offerhero', ['offerhero', null, 'split']) ?>">
  <div class="<?= cx('container', ['offerhero', 'inner']) ?>">
    <div class="<?= cls('offerhero', 'head') ?>">
      <p class="<?= cls('offerhero', 'eyebrow') ?>"><?= e(sx_promo_type_label($p['type'])) ?> · <?= e(sx_ctx('brand')['name']) ?></p>
      <h1 class="<?= cls('offerhero', 'title') ?>"><?= e($title) ?></h1>
      <?php if ($props['subtitle']): ?><p class="<?= cls('offerhero', 'subtitle') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
    </div>
    <aside class="<?= cx('card', ['offerhero', 'valuecard']) ?>">
      <p class="<?= cls('offerhero', 'kind') ?>"><?= e(sx_promo_type_label($p['type'])) ?></p>
      <p class="<?= cls('offerhero', 'big') ?>"><?= e($value) ?></p>
      <dl class="<?= cls('offerhero', 'cardterms') ?>">
        <?php foreach ($keyTerms as $label => $val): ?><div><dt><?= e($label) ?></dt><dd><?= e($val) ?></dd></div><?php endforeach; ?>
      </dl>
      <p class="<?= cls('offerhero', 'verified') ?>"><?= sx_icon('shield') ?><span><?= e(sx_promo_verified($p)) ?></span> · <?= $termsLink ?></p>
    </aside>
    <div class="<?= cls('offerhero', 'action') ?>">
      <?= sx_code_box($p, 'hero') ?>
      <?= sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['offerhero', 'cta']), true) ?>
      <p class="<?= cls('offerhero', 'fine') ?>"><?= e(sx_ctx('market')['min_age']) ?>+ · <?= e(t('terms_apply')) ?></p>
    </div>
  </div>
</section>
