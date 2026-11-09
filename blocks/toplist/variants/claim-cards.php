<?php
/**
 * Claim cards: a centred heading, then a three-column grid of cards. Each card has a dark band with the brand's logo and
 * rank, then the offer (or key fact) in large type, a line naming the brand, a large pill button, a dark review pill and
 * the market's age and responsible-gambling line.
 */
include __DIR__ . '/../_items.php';
$age = (sx_ctx('market')['min_age'] ?? 18) . '+';
?>
<section class="<?= cx('toplist', ['toplist', null, 'claim-cards']) ?>" id="ranking">
  <div class="<?= cx('container', ['toplist', 'inner']) ?>">
    <?php if ($h === 'h1'): ?><?= sx_breadcrumbs(cls('toplist', 'crumbs')) ?><?php endif; ?>
    <?= $head() ?>
    <ol class="<?= $c('ccards') ?>">
      <?php foreach ($entries as [$id, $isF]): $x = $info($id); ?>
        <li class="<?= $c('ccard') ?>">
          <div class="<?= $c('band') ?>" style="--brand: <?= e($x['b']['brand_color']) ?>">
            <?php if (!$isF): ?><span class="<?= $c('tag') ?>">#<?= $rankOf[$id] + 1 ?></span><?php endif; ?>
            <?= sx_brand_mark($id, $c('mark')) ?>
            <p class="<?= $c('name') ?>"><?= e($x['b']['name']) ?></p>
            <span class="<?= $c('bscore') ?>"><?= $x['stars'] ?><strong class="<?= cls('num') ?>"><?= e($x['score']) ?></strong></span>
          </div>
          <div class="<?= $c('cbody') ?>">
            <?php if ($isF): ?><p class="<?= $c('flabel') ?>"><?= $flabel($id) ?></p><?php endif; ?>
            <p class="<?= $c('offer') ?>"><?= e($x['value'] ?: $x['fact']) ?></p>
            <p class="<?= $c('fact') ?>"><?= e($x['value'] ? $x['fact'] : ($x['b']['r']['summary'] ?? '')) ?></p>
            <?= $cta($id, $isF, cls('toplist', 'claim')) ?>
            <?php if ($x['review']): ?><a class="<?= $c('rpill') ?>" href="<?= sx_url($x['review']) ?>"><?= e(t('casinos.read')) ?></a><?php endif; ?>
            <p class="<?= $c('age') ?>"><?= e($age) ?> | <?= e(t('footer.rg')) ?></p>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
