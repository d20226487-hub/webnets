<?php
$lead = sx_lead_promo();
$others = array_values(array_filter(sx_promos(), fn($p) => !$lead || $p['id'] !== $lead['id']));
if (!$others) {
    return;
}
?>
<section class="<?= cx('plist', ['plist', null, $variant]) ?>" id="promotions">
  <div class="<?= cx('container', ['plist', 'inner']) ?>">
    <h2 class="<?= cls('plist', 'title') ?>"><?= e($props['title'] ?: t('promo.more')) ?></h2>
    <ul class="<?= cls('plist', 'items') ?>">
      <?php foreach ($others as $p): ?>
        <li class="<?= cx('card', ['plist', 'item']) ?>">
          <p class="<?= cls('plist', 'kind') ?>"><?= e(sx_promo_type_label($p['type'])) ?></p>
          <h3 class="<?= cls('plist', 'name') ?>"><?= e(tm($p['title'])) ?></h3>
          <p class="<?= cls('plist', 'value') ?>"><?= e(sx_promo_value($p)) ?></p>
          <dl class="<?= cls('plist', 'terms') ?>">
            <?php foreach (sx_promo_key_terms($p) as $label => $val): ?><div><dt><?= e($label) ?></dt><dd><?= e($val) ?></dd></div><?php endforeach; ?>
          </dl>
          <?= sx_code_box($p, 'list') ?>
          <?= sx_cta('list', cx('btn', ['btn', null, 'primary'], ['btn', null, 'block'])) ?>
          <p class="<?= cls('plist', 'foot') ?>"><span><?= e(sx_promo_verified($p)) ?></span>
            <a href="<?= sx_url($p['terms_url'] ?? '#') ?>" rel="nofollow noopener" target="_blank"><?= e(t('promo.full_terms')) ?></a></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
