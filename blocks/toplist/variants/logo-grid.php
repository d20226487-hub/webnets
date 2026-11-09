<?php
/**
 * Logo grid: the heading with the ranking tabs beside it, then a dense grid of tiles. Each tile is the brand's logo on its
 * colour with a rank (or paid-placement) badge, the offer or key fact under it, and a play button.
 */
include __DIR__ . '/../_items.php';
?>
<section class="<?= cx('toplist', ['toplist', null, 'logo-grid']) ?>" id="ranking">
  <div class="<?= cx('container', ['toplist', 'inner']) ?>">
    <?php if ($h === 'h1'): ?><?= sx_breadcrumbs(cls('toplist', 'crumbs')) ?><?php endif; ?>
    <div class="<?= $c('bar') ?>">
      <?= $head() ?>
      <?= $tabsHtml ?>
    </div>
    <ol class="<?= $c('tiles') ?>">
      <?php foreach ($entries as [$id, $isF]): $x = $info($id); ?>
        <li class="<?= $c('ltile') ?>">
          <div class="<?= $c('face') ?>" style="--brand: <?= e($x['b']['brand_color']) ?>">
            <?php if (!$isF): ?><span class="<?= $c('tag') ?>">#<?= $rankOf[$id] + 1 ?></span><?php endif; ?>
            <?= sx_brand_mark($id, $c('mark')) ?>
            <p class="<?= $c('name') ?>"><?= e($x['b']['name']) ?></p>
          </div>
          <?php if ($isF): ?><p class="<?= $c('flabel') ?>"><?= $flabel($id) ?></p><?php endif; ?>
          <p class="<?= $c('offer') ?>"><?= e($x['value'] ?: $x['fact']) ?></p>
          <?= sx_brand_cta($id, $isF ? 'featured' : 'list', cx('btn', ['btn', null, 'primary'], ['toplist', 'cta'], ['toplist', 'play']), false, true) ?>
          <?php if ($x['review']): ?><a class="<?= $c('review') ?>" href="<?= sx_url($x['review']) ?>"><?= e(t('casinos.read')) ?></a><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
