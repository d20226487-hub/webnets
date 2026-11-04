<?php
/**
 * Filters are radio groups; CSS :has() hides non-matching cards, so filtering works without JavaScript.
 * A tiny script only pre-selects a topic from the URL (#t-payouts) so topic links land filtered.
 */
$all = sx_reviews();
$mode = $props['mode'];
$rs = sx_review_list_items($props);
// Paging (set by the engine from the page's "paginate" rule): this page's slice only.
$pageNo = (int)($props['page_no'] ?? 1);
$pageCount = (int)($props['page_count'] ?? 1);
if ($pageCount > 1) {
    $rs = array_slice($rs, ($pageNo - 1) * (int)$props['per_page'], (int)$props['per_page']);
}
$every = max(0, (int)$props['cta_every']);
$filters = $props['filters'] && $mode === 'all';
$title = $props['title'] ?: ($mode === 'latest' ? t('reviews.latest') : ($mode === 'topic' ? sx_topic_label($props['topic']) : t('reviews.all')));
$c = fn($el) => cls('rlist', $el);
?>
<section class="<?= cx('rlist', ['rlist', null, $variant]) ?>" id="reviews">
  <div class="<?= cx('container', ['rlist', 'inner']) ?>">
    <header class="<?= $c('head') ?>">
      <h2 class="<?= $c('title') ?>"><?= e($title) ?></h2>
      <?php if ($mode === 'latest' && isset(sx_pages()['reviews'])): ?>
        <a class="<?= $c('more') ?>" href="<?= sx_url(sx_page_url('reviews')) ?>"><?= e(t('reviews.all_n', ['n' => count($all)])) ?><?= sx_icon('arrow') ?></a>
      <?php endif; ?>
    </header>
    <?php if ($filters): ?>
      <div class="<?= $c('filters') ?>">
        <?php if ($pageCount > 1): ?><p class="<?= $c('scope') ?>"><?= e(t('paging.filter_scope')) ?></p><?php endif; ?>
        <fieldset><legend><?= e(t('reviews.filter_topic')) ?></legend>
          <label><input type="radio" name="rf-topic" value="" checked><span><?= e(t('reviews.any_topic')) ?></span></label>
          <?php foreach (array_keys(sx_topic_stats()) as $t): ?>
            <label><input type="radio" name="rf-topic" value="<?= e($t) ?>" id="t-<?= e($t) ?>"><span><?= e(sx_topic_label($t)) ?></span></label>
          <?php endforeach; ?>
        </fieldset>
        <fieldset><legend><?= e(t('reviews.filter_rating')) ?></legend>
          <label><input type="radio" name="rf-rating" value="" checked><span><?= e(t('reviews.any_rating')) ?></span></label>
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <label><input type="radio" name="rf-rating" value="<?= $i ?>"><span><?= $i ?>★</span></label>
          <?php endfor; ?>
        </fieldset>
      </div>
    <?php endif; ?>
    <ul class="<?= $c('list') ?>">
      <?php foreach ($rs as $i => $r): ?>
        <li data-rating="<?= (int)$r['rating'] ?>" data-topics="<?= e(implode(' ', $r['topics'])) ?>"><?= $variant === 'verified' ? sx_review_card_verified($r) : sx_review_card($r) ?></li>
        <?php if ($every && ($i + 1) % $every === 0 && $i + 1 < count($rs)): ?>
          <li class="<?= $c('cta') ?>"><p><?= e(t('reviews.mid_cta')) ?></p><?= sx_cta('mid', cx('btn', ['btn', null, 'primary']), true) ?></li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ul>
    <?= sx_pagination($props['page_base'] ?? sx_page_id(), $pageNo, $pageCount, $c('pages')) ?>
  </div>
</section>
<?php if ($filters && !isset(SX::$once['rlist_js'])): SX::$once['rlist_js'] = 1; ?>
<script>
(function () {
  var m = location.hash.match(/^#t-([a-z]+)$/), el = m && document.getElementById('t-' + m[1]);
  if (el) { el.checked = true; }
})();
</script>
<?php endif; ?>
