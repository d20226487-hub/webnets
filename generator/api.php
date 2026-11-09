<?php
/** Generator JSON API, routed from generator/index.php: /generator/api/<path>. */
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/diff.php';
require_once __DIR__ . '/briefs.php';

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
        'brief' => $s['status'] === 'built' ? "/generator/api/sites/{$s['id']}/brief" : null, 'checks' => $s['checks'] ? json_decode($s['checks'], true) : null, 'edited' => !empty($plan['edits'])];
}

$path = trim(preg_replace('#^/generator/api#', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');
$method = $_SERVER['REQUEST_METHOD'];
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
    } elseif ($seg[0] === 'projects' && isset($seg[1]) && $method === 'GET') {
        $p = $db->prepare('SELECT * FROM projects WHERE id = ?');
        $p->execute([(int)$seg[1]]);
        $project = $p->fetchAll()[0] ?? throw new RuntimeException('No such project.');
        $j = $db->prepare('SELECT j.id, j.name, j.market, j.status, j.compare, j.created, (SELECT COUNT(*) FROM sites s WHERE s.job_id = j.id) AS sites,
            (SELECT COUNT(*) FROM sites s WHERE s.job_id = j.id AND s.status = \'built\') AS built FROM jobs j WHERE j.project_id = ? ORDER BY j.id DESC');
        $j->execute([$project['id']]);
        gen_json(['project' => $project, 'jobs' => $j->fetchAll()]);
    } elseif ($seg[0] === 'projects' && isset($seg[1]) && $method === 'DELETE') {
        foreach ($db->query('SELECT s.id FROM sites s JOIN jobs j ON j.id = s.job_id WHERE j.project_id = ' . (int)$seg[1])->fetchAll() as $r) {
            gen_remove_files($r['id']);
        }
        $db->prepare('DELETE FROM projects WHERE id = ?')->execute([(int)$seg[1]]);
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
        $rows = $db->prepare('SELECT id, job_id, n, type, brand, choice, plan, status, warnings, error, pages, mobile, checks, created, visual IS NOT NULL AS shot
            FROM sites WHERE job_id = ? ORDER BY type, n'); // not the code fingerprints and screenshot prints: megabytes for a big job
        $rows->execute([$job['id']]);
        $job['spec'] = json_decode($job['spec'], true);
        unset($job['log']);
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
    } elseif ($seg[0] === 'jobs' && ($seg[2] ?? '') === 'rebuild' && $method === 'POST') { // same plans, current engine
        $j = $db->prepare('SELECT market, spec FROM jobs WHERE id = ?');
        $j->execute([(int)$seg[1]]);
        $row = $j->fetchAll()[0] ?? throw new RuntimeException('No such job.');
        $spec = json_decode($row['spec'], true);
        if (gen_user_brands($spec)) { // brand data is placeholder data: re-created with the current generator, same ids
            gen_materialise_brands($spec['brands'], $row['market'], (int)$seg[1], $spec['copy'] ?? 'demo');
            $n = (int)($spec['sample_brands'] ?? 0); // and the sample rivals of casino rankings
            $spec['sample_ids'] = $n > 0 ? gen_materialise_brands(gen_sample_brands($spec['brands'], $n, (int)$seg[1]), $row['market'], (int)$seg[1], $spec['copy'] ?? 'demo') : [];
            $db->prepare('UPDATE jobs SET spec = ? WHERE id = ?')->execute([json_encode($spec, JSON_UNESCAPED_UNICODE), (int)$seg[1]]);
        }
        $db->prepare("UPDATE sites SET status = 'planned', error = NULL, mobile = NULL WHERE job_id = ?")->execute([(int)$seg[1]]);
        $db->prepare("UPDATE jobs SET status = 'planned' WHERE id = ?")->execute([(int)$seg[1]]); // the page polls from now on
        gen_spawn((int)$seg[1]);
        gen_json(['ok' => true]);
    } elseif ($seg[0] === 'jobs' && isset($seg[1]) && $method === 'DELETE') {
        $r = $db->prepare('SELECT id FROM sites WHERE job_id = ?');
        $r->execute([(int)$seg[1]]);
        foreach ($r->fetchAll() as $row) {
            gen_remove_files($row['id']);
        }
        $db->prepare('DELETE FROM jobs WHERE id = ?')->execute([(int)$seg[1]]);
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

