<?php
/**
 * "bar": full-width bottom bar with logo + age badge. "pill": floating centred button.
 * "app-banner": app pages only; app icon + name + age + Get button for the visitor's platform.
 * "code-bar": promo pages only; the lead offer's code with copy + Claim.
 */
if (empty(SX::$ctx['cta_brand']) && (sx_ctx('links')['go'] ?? '#go') === '#go') {
    return; // no brand on this page (e.g. a news article): no sticky CTA
}
$id = sx_ctx('prefix') . 'sticky';
$age = sx_ctx('market')['min_age'];
?>
<div class="<?= cx('sticky', ['sticky', null, $variant]) ?>" id="<?= e($id) ?>" aria-label="<?= e(t('sticky.label')) ?>" role="region" inert>
  <?php if ($variant === 'app-banner'): ?>
    <?= sx_app_icon(cls('sticky', 'appicon')) ?>
    <span class="<?= cls('sticky', 'appname') ?>"><strong><?= e(sx_app()['name'] ?? sx_ctx('brand')['name']) ?></strong><small><?= e(t('app.official')) ?></small></span>
    <span class="<?= cls('age') ?>"><?= e($age) ?>+</span>
    <?= sx_app_get('sticky', cx('btn', ['btn', null, 'primary'], ['btn', null, 'small'], ['sticky', 'cta']), true) ?>
  <?php elseif ($variant === 'code-bar'): ?>
    <?php $lead = sx_lead_promo(); ?>
    <?= sx_code_box($lead, 'sticky', cls('sticky', 'code')) ?>
    <?= sx_cta('sticky', cx('btn', ['btn', null, 'primary'], ['btn', null, 'small'], ['sticky', 'cta'])) ?>
  <?php else: ?>
    <?php if ($variant === 'bar'): ?>
      <span class="<?= cls('sticky', 'brand') ?>"><?= sx_logo() ?></span>
    <?php endif; ?>
    <span class="<?= cls('age') ?>"><?= e($age) ?>+</span>
    <?= sx_cta('sticky', cx('btn', ['btn', null, 'primary'], ['sticky', 'cta'])) ?>
  <?php endif; ?>
</div>
<script>
(function () {
  var bar = document.getElementById(<?= json_encode($id) ?>);
  var hero = document.querySelector('[data-cta="hero"]');
  var fin = document.querySelector('[data-cta="final"]');
  if (!bar || !hero || !('IntersectionObserver' in window)) return;
  var heroGone = false, finalSeen = false;
  function apply() {
    var on = heroGone && !finalSeen;
    bar.toggleAttribute('data-on', on);
    bar.inert = !on;
  }
  new IntersectionObserver(function (es) {
    es.forEach(function (e) {
      if (e.target === hero) heroGone = !e.isIntersecting && e.boundingClientRect.top < 0;
      if (e.target === fin) finalSeen = e.isIntersecting;
    });
    apply();
  }).observe(hero);
  if (fin) new IntersectionObserver(function (es) { finalSeen = es[0].isIntersecting; apply(); }).observe(fin);
})();
</script>
