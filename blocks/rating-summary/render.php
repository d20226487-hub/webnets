<?php
/**
 * board: score card with distribution beside the headline. strip: one compact row for inner pages.
 * quotes: score card, then the latest praise and the latest complaint side by side (balance up front).
 * band: score, one stacked 5-to-1 bar and topic scores in a single horizontal band.
 */
$topic = $props['topic'] ?: null;
$st = sx_review_stats($topic);
$lead = $props['lead_topic'] && !$topic ? $props['lead_topic'] : null;
$ls = $lead ? sx_review_stats($lead) : null;
$crumbs = sx_breadcrumbs(cls('rsum', 'crumbs'));
$c = fn($el) => cls('rsum', $el);
if ($variant === 'quotes' || $variant === 'band'):
    $all = sx_reviews();
    $good = array_values(array_filter($all, fn($r) => $r['rating'] >= 4))[0] ?? null;
    $bad = array_values(array_filter($all, fn($r) => $r['rating'] <= 2))[0] ?? null;
    $topics = array_slice(sx_topic_stats(), 0, 4, true);
?>
<section class="<?= cx('rsum', ['rsum', null, $variant]) ?>">
  <div class="<?= cx('container', ['rsum', 'inner']) ?>">
    <div class="<?= $c('copy') ?>">
      <?= $crumbs ?>
      <h1 class="<?= $c('title') ?>"><?= e($props['title']) ?></h1>
      <?php if ($props['subtitle']): ?><p class="<?= $c('subtitle') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
    </div>
    <div class="<?= cx('card', ['rsum', 'card']) ?>">
      <div class="<?= $c('score') ?>">
        <strong class="<?= cx('num', ['rsum', 'avg']) ?>"><?= e(sx_score($st['avg'])) ?></strong>
        <div><?= sx_stars($st['avg'], cls('rsum', 'stars')) ?><p class="<?= $c('count') ?>"><?= e(sx_plural('reviews.count', $st['count'])) ?></p></div>
      </div>
      <?php if ($variant === 'band'): ?>
        <div class="<?= $c('stack') ?>" role="img" aria-label="<?= e(t('reviews.distribution') . ': ' . implode(', ', array_map(fn($k, $n) => "{$k}★ {$n}", array_keys($st['dist']), $st['dist']))) ?>">
          <?php foreach ($st['dist'] as $stars => $n): if (!$n) { continue; } ?><span data-stars="<?= $stars ?>" style="flex-grow: <?= $n ?>"><?= $stars ?>★</span><?php endforeach; ?>
        </div>
        <ul class="<?= $c('topics') ?>">
          <?php foreach ($topics as $tp => $ts): ?><li><span><?= e(sx_topic_label($tp)) ?></span><strong class="<?= cls('num') ?>"><?= e(sx_score($ts['avg'])) ?></strong></li><?php endforeach; ?>
        </ul>
      <?php elseif ($ls && $ls['count']): ?>
        <p class="<?= $c('lead') ?>"><span><?= e(sx_topic_label($lead)) ?></span><strong><?= e(t('reviews.score_of', ['v' => sx_score($ls['avg'])])) ?></strong><small><?= e(sx_plural('reviews.count', $ls['count'])) ?></small></p>
      <?php endif; ?>
      <?= sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['rsum', 'cta']), true) ?>
    </div>
    <?php if ($variant === 'quotes' && ($good || $bad)): ?>
      <div class="<?= $c('quotes') ?>">
        <?php foreach ([[$good, 'reviews.recent_good'], [$bad, 'reviews.recent_bad']] as [$r, $key]): if (!$r) { continue; } ?>
          <figure class="<?= $c('quote') ?>" data-tone="<?= $r['rating'] >= 4 ? 'good' : 'bad' ?>">
            <figcaption><?= e(t($key)) ?> · <?= sx_stars($r['rating']) ?></figcaption>
            <blockquote><p><strong><?= e($r['title']) ?></strong> <?= e($r['text']) ?></p></blockquote>
            <p class="<?= $c('who') ?>"><?= e($r['name']) ?><?= $r['city'] ? ', ' . e($r['city']) : '' ?> · <?= e(sx_date($r['date'])) ?></p>
          </figure>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php return; endif; ?>
<section class="<?= cx('rsum', ['rsum', null, $variant]) ?>">
  <div class="<?= cx('container', ['rsum', 'inner']) ?>">
    <div class="<?= $c('copy') ?>">
      <?= $crumbs ?>
      <h1 class="<?= $c('title') ?>"><?= e($props['title']) ?></h1>
      <?php if ($props['subtitle']): ?><p class="<?= $c('subtitle') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
    </div>
    <div class="<?= cx('card', ['rsum', 'card']) ?>">
      <div class="<?= $c('score') ?>">
        <strong class="<?= cx('num', ['rsum', 'avg']) ?>"><?= e(sx_score($st['avg'])) ?></strong>
        <div><?= sx_stars($st['avg'], cls('rsum', 'stars')) ?><p class="<?= $c('count') ?>"><?= e($topic ? sx_topic_label($topic) . ' · ' : '') ?><?= e(sx_plural('reviews.count', $st['count'])) ?></p></div>
      </div>
      <?php if ($ls && $ls['count']): ?>
        <p class="<?= $c('lead') ?>"><span><?= e(sx_topic_label($lead)) ?></span><strong><?= e(t('reviews.score_of', ['v' => sx_score($ls['avg'])])) ?></strong><small><?= e(sx_plural('reviews.count', $ls['count'])) ?></small></p>
      <?php endif; ?>
      <?= sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['rsum', 'cta']), true) ?>
      <?php if ($variant === 'board'): ?>
        <ol class="<?= $c('dist') ?>" aria-label="<?= e(t('reviews.distribution')) ?>">
          <?php foreach ($st['dist'] as $stars => $n): ?>
            <li><span><?= $stars ?>★</span><span class="<?= $c('bar') ?>"><span style="width: <?= $st['count'] ? round(100 * $n / $st['count']) : 0 ?>%"></span></span><span class="<?= cls('num') ?>"><?= $n ?></span></li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </div>
  </div>
</section>
