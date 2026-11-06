<?php
$ids = sx_casino_rank();
$w = sx_criteria();
$h = $props['heading'] === 'h1' ? 'h1' : 'h2';
$c = fn($el) => cls('ctable', $el);
$facts = [
    'casinos.f.withdrawal' => fn($id) => sx_key_fact($id, 'payouts'),
    'casinos.f.min_deposit' => fn($id) => money(sx_casino($id)['f']['min_deposit']),
    'casinos.f.games' => fn($id) => sx_number(sx_casino($id)['f']['games']),
    'casinos.f.live' => fn($id) => sx_number(sx_casino($id)['f']['live_tables']),
    'casinos.f.app' => fn($id) => sx_casino($id)['f']['app'] ? t('casinos.app_yes') : t('casinos.app_no'),
    'casinos.f.support' => fn($id) => t('casinos.chat', ['h' => sx_casino($id)['f']['support']['chat']]),
];
if (!empty(SX::$ctx['product'])) { // product sites (casino / sports / both): the product's headline facts replace games + live tables
    $mid = [];
    foreach (array_diff_key(sx_fact_defs(), ['payouts' => 1, 'support' => 1]) as [$label, $fn]) {
        $mid[$label] = $fn;
    }
    $facts = array_slice($facts, 0, 2, true) + $mid + array_slice($facts, 4, null, true);
}
?>
<section class="<?= cx('ctable', ['ctable', null, $variant]) ?>" id="compare">
  <div class="<?= cx('container', ['ctable', 'inner']) ?>">
    <?php if ($h === 'h1'): ?><?= sx_breadcrumbs(cls('ctable', 'crumbs')) ?><?php endif; ?>
    <<?= $h ?> class="<?= $c('title') ?>"><?= e($props['title']) ?></<?= $h ?>>
    <div class="<?= $c('scroll') ?>" tabindex="0" role="region" aria-label="<?= e($props['title']) ?>">
      <table class="<?= $c('table') ?>">
        <thead><tr><td></td><?php foreach ($ids as $id): ?><th scope="col"><?= sx_brand_mark($id, $c('mark')) ?><span><?= e(sx_casino($id)['name']) ?></span></th><?php endforeach; ?></tr></thead>
        <tbody>
          <tr class="<?= $c('overall') ?>"><th scope="row"><?= e(t('casinos.overall')) ?></th><?php foreach ($ids as $id): ?><td><strong class="<?= cls('num') ?>"><?= e(sx_score10(sx_casino_score($id))) ?></strong></td><?php endforeach; ?></tr>
          <?php foreach ($w as $crit => $_): ?>
            <tr><th scope="row"><?= e(t("casinos.crit.$crit")) ?></th><?php foreach ($ids as $id): ?><td><?= e(sx_score10(sx_casino_score($id, $crit))) ?></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
          <?php foreach ($facts as $k => $fn): ?>
            <tr><th scope="row"><?= e(isset(SX::$ctx['strings'][$k]) ? t($k) : $k) ?></th><?php foreach ($ids as $id): ?><td><?= e($fn($id)) ?></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
          <tr class="<?= $c('ctas') ?>"><th scope="row"></th><?php foreach ($ids as $id): $rv = sx_review_url($id); ?><td><?= sx_brand_cta($id, 'compare', cx('btn', ['btn', null, 'primary'], ['btn', null, 'small'])) ?><?php if ($rv): ?><a href="<?= sx_url($rv) ?>"><?= e(t('casinos.read')) ?></a><?php endif; ?></td><?php endforeach; ?></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>
