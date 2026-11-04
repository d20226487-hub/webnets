<?php
/** Resolves mode (with market gating) and default content. Defines $mode, $label, $items. */

$brand = sx_ctx('brand');
$market = sx_ctx('market');
$f = $brand['facts'];

$mode = $props['mode'];
if ($mode === 'offer' && ($market['promotions']['welcome_bonus'] ?? 'allowed') === 'restricted' && empty($props['allow_offer'])) {
    SX::$warnings[] = "bonus-card: offers restricted in market {$market['id']}, rendered as feature card";
    $mode = 'feature';
}

$items = $props['items'] ?? [];
if (!$items) {
    $items[] = ['value' => sx_minutes_label($f['payout_minutes']), 'text' => t('trust.payout')];
    if (isset($f['markets_per_match']) && !empty($market['verticals']['sports'])) {
        $items[] = ['value' => sx_number($f['markets_per_match']) . '+', 'text' => t('nav.sports')];
    }
    if (isset($f['games']) && !empty($market['verticals']['casino'])) {
        $items[] = ['value' => sx_number($f['games']) . '+', 'text' => t('nav.casino')];
    }
    $pp = sx_proof_points();
    $items[] = isset($pp['rating'])
        ? ['value' => $pp['rating'][1] . ' / 5', 'text' => $pp['rating'][2]]
        : ['value' => $pp['since'][1] ?? '', 'text' => t('trust.since')];
    $items = array_slice(array_filter($items, fn($i) => $i['value'] !== ''), 0, 3);
}

$label = $props['label'] ?: t($mode === 'offer' ? 'bonus.offer' : 'bonus.feature');
