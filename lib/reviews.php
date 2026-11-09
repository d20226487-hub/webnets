<?php
/**
 * Review sites (brief 07): the brand's review feed (brands/reviews/<brand>.json), summary statistics computed
 * from it, and the demo-only guard for seeded reviews.
 *
 * Review: id, source (seed | site-form | import), status (pending | published | rejected), market, locale,
 * name, city, date, rating 1-5, topics[], title, text, optional reply {date, text} supplied by the operator.
 */

const SX_REVIEW_TOPICS = ['payouts', 'verification', 'support', 'app', 'odds', 'deposits'];
const SX_REVIEW_MIN_LOW_SHARE = 0.2; // demo seeds: at least 20% of reviews at 1-3 stars

/** Published reviews for this site's brand and market, newest first. */
function sx_reviews(): array
{
    if (isset(SX::$once['reviews'])) {
        return SX::$once['reviews'];
    }
    $file = sx_brand_file(SX::$ctx['root'], 'reviews/' . (SX::$ctx['brand']['id'] ?? '') . '.json');
    $all = is_file($file) ? json_decode(file_get_contents($file), true)['reviews'] ?? [] : [];
    $out = array_values(array_filter($all, fn($r) => ($r['status'] ?? '') === 'published' && ($r['market'] ?? '') === SX::$ctx['market']['id']));
    usort($out, fn($a, $b) => strcmp($b['date'], $a['date']));
    return SX::$once['reviews'] = $out;
}

/**
 * Called by sx_build_site() for types with "requires_reviews". Returns true when the site shows seeded reviews
 * (the builder then forces the sample banner on every page). Throws when seeds would ship outside a demo.
 */
function sx_reviews_gate(array $cfg): bool
{
    $reviews = sx_reviews();
    $brandId = SX::$ctx['brand']['id'] ?? '';
    if (!$reviews) {
        throw new RuntimeException("No published reviews for brand '$brandId' in market '" . SX::$ctx['market']['id'] . "' (brands/reviews/$brandId.json).");
    }
    $seeded = array_filter($reviews, fn($r) => ($r['source'] ?? '') === 'seed');
    if ($seeded && empty($cfg['demo'])) {
        throw new RuntimeException(count($seeded) . " seeded (made-up) reviews in brands/reviews/$brandId.json. Seeded reviews may only be used in demos "
            . '(set "demo": true, which adds a sample-content banner). Real sites need real reviews.');
    }
    $low = count(array_filter($reviews, fn($r) => $r['rating'] <= 3));
    if ($seeded && $low / count($reviews) < SX_REVIEW_MIN_LOW_SHARE) {
        SX::$warnings[] = sprintf('Only %d%% of seeded reviews are 1-3 stars (minimum %d%%): demos must show a realistic mix.', round(100 * $low / count($reviews)), 100 * SX_REVIEW_MIN_LOW_SHARE);
    }
    foreach ($reviews as $r) {
        if (array_diff($r['topics'] ?? [], SX_REVIEW_TOPICS)) {
            SX::$warnings[] = "Review {$r['id']} has an unknown topic (allowed: " . implode(', ', SX_REVIEW_TOPICS) . ').';
        }
    }
    return (bool)$seeded;
}

/** Average, count and 5..1 distribution, for all reviews or one topic. */
function sx_review_stats(?string $topic = null): array
{
    $rs = $topic ? array_filter(sx_reviews(), fn($r) => in_array($topic, $r['topics'], true)) : sx_reviews();
    $dist = array_fill_keys([5, 4, 3, 2, 1], 0);
    foreach ($rs as $r) {
        $dist[(int)$r['rating']]++;
    }
    $n = count($rs);
    return ['avg' => $n ? array_sum(array_column($rs, 'rating')) / $n : 0, 'count' => $n, 'dist' => $dist];
}

/** Per-topic stats in the fixed topic order; topics without reviews are left out. */
function sx_topic_stats(): array
{
    $out = [];
    foreach (SX_REVIEW_TOPICS as $t) {
        $s = sx_review_stats($t);
        if ($s['count']) {
            $out[$t] = $s;
        }
    }
    return $out;
}

/** Share of reviews with a brand reply. */
function sx_reply_rate(): float
{
    $rs = sx_reviews();
    return $rs ? count(array_filter($rs, fn($r) => !empty($r['reply']))) / count($rs) : 0;
}

function sx_score(float $v): string
{
    return sx_number(round($v, 1), 1);
}

/** Five stars filled to $v (CSS gradient on text), with a spoken label. */
function sx_stars(float $v, string $extra = ''): string
{
    SX::$used['_reviews'] = 1;
    return '<span class="' . e(trim(cls('stars') . ' ' . $extra)) . '" style="--v:' . round($v, 2) . '" role="img" aria-label="'
        . e(t('reviews.stars_aria', ['v' => sx_score($v)])) . '">★★★★★</span>';
}

function sx_topic_label(string $t): string
{
    return t("reviews.topic.$t");
}

/** Where a topic links: its topic page when the site has one, else the full list pre-filtered (#t-<topic>). */
function sx_topic_url(string $t): string
{
    $pages = sx_pages();
    foreach ($pages as $pid => $p) {
        if (($p['topic'] ?? null) === $t) {
            return sx_page_url($pid);
        }
    }
    return isset($pages['reviews']) ? sx_page_url('reviews') . "#t-$t" : "#t-$t";
}

function sx_review_card(array $r): string
{
    SX::$used['_reviews'] = 1;
    $c = fn($el) => cls('rcard', $el);
    $topics = implode('', array_map(fn($t) => '<li>' . e(sx_topic_label($t)) . '</li>', $r['topics']));
    $reply = '';
    if (!empty($r['reply'])) {
        $reply = '<div class="' . $c('reply') . '"><p class="' . $c('replyby') . '">' . e(t('reviews.reply')) . ' · <time datetime="' . e($r['reply']['date']) . '">'
            . e(sx_date($r['reply']['date'])) . '</time></p><p>' . e($r['reply']['text']) . '</p></div>';
    }
    return '<article class="' . cls('rcard') . '" data-rating="' . (int)$r['rating'] . '" data-topics="' . e(implode(' ', $r['topics'])) . '">'
        . '<header class="' . $c('head') . '">' . sx_stars($r['rating']) . '<time datetime="' . e($r['date']) . '">' . e(sx_date($r['date'])) . '</time></header>'
        . '<h3 class="' . $c('title') . '">' . e($r['title']) . '</h3>'
        . '<p class="' . $c('text') . '">' . e($r['text']) . '</p>'
        . '<footer class="' . $c('foot') . '"><span class="' . $c('who') . '">' . (sx_images_on() ? sx_avatar($r['name'], cls('rcard', 'avatar')) : '') . e($r['name']) . ($r['city'] ? ', ' . e($r['city']) : '') . '</span>'
        . '<ul class="' . $c('topics') . '">' . $topics . '</ul></footer>' . $reply . '</article>';
}

/**
 * "Verified" card: big score on the left, the player line with a verified-account mark (from the moderation record,
 * field "verified"), topics as chips, the brand's reply set apart. Negative reviews look exactly like positive ones.
 */
function sx_review_card_verified(array $r): string
{
    SX::$used['_reviews'] = 1;
    $c = fn($el) => cls('rver', $el);
    $topics = implode('', array_map(fn($t) => '<li>' . e(sx_topic_label($t)) . '</li>', $r['topics']));
    $reply = !empty($r['reply']) ? '<div class="' . $c('reply') . '"><p>' . sx_icon('chat') . e(t('reviews.reply')) . ' · ' . e(sx_date($r['reply']['date'])) . '</p><p>' . e($r['reply']['text']) . '</p></div>' : '';
    return '<article class="' . cls('rver') . '" data-rating="' . (int)$r['rating'] . '"><div class="' . $c('score') . '"><strong class="' . cls('num') . '">' . (int)$r['rating'] . '</strong>' . '<span aria-hidden="true">★</span></div>'
        . '<div class="' . $c('body') . '"><p class="' . $c('who') . '"><span>' . e($r['name']) . ($r['city'] ? ', ' . e($r['city']) : '') . '</span>'
        . (!empty($r['verified']) ? '<span class="' . $c('badge') . '">' . sx_icon('check') . e(t('reviews.verified')) . '</span>' : '')
        . '<time datetime="' . e($r['date']) . '">' . e(sx_date($r['date'])) . '</time></p>'
        . '<h3 class="' . $c('title') . '">' . e($r['title']) . '</h3><p class="' . $c('text') . '">' . e($r['text']) . '</p>'
        . '<ul class="' . $c('topics') . '">' . $topics . '</ul>' . $reply . '</div></article>';
}

/** The reviews a review-list shows before paging: all, one topic, or the latest N. */
function sx_review_list_items(array $props): array
{
    $all = sx_reviews();
    return match ($props['mode'] ?? 'all') {
        'topic' => array_values(array_filter($all, fn($r) => in_array($props['topic'] ?? '', $r['topics'], true))),
        'latest' => array_slice($all, 0, max(1, (int)($props['limit'] ?? 6))),
        default => $all,
    };
}

/** Pagination nav for page $n of $count of the page $base (page 1 = $base, page n = "$base~n"). */
function sx_pagination(string $base, int $n, int $count, string $class): string
{
    if ($count < 2) {
        return '';
    }
    $url = fn($i) => sx_url(sx_page_url($i === 1 ? $base : "$base~$i"));
    $out = '<nav class="' . e($class) . '" aria-label="' . e(t('paging.label')) . '"><ul>';
    $out .= $n > 1 ? '<li><a href="' . $url($n - 1) . '" rel="prev">' . e(t('paging.prev')) . '</a></li>' : '';
    for ($i = 1; $i <= $count; $i++) {
        $out .= $i === $n
            ? '<li><span aria-current="page">' . $i . '</span></li>'
            : '<li><a href="' . $url($i) . '" aria-label="' . e(t('paging.page', ['n' => $i])) . '">' . $i . '</a></li>';
    }
    $out .= $n < $count ? '<li><a href="' . $url($n + 1) . '" rel="next">' . e(t('paging.next')) . '</a></li>' : '';
    return $out . '</ul></nav>';
}
