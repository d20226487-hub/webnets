<?php
/**
 * Generator worker: builds every planned site of a job, stores warnings, page count and a code fingerprint, and
 * enforces the difference thresholds (generator settings):
 *   1. code: right after each build the site is compared with the sites before it (same type, settings' scope);
 *      under the code threshold it is re-planned and rebuilt at once, up to the attempts limit;
 *   2. visual: once every site is built, one round of first-screen screenshots (desktop and phone); then, site by site
 *      in build order, code and visual are checked again against the final versions of the sites before it, and a site
 *      under either threshold is re-planned, rebuilt and shot again, up to the attempts limit, before the next site.
 * Each site keeps its best attempt; one that still misses a threshold is flagged on the job page, not retried forever.
 * One job builds at a time: a worker that finds another one building queues its job (status "queued") and exits; each
 * worker starts the next queued job when it is done. Jobs left "running" by a worker that died become "stopped".
 * Started in the background by the generator app: php generator/run.php <job id>
 */
require __DIR__ . '/lib.php';

$jobId = (int)($argv[1] ?? 0);
$db = gen_db();
$job = $db->query("SELECT * FROM jobs WHERE id = $jobId")->fetch();
if (!$job) {
    fwrite(STDERR, "No job $jobId\n");
    exit(1);
}
$lock = fopen(gen_lock_file(), 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) { // another job is building: this one waits its turn
    $db->prepare("UPDATE jobs SET status = 'queued', queued_at = COALESCE(queued_at, ?) WHERE id = ?")->execute([gen_now(), $jobId]);
    exit(0);
}
$stale = array_column($db->query("SELECT id FROM jobs WHERE status IN ('running', 'checking') AND id <> $jobId")->fetchAll(), 'id');
gen_stop_jobs($stale); // this worker holds the lock, so no other job is really building
$db->prepare('UPDATE jobs SET queued_at = NULL WHERE id = ?')->execute([$jobId]);
$set = gen_settings();
$brandIds = json_decode($job['spec'], true)['brand_ids'] ?? [];
$db->prepare('UPDATE jobs SET status = ? WHERE id = ?')->execute(['running', $jobId]);
$sites = $db->query("SELECT * FROM sites WHERE job_id = $jobId AND status IN ('planned', 'failed') ORDER BY type, n")->fetchAll();
$update = $db->prepare('UPDATE sites SET status = ?, plan = ?, warnings = ?, error = ?, pages = ?, fingerprint = ?, visual = ?, checks = ? WHERE id = ?');

/** Build one attempt. Returns null on failure, else [warnings, pages, fingerprint]. */
function run_attempt(array $s, string $market, array $brandIds, array $plan, ?string &$error): ?array
{
    global $GEN_ROOT;
    [$ok, $warnings, $error, $pages] = gen_build_site(['plan' => $plan] + $s, $market, $brandIds);
    $home = "$GEN_ROOT/dist/generated/{$s['id']}/index.html";
    if (!$ok || !is_file($home)) {
        return null;
    }
    return [$warnings, $pages, gen_fingerprint(file_get_contents($home))];
}

/** How far an attempt is from failing: the smaller margin over the two thresholds (higher is better). */
function run_margin(?array $code, ?array $visual, array $set): int
{
    $m = [$code ? $code['min'] - $set['code_min'] : 100];
    if ($set['visual']) {
        $m[] = $visual ? $visual['min'] - $set['visual_min'] : 100;
    }
    return min($m);
}

function run_checks(?array $code, ?array $visual, array $set, int $attempts, bool $visualPending = false): string
{
    $row = fn(?array $c, int $min) => $c ? ['min' => $c['min'], 'site' => $c['site'], 'ok' => $c['min'] >= $min] : null;
    return json_encode(['code' => $row($code, $set['code_min']), 'visual' => $set['visual'] ? ($visualPending ? 'pending' : $row($visual, $set['visual_min'])) : null,
        'attempts' => $attempts, 'thresholds' => ['code' => $set['code_min'], 'visual' => $set['visual'] ? $set['visual_min'] : null, 'scope' => $set['scope']], 'checked' => gen_now()]);
}

$state = [];
foreach ($sites as $s) {
    $db->prepare('UPDATE sites SET status = ? WHERE id = ?')->execute(['building', $s['id']]);
    $plan = json_decode($s['plan'], true);
    $best = null;
    $error = null;
    for ($a = 0; $a < $set['attempts']; $a++) {
        if ($a > 0) {
            $plan = gen_replan($plan, $a, $code['style'] ?? null, gen_type_siblings($s, $set['scope']));
        }
        $r = run_attempt($s, $job['market'], $brandIds, $plan, $error);
        if (!$r) {
            if ($a === 0) {
                break;
            }
            continue;
        }
        $code = gen_code_check($r[2], gen_compare_set($s, $set['scope']));
        $try = ['plan' => $plan, 'res' => $r, 'code' => $code, 'margin' => run_margin($code, null, $set), 'attempts' => $a + 1];
        if (!$best || $try['margin'] > $best['margin']) {
            $best = $try;
        }
        if (!$code || $code['min'] >= $set['code_min']) {
            break;
        }
    }
    if (!$best) {
        $update->execute(['failed', $s['plan'], '[]', $error ?: 'Build failed.', 0, null, null, null, $s['id']]);
        continue;
    }
    if ($best['plan'] !== $plan) { // a later attempt was worse: put the best one back
        run_attempt($s, $job['market'], $brandIds, $best['plan'], $error);
    }
    [$warnings, $pages, $fp] = $best['res'];
    $update->execute(['built', json_encode($best['plan']), json_encode($warnings, JSON_UNESCAPED_UNICODE), null, $pages, json_encode($fp), null,
        run_checks($best['code'], null, $set, $best['attempts'], true), $s['id']]);
    $state[$s['id']] = $best + ['site' => $s];
}

if ($set['visual'] && $state) {
    $db->prepare('UPDATE jobs SET status = ? WHERE id = ?')->execute(['checking', $jobId]);
    try {
        $store = $db->prepare('UPDATE sites SET visual = ? WHERE id = ?');
        $missing = array_keys($state);
        foreach ($state as $st) { // earlier sites it is compared with that have no print yet (built before visual checks)
            foreach (gen_compare_set($st['site'], $set['scope']) as $r) {
                if (!$r['visual'] && is_file("$GEN_ROOT/dist/generated/{$r['id']}/index.html")) {
                    $missing[] = $r['id'];
                }
            }
        }
        foreach (gen_shoot(array_values(array_unique($missing))) as $id => $print) {
            $store->execute([$print, $id]);
            if (isset($state[$id])) {
                $state[$id]['print'] = $print;
            }
        }
        foreach ($state as $id => $st) { // in build order: a site is final before any later site is compared with it
            $cur = $st + ['print' => null];
            $cmp = gen_compare_set($st['site'], $set['scope']);
            $cur['code'] = gen_code_check($cur['res'][2], $cmp);
            $cur['visual'] = $cur['print'] ? gen_visual_check($cur['print'], $cmp) : null;
            $cur['margin'] = run_margin($cur['code'], $cur['visual'], $set);
            $keep = $cur;
            $attempts = $st['attempts'];
            $built = $cur['plan'];
            for ($t = 1; $t < $set['attempts'] && $cur['margin'] < 0; $t++) {
                $looks = $cur['visual'] && $cur['visual']['min'] < $set['visual_min'];
                $closest = $looks ? $cur['visual'] : $cur['code'];
                $codeOk = !$cur['code'] || $cur['code']['min'] >= $set['code_min'];
                // only too alike to look at: swap light and dark first (same code); then a new style and plan
                $plan = gen_replan($cur['plan'], $attempts, $closest['style'] ?? null, gen_type_siblings($st['site'], $set['scope']), $looks && $codeOk && $t === 1);
                $attempts++;
                $r = run_attempt($st['site'], $job['market'], $brandIds, $plan, $error);
                $built = $plan;
                if (!$r) {
                    continue;
                }
                try {
                    $print = gen_shoot([$id])[$id] ?? null;
                } catch (Throwable $e) { // a screenshot that fails leaves this attempt without a visual check
                    $print = null;
                }
                $cur = ['plan' => $plan, 'res' => $r, 'print' => $print, 'code' => gen_code_check($r[2], $cmp),
                    'visual' => $print ? gen_visual_check($print, $cmp) : null] + $cur;
                $cur['margin'] = run_margin($cur['code'], $cur['visual'], $set);
                if ($cur['margin'] > $keep['margin']) {
                    $keep = $cur;
                }
            }
            if ($built !== $keep['plan']) { // the last build is not the best attempt: put the best one back
                run_attempt($st['site'], $job['market'], $brandIds, $keep['plan'], $error);
                try {
                    gen_shoot([$id]); // and its thumbnail: the job page showed the last attempt's (e.g. light and dark swapped)
                } catch (Throwable $e) {
                    fwrite(STDERR, date('c') . " thumbnail of $id: " . $e->getMessage() . "
");
                }
            }
            [$warnings, $pages, $fp] = $keep['res'];
            $update->execute(['built', json_encode($keep['plan']), json_encode($warnings, JSON_UNESCAPED_UNICODE), null, $pages, json_encode($fp), $keep['print'],
                run_checks($keep['code'], $keep['visual'], $set, $attempts), $id]);
        }
    } catch (Throwable $e) {
        fwrite(STDERR, date('c') . ' visual check: ' . $e->getMessage() . "\n");
    }
}
$failed = (int)$db->query("SELECT COUNT(*) FROM sites WHERE job_id = $jobId AND status = 'failed'")->fetchColumn();
$db->prepare('UPDATE jobs SET status = ? WHERE id = ?')->execute([$failed ? 'done-with-errors' : 'done', $jobId]);
require_once __DIR__ . '/diff.php';
gen_spawn_diff($jobId); // the job page's difference tables are ready by the time it is opened
$next = $db->query("SELECT id FROM jobs WHERE status = 'queued' ORDER BY queued_at, id LIMIT 1")->fetchColumn();
flock($lock, LOCK_UN);
if ($next) {
    gen_spawn((int)$next);
}
