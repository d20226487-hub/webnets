<?php
/** banner: the offer as a wide visual banner (generated art, value, title, Claim). No code box: banners-only pages. */
if (!(include __DIR__ . '/../_shared.php')) {
    return;
}
$auto = !empty($p['code']) && sx_ctx('offer_mode') === 'banners';
?>
<section class="<?= cx('offerhero', ['offerhero', null, 'banner']) ?>">
  <div class="<?= cx('container', ['offerhero', 'inner']) ?>">
    <div class="<?= cls('offerhero', 'banner') ?>">
      <?= sx_cover_art($p['id'], sx_promo_theme($p['type']), '', true) ?>
      <div class="<?= cls('offerhero', 'bcopy') ?>">
        <p class="<?= cls('offerhero', 'eyebrow') ?>"><?= e(sx_promo_type_label($p['type'])) ?> · <?= e(sx_ctx('brand')['name']) ?></p>
        <h1 class="<?= cls('offerhero', 'title') ?>"<?= sx_fit((string)$title) ?>><?= e($title) ?></h1>
        <p class="<?= cls('offerhero', 'value') ?>"<?= sx_fit((string)$value, 'value') ?>><?= e($value) ?></p>
        <?= sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['offerhero', 'cta']), true) ?>
        <p class="<?= cls('offerhero', 'fine') ?>"><?php if ($auto): ?><?= e(t('promo.code_auto')) ?> · <?php endif; ?><?= e(sx_ctx('market')['min_age']) ?>+ · <?= e(t('terms_apply')) ?></p>
      </div>
    </div>
    <div class="<?= cls('offerhero', 'meta') ?>">
      <dl class="<?= cls('offerhero', 'keyterms') ?>">
        <?php foreach ($keyTerms as $label => $val): ?><div><dt><?= e($label) ?></dt><dd><?= e($val) ?></dd></div><?php endforeach; ?>
      </dl>
      <p class="<?= cls('offerhero', 'verified') ?>"><?= sx_icon('shield') ?><span><?= e(sx_promo_verified($p)) ?></span> · <?= $termsLink ?></p>
    </div>
  </div>
</section>
