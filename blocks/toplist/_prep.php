<?php
/** Toplist: ranking, columns and helpers shared by implementations A (render.php) and B (b/variants). */
$crit = sx_crit(($props['rank_by'] ?? '') === 'overall' ? null : (($props['rank_by'] ?? '') ?: (SX::$ctx['rank_by'] ?? null)));
$ranked = sx_casino_rank($crit);
$rankOf = array_flip($ranked);
$ids = $ranked;
if (!empty($props['exclude_current']) && ($here = sx_page_item())) {
    $ids = array_values(array_diff($ids, [$here]));
}
if ((int)$props['limit'] > 0) {
    $ids = array_slice($ids, 0, (int)$props['limit']);
}
$featured = !empty($props['show_featured']) || !empty(SX::$ctx['promote']) ? (SX::$ctx['featured'] ?? null) : null; // a site that promotes its brand always shows the labelled slot
if (!empty(SX::$ctx['rank_scores'])) { // the job's ranking settings: the brand stays in its place by score, no slot above the others
    $featured = null;
}
if ($featured && !empty(SX::$ctx['promote'])) { // the promoted brand is No. 1 of the ranking already: its labelled entry is that place, not a copy above it
    $ids = array_values(array_diff($ids, [$featured]));
}
$h = $props['heading'] === 'h1' ? 'h1' : 'h2';
$kf = $crit ?: 'payouts';
$c = fn($el) => cls('toplist', $el);
$method = isset(sx_pages()['method']) ? sx_page_url('method') : null;
// table columns: the ranked criterion first, then the other headline facts
$defs = sx_fact_defs(); // per product: casino payouts/live/games/support, sports payouts/live_bet/markets/odds, ...
$colDefs = array_map(fn($d) => $d[0], $defs);
$cols = [$kf => $colDefs[$kf] ?? t("casinos.crit.$kf")] + array_slice($colDefs, 0, 3, true); // a criterion with no fact column (app) is labelled by its own name
$cols = array_slice($cols, 0, 3, true);
$cell = fn(string $id, string $k): string => isset($defs[$k]) ? $defs[$k][1]($id) : sx_key_fact($id, $k);
// podium bars: the ranked criterion, then the criteria this brand scores on, four at most
$barKeys = function (string $id) use ($crit): array {
    $keys = array_keys(sx_casino($id)['r']['scores'] ?? []);
    // a hybrid brand has casino and sports scores: show only this site's criteria
    $keys = array_values(array_intersect($keys, array_keys(SX::$ctx['criteria'] ?? [])));
    $keys = array_values(array_unique(array_merge($crit && in_array($crit, $keys, true) ? [$crit] : [], array_diff($keys, ['safety']))));
    return array_slice($keys, 0, 4);
};
