<?php
/** Copy beside a ticket: value on top, perforation, code + Claim on the stub. On phones the ticket follows the headline. */
if (!(include __DIR__ . '/../_shared.php')) {
    return;
}
?>
<section class="<?= cx('offerhero', ['offerhero', null, 'ticket']) ?>">
  <div class="<?= cx('container', ['offerhero', 'inner']) ?>">
    <div class="<?= cls('offerhero', 'head') ?>">
      <p class="<?= cls('offerhero', 'eyebrow') ?>"><?= e(sx_promo_type_label($p['type'])) ?> · <?= e(sx_ctx('brand')['name']) ?></p>
      <h1 class="<?= cls('offerhero', 'title') ?>"<?= sx_fit((string)$title) ?>><?= e($title) ?></h1>
      <?php if ($props['subtitle']): ?><p class="<?= cls('offerhero', 'subtitle') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
    </div>
    <article class="<?= cx('card', ['offerhero', 'ticket']) ?>">
      <div class="<?= cls('offerhero', 'top') ?>">
        <p class="<?= cls('offerhero', 'kind') ?>"><?= e(sx_promo_type_label($p['type'])) ?></p>
        <p class="<?= cls('offerhero', 'value') ?>"<?= sx_fit((string)$value, 'value') ?>><?= e($value) ?></p>
        <p class="<?= cls('offerhero', 'mini') ?>"><?= e(implode(' · ', array_map(fn($l, $v) => "$l: $v", array_keys($keyTerms), $keyTerms))) ?></p>
      </div>
      <div class="<?= cls('offerhero', 'stub') ?>">
        <?= sx_code_box($p, 'hero') ?>
        <?= sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['btn', null, 'block'], ['offerhero', 'cta']), true) ?>
        <p class="<?= cls('offerhero', 'fine') ?>"><?= e(sx_ctx('market')['min_age']) ?>+ · <?= e(t('terms_apply')) ?></p>
      </div>
    </article>
    <div class="<?= cls('offerhero', 'meta') ?>">
      <dl class="<?= cls('offerhero', 'keyterms') ?>">
        <?php foreach ($keyTerms as $label => $val): ?><div><dt><?= e($label) ?></dt><dd><?= e($val) ?></dd></div><?php endforeach; ?>
      </dl>
      <p class="<?= cls('offerhero', 'verified') ?>"><?= sx_icon('shield') ?><span><?= e(sx_promo_verified($p)) ?></span> · <?= $termsLink ?></p>
    </div>
  </div>
</section>
