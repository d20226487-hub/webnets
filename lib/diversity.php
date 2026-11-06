<?php
/**
 * Diversity between sites of one project (brief 01, section 6), shared by build/diversity.php and the builder.
 * Rule: two sites of the same type must differ in preset, angle AND hero variant (and never share a class prefix).
 * Overlap = share of identical "section:variant" pairs, frame parts with a single layout left out.
 */

require_once __DIR__ . '/site.php';
require_once __DIR__ . '/blueprint.php';

/** Resolve a config through its blueprint: ['id','type','preset','angle','prefix','parts' => [section => variant]]. */
function sx_site_parts(array $cfg, string $root): array
{
    [$ctx] = sx_site_context($cfg, $root);
    sx_context($ctx);
    SX::$warnings = [];
    SX::$once = [];
    $parts = [];
    if (isset(sx_blueprint($cfg['type'], $root)['pages'])) {
        // Multi-page: every page's sections count ("payments.methods"); the home hero is the site's hero.
        [, $summary] = sx_expand_site($cfg, $root);
        foreach ($summary['pages'] as $pid => $sections) {
            foreach ($sections as $s) {
                [$id, $v] = explode(':', $s);
                $parts[in_array($id, ['header', 'footer', 'age-gate', 'sticky-cta'], true) ? $id : ($pid === 'home' && in_array($id, ['hero', 'lead'], true) ? 'hero' : "$pid.$id")] = $v;
            }
        }
    } else {
        [, $summary] = sx_expand_blueprint($cfg, $root);
        foreach ($summary['sections'] as $s) {
            [$id, $v] = explode(':', $s);
            $parts[$id] = $v;
        }
    }
    require_once __DIR__ . '/dialect.php';
    sx_context($ctx);
    $dialect = sx_dialect_resolve($cfg['dialect'] ?? true);
    return ['id' => $cfg['id'], 'type' => $cfg['type'], 'preset' => $cfg['preset'], 'angle' => $summary['angle'], 'prefix' => $ctx['prefix'], 'parts' => $parts, 'dialect' => $dialect];
}

/** Compare two resolved sites: ['same' => [...], 'overlap' => %, 'ok' => bool, 'comparable' => same type]. */
function sx_diversity_pair(array $a, array $b): array
{
    if ($a['type'] !== $b['type']) {
        return ['same' => [], 'overlap' => 0, 'ok' => true, 'comparable' => false];
    }
    $same = [];
    foreach (['preset', 'angle'] as $k) {
        if ($a[$k] === $b[$k]) {
            $same[] = $k;
        }
    }
    if (($a['parts']['hero'] ?? null) === ($b['parts']['hero'] ?? null)) {
        $same[] = 'hero';
    }
    if ($a['prefix'] === $b['prefix']) {
        $same[] = 'class prefix';
    }
    $common = array_diff_key(array_intersect_key($a['parts'], $b['parts']), array_flip(['footer', 'age-gate']));
    $identical = count(array_filter(array_keys($common), fn($k) => $a['parts'][$k] === $b['parts'][$k]));
    // Code dialects (lib/dialect.php): a shared class-name style is the biggest remaining code overlap. A hint, not a failure.
    $hints = [];
    if (($a['dialect']['classes'] ?? 'off') === ($b['dialect']['classes'] ?? 'off')) {
        $hints[] = 'same class style (' . ($a['dialect']['classes'] ?? 'no dialect') . '): pin "dialect": {"classes": …} on one of them';
    }
    return ['same' => $same, 'overlap' => $common ? (int)round(100 * $identical / count($common)) : 0, 'ok' => !$same, 'comparable' => true, 'hints' => $hints];
}
