<?php
/**
 * Vendor pages (brief 04): provider facts (brands/providers.json) merged with what the brand carries
 * in the site's market (brands/catalog/<brand>.json). Nothing about games is typed per site.
 *
 * Game record after merge: provider fields (id, name, volatility, max_win, released, tags)
 * + catalog fields (rtp = the brand's RTP version in this market or null, added, rank) + is_new.
 */

const SX_NEW_DAYS = 60;

function sx_vendor(): ?array
{
    return SX::$ctx['provider'] ?? null;
}

/** Studios this page covers: one (config "provider") or several (config "providers"). */
function sx_vendor_ids(): array
{
    return SX::$ctx['vendor_ids'] ?: array_filter([sx_vendor()['id'] ?? null]);
}

function sx_vendor_multi(): bool
{
    return count(sx_vendor_ids()) > 1;
}

/** "Emberforge, Tidewell Studios e Paper Lantern Games". */
function sx_vendor_label(): string
{
    $names = array_map(fn($id) => SX::$ctx['providers'][$id]['name'] ?? $id, sx_vendor_ids());
    $last = array_pop($names);
    return $names ? implode(', ', $names) . ' ' . t('and') . ' ' . $last : (string)$last;
}

function sx_catalog(): array
{
    if (isset(SX::$once['catalog'])) {
        return SX::$once['catalog'];
    }
    $file = SX::$ctx['root'] . '/brands/catalog/' . (SX::$ctx['brand']['id'] ?? '') . '.json';
    $cat = is_file($file) ? json_decode(file_get_contents($file), true) : [];
    return SX::$once['catalog'] = $cat['markets'][SX::$ctx['market']['id']]['providers'] ?? [];
}

/** All live games of a provider at this brand in this market, sorted by the brand's rank. */
function sx_vendor_games(?string $providerId = null): array
{
    if ($providerId === null && sx_vendor_multi()) { // several studios: all their games, interleaved by each studio's rank
        $all = [];
        foreach (sx_vendor_ids() as $i => $id) {
            foreach (sx_vendor_games($id) as $g) {
                $all[] = $g + ['studio' => SX::$ctx['providers'][$id]['name'] ?? $id, 'studio_id' => $id, '_o' => $g['rank'] * 10 + $i];
            }
        }
        usort($all, fn($a, $b) => $a['_o'] <=> $b['_o']);
        return $all;
    }
    $pid = $providerId ?? (sx_vendor()['id'] ?? '');
    $providers = SX::$ctx['providers'] ?? [];
    $facts = [];
    foreach ($providers[$pid]['games'] ?? [] as $g) {
        $facts[$g['id']] = $g;
    }
    $today = strtotime(sx_today());
    $out = [];
    foreach (sx_catalog()[$pid]['games'] ?? [] as $c) {
        if (!isset($facts[$c['id']])) {
            SX::$warnings[] = "Catalog lists game '{$c['id']}' for provider '$pid', but brands/providers.json has no such game; skipped.";
            continue;
        }
        $g = $facts[$c['id']] + ['rtp_studio' => $facts[$c['id']]['rtp']];
        $g['rtp'] = $c['rtp'] ?? null;
        $g['added'] = $c['added'] ?? null;
        $g['rank'] = $c['rank'] ?? 999;
        $g['is_new'] = $g['added'] && ($today - strtotime($g['added'])) / 86400 <= SX_NEW_DAYS && $g['added'] <= sx_today();
        $out[] = $g;
    }
    usort($out, fn($a, $b) => $a['rank'] <=> $b['rank']);
    return $out;
}

/** Newest additions first. */
function sx_vendor_new_games(): array
{
    $new = array_values(array_filter(sx_vendor_games(), fn($g) => $g['is_new']));
    usort($new, fn($a, $b) => strcmp($b['added'], $a['added']));
    return $new;
}

/** Other providers the brand carries in this market: [id => games count]. */
function sx_other_providers(): array
{
    $out = [];
    foreach (sx_catalog() as $pid => $p) {
        if (!in_array($pid, sx_vendor_ids(), true) && isset(SX::$ctx['providers'][$pid])) {
            $out[$pid] = count($p['games'] ?? []);
        }
    }
    return $out;
}

/** "1 game" / "3 игры" / "5 игр": plural forms for the locales we ship (Russian has one/few/many). */
function sx_n_games(int $n): string
{
    return sx_plural('vendor.n_games', $n);
}

function sx_rtp_label(array $g): string
{
    if ($g['rtp'] === null) {
        return t('vendor.rtp_unknown');
    }
    return t('vendor.rtp_v', ['rtp' => sx_number($g['rtp'], 1), 'market' => sx_market_label()]);
}

function sx_volatility_label(string $v): string
{
    return t('vendor.vol.' . $v);
}

/** Link to one game at the brand: links.game with {game}, else the provider lobby (links.go). */
function sx_game_link(array $g): string
{
    $pattern = SX::$ctx['links']['game'] ?? null;
    return $pattern ? str_replace('{game}', rawurlencode($g['id']), $pattern) : (SX::$ctx['links']['go'] ?? '#');
}

function sx_game_cta(array $g, string $pos, string $classes): string
{
    $href = sx_game_link($g);
    $ext = preg_match('#^https?://#', $href) === 1;
    return '<a class="' . e($classes) . '" href="' . sx_url($href) . '" data-cta="' . e($pos) . '" data-game="' . e($g['id']) . '"'
        . ($ext ? ' rel="nofollow sponsored noopener" target="_blank"' : '') . '>'
        . e(t('vendor.play')) . '<span class="' . cls('visually-hidden') . '"> ' . e($g['name']) . '</span></a>';
}

/** CSS-drawn cover in the provider's colours; motif varies by game. Replace with licensed art via $g['image']. */
function sx_game_cover(array $g, string $extra = ''): string
{
    SX::$used['_game'] = SX::$used['_game'] ?? 1;
    $colors = sx_vendor_colors($g);
    $motif = ['rings', 'wheel', 'rays', 'grid'][crc32($g['id']) % 4];
    return '<span class="' . e(trim(cls('gcover') . ' ' . $extra)) . '" style="--g1:' . e($colors[0]) . ';--g2:' . e($colors[1]) . '" data-motif="' . $motif . '" aria-hidden="true">'
        . '<span class="' . cls('gcover', 'name') . '">' . e($g['name']) . '</span></span>';
}

function sx_vendor_colors(array $g): array
{
    foreach (SX::$ctx['providers'] ?? [] as $p) {
        foreach ($p['games'] as $pg) {
            if ($pg['id'] === $g['id']) {
                return $p['colors'];
            }
        }
    }
    return ['#444444', '#111111'];
}

/** Game card: cover, name, New badge, facts, own CTA. */
function sx_game_card(array $g, string $pos = 'game'): string
{
    $c = fn($el) => cls('gcard', $el);
    $new = $g['is_new'] ? '<span class="' . $c('new') . '">' . e(t('vendor.new')) . '</span>' : '';
    return '<article class="' . cls('gcard') . '">' . sx_game_cover($g) . $new
        . '<h3 class="' . $c('name') . '">' . e($g['name']) . '</h3>'
        . (!empty($g['studio']) ? '<p class="' . $c('studio') . '">' . e($g['studio']) . '</p>' : '')
        . '<dl class="' . $c('facts') . '">'
        . '<div><dt>' . e(t('vendor.rtp')) . '</dt><dd>' . e(sx_rtp_label($g)) . '</dd></div>'
        . '<div><dt>' . e(t('vendor.volatility')) . '</dt><dd>' . e(sx_volatility_label($g['volatility'])) . '</dd></div>'
        . '<div><dt>' . e(t('vendor.max_win')) . '</dt><dd>' . e(t('vendor.max_win_v', ['n' => sx_number($g['max_win'])])) . '</dd></div>'
        . '</dl>' . sx_game_cta($g, $pos, cx('btn', ['btn', null, 'primary'], ['btn', null, 'small'], ['gcard', 'cta'])) . '</article>';
}
