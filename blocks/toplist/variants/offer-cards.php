<?php
/**
 * Offer cards: the first three as tall cards side by side (the middle one raised), each with its offer, promo code,
 * strengths and terms; the rest as wide rows with the same parts. Where the market allows no offers, the key fact leads.
 */
include __DIR__ . '/../_items.php';
$top = array_slice($entries, 0, 3);
$rest = array_slice($entries, 3);
$terms = function (array $x) use ($c): string {
    if (!$x['offer']) {
        return '';
    }
    $url = $x['offer']['terms_url'] ?? null;
    return '<p class="' . $c('terms') . '">' . e($x['terms']) . ($url ? ' · <a href="' . sx_url($url) . '" rel="nofollow noopener" target="_blank">' . e(t('promo.full_terms')) . '</a>' : '') . '</p>';
};
?>
<section class="<?= cx('toplist', ['toplist', null, 'offer-cards']) ?>" id="ranking">
  <div class="<?= cx('container', ['toplist', 'inner']) ?>">
    <?php if ($h === 'h1'): ?><?= sx_breadcrumbs(cls('toplist', 'crumbs')) ?><?php endif; ?>
    <?= $head() ?>
    <ol class="<?= $c('cards') ?>">
      <?php foreach ($top as $k => [$id, $isF]): $x = $info($id); ?>
        <li class="<?= $c('ocard') ?><?= $k === 1 && count($top) === 3 ? ' ' . $c('raised') : '' ?><?= $isF ? ' ' . $c('featured') : '' ?>">
          <p class="<?= $c('flabel') ?>"><?= $isF ? $flabel($id) : e(t('casinos.rank_of', ['n' => $rankOf[$id] + 1, 'total' => count($rankOf)])) ?></p>
          <span class="<?= $c('tile') ?>" style="--brand: <?= e($x['b']['brand_color']) ?>"><?= sx_brand_mark($id, $c('mark')) ?></span>
          <p class="<?= $c('name') ?>"><?= e($x['b']['name']) ?></p>
          <p class="<?= $c('rate') ?>"><?= $x['stars'] ?><strong class="<?= cls('num') ?>"><?= e($x['score']) ?></strong></p>
          <p class="<?= $c('offer') ?>"><?= e($x['value'] ?: $x['fact']) ?></p>
          <?php if ($x['offer']): ?><?= sx_code_box($x['offer'], 'list', $c('code')) ?><?php endif; ?>
          <?php if ($x['pros']): ?><ul class="<?= $c('pros') ?>"><?php foreach ($x['pros'] as $pr): ?><li><?= sx_icon('check') ?><?= e(tm($pr)) ?></li><?php endforeach; ?></ul><?php endif; ?>
          <?= $cta($id, $isF) ?>
          <?php if ($x['review']): ?><a class="<?= $c('review') ?>" href="<?= sx_url($x['review']) ?>"><?= e(t('casinos.read')) ?></a><?php endif; ?>
          <?= $terms($x) ?>
        </li>
      <?php endforeach; ?>
    </ol>
    <?php if ($rest): ?>
      <ol class="<?= $c('orows') ?>" start="4">
        <?php foreach ($rest as [$id, $isF]): $x = $info($id); ?>
          <li>
            <div class="<?= $c('orow') ?>">
              <div class="<?= $c('who') ?>">
                <span class="<?= $c('tile') ?>" style="--brand: <?= e($x['b']['brand_color']) ?>"><?= sx_brand_mark($id, $c('mark')) ?></span>
                <div><p class="<?= $c('name') ?>"><?= e($x['b']['name']) ?></p><p class="<?= $c('rate') ?>"><?= $x['stars'] ?><strong class="<?= cls('num') ?>"><?= e($x['score']) ?></strong></p></div>
              </div>
              <div class="<?= $c('what') ?>">
                <p class="<?= $c('offer') ?>"><?= e($x['value'] ?: $x['fact']) ?></p>
                <?php if ($x['offer']): ?><?= sx_code_box($x['offer'], 'list', $c('code')) ?><?php endif; ?>
              </div>
              <?php if ($x['pros']): ?><ul class="<?= $c('pros') ?>"><?php foreach (array_slice($x['pros'], 0, 3) as $pr): ?><li><?= sx_icon('check') ?><?= e(tm($pr)) ?></li><?php endforeach; ?></ul><?php endif; ?>
              <div class="<?= $c('actions') ?>"><?= $cta($id, $isF) ?><?php if ($x['review']): ?><a class="<?= $c('review') ?>" href="<?= sx_url($x['review']) ?>"><?= e(t('casinos.read')) ?></a><?php endif; ?></div>
            </div>
            <?= $terms($x) ?>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </div>
</section>
