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
$tocBox = false;
if ($variant === 'toc-side' && sx_ctx('nav_style') === 'sidebar') { // the site already has a sidebar on every page: the standard
    $variant = 'standard';                                           // layout with the contents as a box above the text
    $tocBox = true;
}
// Layouts change the look and the placement, never the reading order (title, lead, picture, text, author, related):
// standard; toc-side (contents column beside the text); banner (title band, picture beside the lead, contents box);
// magazine (large centred title, author chip, wide picture, narrow text, related as a list).
$tocNav = count($toc) >= 3 && ($tocBox || ($variant !== 'standard' && $variant !== 'magazine'))
    ? '<nav class="' . $c('toc') . '" aria-label="' . e(t('toc')) . '"><p class="' . cls('eyebrow') . '">' . e(t('toc')) . '</p><ol>'
      . implode('', array_map(fn($id, $text) => '<li><a href="#' . e($id) . '">' . e($text) . '</a></li>', array_keys($toc), $toc)) . '</ol></nav>'
    : '';
$hero = sx_images_on() ? '<figure class="' . $c('hero') . '">' . sx_picture($a, $variant === 'banner' ? '4/3' : '16/8') . '</figure>' : '';
$initials = mb_strtoupper(implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($author['name'])), 0, 2))));
?>
<article class="<?= cx('article', ['article', null, $variant], ['article', null, $a['kind']]) ?>">
  <div class="<?= cx('container', ['article', 'inner']) ?>">
    <header class="<?= $c('head') ?>">
      <?= sx_breadcrumbs(cls('article', 'crumbs')) ?>
      <p class="<?= $c('kicker') ?>"><?php if ($isPr): ?><?= sx_sponsored_badge() ?> <span><?= e(t('news.paid_by', ['sponsor' => $a['sponsor']])) ?></span><?php else: ?><span class="<?= cls('acard', 'kind') ?>"><?= e(sx_article_kind_label($a['kind'])) ?></span><?php endif; ?></p>
      <h1 class="<?= $c('title') ?>"><?= e($a['title']) ?></h1>
      <?php if ($variant === 'banner' && $hero): ?><div class="<?= $c('lead') ?>"><p class="<?= $c('dek') ?>"><?= e($a['dek']) ?></p><?= $hero ?></div><?php else: ?>
      <p class="<?= $c('dek') ?>"><?= e($a['dek']) ?></p>
      <?php endif; ?>
      <p class="<?= $c('byline') ?>"><?php if ($variant === 'magazine'): ?><span class="<?= $c('avatar') ?>" aria-hidden="true"><?= e($initials) ?></span><?php endif; ?><?= e(t('news.by', ['author' => $author['name']])) ?><?php if (!empty($author['role'])): ?>, <?= e($author['role']) ?><?php endif; ?> · <time datetime="<?= e($a['date']) ?>"><?= e(sx_date($a['date'])) ?></time><?php if (!empty($a['updated'])): ?> · <?= e(t('news.updated', ['date' => sx_date($a['updated'])])) ?><?php endif; ?> · <?= e(t('news.read_min', ['n' => sx_reading_minutes($a['body'])])) ?></p>
      <?= sx_share_bar($shareUrl, $a['title'], $c('share')) ?>
    </header>
    <?= $tocNav ?>
    <?= $variant === 'banner' ? '' : $hero ?>
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
