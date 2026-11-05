<?php
/** Compact rows: what it is, the value, one key term, code (or none) and Claim. */
$lead = empty($props['include_lead']) ? sx_lead_promo() : null; // pages without a lead-offer section list every offer
$types = array_filter(array_map('trim', explode(',', $props['types'] ?? '')));
$others = array_values(array_filter(sx_promos(), fn($p) => (!$lead || $p['id'] !== $lead['id']) && (!$types || in_array($p['type'], $types, true))));
if (!$others) {
    return;
}
?>
<section class="<?= cx('plist', ['plist', null, 'list']) ?>" id="promotions">
  <div class="<?= cx('container', ['plist', 'inner']) ?>">
    <h2 class="<?= cls('plist', 'title') ?>"><?= e($props['title'] ?: t('promo.more')) ?></h2>
    <ul class="<?= cls('plist', 'rows') ?>">
      <?php foreach ($others as $p): $kt = sx_promo_key_terms($p); $first = array_key_first($kt); ?>
        <li class="<?= cls('plist', 'row') ?>">
          <div class="<?= cls('plist', 'what') ?>">
            <p class="<?= cls('plist', 'kind') ?>"><?= e(sx_promo_type_label($p['type'])) ?></p>
            <h3 class="<?= cls('plist', 'name') ?>"><?= e(tm($p['title'])) ?></h3>
            <p class="<?= cls('plist', 'meta') ?>"><strong><?= e(sx_promo_value($p)) ?></strong><?php if ($first): ?> · <?= e($first) ?>: <?= e($kt[$first]) ?><?php endif; ?> · <a href="<?= sx_url($p['terms_url'] ?? '#') ?>" rel="nofollow noopener" target="_blank"><?= e(t('promo.full_terms')) ?></a></p>
          </div>
          <?= sx_code_box($p, 'list', cls('plist', 'code')) ?>
          <?= sx_cta('list', cx('btn', ['btn', null, 'primary'], ['plist', 'cta'])) ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
