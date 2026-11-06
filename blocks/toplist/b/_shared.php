<?php
/**
 * Toplist, implementation B (cards, list): each casino is an <article> with its name as a heading, placed with CSS grid
 * areas; the score is a <data> value. Ranking, featured slot and facts come from the same prep as A.
 */
include dirname(__DIR__) . '/_prep.php';
$hn = $h === 'h1' ? 'h2' : 'h3';
$row = function (string $id, bool $isFeatured) use ($rankOf, $kf, $variant, $hn) {
    $b = sx_casino($id);
    $review = sx_review_url($id);
    $n = $rankOf[$id] + 1;
    $score = sx_score10(sx_casino_score($id));
    return '<li class="' . cls('rank', 'slot') . ($isFeatured ? ' ' . cls('rank', 'slot', 'paid') : '') . '">'
        . ($isFeatured ? '<p class="' . cls('rank', 'paidnote') . '">' . e(t('casinos.featured')) . ' · ' . e(t('casinos.featured_note', ['n' => $n])) . '</p>' : '')
        . '<article class="' . cls('rank', 'entry') . '">'
        . '<b class="' . cls('rank', 'pos') . '" aria-label="' . e(t('casinos.rank_aria', ['n' => $n])) . '">' . $n . '</b>'
        . sx_brand_mark($id, cls('rank', 'logo'))
        . '<div class="' . cls('rank', 'who') . '"><' . $hn . ' class="' . cls('rank', 'name') . '">' . e($b['name']) . '</' . $hn . '>'
        . ($variant !== 'list' ? '<p class="' . cls('rank', 'sum') . '">' . e($b['r']['summary']) . '</p>' : '')
        . '<p class="' . cls('rank', 'key') . '">' . sx_icon('bolt') . e(sx_key_fact($id, $kf)) . '</p></div>'
        . '<p class="' . cls('rank', 'score') . '"><data class="' . cls('num') . '" value="' . e(str_replace(',', '.', $score)) . '">' . e($score) . '</data><small>/10</small></p>'
        . '<div class="' . cls('rank', 'go') . '">' . sx_brand_cta($id, $isFeatured ? 'featured' : 'list', cx('btn', ['btn', null, 'primary'], ['rank', 'cta']))
        . ($review ? '<a class="' . cls('rank', 'more') . '" href="' . sx_url($review) . '">' . e(t('casinos.read')) . '</a>' : '') . '</div>'
        . '</article></li>';
};
$headHtml = ($h === 'h1' ? sx_breadcrumbs(cls('rank', 'crumbs')) : '')
    . '<header class="' . cls('rank', 'head') . '"><' . $h . ' class="' . cls('rank', 'title') . '">' . e($props['title']) . '</' . $h . '>'
    . ($props['intro'] ? '<p class="' . cls('rank', 'intro') . '">' . e($props['intro']) . '</p>' : '')
    . ($method && $variant !== 'list' ? '<a class="' . cls('rank', 'how') . '" href="' . sx_url($method) . '">' . sx_icon('info')
        . e(t('casinos.ranked_by', ['c' => $crit ? t("casinos.crit.$crit") : t('casinos.overall')])) . ' · ' . e(t('casinos.method_link')) . '</a>' : '')
    . '</header>';
$rows = ($featured ? $row($featured, true) : '') . implode('', array_map(fn($id) => $row($id, false), $ids));
