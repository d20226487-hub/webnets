<?php
$st = sx_store_figures($variant === 'play' ? 'android' : 'ios');
$reviews = sx_reviews();
$seeded = (bool)array_filter($reviews, fn($r) => ($r['source'] ?? '') === 'seed');
if ($seeded && empty(sx_ctx('demo'))) {
    SX::$warnings[] = 'store-reviews: the review feed is seeded (made-up); set "demo": true or use real reviews. Review cards hidden.';
    $reviews = [];
}
if (!$st && !$reviews) {
    return;
}
// a mix that reflects the score: newest first, at least one critical review when there is one
$pick = array_slice($reviews, 0, max(1, (int)$props['limit']));
$low = array_values(array_filter($reviews, fn($r) => $r['rating'] <= 2));
if ($low && !array_filter($pick, fn($r) => $r['rating'] <= 2)) {
    $pick[count($pick) - 1] = $low[0];
}
$c = fn($el) => cls('sreviews', $el);
$initial = fn($n) => mb_strtoupper(mb_substr($n, 0, 1));
?>
<section class="<?= cx('sreviews', ['sreviews', null, $variant]) ?>" id="ratings">
  <div class="<?= cx('container', ['sreviews', 'inner']) ?>">
    <h2 class="<?= $c('title') ?>"><?= e($props['title'] ?: t($variant === 'play' ? 'store.ratings_reviews' : 'store.ratings_reviews_as')) ?></h2>
    <?php if ($st): ?>
      <div class="<?= $c('summary') ?>">
        <div class="<?= $c('score') ?>"><strong class="<?= cls('num') ?>"><?= e(sx_number($st['rating'], 1)) ?></strong>
          <?php if ($variant === 'play'): ?><?= sx_stars($st['rating']) ?><span><?= e(t('store.reviews_n', ['n' => sx_compact($st['count'])])) ?></span><?php else: ?><span><?= e(t('store.out_of_5')) ?></span><?php endif; ?></div>
        <div class="<?= $c('bars') ?>">
          <?php foreach ([5, 4, 3, 2, 1] as $s): ?><div><span><?= $variant === 'play' ? $s : str_repeat('★', $s) ?></span><i style="--v: <?= (int)($st['split'][$s] ?? 0) ?>%"></i></div><?php endforeach; ?>
          <?php if ($variant === 'appstore'): ?><p><?= e(t('store.ratings_n', ['n' => sx_compact($st['count'])])) ?></p><?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($pick): ?>
      <?php if ($seeded && sx_ctx('show_sample_notice')): ?><p class="<?= $c('sample') ?>"><?= sx_icon('info') ?><?= e(t('store.sample_reviews')) ?></p><?php endif; ?>
      <div class="<?= $c('list') ?>">
        <?php foreach ($pick as $r): ?>
          <article class="<?= $c('card') ?>">
            <?php if ($variant === 'play'): ?>
              <header><span class="<?= $c('avatar') ?>" aria-hidden="true"><?= e($initial($r['name'])) ?></span><strong><?= e($r['name']) ?></strong></header>
              <p class="<?= $c('meta') ?>"><?= sx_stars($r['rating']) ?><time datetime="<?= e($r['date']) ?>"><?= e(sx_date($r['date'])) ?></time></p>
              <p class="<?= $c('text') ?>"><?= e($r['text']) ?></p>
            <?php else: ?>
              <header><h3><?= e($r['title']) ?></h3><time datetime="<?= e($r['date']) ?>"><?= e(sx_date($r['date'])) ?></time></header>
              <p class="<?= $c('meta') ?>"><?= sx_stars($r['rating']) ?><span><?= e($r['name']) ?></span></p>
              <p class="<?= $c('text') ?>"><?= e($r['text']) ?></p>
            <?php endif; ?>
            <?php if (!empty($r['reply'])): ?><div class="<?= $c('reply') ?>"><strong><?= e(t('store.dev_response')) ?></strong><p><?= e($r['reply']['text']) ?></p></div><?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
