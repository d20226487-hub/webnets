<?php
/**
 * Promotions (brief 03): per-market promotion policy + per-brand promotion feed.
 *
 *   markets/<id>.json   promotions: { <type>: allowed | restricted | unknown }   (unknown = restricted)
 *   brands/promotions/<brand>.json   promotions: [ { id, type, markets, title, value, code, terms, valid_*, verified_at, terms_url } ]
 *
 * A promotion renders only when its market matches, its type is allowed, and today is inside
 * its validity window. Expired promos and stale verification dates produce build warnings.
 */

const SX_PROMO_TYPES = ['welcome_bonus', 'deposit_bonus', 'free_bets', 'promo_code', 'odds_boost', 'cashback', 'loyalty', 'tournaments'];
const SX_PROMO_STALE_DAYS = 14;

function sx_promo_allowed(string $type): bool
{
    return (SX::$ctx['market']['promotions'][$type] ?? 'unknown') === 'allowed' || !empty(SX::$ctx['promo_override']);
}

/** Promotion types the current market allows. */
function sx_promo_allowed_types(): array
{
    return array_values(array_filter(SX_PROMO_TYPES, 'sx_promo_allowed'));
}

function sx_today(): string
{
    return SX::$ctx['today'] ?? date('Y-m-d');
}

/** Active, allowed promotions for the current brand and market, in feed order. */
function sx_promos(): array
{
    return SX::$once['promos'] ??= sx_brand_promos(SX::$ctx['brand']['id'] ?? '');
}

/** The lead offer of any brand (ranking designs show each casino's offer): its first active, allowed promotion. */
function sx_brand_lead_offer(string $brandId): ?array
{
    return (SX::$once['brand_promos'][$brandId] ??= sx_brand_promos($brandId))[0] ?? null;
}

/** Active, allowed promotions of one brand in this market, in feed order (policy wins over the feed). */
function sx_brand_promos(string $brandId): array
{
    $file = sx_brand_file(SX::$ctx['root'], "promotions/$brandId.json");
    $feed = is_file($file) ? (json_decode(file_get_contents($file), true)['promotions'] ?? []) : [];
    $market = SX::$ctx['market']['id'];
    $today = sx_today();
    $out = [];
    foreach ($feed as $p) {
        if (!in_array($market, $p['markets'] ?? [], true)) {
            continue;
        }
        if (!sx_promo_allowed($p['type'])) {
            continue; // policy wins over the feed, silently
        }
        if (($pol = SX::$ctx['market']['promotions'][$p['type']] ?? 'unknown') !== 'allowed') {
            sx_legal_block("Promotion type '{$p['type']}' is $pol in market '$market'; '{$p['id']}' is shown anyway.");
        }
        if (($p['valid_to'] ?? '9999-12-31') < $today) {
            SX::$warnings[] = "ERROR: promotion '{$p['id']}' expired on {$p['valid_to']}; it was removed from the page. Update the feed.";
            continue;
        }
        if (($p['valid_from'] ?? '0000-01-01') > $today) {
            continue;
        }
        $verified = $p['verified_at'] ?? '0000-01-01';
        if ((strtotime($today) - strtotime($verified)) / 86400 > SX_PROMO_STALE_DAYS) {
            SX::$warnings[] = "Promotion '{$p['id']}' was last verified on $verified (more than " . SX_PROMO_STALE_DAYS . ' days ago). Re-check it.';
        }
        $out[] = $p;
    }
    if ((SX::$ctx['offer_mode'] ?? 'mixed') === 'codes') {
        $out = array_values(array_filter($out, fn($p) => !empty($p['code']))); // promo-codes-only pages list coded offers only
    } elseif ((SX::$ctx['offer_mode'] ?? 'mixed') === 'banners') {
        $out = array_values(array_filter($out, fn($p) => empty($p['code']) || !empty($p['code_via_link']))); // a banner can only carry a code the link applies
    }
    return $out;
}

/**
 * The lead offer for this page: the first active promo whose type is in SX::$ctx['lead_types']
 * (set from the blueprint angle). "code" in the list matches any promo that has a code.
 */
function sx_lead_promo(): ?array
{
    $types = SX::$ctx['lead_types'] ?? SX_PROMO_TYPES;
    foreach ($types as $t) {
        foreach (sx_promos() as $p) {
            if ($p['type'] === $t || ($t === 'code' && !empty($p['code']))) {
                return $p;
            }
        }
    }
    return sx_promos()[0] ?? null;
}

/** Banner art theme for a promotion type. */
function sx_promo_theme(string $type): string
{
    return ['odds_boost' => 'chart', 'cashback' => 'payments', 'loyalty' => 'star', 'tournaments' => 'star'][$type] ?? 'gift';
}

/** "100% up to €100", "+25% odds", "10% cashback up to €50", "50 free spins". */
function sx_promo_value(array $p): string
{
    $v = $p['value'];
    return match ($v['kind']) {
        'match' => t('promo.v.match', ['percent' => $v['percent'], 'max' => money($v['max'])]),
        'boost' => t('promo.v.boost', ['percent' => $v['percent']]),
        'cashback' => t('promo.v.cashback', ['percent' => $v['percent'], 'max' => money($v['max'])]),
        'free_bets' => t('promo.v.free_bets', ['amount' => money($v['amount'])]),
        'free_spins' => t('promo.v.free_spins', ['n' => $v['count']]),
        'prize_pool' => t('promo.v.prize_pool', ['amount' => money($v['amount'])]),
        default => '',
    };
}

/** Significant terms as [label => value], in a fixed, readable order. Empty terms are omitted. */
function sx_promo_terms(array $p): array
{
    $tm = $p['terms'] ?? [];
    $rows = [
        t('promo.t.min_deposit') => isset($tm['min_deposit']) ? money($tm['min_deposit']) : null,
        t('promo.t.wagering') => isset($tm['wagering']) ? t('promo.t.wagering_v', ['n' => $tm['wagering']]) : null,
        t('promo.t.min_odds') => $tm['min_odds'] ?? null,
        t('promo.t.max_bet') => isset($tm['max_bet']) ? money($tm['max_bet']) : null,
        t('promo.t.expiry') => isset($tm['expiry_days']) ? ($tm['expiry_days'] == 1 ? t('promo.t.day1') : t('promo.t.days', ['n' => $tm['expiry_days']])) : null,
        t('promo.t.eligible') => isset($tm['eligible']) ? tm($tm['eligible']) : null,
        t('promo.t.games') => isset($tm['games']) ? tm($tm['games']) : null,
        t('promo.t.excluded') => isset($tm['excluded_payments']) ? tm($tm['excluded_payments']) : null,
        t('promo.t.ends') => isset($p['valid_to']) ? sx_date($p['valid_to']) : null,
    ];
    return array_filter($rows, fn($v) => $v !== null && $v !== '');
}

/** The two terms shown right next to an offer. */
function sx_promo_key_terms(array $p): array
{
    return array_slice(sx_promo_terms($p), 0, 2, true);
}

function sx_promo_verified(array $p): string
{
    return t('promo.verified', ['date' => sx_date($p['verified_at'])]);
}

/**
 * Code with a copy button (or "No code needed"). Copy is tracked as data-cta="copy".
 * The code is always visible as text; copy falls back to selecting it.
 */
function sx_code_box(?array $p, string $pos, string $extra = ''): string
{
    if ((SX::$ctx['offer_mode'] ?? 'mixed') === 'banners') { // banners-only pages show no code UI; a coded offer says the link applies it
        return $p && !empty($p['code']) ? '<p class="' . e(trim(cls('codebox') . ' ' . cls('codebox', null, 'none') . ' ' . $extra)) . '">' . sx_icon('check') . e(t('promo.code_auto')) . '</p>' : '';
    }
    if (!$p || empty($p['code'])) {
        return '<p class="' . e(trim(cls('codebox') . ' ' . cls('codebox', null, 'none') . ' ' . $extra)) . '">' . sx_icon('check') . e(t('promo.no_code')) . '</p>';
    }
    SX::$used['_promo'] = SX::$used['_promo'] ?? 1;
    $label = t('bonus.code');
    return sx_copy_script()
        . '<div class="' . e(trim(cls('codebox') . ' ' . $extra)) . '">'
        . '<span class="' . cls('codebox', 'label') . '">' . e($label) . '</span>'
        . '<span class="' . cx('num', ['codebox', 'code']) . '" data-code>' . e($p['code']) . '</span>'
        . '<button type="button" class="' . cls('codebox', 'copy') . '" data-copy="' . e($p['code']) . '" data-cta="copy-' . e($pos) . '"'
        . ' data-done="' . e(t('bonus.copied')) . '">' . e(t('bonus.copy')) . '</button></div>';
}

/** Copy-to-clipboard behaviour, once per page. */
function sx_copy_script(): string
{
    if (isset(SX::$once['copy'])) {
        return '';
    }
    SX::$once['copy'] = true;
    return <<<'JS'
<script>
document.addEventListener('click', function (ev) {
  var b = ev.target.closest('[data-copy]');
  if (!b) return;
  var code = b.getAttribute('data-copy'), label = b.textContent, box = b.parentNode.querySelector('[data-code]');
  function done() { b.textContent = b.getAttribute('data-done'); setTimeout(function () { b.textContent = label; }, 2000); }
  function select() { var r = document.createRange(); r.selectNodeContents(box); var s = getSelection(); s.removeAllRanges(); s.addRange(r); }
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(code).then(done, select);
  } else {
    select();
  }
});
</script>
JS;
}

/** Translated type label, e.g. "Welcome offer". */
function sx_promo_type_label(string $type): string
{
    return t('promo.type.' . $type);
}
