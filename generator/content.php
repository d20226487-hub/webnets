<?php
/**
 * Content step: after an SEO has looked at a job's templates and approved sites, Vertex AI (generator/vertex.php)
 * rewrites their copy from the project information and documents, and may add a short text below sections.
 *
 * The copy is every text field of the built site config (data/configs/<site>.json) that placeholder mode would replace:
 * content, page meta and button labels, never settings (ids, urls, variants). One request per page. The answer is
 * checked (only known fields, tags and {placeholders} kept, sane lengths) and stored in the site plan as "ai":
 *   { fields: { "content/hero/title": "…" }, after: { <section> | <page>: { <section> }: "<p>…</p>" }, model, at, tokens }
 * gen_build_site() puts it on top of every build, so it survives restyles and rebuilds; the site is then rebuilt.
 *
 * Per site, sites.seo = { status: approved | changes | null, note, at } and sites.content = { state, error, at, fields }.
 */

require_once dirname(__DIR__) . '/lib/aibrief.php'; // market facts and language names, shared with the AI briefs
require_once __DIR__ . '/vertex.php';
require_once __DIR__ . '/briefs.php';
require_once __DIR__ . '/projectfiles.php';

/** Sections whose text is not rewritten: legal and compliance text, offer terms, user reviews (negative ones stay as they are). */
const GEN_AI_SKIP_BLOCKS = ['disclosure', 'compliance-bar', 'age-gate', 'promo-terms', 'review-list', 'store-reviews', 'sticky-cta'];
/** Blocks that never get a text below them. */
const GEN_AI_NO_AFTER = ['header', 'footer', 'age-gate', 'compliance-bar', 'disclosure', 'sticky-cta', 'promo-terms'];
const GEN_AI_BATCH = 120; // fields per request
/** What a drawn phone screen shows (lib/app.php), so a caption next to it describes it. */
const GEN_AI_PICTURES = ['home' => 'a sports betting line: matches with odds', 'live' => 'a live sports match: score and live odds', 'slip' => 'a bet slip with odds and a total',
    'wallet' => 'the wallet: balance, deposit and withdrawal buttons, transactions', 'casino' => 'a lobby of casino game tiles (slots)', 'table' => 'a live dealer table: video, chips, bet button',
    'install' => 'the phone\'s install dialog'];

function gen_content_columns(PDO $db): void
{
    $cols = array_column($db->query('PRAGMA table_info(sites)')->fetchAll(), 'name');
    foreach (['seo', 'content'] as $c) {
        if (!in_array($c, $cols, true)) {
            $db->exec("ALTER TABLE sites ADD COLUMN $c TEXT");
        }
    }
}

/** Whether a config value is copy (the same rule as gen_placeholders()). */
function gen_is_copy(string $key, $v): bool
{
    return is_string($v) && !in_array($key, GEN_KEEP, true) && !str_starts_with($key, 'show_') && !preg_match('~^(https?:|#|/|page:)~', $v)
        && preg_match('/\s/u', trim($v));
}

/** Copy fields of a site config: [path => text], paths like content/hero/title or content/home/faq/items/0/a. */
function gen_copy_fields(array $cfg, array $skipSections): array
{
    $out = [];
    $walk = function ($v, string $key, string $path) use (&$walk, &$out) {
        if (is_array($v)) {
            foreach ($v as $k => $x) {
                $walk($x, is_int($k) ? $key : (string)$k, "$path/$k");
            }
        } elseif (gen_is_copy($key, $v)) {
            $out[ltrim($path, '/')] = $v;
        }
    };
    foreach (['content', 'meta', 'page', 'cta_label'] as $k) {
        if (isset($cfg[$k])) {
            $walk($cfg[$k], $k, $k);
        }
    }
    return array_filter($out, function ($p) use ($skipSections) {
        foreach ($skipSections as $s) {
            if ($p === $s || str_starts_with($p, "$s/")) {
                return false;
            }
        }
        return true;
    }, ARRAY_FILTER_USE_KEY);
}

/** The site's pages and sections from its type's plan: [page id ('' on one-page types) => [section id => block]]. */
function gen_site_sections(string $type): array
{
    global $GEN_ROOT;
    $bp = sx_json("$GEN_ROOT/blueprints/$type.json");
    $list = fn($secs) => array_column(array_filter((array)$secs, 'is_array'), 'block', 'id');
    if (!empty($bp['pages'])) {
        return array_map(fn($p) => $list($p['sections'] ?? []), $bp['pages']);
    }
    return ['' => $list($bp['sections'] ?? [])];
}

/** What the drawn screens next to copy fields show: [path => description], e.g. a screenshot caption beside a casino lobby. */
function gen_content_pictures(array $cfg, array $verticals): array
{
    $out = [];
    $walk = function ($v, string $path) use (&$walk, &$out, $verticals) {
        if (!is_array($v)) {
            return;
        }
        if (isset($v['kind']) && is_string($v['kind'])) {
            $kind = sx_phone_kind($v['kind'], $verticals ?: null);
            foreach ($v as $k => $x) {
                if (is_string($x) && $k !== 'kind' && isset(GEN_AI_PICTURES[$kind])) {
                    $out[ltrim("$path/$k", '/')] = GEN_AI_PICTURES[$kind];
                }
            }
        }
        foreach ($v as $k => $x) {
            $walk($x, "$path/$k");
        }
    };
    $walk($cfg['content'] ?? [], 'content');
    return $out;
}

/** Request groups for a site: [[label, page id, [section => block], [path => text]], …], one per page (split when large). */
function gen_content_groups(array $cfg, string $type): array
{
    $pages = gen_site_sections($type);
    $multi = !isset($pages['']);
    $skip = [];
    foreach ($pages as $pid => $secs) {
        foreach ($secs as $sid => $blk) {
            if (in_array($blk, GEN_AI_SKIP_BLOCKS, true)) {
                $skip[] = $multi ? "content/$pid/$sid" : "content/$sid";
            }
        }
    }
    $fields = gen_copy_fields($cfg, $skip);
    $groups = [];
    foreach ($pages as $pid => $secs) {
        $mine = array_filter($fields, function ($p) use ($multi, $pid) {
            if (!$multi) {
                return true;
            }
            return str_starts_with($p, "content/$pid/") || str_starts_with($p, "meta/$pid/");
        }, ARRAY_FILTER_USE_KEY);
        if (!$multi) { // one page: everything
            $fields = [];
        } else {
            $fields = array_diff_key($fields, $mine);
        }
        foreach (array_chunk($mine, GEN_AI_BATCH, true) as $i => $chunk) {
            $groups[] = [$multi ? "page $pid" . ($i ? ' (' . ($i + 1) . ')' : '') : 'the page' . ($i ? ' (' . ($i + 1) . ')' : ''), $pid, $i ? [] : $secs, $chunk];
        }
    }
    if ($fields) { // shared copy of multi-page sites (header, footer, button labels)
        foreach (array_chunk($fields, GEN_AI_BATCH, true) as $chunk) {
            $groups[] = ['the shared parts (header, footer, labels)', null, [], $chunk];
        }
    }
    return $groups;
}

/** The fixed instructions for every request of a site. */
function gen_content_system(array $cfg, array $info, string $projectPart, array $brand = []): string
{
    global $GEN_ROOT;
    $m = sx_json("$GEN_ROOT/markets/{$cfg['market']}.json");
    $loc = $cfg['locale'] ?? ($m['default_locale'] ?? 'en');
    $bname = $brand['name'] ?? '';
    $v = $brand['verticals'] ?? [];
    $sports = in_array('sports', $v, true);
    $casino = (bool)array_intersect(['casino', 'live-casino'], $v);
    $offers = $sports && $casino ? 'sports betting and casino' : ($sports ? 'sports betting only (no casino)' : ($casino ? 'casino and live casino only (no sports betting)' : ''));
    $L = [];
    $L[] = 'You are an SEO copywriter. You rewrite the text of one website built from a template so that it is about the project described below,'
        . ' reads naturally and is useful for search. The layout stays exactly as it is: you only replace text.';
    $L[] = '';
    $L[] = 'Language: write every text in ' . sx_brief_lang($loc) . " (locale $loc), whatever language the instructions or project information are in.";
    $L[] = 'Site: ' . ($cfg['site']['name'] ?? $cfg['id']) . ', site type ' . $cfg['type'] . (!empty($cfg['angle']) ? ", angle {$cfg['angle']}" : '') . ($bname !== '' ? ", brand «{$bname}»" : '') . '.';
    if ($offers !== '') {
        $L[] = "The brand offers $offers. Write only about these products; the site's pictures show them too.";
    }
    $L[] = '';
    $L[] = sx_ai_market_facts($m, $loc);
    $L[] = '';
    $L[] = $projectPart !== '' ? $projectPart : "## Project information\n\nNone given: keep the template's subject and improve the wording.";
    $L[] = '';
    $L[] = '## How to rewrite';
    $L[] = '';
    $L[] = '- Rewrite every field you are given. Keep what each field is for: a button label stays a short label, a heading stays a heading, a question stays a question, an answer answers it.';
    $L[] = '- Keep each text within about 70–150% of its current length, so the layout holds.';
    $L[] = '- HTML in a field: keep exactly the same tags in the same structure, change only the text between them.';
    $L[] = '- Keep every {placeholder} in braces (for example {item}) exactly as it is.';
    $L[] = $bname !== ''
        ? "- In every visible text (headings, buttons, body text, FAQ) write the brand exactly as «{$bname}». Other spellings, transliterations or lower-case forms from the search phrases (for example «" . mb_strtolower($bname) . '») may appear only in the page title and meta description, once each at most. Do not add other brands, links or URLs.'
        : '- Brand and product names stay as written. Do not add brands, links or URLs.';
    $L[] = '- A text next to a picture (a screenshot caption, a card beside a screen) describes what that picture shows; the request says what each one shows.';
    $L[] = '- No absolute or guaranteed claims: never call anything absolutely, completely or 100% safe, guaranteed, risk-free or certain, and never promise wins, fixed payout times or bonuses. Say what is true instead (for example: the file comes from the official site; Android asks for permission to install it).';
    $L[] = '- Facts: do not state amounts, times, percentages, odds, licences or awards unless they are in the project information, its documents or the current text. When unsure, write without the number.';
    $L[] = '- Follow the market rules above: age, products allowed, promotions allowed, payment methods and currency. No promises of winnings.';
    $L[] = '- Search: work the project\'s topics and search phrases in where they fit naturally, each page around its own subject, at most one search phrase per text; no keyword stuffing, no repeated sentences across fields.';
    return implode("\n", $L);
}

/** One request: the fields of one group, and the "after" instruction. Returns the parsed answer. */
function gen_content_request(string $system, array $group, string $below, array $cfg, array $pictures = []): array
{
    [$label, $pid, $secs, $fields] = $group;
    $pics = array_intersect_key($pictures, $fields);
    $afterOk = array_keys(array_filter($secs, fn($b) => !in_array($b, GEN_AI_NO_AFTER, true)));
    $P = [];
    $P[] = "Rewrite the text of $label. The fields are given as JSON: path → current text. Sections on this page, in the template's order: "
        . ($secs ? implode(', ', array_map(fn($id, $b) => "$id ($b)", array_keys($secs), $secs)) : 'shared parts only') . '.';
    $P[] = '';
    $P[] = '```json';
    $P[] = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $P[] = '```';
    $P[] = '';
    if ($pics) {
        $P[] = 'Pictures beside these fields (the text must describe what the picture shows):';
        foreach ($pics as $path => $what) {
            $P[] = "- $path: $what";
        }
        $P[] = '';
    }
    if ($below !== 'none' && $afterOk) {
        $P[] = 'Text below sections: ' . ($below === 'every'
            ? 'write a short text for every one of these sections: ' . implode(', ', $afterOk) . '. Each: 1–2 paragraphs, 50–120 words, saying something new.'
            : 'where a section among ' . implode(', ', $afterOk) . ' would gain from explanation, write a short text for it: 1–3 paragraphs, 60–150 words. Most sections need none; at most three on this page.')
            . ' Format: HTML with <p>, optionally one <h2> or <h3> first, <ul>/<li>, <strong>. No links, no images.';
    } else {
        $P[] = 'Text below sections: none (leave "after" empty).';
    }
    $P[] = '';
    $P[] = 'Answer with JSON only, in this shape: {"fields": {"<path>": "<new text>", …every path above…}, "after": {"<section id>": "<html>"}}';
    $r = gen_vertex_generate(implode("\n", $P), $system, true);
    $json = trim($r['text']);
    if (preg_match('/```(?:json)?\s*(.*?)```/s', $json, $mm)) {
        $json = $mm[1];
    }
    $a = json_decode($json, true);
    if (!is_array($a) || !is_array($a['fields'] ?? null)) {
        throw new RuntimeException("The model's answer for $label is not the JSON asked for.");
    }
    return ['fields' => $a['fields'], 'after' => is_array($a['after'] ?? null) ? array_intersect_key($a['after'], array_flip($afterOk)) : [], 'pid' => $pid, 'tokens' => $r['tokens'], 'model' => $r['model']];
}

/** The brand written as the brand in a visible text (not the page title or description): "boostwin" → "BoostWin". */
function gen_content_brand(string $path, string $text, string $brand): string
{
    if ($brand === '' || preg_match('#^(page|meta/[^/]+)/(title|description)$#', $path)) {
        return $text;
    }
    return preg_replace('/(?<![\p{L}\p{N}])' . preg_quote($brand, '/') . '(?![\p{L}\p{N}])/iu', $brand, $text);
}

/** An answer's text for a field, or null when it breaks the field (tags, placeholders, length). */
function gen_content_check(string $old, $new): ?string
{
    if (!is_string($new) || trim($new) === '') {
        return null;
    }
    $new = trim($new);
    $tags = fn($s) => preg_match_all('#</?([a-z][a-z0-9]*)#i', $s, $m) ? array_map('strtolower', $m[1]) : [];
    if ($tags($old) !== $tags($new)) {
        return null;
    }
    preg_match_all('/\{[\w-]+\}/', $old, $po);
    preg_match_all('/\{[\w-]+\}/', $new, $pn);
    sort($po[0]);
    sort($pn[0]);
    if ($po[0] !== $pn[0]) {
        return null;
    }
    $lo = mb_strlen(strip_tags($old));
    $ln = mb_strlen(strip_tags($new));
    return $ln > max(40, $lo * 2.2) || $ln < min($lo, 3) ? null : $new;
}

/** Text below a section, cleaned: only simple text tags, no attributes. */
function gen_content_after($html): string
{
    if (!is_string($html)) {
        return '';
    }
    $html = preg_replace('#<(script|style|iframe|object|template)\b.*?(</\1>|$)#is', '', $html); // their content is code, not text
    $html = strip_tags($html, '<p><h2><h3><ul><ol><li><strong><em><br>');
    $html = preg_replace('#<(\w+)\s[^>]*>#', '<$1>', $html);
    return mb_strlen(strip_tags($html)) >= 40 ? trim($html) : '';
}

/** Writes one site's copy. Returns the plan's "ai" entry. */
function gen_content_site(array $site, string $below, string $projectPart, array $info, array $job = []): array
{
    global $GEN_ROOT;
    if ($site['status'] !== 'built') {
        throw new RuntimeException('The site has no build yet. Build it first.');
    }
    $spec = json_decode($job['spec'] ?? '{}', true) ?: [];
    // always from the template's text (a second run does not rewrite the first run's text)
    $cfg = gen_site_full_config($site, $job['market'] ?? 'xx', $spec['brand_ids'] ?? [], false);
    $brand = $site['brand'] ? (sx_brand_data($GEN_ROOT, 'operators.json')[$site['brand']] ?? []) : [];
    $system = gen_content_system($cfg, $info, $projectPart, $brand);
    $pictures = gen_content_pictures($cfg, $brand['verticals'] ?? []);
    $groups = gen_content_groups($cfg, $site['type']);
    $multi = !isset(gen_site_sections($site['type'])['']);
    $out = ['fields' => [], 'after' => [], 'model' => '', 'at' => gen_now(), 'tokens' => 0, 'skipped' => 0];
    foreach ($groups as $g) {
        $a = gen_content_request($system, $g, $below, $cfg, $pictures);
        foreach ($g[3] as $path => $old) {
            $new = gen_content_check($old, $a['fields'][$path] ?? null);
            $new = $new === null ? null : gen_content_brand($path, $new, $brand['name'] ?? '');
            if ($new === null) {
                $out['skipped']++;
            } elseif ($new !== $old) {
                $out['fields'][$path] = $new;
            }
        }
        foreach ($a['after'] as $sid => $html) {
            if (($h = gen_content_after($html)) !== '') {
                if ($multi && $a['pid'] !== null) {
                    $out['after'][$a['pid']][$sid] = $h;
                } else {
                    $out['after'][$sid] = $h;
                }
            }
        }
        $out['tokens'] += $a['tokens'];
        $out['model'] = $a['model'];
    }
    return $out;
}

/** A site config with the AI copy on top (called by gen_build_site after the edits). */
function gen_apply_ai(array $cfg, array $ai): array
{
    foreach ($ai['fields'] ?? [] as $path => $text) {
        $ref = &$cfg;
        $ok = true;
        foreach (explode('/', $path) as $k) {
            if (!is_array($ref) || !array_key_exists($k, $ref)) {
                $ok = false;
                break;
            }
            $ref = &$ref[$k];
        }
        if ($ok && is_string($ref)) {
            $ref = $text;
        }
        unset($ref);
    }
    if (!empty($ai['after'])) {
        $cfg['after'] = $ai['after'];
    }
    return $cfg;
}

/** The content run of a job (generator/contentjob.php): every site queued for it, then a rebuild of those that got text. */
function gen_content_run(int $jobId): void
{
    $db = gen_db();
    gen_content_columns($db);
    $q = $db->prepare('SELECT p.id, p.info FROM projects p JOIN jobs j ON j.project_id = p.id WHERE j.id = ?');
    $q->execute([$jobId]);
    $p = $q->fetch() ?: ['id' => 0, 'info' => '{}'];
    $info = gen_project_info(json_decode((string)$p['info'], true) ?: []);
    $docs = gen_pfiles_brief($db, (int)$p['id']);
    $part = gen_project_brief(['below' => 'none'] + $info, $docs);
    $part = preg_replace('/\n### Text below sections.*$/s', '', $part); // below-section text is asked per request
    $set = $db->prepare('UPDATE sites SET content = ? WHERE id = ?');
    $jq = $db->prepare('SELECT market, spec FROM jobs WHERE id = ?');
    $jq->execute([$jobId]);
    $job = $jq->fetch() ?: [];
    $done = 0;
    while (true) {
        $s = $db->prepare("SELECT * FROM sites WHERE job_id = ? AND json_extract(content, '$.state') = 'queued' ORDER BY type, n LIMIT 1");
        $s->execute([$jobId]);
        $site = $s->fetch();
        if (!$site) {
            break;
        }
        $set->execute([json_encode(['state' => 'writing', 'at' => gen_now()]), $site['id']]);
        try {
            $ai = gen_content_site($site, $info['below'], $part, $info, $job);
            $plan = json_decode($site['plan'], true) ?: [];
            $plan['ai'] = $ai;
            $db->prepare("UPDATE sites SET plan = ?, status = 'planned', error = NULL, mobile = NULL WHERE id = ?")->execute([json_encode($plan, JSON_UNESCAPED_UNICODE), $site['id']]);
            $set->execute([json_encode(['state' => 'done', 'at' => gen_now(), 'fields' => count($ai['fields']), 'after' => count(array_filter($ai['after'], 'is_string')) + array_sum(array_map(fn($x) => is_array($x) ? count($x) : 0, $ai['after'])), 'skipped' => $ai['skipped'], 'model' => $ai['model'], 'tokens' => $ai['tokens']]), $site['id']]);
            $done++;
        } catch (Throwable $e) {
            $set->execute([json_encode(['state' => 'error', 'at' => gen_now(), 'error' => $e->getMessage()]), $site['id']]);
        }
    }
    if ($done) { // the worker rebuilds them (one job at a time, as every build)
        $db->prepare("UPDATE jobs SET status = 'planned' WHERE id = ?")->execute([$jobId]);
        gen_spawn($jobId);
    }
}
