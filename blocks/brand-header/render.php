<?php
$id = sx_page_item() ?? sx_casino_ids()[0];
$b = sx_casino($id);
$c = fn($el) => cls('bhead', $el);
$method = isset(sx_pages()['method']) ? sx_page_url('method') : null;
$rank = array_search($id, sx_casino_rank(), true) + 1;
?>
<section class="<?= cx('bhead', ['bhead', null, $variant]) ?>">
  <div class="<?= cx('container', ['bhead', 'inner']) ?>">
    <?= sx_breadcrumbs(cls('bhead', 'crumbs')) ?>
    <div class="<?= $c('top') ?>">
      <?= sx_brand_mark($id, $c('mark')) ?>
      <h1 class="<?= $c('title') ?>"><?= e($props['title']) ?></h1>
    </div>
    <div class="<?= cx('card', ['bhead', 'card']) ?>">
      <p class="<?= $c('score') ?>"><strong class="<?= cls('num') ?>"><?= e(sx_score10(sx_casino_score($id))) ?></strong><span>/10</span><small><?= e(t('casinos.rank_of', ['n' => $rank, 'total' => count(sx_casino_ids())])) ?></small></p>
      <p class="<?= $c('verdict') ?>"><?= e($b['r']['verdict']) ?></p>
      <ul class="<?= $c('chips') ?>">
        <li><?= sx_icon('bolt') ?><?= e(sx_key_fact($id, 'payouts')) ?></li>
        <li><?= sx_icon('wallet') ?><?= e(t('casinos.kf.min', ['v' => money($b['f']['min_deposit'])])) ?></li>
        <li><?= sx_icon('shield') ?><?= e(t('casinos.kf.licence', ['no' => $b['license_no']])) ?></li>
      </ul>
      <?= sx_brand_cta($id, 'hero', cx('btn', ['btn', null, 'primary'], ['bhead', 'cta']), true) ?>
      <p class="<?= $c('tested') ?>"><?= e(t('casinos.tested', ['date' => sx_date($b['r']['tested_at'])])) ?><?php if ($method): ?> · <a href="<?= sx_url($method) ?>"><?= e(t('casinos.method_link')) ?></a><?php endif; ?></p>
    </div>
  </div>
</section>
