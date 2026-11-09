<?php
/**
 * How different a job's sites are: each site against the most similar same-type site in this job, in the project's
 * other jobs and in every compared job (code and visual). Comparing a big network takes up to a minute, so it runs in
 * the background (generator/diffjob.php, started by the job page or at the end of a build) and is cached.
 */

/**
 * Differences for a job: every pair of same-type sites in the job, and for each site its closest same-type site in
 * the rest of the project and across every other job.
 */
function gen_job_diff(int $jobId, bool $compute = true): ?array
{
    global $GEN_ROOT;
    $db = gen_db();
    $project = (int)$db->query("SELECT project_id FROM jobs WHERE id = $jobId")->fetchColumn();
    $types = array_column($db->query("SELECT DISTINCT type FROM sites WHERE job_id = $jobId")->fetchAll(), 'type') ?: [''];
    $in = implode(',', array_map(fn($t) => $db->quote($t), $types));
    $where = "s.fingerprint IS NOT NULL AND s.status = 'built' AND s.type IN ($in) AND (j.compare = 1 OR j.id = $jobId)";
    // the answer only changes when a compared site does (a build or a visual check rewrites its checks): cached under a
    // hash of the light columns, so a cached answer reads no fingerprints or screenshot prints
    $light = $db->query("SELECT s.id, s.job_id, j.project_id, s.checks, s.plan FROM sites s JOIN jobs j ON j.id = s.job_id WHERE $where ORDER BY s.id")->fetchAll();
    $sig = md5($jobId . '|' . $project . '|' . implode(';', array_map(fn($r) => $r['id'] . ':' . $r['job_id'] . ':' . $r['project_id'] . ':' . md5($r['checks'] . '|' . $r['plan']), $light)));
    $cache = gen_cache_dir('diff') . "/job-$jobId.json";
    $c = is_file($cache) ? json_decode((string)file_get_contents($cache), true) : null;
    if (($c['sig'] ?? '') === $sig) {
        return ['closest' => $c['closest'], 'settings' => gen_settings()];
    }
    if (!$compute) {
        return null;
    }
    set_time_limit(0);
    // one site type at a time (sites are only compared with their own type): its compared sites read one row at a time,
    // keeping prepared fingerprints and decoded prints only, then freed before the next type
    ini_set('memory_limit', '1536M');
    $closest = [];
    foreach ($types as $type) {
        $q = $db->query("SELECT s.id, s.job_id, j.project_id, s.fingerprint, s.visual FROM sites s JOIN jobs j ON j.id = s.job_id WHERE $where AND s.type = " . $db->quote($type));
        $rows = $fp = $prints = [];
        while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
            $fp[$r['id']] = gen_diff_prep(json_decode($r['fingerprint'], true) ?: []);
            $prints[$r['id']] = $r['visual'] ? gen_visual_raw($r['visual']) : null;
            $rows[] = ['id' => $r['id'], 'job_id' => (int)$r['job_id'], 'project_id' => (int)$r['project_id']];
        }
        $q = null;
        foreach ($rows as $a) {
            if ($a['job_id'] !== $jobId) {
                continue;
            }
            $best = ['job' => [null, null], 'project' => [null, null], 'global' => [null, null]];
            foreach ($rows as $o) {
                if ($o['id'] === $a['id']) {
                    continue;
                }
                $scopes = $o['job_id'] === $jobId ? ['job'] : ($o['project_id'] === $project ? ['project', 'global'] : ['global']);
                $d = gen_diff_sets($fp[$a['id']], $fp[$o['id']]) + ['visual' => $prints[$a['id']] && $prints[$o['id']] ? gen_visual_diff_raw($prints[$a['id']], $prints[$o['id']]) : null, 'site' => $o['id'], 'job' => $o['job_id']];
                foreach ($scopes as $sc) { // each pair is measured once, then counted in every scope it belongs to
                    if (!$best[$sc][0] || $d['code'] < $best[$sc][0]['code']) {
                        $best[$sc][0] = $d;
                    }
                    if ($d['visual'] !== null && (!$best[$sc][1] || $d['visual'] < $best[$sc][1]['visual'])) {
                        $best[$sc][1] = $d;
                    }
                }
            }
            foreach ($best as $sc => [$code, $vis]) {
                $closest[$a['id']][$sc] = $code ? ['code' => $code, 'visual' => $vis] : null;
            }
        }
        unset($rows, $fp, $prints);
    }
    @file_put_contents($cache, json_encode(['sig' => $sig, 'closest' => $closest]));
    return ['closest' => $closest, 'settings' => gen_settings()];
}

/** Work out a job's differences in the background, once at a time per job. */
function gen_spawn_diff(int $jobId): void
{
    $cmd = gen_php_cmd() . ' ' . escapeshellarg(__DIR__ . '/diffjob.php') . ' ' . $jobId;
    if (PHP_OS_FAMILY === 'Windows') {
        pclose(popen('start "" /B /BELOWNORMAL cmd /c "' . $cmd . ' >NUL 2>&1"', 'r'));
    } else {
        exec('nice -n 10 ' . $cmd . ' > /dev/null 2>&1 &');
    }
}
