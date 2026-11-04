<?php
/**
 * Diversity check for sites in one project. Resolves each config through its blueprint (same seed
 * logic as the build) and compares every pair.
 *
 * Rule (brief 01, section 6): two sites of the same type must differ in preset, hero variant AND angle.
 * Also reports structural overlap: share of identical "section:variant" pairs, and class-prefix clashes.
 *
 * Usage: php build/diversity.php sites/project-x/*.json      (exit 1 if any pair breaks the rule)
 */

$root = dirname(__DIR__);
require "$root/lib/diversity.php";

$sites = [];
foreach (array_slice($argv, 1) as $path) {
    $cfg = sx_json($path);
    if (isset($cfg['type'])) {
        $sites[$cfg['id']] = sx_site_parts($cfg, $root);
    }
}

if (count($sites) < 2) {
    fwrite(STDERR, "Need at least two typed site configs.\n");
    exit(2);
}

printf("%-16s %-18s %-12s %-12s %s\n", 'site', 'preset', 'angle', 'hero', 'prefix');
foreach ($sites as $id => $s) {
    printf("%-16s %-18s %-12s %-12s %s\n", $id, $s['preset'], $s['angle'], $s['parts']['hero'] ?? '-', $s['prefix']);
}
echo "\n";

$fail = 0;
$ids = array_keys($sites);
for ($i = 0; $i < count($ids); $i++) {
    for ($j = $i + 1; $j < count($ids); $j++) {
        $a = $sites[$ids[$i]];
        $b = $sites[$ids[$j]];
        if ($a['type'] !== $b['type']) {
            continue;
        }
        $d = sx_diversity_pair($a, $b);
        [$same, $overlap] = [$d['same'], $d['overlap']];
        $ok = !$same;
        $fail += $ok ? 0 : 1;
        printf("%s  %s vs %s  overlap %d%%%s%s\n", $ok ? 'ok  ' : 'FAIL', $ids[$i], $ids[$j], $overlap,
            $same ? '  same: ' . implode(', ', $same) : '', $overlap > 60 ? '  (high overlap: pin different variants)' : '');
    }
}
echo "\nOverlap = share of sections using the same variant. Lower is more diverse.\n";
exit($fail ? 1 : 0);
