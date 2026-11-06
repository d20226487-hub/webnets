<?php
include __DIR__ . '/_prep.php';
$entry = function (string $id, bool $isFeatured) use ($c, $rankOf, $kf, $variant) {
    $b = sx_casino($id);
    $review = sx_review_url($id);
    $pos = $isFeatured ? 'featured' : 'list';
    ob_start(); ?>
    <li class="<?= $isFeatured ? $c('featured') : '' ?>">
      <?php if ($isFeatured): ?><p class="<?= $c('flabel') ?>"><?= e(t('casinos.featured')) ?> · <?= e(t('casinos.featured_note', ['n' => $rankOf[$id] + 1])) ?></p><?php endif; ?>
      <div class="<?= $c('item') ?>">
        <span class="<?= $c('rank') ?>" aria-label="<?= e(t('casinos.rank_aria', ['n' => $rankOf[$id] + 1])) ?>"><?= $rankOf[$id] + 1 ?></span>
        <?= sx_brand_mark($id, $c('mark')) ?>
        <div class="<?= $c('main') ?>">
          <p class="<?= $c('name') ?>"><?= e($b['name']) ?></p>
          <?php if ($variant !== 'list'): ?><p class="<?= $c('summary') ?>"><?= e($b['r']['summary']) ?></p><?php endif; ?>
          <p class="<?= $c('fact') ?>"><?= sx_icon('bolt') ?><?= e(sx_key_fact($id, $kf)) ?></p>
        </div>
        <p class="<?= $c('score') ?>"><strong class="<?= cls('num') ?>"><?= e(sx_score10(sx_casino_score($id))) ?></strong><small>/10</small></p>
        <div class="<?= $c('actions') ?>">
          <?= sx_brand_cta($id, $pos, cx('btn', ['btn', null, 'primary'], ['toplist', 'cta'])) ?>
          <?php if ($review): ?><a class="<?= $c('review') ?>" href="<?= sx_url($review) ?>"><?= e(t('casinos.read')) ?></a><?php endif; ?>
        </div>
      </div>
    </li>
    <?php return ob_get_clean();
};
?>
<section class="<?= cx('toplist', ['toplist', null, $variant]) ?>" id="ranking">
  <div class="<?= cx('container', ['toplist', 'inner']) ?>">
    <?php if ($h === 'h1'): ?><?= sx_breadcrumbs(cls('toplist', 'crumbs')) ?><?php endif; ?>
    <header class="<?= $c('head') ?>">
      <<?= $h ?> class="<?= $c('title') ?>"><?= e($props['title']) ?></<?= $h ?>>
      <?php if ($props['intro']): ?><p class="<?= $c('intro') ?>"><?= e($props['intro']) ?></p><?php endif; ?>
      <?php if ($method && $variant !== 'list'): ?><a class="<?= $c('method') ?>" href="<?= sx_url($method) ?>"><?= sx_icon('info') ?><?= e(t('casinos.ranked_by', ['c' => $crit ? t("casinos.crit.$crit") : t('casinos.overall')])) ?> · <?= e(t('casinos.method_link')) ?></a><?php endif; ?>
    </header>
    <?php if ($variant === 'table'): /* a real comparison table; the ranked criterion's column is marked */ ?>
      <table class="<?= $c('table') ?>">
        <thead><tr>
          <th scope="col">#</th><th scope="col"><?= e(t('casinos.col_casino')) ?></th><th scope="col"><?= e($crit ? t("casinos.crit.$crit") : t('casinos.col_score')) ?></th>
          <?php foreach ($cols as $k => $label): ?><th scope="col"<?= $k === $kf ? ' data-ranked' : '' ?>><?= e($label) ?></th><?php endforeach; ?>
          <th scope="col"><span class="<?= cls('visually-hidden') ?>"><?= e(t('casinos.visit_short')) ?></span></th>
        </tr></thead>
        <tbody>
          <?php foreach (array_merge($featured ? [[$featured, true]] : [], array_map(fn($id) => [$id, false], $ids)) as [$id, $isF]): $b = sx_casino($id); $review = sx_review_url($id); ?>
            <?php if ($isF): ?><tr class="<?= $c('flabelrow') ?>"><td colspan="<?= 4 + count($cols) ?>"><?= e(t('casinos.featured')) ?> · <?= e(t('casinos.featured_note', ['n' => $rankOf[$id] + 1])) ?></td></tr><?php endif; ?>
            <tr class="<?= $isF ? $c('frow') : '' ?>">
              <td class="<?= $c('rank') ?>"><?= $rankOf[$id] + 1 ?></td>
              <th scope="row" class="<?= $c('brand') ?>"><?= sx_brand_mark($id, $c('mark')) ?><span><?= e($b['name']) ?><?php if ($review): ?><a class="<?= $c('review') ?>" href="<?= sx_url($review) ?>"><?= e(t('casinos.read')) ?></a><?php endif; ?></span></th>
              <td class="<?= $c('score') ?>"><strong class="<?= cls('num') ?>"><?= e(sx_score10(sx_casino_score($id, $crit))) ?></strong></td>
              <?php foreach ($cols as $k => $label): ?><td data-label="<?= e($label) ?>"<?= $k === $kf ? ' data-ranked' : '' ?>><?= e($cell($id, $k)) ?></td><?php endforeach; ?>
              <td class="<?= $c('go') ?>"><?= sx_brand_cta($id, $isF ? 'featured' : 'list', cx('btn', ['btn', null, 'primary'], ['btn', null, 'small'], ['toplist', 'cta']), false, true) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php elseif ($variant === 'podium' && $ids): /* the winner as a spotlight with its criterion bars, runners-up beside it */
        $top = array_shift($ids); $tb = sx_casino($top); $treview = sx_review_url($top); ?>
      <div class="<?= $c('podium') ?>">
        <article class="<?= $c('winner') ?>">
          <p class="<?= $c('badge') ?>"><span>#<?= $rankOf[$top] + 1 ?></span> <?= e(t('casinos.ranked_by', ['c' => $crit ? t("casinos.crit.$crit") : t('casinos.overall')])) ?></p>
          <div class="<?= $c('whead') ?>">
            <?= sx_brand_mark($top, $c('wmark')) ?>
            <h<?= $h === 'h1' ? '2' : '3' ?> class="<?= $c('wname') ?>"><?= e($tb['name']) ?></h<?= $h === 'h1' ? '2' : '3' ?>>
            <p class="<?= $c('wscore') ?>"><strong class="<?= cls('num') ?>"><?= e(sx_score10(sx_casino_score($top))) ?></strong><small>/10</small></p>
          </div>
          <p class="<?= $c('wsum') ?>"><?= e($tb['r']['summary']) ?></p>
          <ul class="<?= $c('bars') ?>">
            <?php foreach ($barKeys($top) as $k): $v = sx_casino_score($top, $k); ?>
              <li><span><?= e(t("casinos.crit.$k")) ?></span><i style="--v: <?= round($v * 10) ?>%"></i><strong class="<?= cls('num') ?>"><?= e(sx_score10($v)) ?></strong></li>
            <?php endforeach; ?>
          </ul>
          <div class="<?= $c('wactions') ?>">
            <?= sx_brand_cta($top, 'list', cx('btn', ['btn', null, 'primary'], ['toplist', 'cta']), true) ?>
            <?php if ($treview): ?><a class="<?= $c('review') ?>" href="<?= sx_url($treview) ?>"><?= e(t('casinos.read')) ?></a><?php endif; ?>
          </div>
        </article>
        <ol class="<?= $c('list') ?>" start="2">
          <?php if ($featured): ?><?= $entry($featured, true) ?><?php endif; ?>
          <?php foreach ($ids as $id): ?><?= $entry($id, false) ?><?php endforeach; ?>
        </ol>
      </div>
    <?php else: ?>
    <ol class="<?= $c('list') ?>">
      <?php if ($featured): ?><?= $entry($featured, true) ?><?php endif; ?>
      <?php foreach ($ids as $id): ?><?= $entry($id, false) ?><?php endforeach; ?>
    </ol>
    <?php endif; ?>
  </div>
</section>
