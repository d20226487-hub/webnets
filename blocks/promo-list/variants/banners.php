<?php
/** banners: every other offer as a small visual banner (art, value, title, Claim); no code boxes. */
$lead = empty($props['include_lead']) ? sx_lead_promo() : null; // pages without a lead-offer section list every offer
$types = array_filter(array_map('trim', explode(',', $props['types'] ?? '')));
$others = array_values(array_filter(sx_promos(), fn($p) => (!$lead || $p['id'] !== $lead['id']) && (!$types || in_array($p['type'], $types, true))));
if (!empty($props['limit'])) {
    $others = array_slice($others, 0, (int)$props['limit']);
}
if (!$others) {
    return;
}
?>
<?php $strip = ($props['mobile'] ?? 'stack') === 'strip' && sx_ctx('rows') !== 'stack'; ?>
<section class="<?= cx('plist', ['plist', null, 'banners']) ?><?= $strip ? ' ' . cls('plist', null, 'strip') : '' ?>" id="promotions">
  <div class="<?= cx('container', ['plist', 'inner']) ?>">
    <h2 class="<?= cls('plist', 'title') ?>"><?= e($props['title'] ?: (!empty($props['include_lead']) ? t('promo.brand_offers', ['brand' => sx_ctx('brand')['name'] ?? '']) : t('promo.more'))) ?></h2>
    <ul class="<?= cls('plist', 'items') ?><?= $strip ? ' ' . cls('swipe') : '' ?>">
      <?php foreach ($others as $p): ?>
        <li class="<?= cls('plist', 'bitem') ?>">
          <div class="<?= cls('plist', 'banner') ?>">
            <?= sx_cover_art($p['id'], sx_promo_theme($p['type']), '', true) ?>
            <div class="<?= cls('plist', 'bcopy') ?>">
              <p class="<?= cls('plist', 'kind') ?>"><?= e(sx_promo_type_label($p['type'])) ?></p>
              <p class="<?= cls('plist', 'value') ?>"><?= e(sx_promo_value($p)) ?></p>
              <h3 class="<?= cls('plist', 'name') ?>"><?= e(tm($p['title'])) ?></h3>
              <?= sx_cta('list', cx('btn', ['btn', null, 'primary'], ['plist', 'bcta'])) ?>
            </div>
          </div>
          <?php if (!empty($p['code']) && sx_ctx('offer_mode') !== 'banners' && ($props['show_codes'] ?? true)): ?><?= sx_code_box($p, 'list') ?><?php endif; ?>
          <p class="<?= cls('plist', 'foot') ?>"><span><?= e(implode(' · ', array_map(fn($l, $v) => "$l: $v", array_keys(sx_promo_key_terms($p)), sx_promo_key_terms($p)))) ?></span>
            <a href="<?= sx_url($p['terms_url'] ?? '#') ?>" rel="nofollow noopener" target="_blank"><?= e(t('promo.full_terms')) ?></a></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
