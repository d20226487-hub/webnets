<?php
/** One centred column: the code is the hero. Value, code + copy, Claim, then the key terms in one row. */
if (!(include __DIR__ . '/../_shared.php')) {
    return;
}
?>
<section class="<?= cx('offerhero', ['offerhero', null, 'centered']) ?>">
  <div class="<?= cx('container', ['offerhero', 'inner']) ?>">
    <p class="<?= cls('offerhero', 'eyebrow') ?>"><?= e(sx_promo_type_label($p['type'])) ?> · <?= e(sx_ctx('brand')['name']) ?></p>
    <h1 class="<?= cls('offerhero', 'title') ?>"<?= sx_fit((string)$title) ?>><?= e($title) ?></h1>
    <p class="<?= cls('offerhero', 'value') ?>"<?= sx_fit((string)$value, 'value') ?>><?= e($value) ?></p>
    <div class="<?= cls('offerhero', 'codewrap') ?>"><?= sx_code_box($p, 'hero') ?></div>
    <?= sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['offerhero', 'cta']), true) ?>
    <dl class="<?= cls('offerhero', 'keyterms') ?>">
      <?php foreach ($keyTerms as $label => $val): ?><div><dt><?= e($label) ?></dt><dd><?= e($val) ?></dd></div><?php endforeach; ?>
    </dl>
    <p class="<?= cls('offerhero', 'verified') ?>"><?= sx_icon('shield') ?><span><?= e(sx_promo_verified($p)) ?></span> · <?= $termsLink ?> · <?= e(sx_ctx('market')['min_age']) ?>+</p>
  </div>
</section>
