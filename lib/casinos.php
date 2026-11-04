<?php
/**
 * Multi-brand comparison sites (brief 08). A site lists several operators ("brands" in the site config), each
 * with its own tracked link (links.brands.<id>). Per brand: identity (brands/operators.json), per-market facts
 * (brands/facts/<market>.json) and editorial ratings (brands/ratings/<market>.json).
 *
 * Criteria and weights come from the blueprint ("criteria"); the overall score is always computed here.
 */

const SX_RATING_MAX_AGE_DAYS = 90;
const SX_FACTS_MAX_AGE_DAYS = 30;

/** Brand ids this site compares, in config order. */
function sx_casino_ids(): array
{
    return SX::$ctx['brands_list'] ?? [];
}

/** Everything about one compared brand: operator + facts + rating + link. */
function sx_casino(string $id): array
{
    if (isset(SX::$once['casino'][$id])) {
        return SX::$once['casino'][$id];
    }
    $root = SX::$ctx['root'];
    $m = SX::$ctx['market']['id'];
    $ops = SX::$once['operators'] ??= sx_json("$root/brands/operators.json");
    $facts = SX::$once['facts'] ??= (is_file("$root/brands/facts/$m.json") ? sx_json("$root/brands/facts/$m.json")['brands'] : []);
    $rf = "$root/brands/ratings/$m." . (SX::$ctx['locale'] ?? '') . '.json'; // editorial text in the site's language, when the market has several
    $rf = is_file($rf) ? $rf : "$root/brands/ratings/$m.json";
    $ratings = SX::$once['ratings'] ??= (is_file($rf) ? sx_json($rf)['brands'] : []);
    return SX::$once['casino'][$id] = ($ops[$id] ?? []) + [
        'id' => $id,
        'f' => $facts[$id] ?? null,
        'r' => $ratings[$id] ?? null,
        'link' => SX::$ctx['links']['brands'][$id] ?? null,
    ];
}

/**
 * Criteria that count in this market: [id => weight]. A criterion with "requires_promo" counts only where the
 * market allows at least one promotion type (bonuses are not scored in Brazil).
 */
function sx_criteria(): array
{
    $out = [];
    foreach (SX::$ctx['criteria'] ?? [] as $id => $c) {
        if (!empty($c['requires_promo']) && !sx_promo_allowed_types()) {
            continue;
        }
        $out[$id] = $c['weight'];
    }
    return $out;
}

/** Overall score (0-10, one decimal) or one criterion's score. */
function sx_casino_score(string $id, ?string $criterion = null): float
{
    $s = sx_casino($id)['r']['scores'] ?? [];
    if ($criterion) {
        return (float)($s[$criterion]['score'] ?? 0);
    }
    $w = sx_criteria();
    $sum = 0;
    foreach ($w as $c => $weight) {
        $sum += $weight * ($s[$c]['score'] ?? 0);
    }
    return round($sum / max(1, array_sum($w)), 1);
}

/** Brand ids ranked by a criterion (ties: overall), or by overall score. */
function sx_casino_rank(?string $criterion = null): array
{
    $ids = sx_casino_ids();
    usort($ids, fn($a, $b) => [sx_casino_score($b, $criterion), sx_casino_score($b)] <=> [sx_casino_score($a, $criterion), sx_casino_score($a)]);
    return $ids;
}

function sx_score10(float $v): string
{
    return sx_number($v, 1);
}

/**
 * Build-time checks (sx_build_site for types with "compares_brands"). Throws when a brand cannot legally or
 * honestly be listed; warns on stale data or copy-paste verdicts.
 */
function sx_casinos_gate(?string $vertical = null): void
{
    $ids = sx_casino_ids();
    $m = SX::$ctx['market'];
    if (count($ids) < 3) {
        throw new RuntimeException('A comparison site needs at least 3 brands ("brands" in the site config).');
    }
    $suffix = $m['compliance']['brand_domain_suffix'] ?? null;
    $today = strtotime(SX::$ctx['today']);
    $verdicts = [];
    foreach ($ids as $id) {
        $b = sx_casino($id);
        if (empty($b['name'])) {
            throw new RuntimeException("Unknown brand '$id' (brands/operators.json).");
        }
        if (!in_array($m['id'], $b['markets'], true) || empty($b['license_no'])) {
            throw new RuntimeException("Brand '$id' is not licensed in market '{$m['id']}' (markets + license_no in brands/operators.json); it cannot be listed.");
        }
        if ($vertical && !in_array($vertical, $b['verticals'], true)) {
            throw new RuntimeException("Brand '$id' does not offer $vertical; it cannot be listed on a $vertical comparison site.");
        }
        if (!$b['link']) {
            throw new RuntimeException("No tracked link for brand '$id' (links.brands.$id).");
        }
        if ($suffix && !str_ends_with((string)parse_url($b['link'], PHP_URL_HOST), $suffix)) {
            throw new RuntimeException("Link for brand '$id' must point to a $suffix domain in market '{$m['id']}'.");
        }
        if (!$b['f'] || !$b['r']) {
            throw new RuntimeException("Brand '$id' needs facts (brands/facts/{$m['id']}.json) and a rating (brands/ratings/{$m['id']}.json).");
        }
        if (empty($b['r']['cons'])) {
            throw new RuntimeException("The review of '$id' has no cons. Every review lists at least one weakness.");
        }
        foreach (array_keys(sx_criteria()) as $c) {
            if (!isset($b['r']['scores'][$c]['score']) || empty($b['r']['scores'][$c]['reason'])) {
                SX::$warnings[] = "Rating of '$id' has no score or reason for criterion '$c'.";
            }
        }
        if (($today - strtotime($b['r']['tested_at'])) / 86400 > SX_RATING_MAX_AGE_DAYS) {
            SX::$warnings[] = "Rating of '$id' was tested on {$b['r']['tested_at']}, over " . SX_RATING_MAX_AGE_DAYS . ' days ago: re-test before publishing.';
        }
        if (($today - strtotime($b['f']['checked_at'])) / 86400 > SX_FACTS_MAX_AGE_DAYS) {
            SX::$warnings[] = "Facts for '$id' were checked on {$b['f']['checked_at']}, over " . SX_FACTS_MAX_AGE_DAYS . ' days ago.';
        }
        foreach ($verdicts as $other => $v) {
            similar_text($v, $b['r']['verdict'], $pct);
            if ($pct > 80) {
                SX::$warnings[] = "Verdicts of '$id' and '$other' are " . round($pct) . '% identical: reviews must be written per brand.';
            }
        }
        $verdicts[$id] = $b['r']['verdict'];
    }
    $featured = SX::$ctx['featured'] ?? null;
    if ($featured && !in_array($featured, $ids, true)) {
        throw new RuntimeException("Featured brand '$featured' is not one of this site's brands.");
    }
}

/** Brand mark (logo in the brand colour), from the page sprite. */
function sx_brand_mark(string $id, string $class = ''): string
{
    $b = sx_casino($id);
    $file = SX::$ctx['root'] . '/brands/' . $b['logo'];
    return '<span class="' . e(trim(cls('bmark') . ' ' . $class)) . '" style="color:' . e($b['brand_color']) . '">'
        . sx_use('l-' . $id, fn() => file_get_contents($file)) . '</span>';
}

/** Tracked CTA for one brand: data-cta=<position>, data-brand=<id>. */
function sx_brand_cta(string $id, string $pos, string $classes, bool $arrow = false, bool $short = false): string
{
    $b = sx_casino($id);
    $label = $short ? t('casinos.visit_short') : t('casinos.visit', ['name' => $b['name']]);
    if (mb_strlen($label) > 22) {
        $classes .= ' ' . cls('btn', null, 'wrap');
    }
    return '<a class="' . e($classes) . '" href="' . sx_url($b['link']) . '" data-cta="' . e($pos) . '" data-brand="' . e($id) . '" rel="nofollow sponsored noopener" target="_blank">'
        . e($label) . ($arrow ? sx_icon('arrow') : '') . '</a>';
}

/** The review page of a brand, if the site has one. */
function sx_review_url(string $id): ?string
{
    foreach (sx_pages() as $pid => $p) {
        if (($p['item'] ?? null) === $id) {
            return sx_page_url($pid);
        }
    }
    return null;
}

/** The compared brand of the current page (generated review pages). */
function sx_page_item(): ?string
{
    return sx_pages()[sx_page_id()]['item'] ?? null;
}

/** Minutes as a rough, readable duration: ~45 min, ~6 h, ~2 days. */
function sx_duration(int $min): string
{
    return match (true) {
        $min < 120 => t('dur.min', ['n' => $min]),
        $min < 2880 => t('dur.h', ['n' => (int) round($min / 60)]),
        default => t('dur.d', ['n' => (int) round($min / 1440)]),
    };
}

/** The key fact for a criterion, used in ranked lists ("Saque PIX em ~5 min"). */
function sx_key_fact(string $id, string $criterion): string
{
    $f = sx_casino($id)['f'];
    $fast = array_keys($f['withdrawal_minutes'], min($f['withdrawal_minutes']))[0];
    $payout = t('casinos.kf.payouts', ['method' => sx_payment_name($fast), 't' => sx_duration(min($f['withdrawal_minutes']))]);
    return match ($criterion) {
        'payouts' => $payout,
        'live' => t('casinos.kf.live', ['n' => sx_number($f['live_tables'])]),
        'games' => t('casinos.kf.games', ['n' => sx_number($f['games'])]),
        'app' => $f['app'] ? t('casinos.app_yes') : t('casinos.app_no'),
        'support' => t('casinos.kf.support', ['h' => $f['support']['chat']]),
        default => $payout,
    };
}
