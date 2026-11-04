<?php
/**
 * split: big lead story with two beside it. stack: headline set on a full-width colour panel, three stories in a row under it.
 * list: text-first wire, numbered stories with no cover (fast to scan, light on phones).
 */
$lead = array_slice(array_filter(sx_articles(), fn($a) => $a['kind'] !== 'pr'), 0, sx_lead_count($variant));
SX::$once['lead_slugs'] = array_column($lead, 'slug');
if (!$lead) {
    return;
}
$main = array_shift($lead);
$c = fn($el) => cls('alead', $el);
SX::$used['_news'] = 1;
$author = sx_authors()[$main['author']]['name'] ?? $main['author'];
$readMeta = fn($a) => '<time datetime="' . e($a['date']) . '">' . e(sx_date($a['date'])) . '</time> · ' . e(sx_authors()[$a['author']]['name'] ?? $a['author']) . ' · ' . e(t('news.read_min', ['n' => sx_reading_minutes($a['body'])]));
if ($variant === 'stack' || $variant === 'list'):
?>
<section class="<?= cx('alead', ['alead', null, $variant]) ?>">
  <div class="<?= cx('container', ['alead', 'inner']) ?>">
    <h1 class="<?= $c('masthead') ?>"><?= e($props['title']) ?></h1>
    <?php if ($variant === 'stack'): ?>
      <article class="<?= $c('feature') ?>" data-kind="<?= e($main['kind']) ?>"><?php if (sx_images_on()): ?><?= sx_picture($main, 'auto', cls('alead', 'fbg'), '', true) ?><?php endif; ?>
        <div class="<?= $c('body') ?>">
          <p class="<?= $c('flag') ?>"><?= e(sx_article_kind_label($main['kind'])) ?></p>
          <h2 class="<?= $c('title') ?>"><a href="<?= sx_url(sx_article_url($main['slug'])) ?>"><?= e($main['title']) ?></a></h2>
          <p class="<?= $c('dek') ?>"><?= e($main['dek']) ?></p>
          <p class="<?= $c('byline') ?>"><?= $readMeta($main) ?></p>
        </div>
      </article>
      <?php if ($lead): ?><div class="<?= $c('row') ?>"><?php foreach ($lead as $a): ?><?= sx_article_card($a, false, 'h2') ?><?php endforeach; ?></div><?php endif; ?>
    <?php else: ?>
      <ol class="<?= $c('wire') ?>">
        <?php foreach (array_values(array_merge([$main], $lead)) as $i => $a): ?>
          <li class="<?= $c('item') ?><?= sx_images_on() ? ' ' . cls('alead', 'item', 'img') : '' ?>"<?= $i === 0 ? ' data-top' : '' ?>>
            <?php if (sx_images_on()): ?><a class="<?= $c('ithumb') ?>" href="<?= sx_url(sx_article_url($a['slug'])) ?>" tabindex="-1" aria-hidden="true"><?= sx_picture($a, $i === 0 ? '16/9' : '4/3') ?></a><?php endif; ?>
            <span class="<?= cx('num', ['alead', 'n']) ?>" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <div>
              <p class="<?= cx(['acard', 'kicker']) ?>"><span class="<?= cls('acard', 'kind') ?>"><?= e(sx_article_kind_label($a['kind'])) ?></span></p>
              <h2 class="<?= $c('title') ?>"><a href="<?= sx_url(sx_article_url($a['slug'])) ?>"><?= e($a['title']) ?></a></h2>
              <?php if ($i === 0): ?><p class="<?= $c('dek') ?>"><?= e($a['dek']) ?></p><?php endif; ?>
              <p class="<?= cls('acard', 'meta') ?>"><?= $readMeta($a) ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </div>
</section>
<?php return; endif; ?>
<section class="<?= cx('alead', ['alead', null, $variant]) ?>">
  <div class="<?= cx('container', ['alead', 'inner']) ?>">
    <h1 class="<?= $c('masthead') ?>"><?= e($props['title']) ?></h1>
    <div class="<?= $c('grid') ?>">
      <article class="<?= $c('main') ?>">
        <a class="<?= $c('cover') ?><?= sx_images_on() ? ' ' . cls('alead', 'cover', 'art') : '' ?>" href="<?= sx_url(sx_article_url($main['slug'])) ?>" tabindex="-1" aria-hidden="true" data-kind="<?= e($main['kind']) ?>"><?= sx_images_on() ? sx_picture($main, '16/7') : '' ?></a>
        <p class="<?= cx(['acard', 'kicker']) ?>"><span class="<?= cls('acard', 'kind') ?>"><?= e(sx_article_kind_label($main['kind'])) ?></span></p>
        <h2 class="<?= $c('title') ?>"><a href="<?= sx_url(sx_article_url($main['slug'])) ?>"><?= e($main['title']) ?></a></h2>
        <p class="<?= $c('dek') ?>"><?= e($main['dek']) ?></p>
        <p class="<?= cls('acard', 'meta') ?>"><time datetime="<?= e($main['date']) ?>"><?= e(sx_date($main['date'])) ?></time> · <?= e($author) ?> · <?= e(t('news.read_min', ['n' => sx_reading_minutes($main['body'])])) ?></p>
      </article>
      <div class="<?= $c('side') ?>">
        <?php foreach ($lead as $a): ?><?= sx_article_card($a, true, 'h2') ?><?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
