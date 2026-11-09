<?php
/**
 * Shared by the toplist's own-file layouts (variants/<v>.php): the entries in order (the labelled paid slot first,
 * then the ranking), each brand's figures, and the ranking tabs (multi-page sites: the pages ranked by another criterion).
 */
include __DIR__ . '/_prep.php';
$variant = $props['variant'];
$entries = array_merge($featured ? [[$featured, true]] : [], array_map(fn($id) => [$id, false], $ids));
$info = function (string $id) use ($crit, $kf): array {
    $b = sx_casino($id);
    $score = sx_casino_score($id, $crit);
    $offer = sx_brand_lead_offer($id);
    return [
        'b' => $b,
        'score' => sx_score10($score),
        'stars' => sx_stars($score / 2),
        'fact' => sx_key_fact($id, $kf),
        'offer' => $offer,
        'value' => $offer ? sx_promo_value($offer) : '',
        'title' => $offer ? tm($offer['title']) : '',
        'terms' => $offer ? implode(' · ', array_map(fn($k, $v) => "$k: $v", array_keys(sx_promo_key_terms($offer)), sx_promo_key_terms($offer))) : '',
        'pros' => array_slice((array)($b['r']['pros'] ?? []), 0, 4),
        'review' => sx_review_url($id),
    ];
};
$flabel = fn(string $id) => e(t('casinos.featured')) . ' · ' . e(t('casinos.featured_note', ['n' => $rankOf[$id] + 1]));
$tabs = [];
foreach (['home' => null, 'fast' => 'payouts', 'live' => 'live', 'app' => 'app'] as $pid => $tc) {
    if (isset(sx_pages()[$pid])) {
        $tabs[] = ['label' => $tc ? t("casinos.crit.$tc") : t('casinos.overall'), 'url' => sx_page_url($pid), 'current' => sx_page_id() === $pid];
    }
}
$tabsHtml = count($tabs) > 1 ? '<nav class="' . $c('tabs') . '" aria-label="' . e(t('casinos.ranked_by', ['c' => ''])) . '"><ul>' . implode('', array_map(fn($tb) => '<li><a href="' . sx_url($tb['url']) . '"'
    . ($tb['current'] ? ' aria-current="page"' : '') . '>' . e($tb['label']) . '</a></li>', $tabs)) . '</ul></nav>' : '';
$head = function () use ($h, $props, $c, $method, $crit) {
    ob_start(); ?>
    <header class="<?= $c('head') ?>">
      <<?= $h ?> class="<?= $c('title') ?>"><?= e($props['title']) ?></<?= $h ?>>
      <?php if ($props['intro']): ?><p class="<?= $c('intro') ?>"><?= e($props['intro']) ?></p><?php endif; ?>
      <?php if ($method): ?><a class="<?= $c('method') ?>" href="<?= sx_url($method) ?>"><?= sx_icon('info') ?><?= e(t('casinos.ranked_by', ['c' => $crit ? t("casinos.crit.$crit") : t('casinos.overall')])) ?> · <?= e(t('casinos.method_link')) ?></a><?php endif; ?>
    </header>
    <?php return ob_get_clean();
};
$cta = fn(string $id, bool $isF, string $extra = '') => sx_brand_cta($id, $isF ? 'featured' : 'list', cx('btn', ['btn', null, 'primary'], ['toplist', 'cta']) . ($extra ? " $extra" : ''));
