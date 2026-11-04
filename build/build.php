<?php
/**
 * Static builder: site config JSON -> dist/sites/<id>/<slug>.html
 *
 * Usage: php build/build.php sites/examples/vantro-br.json [more.json ...]
 *
 * A config with "type" is expanded from blueprints/<type>.json (see lib/blueprint.php).
 * A config with "pages" lists blocks explicitly. Rendering lives in lib/page.php so the
 * WordPress page template produces the same HTML.
 *
 * Exits 1 when any page has warnings: a build with warnings is not shippable.
 */

$root = dirname(__DIR__);
require "$root/lib/page.php";

if ($argc < 2) {
    fwrite(STDERR, "Usage: php build/build.php <site.json> [...]\n");
    exit(2);
}

$exit = 0;
foreach (array_slice($argv, 1) as $path) {
    $cfg = sx_json($path);
    $outDir = "$root/dist/sites/{$cfg['id']}";
    @mkdir($outDir, 0777, true);

    try {
        $pages = sx_build_site($cfg, $root);
    } catch (RuntimeException $e) {
        echo "$path -> ERROR: {$e->getMessage()}\n";
        $exit = 1;
        continue;
    }
    foreach ($pages as $i => $p) {
        $file = "$outDir/{$p['file']}";
        @mkdir(dirname($file), 0777, true);
        file_put_contents($file, $p['html']);
        printf("%s -> %s (%s KB%s)\n", $path, substr($file, strlen($root) + 1), round(strlen($p['html']) / 1024, 1), empty($p['asset']) ? ", prefix {$p['prefix']}" : '');
        if ($p['summary'] && isset($p['summary']['pages'])) {
            if ($i === 0) {
                printf("  %s · angle %s · preset %s · %d pages\n", $cfg['type'], $p['summary']['angle'], $cfg['preset'], count($p['summary']['pages']));
            }
            printf("  %s\n", implode(' → ', $p['summary']['pages'][$p['id']]));
        } elseif ($p['summary']) {
            printf("  %s · angle %s · preset %s\n  %s\n", $cfg['type'], $p['summary']['angle'], $cfg['preset'], implode(' → ', $p['summary']['sections']));
        }
        foreach ($p['warnings'] as $w) {
            echo "  warning: $w\n";
            $exit = 1;
        }
    }
}
exit($exit);
