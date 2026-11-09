<?php
/** php generator/diffjob.php <job id>: work out and cache a job's differences (see diff.php); one run per job at a time. */
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/diff.php';
$jobId = (int)($argv[1] ?? 0);
$lock = fopen(sys_get_temp_dir() . "/satellite-generator-diff-$jobId.lock", 'c');
if (!$jobId || !$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    exit(0); // already being worked out
}
gen_job_diff($jobId);
flock($lock, LOCK_UN);
