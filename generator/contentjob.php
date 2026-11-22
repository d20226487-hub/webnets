<?php
/** Background content run of one job: php generator/contentjob.php <job id> (started by the API, one per job at a time). */
require __DIR__ . '/lib.php';
require_once __DIR__ . '/content.php';
$jobId = (int)($argv[1] ?? 0);
$lock = gen_lock_open(gen_content_lock($jobId));
if (!$jobId || !flock($lock, LOCK_EX | LOCK_NB)) { // already writing this job's text: that run picks up the newly queued sites
    exit(0);
}
gen_content_run($jobId);
flock($lock, LOCK_UN);
