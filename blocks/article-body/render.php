<?php
$a = sx_article(sx_page_item() ?? '') ?? array_values(sx_articles())[0];
$isPr = $a['kind'] === 'pr';
$author = sx_authors()[$a['author']] ?? ['name' => $a['author']];
$c = fn($el) => cls('article', $el);
SX::$used['_news'] = 1;
SX::$used['prose'] ??= 1; // body typography comes from the prose block's styles
$toc = [];
$body = sx_safe_html($a['body'], $toc);
if (!$isPr) {
    // Editorial articles only: paid content must not be marked up as news.
    SX::$jsonld[] = ['@context' => 'https://schema.org', '@type' => $a['kind'] === 'news' ? 'NewsArticle' : 'Article', 'headline' => $a['title'],
        'description' => $a['dek'], 'datePublished' => $a['date'], 'dateModified' => $a['updated'] ?? $a['date'],
        'author' => array_filter(['@type' => 'Person', 'name' => $author['name'], 'jobTitle' => $author['role'] ?? null, 'description' => $author['bio'] ?? null]), 'publisher' => ['@type' => 'Organization', 'name' => sx_ctx('site')['name']]];
}
$shareUrl = sx_abs_url(SX::$ctx['page_id'] ?? '');
$authorLines = [];
$n = count(array_filter(sx_articles(), fn($x) => $x['author'] === $a['author']));
if ($n > 1) {
    $authorLines[] = e(sx_plural('news.articles_by', $n));
}
$related = array_slice(array_filter(sx_articles($a['kind']), fn($r) => $r['slug'] !== $a['slug']), 0, 3);
$brand = $isPr && !empty($a['brand']) ? sx_casino($a['brand']) : null;
?>
<article class="<?= cx('article', ['article', null, $variant], ['article', null, $a['kind']]) ?>">
  <div class="<?= cx('container', ['article', 'inner']) ?>">
    <header class="<?= $c('head') ?>">
      <?= sx_breadcrumbs(cls('article', 'crumbs')) ?>
      <p class="<?= $c('kicker') ?>"><?php if ($isPr): ?><?= sx_sponsored_badge() ?> <span><?= e(t('news.paid_by', ['sponsor' => $a['sponsor']])) ?></span><?php else: ?><span class="<?= cls('acard', 'kind') ?>"><?= e(sx_article_kind_label($a['kind'])) ?></span><?php endif; ?></p>
      <h1 class="<?= $c('title') ?>"><?= e($a['title']) ?></h1>
      <p class="<?= $c('dek') ?>"><?= e($a['dek']) ?></p>
      <p class="<?= $c('byline') ?>"><?= e(t('news.by', ['author' => $author['name']])) ?><?php if (!empty($author['role'])): ?>, <?= e($author['role']) ?><?php endif; ?> · <time datetime="<?= e($a['date']) ?>"><?= e(sx_date($a['date'])) ?></time><?php if (!empty($a['updated'])): ?> · <?= e(t('news.updated', ['date' => sx_date($a['updated'])])) ?><?php endif; ?> · <?= e(t('news.read_min', ['n' => sx_reading_minutes($a['body'])])) ?></p>
      <?= sx_share_bar($shareUrl, $a['title'], $c('share')) ?>
    </header>
    <?php if (sx_images_on()): ?><figure class="<?= $c('hero') ?>"><?= sx_picture($a, '16/8') ?></figure><?php endif; ?>
    <?php if ($isPr): ?>
      <aside class="<?= $c('disclosure') ?>" role="note"><?= sx_icon('info') ?><p><?= e(t('news.disclosure_box', ['sponsor' => $a['sponsor']])) ?></p></aside>
    <?php endif; ?>
    <div class="<?= cx(['prose', 'body'], ['article', 'body']) ?>"><?= $body ?></div>
    <?= sx_share_bar($shareUrl, $a['title'], $c('share-end')) ?>
    <?= sx_person_box($author, t($isPr ? 'news.prepared_by' : 'news.about_author'), $authorLines, $c('author')) ?>
    <?php if ($brand): ?>
      <aside class="<?= cx('card', ['article', 'brand']) ?>">
        <?= sx_brand_mark($brand['id'], $c('mark')) ?>
        <div><p class="<?= $c('bname') ?>"><?= e($brand['name']) ?></p><p class="<?= $c('btag') ?>"><?= e(tm($brand['tagline'])) ?></p></div>
        <?= sx_brand_cta($brand['id'], 'article', cx('btn', ['btn', null, 'primary'], ['article', 'cta']), true) ?>
        <p class="<?= $c('age') ?>"><?= e(tm(sx_ctx('market')['compliance']['age_notice'])) ?></p>
      </aside>
    <?php endif; ?>
    <?php if ($related): ?>
      <section class="<?= $c('related') ?>">
        <h2><?= e($props['related_title'] ?: t('news.related')) ?></h2>
        <ul><?php foreach ($related as $r): ?><li><?= sx_article_card($r, false) ?></li><?php endforeach; ?></ul>
      </section>
    <?php endif; ?>
  </div>
</article>
