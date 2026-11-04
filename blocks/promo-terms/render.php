<?php
/** "table": two-column fact sheet. "checklist": one line per term with a check icon. */
$p = sx_lead_promo();
if (!$p) {
    return;
}
$terms = sx_promo_terms($p);
?>
<section class="<?= cx('pterms', ['pterms', null, $variant]) ?>" id="terms">
  <div class="<?= cx('container', ['pterms', 'inner']) ?>">
    <header class="<?= cls('pterms', 'head') ?>">
      <h2 class="<?= cls('pterms', 'title') ?>"><?= e($props['title'] ?: t('promo.key_terms')) ?></h2>
      <?php if ($props['intro']): ?><p class="<?= cls('pterms', 'intro') ?>"><?= e($props['intro']) ?></p><?php endif; ?>
    </header>
    <?php if ($variant === 'checklist'): ?>
      <ul class="<?= cls('pterms', 'list') ?>">
        <?php foreach ($terms as $label => $val): ?><li><?= sx_icon('check') ?><span><strong><?= e($label) ?>:</strong> <?= e($val) ?></span></li><?php endforeach; ?>
      </ul>
    <?php else: ?>
      <dl class="<?= cx('card', ['pterms', 'list']) ?>">
        <?php foreach ($terms as $label => $val): ?><div><dt><?= e($label) ?></dt><dd><?= e($val) ?></dd></div><?php endforeach; ?>
      </dl>
    <?php endif; ?>
    <p class="<?= cls('pterms', 'foot') ?>"><span><?= e(sx_promo_verified($p)) ?></span>
      <a href="<?= sx_url($p['terms_url'] ?? '#') ?>" rel="nofollow noopener" target="_blank"><?= e(t('promo.full_terms')) ?></a></p>
  </div>
</section>
