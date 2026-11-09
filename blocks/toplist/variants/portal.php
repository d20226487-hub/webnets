<?php
/**
 * Editorial portal: the heading, then a magazine grid. The number one as a large colour tile with its summary, the next
 * two as smaller tiles under it, and a "top" sidebar with every ranked brand in compact rows.
 */
include __DIR__ . '/../_items.php';
$lead = $entries[0] ?? null;
$next = array_slice($entries, 1, 2);
?>
<section class="<?= cx('toplist', ['toplist', null, 'portal']) ?>" id="ranking">
  <div class="<?= cx('container', ['toplist', 'inner']) ?>">
    <?php if ($h === 'h1'): ?><?= sx_breadcrumbs(cls('toplist', 'crumbs')) ?><?php endif; ?>
    <?= $head() ?>
    <div class="<?= $c('mag') ?>">
      <div class="<?= $c('main') ?>">
        <?php if ($lead): [$id, $isF] = $lead; $x = $info($id); ?>
          <article class="<?= $c('lead') ?>" style="--brand: <?= e($x['b']['brand_color']) ?>">
            <p class="<?= $c('badge') ?>"><?= $isF ? $flabel($id) : '<span>#' . ($rankOf[$id] + 1) . '</span> ' . e(t('casinos.ranked_by', ['c' => $crit ? t("casinos.crit.$crit") : t('casinos.overall')])) ?></p>
            <div class="<?= $c('lhead') ?>"><?= sx_brand_mark($id, $c('mark')) ?><p class="<?= $c('lname') ?>"><?= e($x['b']['name']) ?></p><p class="<?= $c('lscore') ?>"><strong class="<?= cls('num') ?>"><?= e($x['score']) ?></strong><small>/10</small></p></div>
            <p class="<?= $c('lsum') ?>"><?= e($x['b']['r']['summary'] ?? $x['fact']) ?></p>
            <p class="<?= $c('fact') ?>"><?= sx_icon('bolt') ?><?= e($x['value'] ?: $x['fact']) ?></p>
            <div class="<?= $c('actions') ?>"><?= $cta($id, $isF) ?><?php if ($x['review']): ?><a class="<?= $c('review') ?>" href="<?= sx_url($x['review']) ?>"><?= e(t('casinos.read')) ?></a><?php endif; ?></div>
          </article>
        <?php endif; ?>
        <?php if ($next): ?>
          <div class="<?= $c('pair') ?>">
            <?php foreach ($next as [$id, $isF]): $x = $info($id); ?>
              <article class="<?= $c('tile2') ?>" style="--brand: <?= e($x['b']['brand_color']) ?>">
                <p class="<?= $c('flabel') ?>"><?= $isF ? $flabel($id) : '#' . ($rankOf[$id] + 1) ?></p>
                <div class="<?= $c('lhead') ?>"><?= sx_brand_mark($id, $c('mark')) ?><p class="<?= $c('name') ?>"><?= e($x['b']['name']) ?></p><p class="<?= $c('score') ?>"><strong class="<?= cls('num') ?>"><?= e($x['score']) ?></strong><small>/10</small></p></div>
                <p class="<?= $c('fact') ?>"><?= sx_icon('bolt') ?><?= e($x['fact']) ?></p>
                <?= $cta($id, $isF) ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <aside class="<?= $c('side') ?>" aria-label="<?= e($props['title']) ?>">
        <p class="<?= $c('sidetitle') ?>"><?= e(t('casinos.ranked_by', ['c' => $crit ? t("casinos.crit.$crit") : t('casinos.overall')])) ?></p>
        <ol class="<?= $c('mini') ?>">
          <?php foreach ($entries as [$id, $isF]): $x = $info($id); ?>
            <li>
              <span class="<?= $c('tile') ?>" style="--brand: <?= e($x['b']['brand_color']) ?>"><?= sx_brand_mark($id, $c('mark')) ?></span>
              <div><p class="<?= $c('name') ?>"><?= $x['review'] ? '<a href="' . sx_url($x['review']) . '">' . e($x['b']['name']) . '</a>' : e($x['b']['name']) ?></p><p class="<?= $c('mininote') ?>"><?= $isF ? e(t('casinos.featured')) : e($x['value'] ?: $x['fact']) ?></p></div>
              <strong class="<?= cls('num') ?>"><?= e($x['score']) ?></strong>
            </li>
          <?php endforeach; ?>
        </ol>
      </aside>
    </div>
  </div>
</section>
