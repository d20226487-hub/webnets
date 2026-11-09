<?php
/**
 * Availability probes, run by the generator app.
 *   php generator/probe.php <market> <type> <probe brand ids> [<axis> <option>]   one probe build, as JSON {error}
 *                                                               (run in parallel, one per type and option)
 *   php generator/probe.php warm                                every out-of-date market, in turn
 */
require __DIR__ . '/lib.php';

if (($argv[1] ?? '') === 'warm') {
    foreach (gen_stale_markets() as $m) {
        $t = microtime(true);
        gen_availability($m);
        echo date('c') . " $m " . round(microtime(true) - $t, 1) . "s\n";
    }
    exit(0);
}
[$market, $type, $ids, $axis, $opt] = [preg_replace('/[^a-z]/', '', $argv[1] ?? ''), $argv[2] ?? '', explode(',', $argv[3] ?? ''), $argv[4] ?? '', $argv[5] ?? ''];
if (!isset(gen_catalog()[$type]) || !is_file("$GEN_ROOT/markets/$market.json")) {
    fwrite(STDERR, "Unknown market or type." . PHP_EOL);
    exit(1);
}
echo json_encode(['error' => gen_try_build($type, $market, $ids[0], $axis === '' ? [] : [$axis => $opt], $ids, 'probe-' . substr(md5("$market|$type|$axis|$opt"), 0, 8))]); // per market: the markets' checks run side by side
