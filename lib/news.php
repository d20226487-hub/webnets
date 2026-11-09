<?php
/**
 * News sites (brief 09): an article store (content/<site>/articles/*.json), checks for paid (PR) content, and the
 * HTML sanitizer shared with the prose block.
 *
 * Article: slug, kind (news | guide | pr), title, dek, date, updated?, author (id in authors.json), sponsor (pr),
 * brand? (operator id, pr), body (HTML), sample? (made-up demo content).
 */

const SX_ARTICLE_KINDS = ['news', 'guide', 'pr'];
const SX_ARTICLE_SECTIONS = ['news' => 'news', 'guide' => 'guides', 'pr' => 'partner-content'];
/** Offer words checked in PR text where the market restricts inducements (lower case). */
const SX_OFFER_WORDS = ['bonus', 'free bet', 'promo code', 'odds boost', 'cashback', 'deposit match', 'bônus', 'código promocional', 'aposta grátis', 'бонус', 'фрибет', 'промокод', 'кешбэк'];

/** Published articles (dated today or earlier), newest first, keyed by slug. Future-dated ones are scheduled. */
function sx_articles(?string $kind = null): array
{
    if (!isset(SX::$once['articles'])) {
        $all = [];
        foreach (glob(SX::$ctx['root'] . '/' . (SX::$ctx['articles_dir'] ?? '_none') . '/*.json') ?: [] as $f) {
            $a = sx_json($f);
            $all[$a['slug'] ?? basename($f, '.json')] = $a + ['slug' => basename($f, '.json'), 'file' => basename($f)];
        }
        $today = SX::$ctx['today'];
        $all = array_filter($all, fn($a) => ($a['date'] ?? '9999') <= $today);
        uasort($all, fn($a, $b) => [$b['date'], $a['slug']] <=> [$a['date'], $b['slug']]);
        SX::$once['articles'] = $all;
    }
    return $kind ? array_filter(SX::$once['articles'], fn($a) => $a['kind'] === $kind) : SX::$once['articles'];
}

function sx_article(string $slug): ?array
{
    return sx_articles()[$slug] ?? null;
}

function sx_authors(): array
{
    return SX::$once['authors'] ??= (is_file($f = SX::$ctx['root'] . '/' . dirname(SX::$ctx['articles_dir'] ?? '_none') . '/authors.json') ? sx_json($f) : []);
}

/**
 * Build-time checks for types with "articles". Returns true when sample (made-up) articles are used.
 * Throws for unlabelled or unlawful paid content; warns on offer words where inducements are restricted.
 */
function sx_articles_gate(array $cfg): bool
{
    $m = SX::$ctx['market'];
    $dir = SX::$ctx['articles_dir'] ?? null;
    if (!$dir || !is_dir(SX::$ctx['root'] . "/$dir")) {
        throw new RuntimeException('News sites need "articles": a folder of article files (e.g. content/<site>/articles).');
    }
    $arts = sx_articles();
    if (count($arts) < 3) {
        throw new RuntimeException("Only " . count($arts) . " published articles in $dir (minimum 3; future-dated articles are scheduled, not published).");
    }
    $ops = sx_brand_data(SX::$ctx['root'], 'operators.json');
    $restricted = !sx_promo_allowed_types();
    $suffix = $m['compliance']['brand_domain_suffix'] ?? null;
    $sample = false;
    foreach ($arts as $slug => $a) {
        foreach (['kind', 'title', 'dek', 'date', 'author', 'body'] as $k) {
            if (empty($a[$k])) {
                throw new RuntimeException("Article {$a['file']} has no '$k'.");
            }
        }
        if (!in_array($a['kind'], SX_ARTICLE_KINDS, true) || !preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new RuntimeException("Article {$a['file']}: kind must be " . implode('/', SX_ARTICLE_KINDS) . ' and slug lowercase-with-dashes.');
        }
        if (!isset(sx_authors()[$a['author']])) {
            SX::$warnings[] = "Article $slug: author '{$a['author']}' is not in authors.json.";
        }
        $sample = $sample || !empty($a['sample']);
        if ($a['kind'] !== 'pr') {
            if (!empty($a['sponsor']) || !empty($a['brand'])) {
                throw new RuntimeException("Article $slug has a sponsor or brand but is not kind 'pr'. Paid content must be labelled as pr.");
            }
            continue;
        }
        if (empty($a['sponsor'])) {
            throw new RuntimeException("PR article $slug has no sponsor. Paid articles must say who paid for them.");
        }
        if ($id = $a['brand'] ?? null) {
            $b = $ops[$id] ?? throw new RuntimeException("PR article $slug: unknown brand '$id'.");
            $verticals = array_filter($b['verticals'], fn($v) => !empty($m['verticals'][['live' => 'sports', 'live-casino' => 'casino'][$v] ?? $v]));
            if (!in_array($m['id'], $b['markets'], true) || empty($b['license_no']) || !$verticals) {
                sx_legal_block("PR article $slug promotes '$id', which is not licensed for an allowed vertical in market '{$m['id']}'.");
            }
            $link = SX::$ctx['links']['brands'][$id] ?? throw new RuntimeException("PR article $slug: no tracked link for '$id' (links.brands.$id).");
            if ($suffix && !str_ends_with((string)parse_url($link, PHP_URL_HOST), $suffix)) {
                sx_legal_block("Link for brand '$id' must point to a $suffix domain in market '{$m['id']}'.");
            }
        }
        if ($restricted) {
            $text = mb_strtolower(strip_tags($a['title'] . ' ' . $a['dek'] . ' ' . $a['body']));
            foreach (SX_OFFER_WORDS as $w) {
                if (str_contains($text, $w)) {
                    SX::$warnings[] = "PR article $slug mentions \"$w\": market '{$m['id']}' restricts inducements in advertising. Remove the offer.";
                }
            }
        }
    }
    if ($sample && empty($cfg['demo'])) {
        throw new RuntimeException('Sample (made-up) articles may only be used in demos (set "demo": true, which adds a sample banner).');
    }
    return $sample;
}

function sx_article_kind_label(string $kind): string
{
    return t("news.kind.$kind");
}

/** The site page of an article, if generated. */
function sx_article_url(string $slug): ?string
{
    foreach (sx_pages() as $pid => $p) {
        if (($p['item'] ?? null) === $slug && ($p['source'] ?? null) === 'articles') {
            return sx_page_url($pid);
        }
    }
    return null;
}

function sx_reading_minutes(string $html): int
{
    return max(1, (int)round(str_word_count(strip_tags($html)) / 200));
}

/** "Sponsored" badge for paid content. */
function sx_sponsored_badge(): string
{
    return '<span class="' . cls('sponsored') . '">' . e(t('news.sponsored')) . '</span>';
}

/**
 * Sanitise editor HTML: keep basic tags, drop every attribute except href, resolve page:<id> links,
 * give H2s ids and collect them in $toc. Used by prose and article bodies.
 */
function sx_safe_html(string $html, array &$toc = []): string
{
    $allowed = ['h2', 'h3', 'p', 'ul', 'ol', 'li', 'strong', 'em', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'blockquote', 'br'];
    $html = strip_tags($html, array_map(fn($t) => "<$t>", $allowed));
    $html = preg_replace_callback('#<(/?)([a-z0-9]+)([^>]*)>#i', function ($m) {
        [, $close, $tag, $attrs] = $m;
        $tag = strtolower($tag);
        if ($close) {
            return "</$tag>";
        }
        if ($tag === 'a') {
            $href = preg_match('/href\s*=\s*"([^"]*)"/i', $attrs, $h) ? html_entity_decode($h[1]) : '';
            if (str_starts_with($href, 'page:')) { // a page the site does not have: the words stay, the link goes
                return isset(sx_pages()[substr($href, 5)]) ? '<a href="' . sx_url(sx_page_url(substr($href, 5))) . '">' : '<a>';
            }
            return preg_match('#^(https://|\#)#', $href) ? '<a href="' . e($href) . '"' . ($href[0] === '#' ? '' : ' rel="noopener" target="_blank"') . '>' : '<a>';
        }
        return "<$tag>";
    }, $html);
    return preg_replace_callback('#<h2>(.*?)</h2>#s', function ($m) use (&$toc) {
        $text = trim(strip_tags($m[1]));
        // Windows iconv spells accents as marks ("~a", "'a"): drop them so anchors match glibc ("nao", not "n-ao").
        // Real apostrophes become separators first, as glibc keeps them ("vantro-s-replies").
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', preg_replace("/['’]/u", ' ', $text)) ?: '';
        $id = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(preg_replace('/[\'"`^~]/', '', $ascii))), '-') ?: 's' . (count($toc) + 1);
        $id = isset($toc[$id]) ? $id . '-' . (count($toc) + 1) : $id;
        $toc[$id] = $text;
        return '<h2 id="' . e($id) . '">' . $m[1] . '</h2>';
    }, $html);
}

/** How many non-PR stories each article-lead layout shows. */
function sx_lead_count(string $variant): int
{
    return ['split' => 3, 'stack' => 4, 'list' => 6][$variant] ?? 3;
}

/** Articles a list shows before paging: one kind or all, optionally without PR, minus the lead stories. */
function sx_article_list_items(array $props): array
{
    $arts = sx_articles(($props['kind'] ?? '') ?: null);
    if (!empty($props['exclude_pr'])) {
        $arts = array_filter($arts, fn($a) => $a['kind'] !== 'pr');
    }
    if (!empty($props['skip_lead'])) {
        $shown = SX::$once['lead_slugs'] ?? null; // stories the lead block already shows on this page
        $arts = $shown === null ? array_slice($arts, 3, null, true) : array_filter($arts, fn($a) => !in_array($a['slug'], $shown, true));
    }
    return $arts;
}

/** Small text-first card: kicker (or Sponsored label), title, dek, meta line. */
function sx_article_card(array $a, bool $dek = true, string $h = 'h3'): string
{
    SX::$used['_news'] = 1;
    $c = fn($el) => cls('acard', $el);
    $url = sx_article_url($a['slug']);
    $kicker = $a['kind'] === 'pr'
        ? sx_sponsored_badge() . '<span class="' . $c('sponsor') . '">' . e(t('news.paid_by', ['sponsor' => $a['sponsor']])) . '</span>'
        : '<span class="' . $c('kind') . '">' . e(sx_article_kind_label($a['kind'])) . '</span>';
    $author = sx_authors()[$a['author']]['name'] ?? $a['author'];
    $thumb = sx_images_on() ? '<a class="' . $c('thumb') . '" href="' . sx_url($url ?? '#') . '" tabindex="-1" aria-hidden="true">' . sx_picture($a, '16/9') . '</a>' : '';
    return '<article class="' . cls('acard') . ' ' . $c($a['kind']) . ($thumb ? ' ' . cls('acard', null, 'img') : '') . '">' . $thumb . '<p class="' . $c('kicker') . '">' . $kicker . '</p>'
        . "<$h class=\"" . $c('title') . '"><a href="' . sx_url($url ?? '#') . '">' . e($a['title']) . "</a></$h>"
        . ($dek ? '<p class="' . $c('dek') . '">' . e($a['dek']) . '</p>' : '')
        . '<p class="' . $c('meta') . '"><time datetime="' . e($a['date']) . '">' . e(sx_date($a['date'])) . '</time> · ' . e($author) . '</p></article>';
}
