<?php
/** Background translation run of one job: php generator/translatejob.php <job id> (started by the API, one per job at a time). */
require __DIR__ . '/lib.php';
require_once __DIR__ . '/vertex.php';
require_once __DIR__ . '/translate.php';
$jobId = (int)($argv[1] ?? 0);
$lock = gen_lock_open(gen_tr_lock($jobId));
if (!$jobId || !flock($lock, LOCK_EX | LOCK_NB)) { // already translating this job: that run picks up the newly queued sites
    exit(0);
}
gen_tr_run($jobId);
flock($lock, LOCK_UN);
