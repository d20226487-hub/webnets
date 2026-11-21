<?php
/** Generator JSON API, routed from generator/index.php: /generator/api/<path>. */

// PHP's own errors never go into the answer as HTML (the app would only see "not valid JSON"): they go to the server
// log, and a fatal error or an uncaught exception comes back as JSON {error} with its message, file and line.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_exception_handler(function (Throwable $e) {
    error_log('Generator API: ' . $e);
    gen_api_fail(get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
});
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        gen_api_fail('PHP error: ' . $e['message'] . ' (' . basename($e['file']) . ':' . $e['line'] . ')');
    }
});
function gen_api_fail(string $msg): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['error' => $msg, 'server_error' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
}
foreach (['pdo_sqlite', 'mbstring'] as $ext) { // without these nothing works; say which one, before the first call fails
    if (!extension_loaded($ext)) {
        gen_api_fail("The server's PHP is missing the $ext extension (install php-" . ($ext === 'pdo_sqlite' ? 'sqlite3' : 'mbstring') . ' and restart PHP).');
        exit;
    }
}
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/diff.php';
require_once __DIR__ . '/briefs.php';
require_once __DIR__ . '/projectfiles.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/brandfacts.php';

/** Quick refresh: the built sites of a job (or only $siteIds) get their pages written again from their plans, with the
 *  current brand data, licence and AI text, and new first-screen pictures; no checks or re-planning. Returns the count. */
function gen_refresh_job(int $jobId, ?array $siteIds = null): int
{
    $db = gen_db();
    $j = $db->prepare('SELECT market, spec FROM jobs WHERE id = ?');
    $j->execute([$jobId]);
    $row = $j->fetch() ?: throw new RuntimeException('No such job.');
    $spec = json_decode($row['spec'], true) ?: [];
    if (gen_user_brands($spec)) { // brand data made again (same ids), with the confirmed facts and the project's licence
        gen_materialise_brands($spec['brands'], $row['market'], $jobId, $spec['copy'] ?? 'demo');
    }
    $q = $db->prepare("SELECT id FROM sites WHERE job_id = ? AND status IN ('built', 'failed')");
    $q->execute([$jobId]);
    $ids = array_column($q->fetchAll(), 'id');
    if ($siteIds !== null) {
        $ids = array_values(array_intersect($ids, $siteIds));
    }
    $set = $db->prepare("UPDATE sites SET status = 'refresh', error = NULL WHERE id = ?");
    foreach ($ids as $id) {
        $set->execute([$id]);
    }
    $db->prepare('UPDATE jobs SET outdated = 0' . ($ids ? ", status = 'planned'" : '') . ' WHERE id = ?')->execute([$jobId]);
    if ($ids) {
        gen_spawn($jobId);
    }
    return count($ids);
}

/** Rebuild every site of a job with the same plans and the current engine (and brand data, made again). */
function gen_rebuild_job(int $jobId): void
{
    $db = gen_db();
    $j = $db->prepare('SELECT market, spec FROM jobs WHERE id = ?');
    $j->execute([$jobId]);
    $row = $j->fetchAll()[0] ?? throw new RuntimeException('No such job.');
    $spec = json_decode($row['spec'], true);
    if (gen_user_brands($spec)) { // brand data is placeholder data: re-created with the current generator, same ids
        gen_materialise_brands($spec['brands'], $row['market'], $jobId, $spec['copy'] ?? 'demo');
        $n = (int)($spec['sample_brands'] ?? 0); // and the sample rivals of casino rankings
        $spec['sample_ids'] = $n > 0 ? gen_materialise_brands(gen_sample_brands($spec['brands'], $n, $jobId), $row['market'], $jobId, $spec['copy'] ?? 'demo') : [];
        $db->prepare('UPDATE jobs SET spec = ? WHERE id = ?')->execute([json_encode($spec, JSON_UNESCAPED_UNICODE), $jobId]);
    }
    $db->prepare("UPDATE sites SET status = 'planned', error = NULL, mobile = NULL WHERE job_id = ?")->execute([$jobId]);
    $db->prepare("UPDATE jobs SET status = 'planned', outdated = 0 WHERE id = ?")->execute([$jobId]); // the page polls from now on; it shows the project's licence again
    gen_spawn($jobId);
}

function gen_json($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function gen_body(): array
{
    return json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
}

/** Start the worker for a job in the background. */

function gen_site_row(array $s, array $cat): array
{
    global $GEN_ROOT;
    static $ops = null;
    $ops ??= sx_brand_data($GEN_ROOT, 'operators.json');
    $warnings = json_decode($s['warnings'] ?: '[]', true);
    $blocks = $notes = $promo = [];
    foreach ($warnings as $w) { // "page : Override: ..." = a market block the user built through; shown once per site
        if (preg_match("/^(?:[\w-]+ : )?Override: Promotion type '(\w+)' is \w+ in market '(\w+)'/", $w, $m)) {
            $promo[$m[2]][$m[1]] = true;
        } elseif (preg_match('/^(?:[\w-]+ : )?Override: (.*)$/s', $w, $m)) {
            $blocks[$m[1]] = true;
        } else {
            $notes[] = $w;
        }
    }
    foreach ($promo as $mk => $types) {
        $blocks["Offers market '$mk' does not allow are shown anyway: " . str_replace('_', ' ', implode(', ', array_keys($types))) . '.'] = true;
    }
    $choice = json_decode($s['choice'], true);
    $plan = json_decode($s['plan'], true);
    $labels = [];
    foreach ($choice as $axis => $o) {
        $meaningful = isset($cat[$s['type']]['axes'][$axis]);
        $labels[] = ['axis' => $axis, 'option' => $o, 'label' => $meaningful ? ($cat[$s['type']]['axes'][$axis]['options'][$o] ?? $o) : "$axis: $o", 'auto' => !$meaningful];
    }
    return ['id' => $s['id'], 'type' => $s['type'], 'n' => (int)$s['n'], 'brand' => $s['brand'], 'status' => $s['status'], 'pages' => (int)$s['pages'],
        'preset' => gen_style_label($plan), 'choice' => $labels, 'warnings' => $notes, 'blocks' => array_keys($blocks), 'brand_name' => $ops[$s['brand'] ?? '']['name'] ?? null, 'error' => $s['error'],
        'mobile' => $s['mobile'] ? json_decode($s['mobile'], true) : null, 'url' => "/generated/{$s['id']}/index.html", 'v' => $v = substr(md5($s['status'] . '|' . ($s['checks'] ?? '') . '|' . $s['plan']), 0, 10),
        'thumb' => !empty($s['shot']) && $s['status'] === 'built' ? "/generated/{$s['id']}/_thumb.png?v=$v-" . GEN_THUMB_REV : null, // no file checks on the mounted folder: slow for hundreds of sites
        'brief' => $s['status'] === 'built' ? "/generator/api/sites/{$s['id']}/brief" : null, 'checks' => $s['checks'] ? json_decode($s['checks'], true) : null, 'edited' => !empty($plan['edits']),
        'seo' => !empty($s['seo']) ? json_decode($s['seo'], true) : null, 'content' => !empty($s['content']) ? json_decode($s['content'], true) : null, 'ai' => !empty($plan['ai'])];
}

$path = trim(preg_replace('#^/generator/api#', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');
$method = $_SERVER['REQUEST_METHOD'];
if ($path === 'health') { // before the database: it also answers when the database is what fails
    gen_json(gen_health());
    return;
}
$db = gen_db();
try {
    $seg = explode('/', $path);
    if ($path === 'catalog') {
        $markets = [];
        foreach (glob("$GEN_ROOT/markets/*.json") as $f) {
            $m = sx_json($f);
            $markets[] = ['id' => $m['id'], 'name' => is_array($m['name'] ?? null) ? ($m['name']['en'] ?? reset($m['name'])) : ($m['name'] ?? $m['id'])];
        }
        $presets = [];
        foreach (glob("$GEN_ROOT/tokens/presets/*.json") as $f) {
            $p = sx_json($f);
            $presets[$p['id']] = ['name' => $p['name'], 'accent' => $p['color']['accent'], 'accent2' => $p['color']['accent2'], 'bg' => $p['color']['bg']];
        }
        gen_json(['types' => gen_catalog_full(), 'markets' => $markets, 'presets' => $presets]);
        gen_warm_availability(); // the job form opens next: every market's checks are ready by the time it is switched
    } elseif ($seg[0] === 'sites' && ($seg[2] ?? '') === 'brief' && $method === 'GET') { // one site's AI brief, as a download
        gen_send_site_brief($db, $seg[1]);
    } elseif ($seg[0] === 'jobs' && ($seg[2] ?? '') === 'briefs' && $method === 'GET') { // a job's AI briefs as a zip (?types=a,b)
        gen_send_job_briefs($db, (int)$seg[1], array_values(array_filter(explode(',', (string)($_GET['types'] ?? '')))));
    } elseif ($seg[0] === 'types') { // site types: list, copy, edit, hide, delete, history (generator/types.php)
        gen_types_api($db, $seg, $method);
    } elseif ($seg[0] === 'markets') { // markets: list, add, edit, delete, history (generator/markets.php)
        gen_markets_api($db, $seg, $method);
    } elseif ($path === 'vertex' && $method === 'GET') { // Vertex AI connection (secrets are never sent back)
        gen_json(gen_vertex_public());
    } elseif ($path === 'vertex' && $method === 'PUT') {
        try {
            gen_json(gen_vertex_save(gen_body()));
        } catch (InvalidArgumentException $e) {
            gen_json(['error' => $e->getMessage()], 422);
        }
    } elseif ($path === 'vertex/test' && $method === 'POST') {
        try {
            gen_json(gen_vertex_test());
        } catch (RuntimeException $e) {
            gen_json(['ok' => false, 'error' => $e->getMessage()]);
        }
    } elseif ($seg[0] === 'jobs' && ($seg[2] ?? '') === 'brands' && !isset($seg[3]) && $method === 'GET') { // the job's brand facts
        gen_json(['brands' => gen_bfacts_job($db, (int)$seg[1])]);
    } elseif ($seg[0] === 'jobs' && ($seg[2] ?? '') === 'brands' && isset($seg[3]) && ($seg[4] ?? '') === 'fill' && $method === 'POST') { // facts read from the project by Vertex AI (suggestions, not saved)
        try {
            gen_json(['facts' => (object)gen_bfacts_fill($db, (int)$seg[1], $seg[3])]);
        } catch (InvalidArgumentException $e) {
            gen_json(['error' => $e->getMessage()], 422);
        }
    } elseif ($seg[0] === 'jobs' && ($seg[2] ?? '') === 'brands' && isset($seg[3]) && !isset($seg[4]) && $method === 'PUT') { // confirm facts, then rebuild the built sites
        try {
            gen_bfacts_save($db, (int)$seg[1], $seg[3], (array)(gen_body()['facts'] ?? []));
        } catch (InvalidArgumentException $e) {
            gen_json(['error' => $e->getMessage()], 422);
            return;
        }
        // only the sites that show this brand: its own sites and the multi-brand ones (rankings, reviews) that list it
        $q = $db->prepare('SELECT id FROM sites WHERE job_id = ? AND (brand = ? OR brand IS NULL OR brand = \'\')');
        $q->execute([(int)$seg[1], $seg[3]]);
        gen_json(['ok' => true, 'refresh' => gen_refresh_job((int)$seg[1], array_column($q->fetchAll(), 'id'))]);
    } elseif ($seg[0] === 'sites' && ($seg[2] ?? '') === 'seo' && $method === 'PUT') { // the SEO's verdict on a template: approved, changes (with a note) or none
        gen_content_columns($db);
        $b = gen_body();
        $st = in_array($b['status'] ?? null, ['approved', 'changes'], true) ? $b['status'] : null;
        $seo = $st || trim((string)($b['note'] ?? '')) !== '' ? ['status' => $st, 'note' => mb_substr(trim((string)($b['note'] ?? '')), 0, 2000), 'at' => gen_now()] : null;
        $db->prepare('UPDATE sites SET seo = ? WHERE id = ?')->execute([$seo ? json_encode($seo, JSON_UNESCAPED_UNICODE) : null, $seg[1]]);
        gen_json(['ok' => true, 'seo' => $seo]);
    } elseif ($seg[0] === 'jobs' && ($seg[2] ?? '') === 'seo' && $method === 'POST') { // approve every built site of the job (or withdraw: status none)
        gen_content_columns($db);
        $b = gen_body();
        $seo = ($b['status'] ?? '') === 'approved' ? json_encode(['status' => 'approved', 'note' => '', 'at' => gen_now()]) : null;
        $q = $db->prepare("UPDATE sites SET seo = ? WHERE job_id = ? AND status = 'built'" . ($seo ? " AND (seo IS NULL OR json_extract(seo, '$.status') IS NOT 'changes')" : ''));
        $q->execute([$seo, (int)$seg[1]]);
        gen_json(['ok' => true, 'sites' => $q->rowCount()]);
    } elseif ($seg[0] === 'jobs' && ($seg[2] ?? '') === 'content' && $method === 'POST') { // write the text of the approved sites (all of them, or the ids given)
        gen_content_columns($db);
        if (!gen_vertex_ready(gen_vertex_settings())) {
            gen_json(['error' => 'Vertex AI is not connected. Add an API key or a service account in Settings → Vertex AI.'], 422);
            return;
        }
        $ids = array_values(array_filter((array)(gen_body()['sites'] ?? []), 'is_string'));
        $q = $db->prepare("SELECT id FROM sites WHERE job_id = ? AND status = 'built' AND json_extract(seo, '$.status') = 'approved'
            AND (content IS NULL OR json_extract(content, '$.state') NOT IN ('queued', 'writing'))");
        $q->execute([(int)$seg[1]]);
        $todo = array_values(array_filter(array_column($q->fetchAll(), 'id'), fn($id) => !$ids || in_array($id, $ids, true)));
        if (!$todo) {
            gen_json(['error' => 'No approved, built site is waiting for text. Approve sites first.'], 422);
            return;
        }
        $set = $db->prepare('UPDATE sites SET content = ? WHERE id = ?');
        foreach ($todo as $id) {
            $set->execute([json_encode(['state' => 'queued', 'at' => gen_now()]), $id]);
        }
        gen_mkdir("$GEN_ROOT/data/logs");
        $cmd = gen_php_cmd() . ' ' . escapeshellarg(__DIR__ . '/contentjob.php') . ' ' . (int)$seg[1] . ' >> ' . escapeshellarg("$GEN_ROOT/data/logs/content-" . (int)$seg[1] . '.log') . ' 2>&1';
        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen('start "" /B cmd /c "' . $cmd . '"', 'r'));
        } else {
            exec($cmd . ' &');
        }
        gen_json(['ok' => true, 'sites' => count($todo)]);
    } elseif ($seg[0] === 'sites' && ($seg[2] ?? '') === 'ai' && $method === 'DELETE') { // back to the template's text
        $q = $db->prepare('SELECT job_id, plan FROM sites WHERE id = ?');
        $q->execute([$seg[1]]);
        $row = $q->fetch() ?: throw new RuntimeException('No such site.');
        $plan = json_decode($row['plan'], true) ?: [];
        unset($plan['ai']);
        gen_content_columns($db);
        $db->prepare('UPDATE sites SET plan = ?, content = NULL WHERE id = ?')->execute([json_encode($plan, JSON_UNESCAPED_UNICODE), $seg[1]]);
        gen_refresh_job((int)$row['job_id'], [$seg[1]]);
        gen_json(['ok' => true]);
    } elseif ($path === 'settings' && $method === 'GET') {
        gen_json(['settings' => gen_settings(), 'defaults' => GEN_SETTINGS]);
    } elseif ($path === 'settings' && $method === 'PUT') {
        gen_json(['settings' => gen_save_settings(gen_body())]);
    } elseif ($path === 'availability') {
        gen_json(gen_availability(preg_replace('/[^a-z]/', '', $_GET['market'] ?? 'xx')));
    } elseif ($path === 'projects' && $method === 'GET') {
        gen_json($db->query('SELECT p.*, (SELECT COUNT(*) FROM jobs j WHERE j.project_id = p.id) AS jobs,
            (SELECT COUNT(*) FROM sites s JOIN jobs j ON j.id = s.job_id WHERE j.project_id = p.id) AS sites FROM projects p ORDER BY p.id DESC')->fetchAll());
    } elseif ($path === 'projects' && $method === 'POST') {
        $name = trim(gen_body()['name'] ?? '');
        if ($name === '') {
            gen_json(['error' => 'Give the project a name.'], 422);
            return;
        }
        $db->prepare('INSERT INTO projects (name, created) VALUES (?, ?)')->execute([mb_substr($name, 0, 120), gen_now()]);
        gen_json(['id' => (int)$db->lastInsertId()]);
    } elseif ($seg[0] === 'projects' && ($seg[2] ?? '') === 'files' && !isset($seg[3]) && $method === 'POST') { // upload a document as project information
        $b = gen_body();
        $p = $db->prepare('SELECT id FROM projects WHERE id = ?');
        $p->execute([(int)$seg[1]]);
        $p->fetchColumn() ?: throw new RuntimeException('No such project.');
        $data = base64_decode((string)($b['data'] ?? ''), true);
        if ($data === false) {
            gen_json(['error' => 'The file did not arrive. Try again.'], 422);
            return;
        }
        try {
            gen_json(gen_pfile_add($db, (int)$seg[1], (string)($b['name'] ?? ''), $data));
        } catch (InvalidArgumentException $e) {
            gen_json(['error' => $e->getMessage()], 422);
        }
    } elseif ($seg[0] === 'projects' && ($seg[2] ?? '') === 'files' && isset($seg[3]) && $method === 'GET') { // the text kept from a document
        gen_pfiles_table($db);
        $q = $db->prepare('SELECT name, text FROM project_files WHERE id = ? AND project_id = ?');
        $q->execute([(int)$seg[3], (int)$seg[1]]);
        $f = $q->fetch() ?: throw new RuntimeException('No such file.');
        header('Content-Type: text/plain; charset=utf-8');
        echo $f['text'];
    } elseif ($seg[0] === 'projects' && ($seg[2] ?? '') === 'files' && isset($seg[3]) && $method === 'DELETE') {
        gen_pfiles_table($db);
        $db->prepare('DELETE FROM project_files WHERE id = ? AND project_id = ?')->execute([(int)$seg[3], (int)$seg[1]]);
        gen_json(['ok' => true]);
    } elseif ($seg[0] === 'projects' && isset($seg[1]) && $method === 'GET') {
        $p = $db->prepare('SELECT * FROM projects WHERE id = ?');
        $p->execute([(int)$seg[1]]);
        $project = $p->fetchAll()[0] ?? throw new RuntimeException('No such project.');
        $project['info'] = gen_project_info(json_decode($project['info'] ?? '{}', true) ?: []);
        $j = $db->prepare('SELECT j.id, j.name, j.market, j.status, j.compare, j.created, j.outdated, (SELECT COUNT(*) FROM sites s WHERE s.job_id = j.id) AS sites,
            (SELECT COUNT(*) FROM sites s WHERE s.job_id = j.id AND s.status = \'built\') AS built FROM jobs j WHERE j.project_id = ? ORDER BY j.id DESC');
        $j->execute([$project['id']]);
        gen_json(['project' => $project, 'jobs' => $j->fetchAll(), 'files' => gen_pfiles_list($db, (int)$project['id'])]);
    } elseif ($seg[0] === 'projects' && isset($seg[1]) && $method === 'PUT') { // rename the project, edit its information for the AI briefs
        $b = gen_body();
        $name = trim((string)($b['name'] ?? ''));
        if ($name === '') {
            gen_json(['error' => 'Give the project a name.'], 422);
            return;
        }
        $old = $db->prepare('SELECT info FROM projects WHERE id = ?');
        $old->execute([(int)$seg[1]]);
        $old = gen_project_info(json_decode((string)$old->fetchColumn(), true) ?: []);
        $info = gen_project_info($b['info'] ?? []);
        $q = $db->prepare('UPDATE projects SET name = ?, info = ? WHERE id = ?');
        $q->execute([mb_substr($name, 0, 120), json_encode($info, JSON_UNESCAPED_UNICODE), (int)$seg[1]]);
        if (!$q->rowCount()) {
            throw new RuntimeException('No such project.');
        }
        $outdated = 0;
        $licKeys = ['licence_regulator', 'licence_no', 'licence_short'];
        if (array_intersect_key($old, array_flip($licKeys)) !== array_intersect_key($info, array_flip($licKeys))) {
            // the licence is on the pages: the jobs with built sites are marked, and the user picks which to rebuild
            $o = $db->prepare("UPDATE jobs SET outdated = 1 WHERE project_id = ? AND EXISTS (SELECT 1 FROM sites s WHERE s.job_id = jobs.id AND s.status IN ('built', 'failed'))");
            $o->execute([(int)$seg[1]]);
            $outdated = $o->rowCount();
        }
        gen_json(['ok' => true, 'outdated' => $outdated]);
    } elseif ($seg[0] === 'projects' && ($seg[2] ?? '') === 'outdated' && $method === 'POST') { // rebuild the chosen out-of-date jobs, and leave the others as they are
        $b = gen_body();
        $rebuild = array_map('intval', (array)($b['rebuild'] ?? []));
        $refresh = array_map('intval', (array)($b['refresh'] ?? []));
        $q = $db->prepare('SELECT id FROM jobs WHERE project_id = ? AND outdated = 1');
        $q->execute([(int)$seg[1]]);
        foreach (array_column($q->fetchAll(), 'id') as $jid) {
            if (in_array((int)$jid, $refresh, true)) {
                gen_refresh_job((int)$jid);
            } elseif (in_array((int)$jid, $rebuild, true)) {
                gen_rebuild_job((int)$jid);
            } elseif (!empty($b['dismiss'])) {
                $db->prepare('UPDATE jobs SET outdated = 0 WHERE id = ?')->execute([(int)$jid]);
            }
        }
        gen_json(['ok' => true]);
    } elseif ($seg[0] === 'projects' && isset($seg[1]) && $method === 'DELETE') {
        foreach ($db->query('SELECT s.id FROM sites s JOIN jobs j ON j.id = s.job_id WHERE j.project_id = ' . (int)$seg[1])->fetchAll() as $r) {
            gen_remove_files($r['id']);
        }
        $db->prepare('DELETE FROM projects WHERE id = ?')->execute([(int)$seg[1]]);
        gen_purge_deleted_jobs(); // its jobs' brands, articles and logs
        gen_json(['ok' => true]);
    } elseif ($path === 'jobs' && $method === 'POST') {
        $b = gen_body();
        [$name, $spec, $err] = gen_job_input($b);
        if ($err || empty($b['project_id'])) {
            gen_json(['error' => $err ?: 'No project.'], 422);
            return;
        }
        $db->prepare('INSERT INTO jobs (project_id, name, market, spec, status, created) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([(int)$b['project_id'], $name, $spec['market'], json_encode($spec, JSON_UNESCAPED_UNICODE), 'planned', gen_now()]);
        $jobId = (int)$db->lastInsertId();
        $n = gen_replan_job($jobId, $spec);
        gen_json(['id' => $jobId, 'sites' => $n]);
    } elseif ($seg[0] === 'jobs' && isset($seg[1]) && !isset($seg[2]) && $method === 'PUT') { // edit the job, then rebuild it
        $jobId = (int)$seg[1];
        $cur = $db->prepare('SELECT status FROM jobs WHERE id = ?');
        $cur->execute([$jobId]);
        $status = $cur->fetchAll(PDO::FETCH_COLUMN)[0] ?? false;
        if ($status === false) {
            throw new RuntimeException('No such job.');
        }
        if (in_array($status, ['planned', 'queued', 'running', 'checking'], true)) {
            gen_json(['error' => 'The job is still building. Edit it when it is done.'], 409);
            return;
        }
        [$name, $spec, $err] = gen_job_input(gen_body());
        if ($err) {
            gen_json(['error' => $err], 422);
            return;
        }
        $db->prepare('UPDATE jobs SET name = ?, market = ?, spec = ?, status = ? WHERE id = ?')
            ->execute([$name, $spec['market'], json_encode($spec, JSON_UNESCAPED_UNICODE), 'planned', $jobId]);
        $n = gen_replan_job($jobId, $spec);
        gen_json(['id' => $jobId, 'sites' => $n]);
    } elseif ($seg[0] === 'jobs' && isset($seg[1]) && !isset($seg[2]) && $method === 'GET') {
        $j = $db->prepare('SELECT j.*, p.name AS project_name FROM jobs j JOIN projects p ON p.id = j.project_id WHERE j.id = ?');
        $j->execute([(int)$seg[1]]);
        $job = $j->fetchAll()[0] ?? throw new RuntimeException('No such job.');
        $job['status'] = gen_heal_job((int)$job['id'], $job['status']);
        $busy = gen_busy_job();
        $job['busy'] = $busy && (int)$busy['id'] !== (int)$job['id'] ? $busy : null;
        $cat = gen_catalog_full();
        gen_content_columns($db);
        $rows = $db->prepare('SELECT id, job_id, n, type, brand, choice, plan, status, warnings, error, pages, mobile, checks, created, seo, content, visual IS NOT NULL AS shot
            FROM sites WHERE job_id = ? ORDER BY type, n'); // not the code fingerprints and screenshot prints: megabytes for a big job
        $rows->execute([$job['id']]);
        $job['spec'] = json_decode($job['spec'], true);
        unset($job['log']);
        $job['facts_left'] = gen_bfacts_unconfirmed($db, (int)$job['id']); // brand facts still example data: {brand name: [labels]}
        gen_json(['job' => $job, 'types' => array_map(fn($t) => $t['label'], $cat), 'sites' => array_map(fn($s) => gen_site_row($s, $cat), $rows->fetchAll())]);
    } elseif ($seg[0] === 'jobs' && ($seg[2] ?? '') === 'diff') {
        // worked out in the background (seconds to a minute on a big network); the page asks again until it is ready
        $r = gen_job_diff((int)$seg[1], false);
        if ($r === null) {
            gen_spawn_diff((int)$seg[1]);
            $r = ['pending' => true];
        }
        gen_json($r);
    } elseif ($seg[0] === 'jobs' && ($seg[2] ?? '') === 'retry' && $method === 'POST') {
        $db->prepare("UPDATE sites SET status = 'planned', error = NULL WHERE job_id = ? AND status = 'failed'")->execute([(int)$seg[1]]);
        $db->prepare("UPDATE jobs SET status = 'planned' WHERE id = ?")->execute([(int)$seg[1]]); // the page polls from now on
        gen_spawn((int)$seg[1]);
        gen_json(['ok' => true]);
    } elseif ($seg[0] === 'jobs' && ($seg[2] ?? '') === 'editor' && isset($seg[3]) && in_array($method, ['GET', 'PUT'], true)) { // bulk editor of a type in a job
        $jobId = (int)$seg[1];
        $type = preg_replace('/[^a-z-]/', '', $seg[3]);
        $q = $db->prepare('SELECT id, name, status, spec FROM jobs WHERE id = ?');
        $q->execute([$jobId]);
        $job = $q->fetchAll()[0] ?? throw new RuntimeException('No such job.');
        $spec = json_decode($job['spec'], true);
        if (!isset($spec['types'][$type])) {
            throw new RuntimeException('This job has no sites of that type.');
        }
        if ($method === 'GET') {
            $n = $db->prepare('SELECT COUNT(*) FROM sites WHERE job_id = ? AND type = ?');
            $n->execute([$jobId, $type]);
            gen_json(['job' => ['id' => $jobId, 'name' => $job['name'], 'status' => $job['status']], 'type' => $type, 'label' => gen_catalog()[$type]['label'],
                'sites' => (int)$n->fetchColumn(), 'schema' => gen_editor_schema($type), 'edits' => $spec['edits'][$type] ?? (object)[]]);
            return;
        }
        if (in_array($job['status'], ['planned', 'queued', 'running', 'checking'], true)) {
            gen_json(['error' => 'The job is still building. Edit it when it is done.'], 409);
            return;
        }
        [$edits, $err] = gen_clean_edits(gen_body(), $type);
        if ($err) {
            gen_json(['error' => $err], 422);
            return;
        }
        $spec['edits'][$type] = $edits;
        $db->prepare('UPDATE jobs SET spec = ?, status = ? WHERE id = ?')->execute([json_encode($spec, JSON_UNESCAPED_UNICODE), 'planned', $jobId]);
        $db->prepare("UPDATE sites SET status = 'planned', error = NULL, mobile = NULL WHERE job_id = ? AND type = ?")->execute([$jobId, $type]);
        gen_spawn($jobId);
        gen_json(['ok' => true]);
    } elseif ($seg[0] === 'sites' && ($seg[2] ?? '') === 'editor' && in_array($method, ['GET', 'PUT'], true)) { // one site's own edits
        $q = $db->prepare('SELECT s.id, s.type, s.plan, s.job_id, j.name, j.status, j.spec FROM sites s JOIN jobs j ON j.id = s.job_id WHERE s.id = ?');
        $q->execute([$seg[1]]);
        $row = $q->fetchAll()[0] ?? throw new RuntimeException('No such site.');
        $plan = json_decode($row['plan'], true);
        $spec = json_decode($row['spec'], true);
        if ($method === 'GET') {
            gen_json(['job' => ['id' => (int)$row['job_id'], 'name' => $row['name'], 'status' => $row['status']], 'site' => $row['id'], 'type' => $row['type'],
                'label' => gen_catalog()[$row['type']]['label'], 'schema' => gen_editor_schema($row['type']), 'edits' => $plan['edits'] ?? (object)[],
                'type_edits' => $spec['edits'][$row['type']] ?? (object)[], 'url' => "/generated/{$row['id']}/index.html",
                'current' => gen_site_current($row['id'], gen_editor_schema($row['type']))]);
            return;
        }
        if (in_array($row['status'], ['planned', 'queued', 'running', 'checking'], true)) {
            gen_json(['error' => 'The job is still building. Edit it when it is done.'], 409);
            return;
        }
        [$edits, $err] = gen_clean_edits(gen_body(), $row['type']);
        if ($err) {
            gen_json(['error' => $err], 422);
            return;
        }
        $plan['edits'] = $edits;
        $db->prepare("UPDATE sites SET plan = ?, status = 'planned', error = NULL, mobile = NULL WHERE id = ?")->execute([json_encode($plan), $row['id']]);
        $db->prepare("UPDATE jobs SET status = 'planned' WHERE id = ?")->execute([(int)$row['job_id']]);
        gen_spawn((int)$row['job_id']);
        gen_json(['ok' => true]);
    } elseif ($seg[0] === 'jobs' && ($seg[2] ?? '') === 'compare' && $method === 'PUT') { // count the job in other jobs' comparisons, or not
        $db->prepare('UPDATE jobs SET compare = ? WHERE id = ?')->execute([empty(gen_body()['compare']) ? 0 : 1, (int)$seg[1]]);
        gen_json(['ok' => true]);
    } elseif ($seg[0] === 'jobs' && ($seg[2] ?? '') === 'restyle' && $method === 'POST') { // new looks, same variations, then rebuild
        gen_restyle_job((int)$seg[1]);
        $db->prepare("UPDATE sites SET status = 'planned', error = NULL, mobile = NULL WHERE job_id = ?")->execute([(int)$seg[1]]);
        $db->prepare("UPDATE jobs SET status = 'planned' WHERE id = ?")->execute([(int)$seg[1]]);
        gen_spawn((int)$seg[1]);
        gen_json(['ok' => true]);
    } elseif ($seg[0] === 'jobs' && ($seg[2] ?? '') === 'refresh' && $method === 'POST') { // quick refresh of every built site
        gen_json(['ok' => true, 'sites' => gen_refresh_job((int)$seg[1])]);
    } elseif ($seg[0] === 'jobs' && ($seg[2] ?? '') === 'rebuild' && $method === 'POST') { // same plans, current engine
        gen_rebuild_job((int)$seg[1]);
        gen_json(['ok' => true]);
    } elseif ($seg[0] === 'jobs' && isset($seg[1]) && $method === 'DELETE') {
        $r = $db->prepare('SELECT id FROM sites WHERE job_id = ?');
        $r->execute([(int)$seg[1]]);
        foreach ($r->fetchAll() as $row) {
            gen_remove_files($row['id']);
        }
        $db->prepare('DELETE FROM jobs WHERE id = ?')->execute([(int)$seg[1]]);
        gen_purge_deleted_jobs(); // its brands, articles and logs
        gen_json(['ok' => true]);
    } elseif ($seg[0] === 'sites' && ($seg[2] ?? '') === 'mobile' && $method === 'POST') {
        $b = gen_body();
        $m = ['overflow' => (int)($b['overflow'] ?? 0), 'cta' => isset($b['cta']) ? (int)$b['cta'] : null, 'fold' => 667, 'checked' => gen_now()];
        $db->prepare('UPDATE sites SET mobile = ? WHERE id = ?')->execute([json_encode($m), $seg[1]]);
        gen_json(['ok' => true]);
    } else {
        gen_json(['error' => 'Not found'], 404);
    }
} catch (Throwable $e) {
    gen_json(['error' => $e->getMessage()], 400);
}

/** Name and spec from the job form. Returns [name, spec, error]. */
function gen_job_input(array $b): array
{
    global $GEN_ROOT;
    $types = [];
    foreach ((array)($b['types'] ?? []) as $t => $v) {
        $count = max(0, min(50, (int)($v['count'] ?? 0)));
        if ($count && isset(gen_catalog()[$t])) {
            $types[$t] = ['count' => $count, 'variations' => array_map(fn($o) => array_values(array_filter((array)$o, 'is_string')), (array)($v['variations'] ?? []))];
        }
    }
    $spec = ['market' => preg_replace('/[^a-z]/', '', $b['market'] ?? ''), 'brands' => gen_clean_brands((array)($b['brands'] ?? [])), 'types' => $types,
        'colors' => array_filter(array_map(fn($c) => preg_match('/^#[0-9a-fA-F]{6}$/', (string)$c) ? $c : null, (array)($b['colors'] ?? []))),
        'copy' => ($b['copy'] ?? 'demo') === 'placeholder' ? 'placeholder' : 'demo',
        'rows' => in_array($b['rows'] ?? '', ['swipe', 'stack', 'both'], true) ? $b['rows'] : 'both',
        'disclosure' => in_array($b['disclosure'] ?? 'off', ['top', 'footer', 'off'], true) ? $b['disclosure'] : 'off',
        'sample_brands' => max(0, min(count(GEN_SAMPLE_NAMES), (int)($b['sample_brands'] ?? 7)))];
    $name = mb_substr(trim($b['name'] ?? ''), 0, 120);
    $err = match (true) {
        $name === '' => 'Give the job a name.',
        !is_file("$GEN_ROOT/markets/{$spec['market']}.json") => 'Pick a market.',
        !$types => 'Tick at least one site type.',
        !$spec['brands'] => 'Add at least one brand.',
        default => null,
    };
    return [$name, $spec, $err];
}

/** Jobs made before user brands kept a list of demo brand ids instead. */
function gen_user_brands(array $spec): bool
{
    return !empty($spec['brands']) && is_array($spec['brands'][0] ?? null);
}

/**
 * Write the job's brands, plan its sites and start the worker. Sites that keep their id (same type and number) keep
 * their seed and preset, so an edit changes what was edited and not every site's look. Removed sites lose their files.
 */
function gen_replan_job(int $jobId, array $spec): int
{
    $db = gen_db();
    $spec['brand_ids'] = gen_materialise_brands($spec['brands'], $spec['market'], $jobId, $spec['copy'] ?? 'demo');
    $n = (int)($spec['sample_brands'] ?? 0); // made-up rivals in casino rankings, next to the job's own brands
    $spec['sample_ids'] = $n > 0 && gen_user_brands($spec) ? gen_materialise_brands(gen_sample_brands($spec['brands'], $n, $jobId), $spec['market'], $jobId, $spec['copy'] ?? 'demo') : [];
    $db->prepare('UPDATE jobs SET spec = ? WHERE id = ?')->execute([json_encode($spec, JSON_UNESCAPED_UNICODE), $jobId]);
    $old = [];
    $q = $db->prepare('SELECT id, plan FROM sites WHERE job_id = ?');
    $q->execute([$jobId]);
    foreach ($q->fetchAll() as $r) {
        $old[$r['id']] = json_decode($r['plan'], true);
    }
    $sites = gen_plan_job($jobId, $spec);
    $db->prepare('DELETE FROM sites WHERE job_id = ?')->execute([$jobId]);
    $ins = $db->prepare('INSERT INTO sites (id, job_id, n, type, brand, choice, plan, status, created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $keep = [];
    foreach ($sites as $s) {
        $id = gen_site_id($jobId, $s['type'], $s['n']);
        if (isset($old[$id])) {
            $s['plan']['seed'] = $old[$id]['seed'];
            foreach (['preset', 'flip', 'hue'] as $k) {
                if (array_key_exists($k, $old[$id])) {
                    $s['plan'][$k] = $old[$id][$k];
                }
            }
        }
        $keep[$id] = true;
        $ins->execute([$id, $jobId, $s['n'], $s['type'], $s['brand'], json_encode($s['choice']), json_encode($s['plan']), 'planned', gen_now()]);
    }
    foreach (array_diff_key($old, $keep) as $id => $_) {
        gen_remove_files($id);
    }
    gen_spawn($jobId);
    return count($sites);
}

function gen_remove_files(string $id): void
{
    global $GEN_ROOT;
    if (!preg_match('/^j\d+-[a-z-]+-\d+$/', $id)) {
        return;
    }
    foreach (glob("$GEN_ROOT/dist/generated/$id/*") as $f) {
        @unlink($f);
    }
    @rmdir("$GEN_ROOT/dist/generated/$id");
    @unlink("$GEN_ROOT/data/configs/$id.json");
}

