<?php
/**
 * Multi-language jobs: the job's languages, each with the market whose rules it follows (spec "langs": {default,
 * list: [{lang, market}]}), the language versions of a site (built from its main-language config with the
 * translated text on top) and the translation step (Vertex AI, after the main text: generator/translatejob.php).
 *
 * A site's translations live in its plan: "tr": {<lang>: {<field path>: {s: source text, t: translation}}}. A
 * translation counts only while its source text is unchanged, so new or rewritten text waits for the next run.
 */

require_once __DIR__ . '/../lib/langs.php';

const GEN_LANG_NAMES = ['en' => 'English', 'ru' => 'Russian', 'kk' => 'Kazakh', 'pt' => 'Portuguese'];

/** The job's languages: [code => market], the main one first; [] for a one-language job. */
function gen_job_langs(array $spec): array
{
    $l = $spec['langs'] ?? null;
    if (!is_array($l) || count($l['list'] ?? []) < 2) {
        return [];
    }
    $out = [];
    foreach ($l['list'] as $x) {
        $out[$x['lang']] = $x['market'];
    }
    $def = isset($out[$l['default'] ?? '']) ? $l['default'] : array_key_first($out);
    return [$def => $out[$def]] + $out;
}

/** The job form's languages, checked: [spec langs or null, error or null]. */
function gen_job_langs_input(array $in): array
{
    global $GEN_ROOT;
    if (empty($in['on'])) {
        return [null, null];
    }
    $list = [];
    foreach ((array)($in['list'] ?? []) as $x) {
        $c = preg_replace('/[^a-z]/', '', (string)($x['lang'] ?? ''));
        $mk = preg_replace('/[^a-z]/', '', (string)($x['market'] ?? ''));
        if ($c === '' || $mk === '') {
            continue;
        }
        if (isset($list[$c])) {
            return [null, 'Each language can be added once.'];
        }
        $file = "$GEN_ROOT/markets/$mk.json";
        if (!is_file($file) || !in_array($c, sx_json($file)['locales'] ?? [], true)) {
            return [null, 'A language must be one of its market’s languages.'];
        }
        $list[$c] = $mk;
    }
    if (count($list) < 2) {
        return [null, 'Add at least two languages, or turn multi-language sites off.'];
    }
    $def = isset($list[$in['default'] ?? '']) ? $in['default'] : array_key_first($list);
    $rows = [['lang' => $def, 'market' => $list[$def]]];
    foreach ($list as $c => $mk) {
        if ($c !== $def) {
            $rows[] = ['lang' => $c, 'market' => $mk];
        }
    }
    return [['default' => $def, 'list' => $rows], null];
}

/** Where a site's language switcher goes and how it looks: spread over the job's sites (by the plan's slot). */
function gen_lang_ui(array $plan, string $id): array
{
    $combos = [];
    foreach (SX_LANG_LOOKS as $place => $looks) {
        foreach ($looks as $look) {
            $combos[] = ['place' => $place, 'look' => $look];
        }
    }
    $n = isset($plan['slot']) ? (int)$plan['slot'] * 5 + 3 : crc32("$id|lang");
    return $combos[$n % count($combos)];
}

/** The switcher's list: [{code, name, hreflang}] for the languages given ([code => market]), the main one first. */
function gen_lang_list(array $langs): array
{
    global $GEN_ROOT;
    $out = [];
    foreach ($langs as $c => $mk) {
        $out[] = ['code' => $c, 'name' => SX_LANG_NAMES[$c] ?? $c, 'hreflang' => sx_html_lang($c, $mk, sx_json("$GEN_ROOT/markets/$mk.json"))];
    }
    return $out;
}

/**
 * A config's text to translate: [path => text], path segments joined by a tab. Copy, page titles, labels and the
 * licence badge; settings, ids, links, codes and figures alone are left as they are.
 */
function gen_tr_fields(array $cfg): array
{
    $out = [];
    $walk = function ($v, string $key, array $path) use (&$walk, &$out) {
        if (is_array($v)) {
            foreach ($v as $k => $x) {
                $walk($x, is_int($k) ? $key : (string)$k, array_merge($path, [(string)$k]));
            }
            return;
        }
        if (!is_string($v) || in_array($key, GEN_KEEP, true) && !in_array($key, ['unit', 'value'], true) || str_starts_with($key, 'show_') || preg_match('~^(https?:|#|/|page:|mailto:|tel:)~', $v)) {
            return;
        }
        $t = trim(strip_tags($v));
        // an id, a setting, a code; a unit or value only when it is words («мин», «~10 мин»), not a figure or a symbol
        if (!preg_match('/\p{L}/u', $t) || preg_match('/^[a-z0-9_.-]+$/', $t) || preg_match('/^[A-Z0-9_-]+$/', $t) || preg_match('/^П[12]$/u', $t) || in_array($key, ['unit', 'value'], true) && !preg_match('/\p{L}{2}/u', $t)) {
            return;
        }
        $out[implode("\t", $path)] = $v;
    };
    foreach (['content', 'meta', 'page', 'cta_label', 'cta_short', 'brand_text', 'after'] as $k) {
        if (isset($cfg[$k])) {
            $walk($cfg[$k], $k, [$k]);
        }
    }
    if (isset($cfg['licence']['short'])) {
        $walk($cfg['licence']['short'], 'short', ['licence', 'short']);
    }
    return $out;
}

/** A config with a language's translations on top: [config, how many texts have no current translation]. */
function gen_tr_apply(array $cfg, array $tr): array
{
    $miss = 0;
    foreach (gen_tr_fields($cfg) as $pk => $text) {
        $t = $tr[$pk] ?? null;
        if (!is_array($t) || ($t['s'] ?? null) !== $text || !is_string($t['t'] ?? null)) {
            $miss++;
            continue;
        }
        $ref = &$cfg;
        foreach (explode("\t", $pk) as $k) {
            $ref = &$ref[$k];
        }
        $ref = $t['t'];
        unset($ref);
    }
    return [$cfg, $miss];
}

/**
 * The language versions of a site that has translations: [[code => config], warnings]. Each is the main config in
 * that language, under its market's rules ("rules_market").
 */
function gen_site_lang_configs(array $site, array $cfg, array $langs): array
{
    $plan = is_array($site['plan']) ? $site['plan'] : (json_decode($site['plan'], true) ?: []);
    $out = $warn = [];
    foreach (array_slice($langs, 1, null, true) as $c => $mk) {
        $tr = $plan['tr'][$c] ?? null;
        if (!$tr) {
            continue;
        }
        [$a, $miss] = gen_tr_apply($cfg, $tr);
        $a['locale'] = $c;
        $a['rules_market'] = $mk;
        if ($c !== 'ru' && isset($a['content'])) { // odds columns: «П1 / П2» is Russian notation
            array_walk_recursive($a['content'], function (&$v) {
                if (is_string($v) && preg_match('/^П([12])$/u', $v, $mm)) {
                    $v = $mm[1];
                }
            });
        }
        if (!empty($cfg['site']['url'])) { // canonical links and the sitemap: the version's own folder
            $a['site']['url'] = rtrim($cfg['site']['url'], '/') . "/$c";
        }
        unset($a['slugs']);
        if ($miss) {
            $warn[] = "$c: $miss texts are not translated yet (new or changed since the last translation): they show in the main language. Press «Translate» on this site’s card.";
        }
        $out[$c] = $a;
    }
    return [$out, $warn];
}

/* ---------------------------------------------------------------- the translation step */

function gen_tr_columns(PDO $db): void
{
    if (!in_array('tr', array_column($db->query('PRAGMA table_info(sites)')->fetchAll(), 'name'), true)) {
        gen_db_add_column($db, 'ALTER TABLE sites ADD COLUMN tr TEXT');
    }
    $db->exec('CREATE TABLE IF NOT EXISTS tr_cache (lang TEXT NOT NULL, src TEXT NOT NULL, out TEXT NOT NULL, PRIMARY KEY (lang, src))');
}

function gen_tr_lock(int $jobId): string
{
    return sys_get_temp_dir() . "/satellite-generator-translate-$jobId.lock";
}

function gen_tr_spawn(int $jobId): void
{
    global $GEN_ROOT;
    gen_mkdir("$GEN_ROOT/data/logs");
    $cmd = gen_php_cmd() . ' ' . escapeshellarg(__DIR__ . '/translatejob.php') . ' ' . $jobId . ' >> ' . escapeshellarg("$GEN_ROOT/data/logs/translate-$jobId.log") . ' 2>&1';
    if (PHP_OS_FAMILY === 'Windows') {
        pclose(popen('start "" /B cmd /c "' . $cmd . '"', 'r'));
    } else {
        exec($cmd . ' &');
    }
}

/** Sites waiting for translation while no run is writing (a run that died): start one. Called when the job page loads. */
function gen_tr_heal(PDO $db, int $jobId): void
{
    gen_tr_columns($db);
    $q = $db->prepare("SELECT COUNT(*) FROM sites WHERE job_id = ? AND json_extract(tr, '$.state') IN ('queued', 'writing')");
    $q->execute([$jobId]);
    if (!(int)$q->fetchColumn()) {
        return;
    }
    $h = @fopen(gen_tr_lock($jobId), 'c');
    if ($h && flock($h, LOCK_EX | LOCK_NB)) {
        flock($h, LOCK_UN);
        fclose($h);
        gen_tr_spawn($jobId);
    }
}

/** The instructions for translating into $to for market $mk. */
function gen_tr_system(string $from, string $to, string $mk): string
{
    global $GEN_ROOT;
    $m = sx_json("$GEN_ROOT/markets/$mk.json");
    $name = is_array($m['name'] ?? null) ? ($m['name']['en'] ?? reset($m['name'])) : ($m['name'] ?? strtoupper($mk));
    $src = $from === '' ? 'its language' : (GEN_LANG_NAMES[$from] ?? $from); // '': the project's own text, in whatever language it was written
    $dst = GEN_LANG_NAMES[$to] ?? $to;
    $age = (int)($m['min_age'] ?? 18);
    return implode("\n", [
        "You are a native $dst copywriter and professional translator of gambling affiliate websites for $name.",
        "Translate website copy from $src into $dst, the way a good $dst website for players in $name says it.",
        '',
        'Rules:',
        "- Translate the meaning naturally and idiomatically, not word for word. Keep each text about as long as the source: it fills the same place in the layout. Headings and button labels stay short.",
        '- Keep HTML tags and their attributes exactly; translate only the text between them.',
        '- Keep placeholders in curly braces ({item}, {brand}, {n} …) exactly as they are.',
        '- Keep brand names, product and game titles, promo codes, URLs and e-mail addresses unchanged.',
        '- Keep every number, amount, percentage and currency exactly as in the source. Do not convert currencies, round or change figures.',
        "- A text already written in $dst comes back as it is.",
        '- Add nothing the source does not say (no claims, promises, offers or figures) and leave nothing out.',
        "- Gambling in $name is for players aged $age and over: any age in the copy is $age.",
        "- Use the words players in $name search for, and the form of address usual for gambling websites in $dst.",
        '- The site adds its own licence and responsible-gambling notices in this language: do not add them.',
        '',
        'Answer with JSON only: {"<key>": "<translation>", …} with every key given.',
    ]);
}

/** The minimum ages of two markets (a translation may put the second's for the first's). */
function gen_tr_ages(string $from, string $to): array
{
    global $GEN_ROOT;
    return array_values(array_unique(array_map(fn($m) => (int)(sx_json("$GEN_ROOT/markets/$m.json")['min_age'] ?? 18), [$from, $to])));
}

/** The numbers a text states, separators removed ("5 500 000 ₸" and "5,500,000 ₸" are the same), sorted. */
function gen_tr_numbers(string $text): array
{
    preg_match_all('/\d(?:[\d\s\x{00A0}\x{202F}.,]*\d)?/u', strip_tags($text), $m);
    $n = array_map(fn($x) => ltrim(preg_replace('/\D/', '', $x), '0') ?: '0', $m[0]);
    sort($n);
    return $n;
}

/**
 * Whether a translation keeps the source's figures: every number of the source, and no other, except the age (the
 * target market's age may replace the source market's: $ages) and 24, 7 and 365 a language may spell out
 * («круглосуточно» → "24/7").
 */
function gen_tr_figures_ok(string $src, string $t, array $ages): bool
{
    $ages = array_map('strval', $ages);
    $a = array_values(array_diff(gen_tr_numbers($src), $ages));
    $b = array_values(array_diff(gen_tr_numbers($t), $ages));
    $have = array_count_values($a);
    foreach ($b as $i => $n) {
        if (in_array($n, ['24', '7', '365'], true) && ($have[$n] ?? 0) < count(array_keys($b, $n, true))) {
            unset($b[$i]);
        }
    }
    $b = array_values($b);
    sort($a);
    sort($b);
    return $a === $b;
}

/** Requests for texts to translate ([key => text]) into $to, in groups of GEN_AI_BATCH. */
function gen_tr_calls(array $texts, string $from, string $to, string $mk, array $meta, string $note = ''): array
{
    $system = gen_tr_system($from, $to, $mk);
    $calls = [];
    foreach (array_chunk($texts, GEN_AI_BATCH, true) as $i => $chunk) {
        $calls[$i] = ['keys' => array_keys($chunk), 'call' => ['prompt' => ($note !== '' ? "$note\n\n" : '') . "Translate these texts. JSON: key → text.\n\n```json\n"
            . json_encode($chunk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n```", 'system' => $system, 'json' => true,
            'max' => gen_vertex_max('tok_translate'), 'meta' => $meta]];
    }
    return $calls;
}

/** A reply read: [key => translation] for the keys asked, the ones whose figures differ from the source left out ([ok, bad]). */
function gen_tr_read($r, array $keys, array $texts, array $ages = []): array
{
    if ($r instanceof Throwable) {
        throw $r;
    }
    $json = trim($r['text'] ?? '');
    if (preg_match('/```(?:json)?\s*(.*?)```/s', $json, $mm)) {
        $json = $mm[1];
    }
    $a = json_decode($json, true);
    if (!is_array($a)) {
        throw new RuntimeException("The model's answer is not the JSON asked for.");
    }
    $ok = $bad = [];
    foreach ($keys as $k) {
        $t = $a[$k] ?? null;
        if (!is_string($t) || trim($t) === '') {
            continue;
        }
        if (!gen_tr_figures_ok($texts[$k], $t, $ages)) {
            $bad[$k] = $t;
            continue;
        }
        $ok[$k] = $t;
    }
    return [$ok, $bad];
}

/**
 * Translate [key => text] into $to: one round, then once more for the texts whose figures came back different.
 * Returns [[key => translation], tokens, model, how many were left out].
 */
function gen_tr_texts(array $texts, string $from, string $to, string $mk, array $meta, array $lim, array $ages = []): array
{
    if (!$texts) {
        return [[], 0, '', 0];
    }
    $out = [];
    $tokens = 0;
    $model = '';
    $round = function (array $todo, string $note) use ($from, $to, $mk, $meta, $lim, $ages, &$tokens, &$model): array {
        $calls = gen_tr_calls($todo, $from, $to, $mk, $meta, $note);
        $replies = gen_vertex_many(array_map(fn($c) => $c['call'], $calls), null, $lim);
        $ok = $bad = [];
        foreach ($calls as $i => $c) {
            $r = $replies[$i] ?? new RuntimeException('No reply from Vertex AI.');
            [$o, $b] = gen_tr_read($r, $c['keys'], $todo, $ages);
            $ok += $o;
            $bad += $b;
            if (is_array($r)) {
                $tokens += (int)($r['tokens'] ?? 0);
                $model = $r['model'] ?? $model;
            }
        }
        return [$ok, $bad];
    };
    [$out, $bad] = $round($texts, '');
    $left = array_diff_key($texts, $out);
    if ($left) {
        [$o2] = $round($left, 'Some earlier translations changed or dropped a figure. Keep every number exactly as in the source this time.');
        $out += $o2;
    }
    return [$out, $tokens, $model, count($texts) - count($out)];
}

/**
 * The job's own offers (project offers on its brands) in the job's languages: their texts are translated once
 * (tr_cache) and the brands' offer feeds rewritten with them (generator/brandfacts.php gen_poffers_apply).
 */
function gen_tr_offers(PDO $db, int $jobId, string $market, array $spec, array $langs, array $lim): void
{
    require_once __DIR__ . '/brandfacts.php';
    $q = $db->prepare('SELECT p.id, p.info FROM projects p JOIN jobs j ON j.project_id = p.id WHERE j.id = ?');
    $q->execute([$jobId]);
    $p = $q->fetch() ?: [];
    $q->closeCursor();
    $offers = gen_poffers_clean((json_decode((string)($p['info'] ?? '{}'), true) ?: [])['offers'] ?? []);
    if (!$offers) {
        return;
    }
    $texts = [];
    foreach ($offers as $o) {
        foreach (['title', 'conditions'] as $k) {
            if (($o[$k] ?? '') !== '') {
                $texts[md5($o[$k])] = $o[$k];
            }
        }
    }
    $have = $db->prepare('SELECT 1 FROM tr_cache WHERE lang = ? AND src = ?');
    $put = $db->prepare('INSERT OR REPLACE INTO tr_cache (lang, src, out) VALUES (?, ?, ?)');
    foreach ($langs as $c => $mk) { // every language, the main one too: the project may write its offers in another one
        $todo = array_filter($texts, function ($t) use ($have, $c) {
            $have->execute([$c, $t]);
            $found = $have->fetchColumn();
            $have->closeCursor();
            return !$found;
        });
        [$out] = gen_tr_texts($todo, '', $c, $mk, ['project' => (int)($p['id'] ?? 0) ?: null, 'job' => $jobId, 'kind' => 'translate', 'limit' => 'tok_translate'], $lim, gen_tr_ages($market, $mk));
        foreach ($out as $k => $t) {
            gen_db_write($put, [$c, $texts[$k], $t]);
        }
    }
    // only what is not translated yet was sent; the feeds are written again and their texts compared (not their dates)
    $sig = function (string $bid): string {
        $file = gen_overlay_dir() . "/promotions/$bid.json";
        $d = is_file($file) ? (json_decode((string)file_get_contents($file), true) ?: []) : [];
        return md5(json_encode(array_map(fn($p) => [$p['title'] ?? null, $p['terms']['eligible'] ?? null], $d['promotions'] ?? []), JSON_UNESCAPED_UNICODE));
    };
    $changed = false;
    foreach ($spec['brand_ids'] ?? [] as $bid) {
        $before = $sig($bid);
        gen_poffers_apply($db, $bid, $market);
        $changed = $changed || $sig($bid) !== $before;
    }
    if (!$changed) {
        return;
    }
    // every built site shows the brands' offers: each is updated with them (the sites being translated are anyway)
    gen_db_write($db->prepare("UPDATE sites SET status = 'refresh', error = NULL WHERE job_id = ? AND status = 'built'"), [$jobId]);
    gen_db_write($db->prepare("UPDATE jobs SET status = 'planned' WHERE id = ? AND status NOT IN ('running', 'checking')"), [$jobId]);
    gen_spawn($jobId);
}

/** A text's translation into $lang from tr_cache, else null. */
function gen_tr_cached(PDO $db, string $lang, string $text): ?string
{
    static $q = null;
    try {
        $q ??= $db->prepare('SELECT out FROM tr_cache WHERE lang = ? AND src = ?');
        $q->execute([$lang, $text]);
        $v = $q->fetchColumn();
        $q->closeCursor();
        return $v === false ? null : (string)$v;
    } catch (PDOException $e) { // no cache table yet: nothing translated
        return null;
    }
}

/** The translation run of a job: the job's offers, then every queued site into every other language, then a rebuild. */
function gen_tr_run(int $jobId): void
{
    require_once __DIR__ . '/content.php';
    $db = gen_db();
    gen_tr_columns($db);
    $set = $db->prepare('UPDATE sites SET tr = ? WHERE id = ?');
    gen_db_write($db->prepare("UPDATE sites SET tr = json_set(tr, '$.state', 'queued') WHERE job_id = ? AND json_extract(tr, '$.state') = 'writing'"), [$jobId]);
    $jq = $db->prepare('SELECT market, spec, project_id FROM jobs WHERE id = ?');
    $jq->execute([$jobId]);
    $job = $jq->fetch() ?: [];
    $jq->closeCursor(); // a read left open would hold the database for the whole run (the worker could not write, nor this run after it)
    $spec = json_decode($job['spec'] ?? '{}', true) ?: [];
    $langs = gen_job_langs($spec);
    $lim = gen_vertex_limits();
    if (!$langs) {
        gen_db_write($db->prepare("UPDATE sites SET tr = NULL WHERE job_id = ? AND json_extract(tr, '$.state') = 'queued'"), [$jobId]);
        return;
    }
    $from = array_key_first($langs);
    try {
        gen_tr_offers($db, $jobId, (string)$job['market'], $spec, $langs, $lim);
    } catch (Throwable $e) {
        fwrite(STDERR, date('c') . " offers: " . $e->getMessage() . "\n");
    }
    while (true) {
        $s = $db->prepare("SELECT * FROM sites WHERE job_id = ? AND json_extract(tr, '$.state') = 'queued' ORDER BY type, n LIMIT 1");
        $s->execute([$jobId]);
        $site = $s->fetch();
        $s->closeCursor(); // no read left open while the run writes
        if (!$site) {
            break;
        }
        $fresh = !empty((json_decode((string)$site['tr'], true) ?: [])['fresh']); // translated anew: earlier translations are not kept
        gen_db_write($set, [json_encode(['state' => 'writing', 'at' => gen_now()] + ($fresh ? ['fresh' => true] : [])), $site['id']]);
        try {
            $cfg = gen_site_full_config($site, $job['market'], $spec['brand_ids'] ?? []);
            $fields = gen_tr_fields($cfg);
            $keys = array_keys($fields);
            $plan = json_decode($site['plan'], true) ?: [];
            $stat = ['state' => 'done', 'at' => gen_now(), 'langs' => [], 'tokens' => 0, 'model' => '', 'left' => 0];
            foreach (array_slice($langs, 1, null, true) as $c => $mk) {
                $old = $fresh ? [] : ($plan['tr'][$c] ?? []);
                $todo = [];
                foreach ($keys as $i => $pk) { // only new or changed text is sent; short numbered keys for the model
                    if (($old[$pk]['s'] ?? null) !== $fields[$pk]) {
                        $todo[(string)($i + 1)] = $fields[$pk];
                    }
                }
                [$out, $tok, $model, $left] = gen_tr_texts($todo, $from, $c, $mk, ['project' => $job['project_id'] ?? null, 'job' => $jobId, 'site' => $site['id'], 'kind' => 'translate', 'limit' => 'tok_translate'], $lim, gen_tr_ages((string)$job['market'], $mk));
                $new = [];
                foreach ($keys as $i => $pk) {
                    if (isset($out[(string)($i + 1)])) {
                        $new[$pk] = ['s' => $fields[$pk], 't' => $out[(string)($i + 1)]];
                    } elseif (($old[$pk]['s'] ?? null) === $fields[$pk]) {
                        $new[$pk] = $old[$pk];
                    }
                }
                $plan['tr'][$c] = $new;
                $stat['langs'][$c] = count($new);
                $stat['tokens'] += $tok;
                $stat['model'] = $model ?: $stat['model'];
                $stat['left'] += $left;
            }
            $stat['fields'] = count($keys);
            gen_db_write($db->prepare("UPDATE sites SET plan = ?, status = 'refresh', error = NULL WHERE id = ?"), [json_encode($plan, JSON_UNESCAPED_UNICODE), $site['id']]);
            gen_db_write($set, [json_encode($stat), $site['id']]);
            gen_db_write($db->prepare("UPDATE jobs SET status = 'planned' WHERE id = ? AND status NOT IN ('running', 'checking')"), [$jobId]);
            gen_spawn($jobId); // the worker builds the language versions while the next site is translated
        } catch (Throwable $e) {
            try { // a statement of its own: the failure may have left the shared one unusable
                gen_db_write($db->prepare('UPDATE sites SET tr = ? WHERE id = ?'), [json_encode(['state' => 'error', 'at' => gen_now(), 'error' => $e->getMessage()]), $site['id']]);
            } catch (Throwable $e2) {
                fwrite(STDERR, date('c') . " {$site['id']}: " . $e->getMessage() . "\n");
                return;
            }
        }
    }
}
