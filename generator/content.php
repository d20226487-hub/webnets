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
    foreach (['content', 'meta', 'page', 'cta_label', 'brand_text'] as $k) {
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
    $skip[] = 'content/header/note'; // the header's licence note comes from the licence settings, not from the writer
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
    $multi = !empty($cfg['publisher']);
    $L[] = 'Site: ' . ($cfg['site']['name'] ?? $cfg['id']) . ', site type ' . $cfg['type'] . (!empty($cfg['angle']) ? ", angle {$cfg['angle']}" : '') . ($bname !== '' ? ", " . ($multi ? 'promoted brand' : 'brand') . " $bname" : '') . '.';
    if ($multi) { // a ranking or news site: run by the publisher, about several operators
        $names = array_filter(array_map(fn($id) => sx_brand_data($GEN_ROOT, 'operators.json')[$id]['name'] ?? null, (array)($cfg['brands'] ?? [])));
        $L[] = 'This site is an independent comparison run by ' . ($cfg['site']['name'] ?? 'a publisher') . ' about several operators (' . implode(', ', $names) . '). It is not any operator\'s own site:'
            . ' headings, intros, table titles and FAQ are about the comparison and the operators in general, never titled as one brand\'s page.'
            . ($bname !== '' ? " $bname is the brand the site recommends: name it as the top pick where it fits, without writing as if the site belonged to it." : '');
    }
    $facts = gen_content_brand_facts($brand, $cfg['market'], $m);
    if ($facts) {
        $L[] = '';
        $L[] = "## {$bname} figures (what the site shows; use exactly these)";
        $L[] = '';
        foreach ($facts as $k => $v) {
            $L[] = "- $k: $v";
        }
    }
    if ($offers !== '') {
        $L[] = "The brand offers $offers. Write only about these products; the site's pictures show them too.";
    }
    $L[] = '';
    $L[] = sx_ai_market_facts($m, $loc, $cfg['licence'] ?? []);
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
        ? "- In every visible text (headings, buttons, body text, FAQ) write the brand exactly as {$bname}, without quotation marks around it. Other spellings, transliterations or lower-case forms from the search phrases (for example " . mb_strtolower($bname) . ') may appear only in the page title and meta description, once each at most. Do not add other brands, links or URLs.'
        : '- Brand and product names stay as written. Do not add brands, links or URLs.';
    $L[] = '- Disclosures stay as clear as in the current text: when a text says the site earns a commission, is paid, or that something is advertising or a partner offer, the new text says so just as plainly (for example «we earn a commission when you sign up through our links»). Never soften or drop it.';
    $L[] = '- A text next to a picture (a screenshot caption, a card beside a screen) describes what that picture shows; the request says what each one shows.';
    $L[] = '- No absolute or guaranteed claims: never call anything absolutely, completely or 100% safe, guaranteed, risk-free or certain, and never promise wins, fixed payout times or bonuses. Say what is true instead (for example: the file comes from the official site; Android asks for permission to install it).';
    $L[] = '- Figures: the current text is template copy and its figures (amounts, times, percentages, odds, counts, years) are only examples. Use a figure only when it is in the brand figures above, the project information or its documents, and then exactly as given; otherwise write the sentence without a figure. Never state licences or awards that are not given.';
    $L[] = '- Follow the market rules above: age, products allowed, promotions allowed, payment methods and currency. No promises of winnings.';
    $L[] = '- Search: work the project\'s topics and search phrases in where they fit naturally, each page around its own subject, at most one search phrase per text; no keyword stuffing, no repeated sentences across fields.';
    return implode("\n", $L);
}

/** The brand's figures for the instructions: the market facts (minimum deposit, payout times, games, live tables) and
 *  the brand's own (start year, licence), as the site shows them. */
function gen_content_brand_facts(array $brand, string $market, array $m): array
{
    global $GEN_ROOT;
    if (empty($brand['_id'])) {
        return [];
    }
    $f = sx_brand_data($GEN_ROOT, "facts/$market.json")['brands'][$brand['_id']] ?? [];
    $cur = $m['currency']['symbol'] ?? ($m['currency']['code'] ?? '');
    $out = [];
    if (isset($f['min_deposit'])) {
        $out['Minimum deposit'] = $f['min_deposit'] . " $cur";
    }
    $pname = fn($p) => function_exists('sx_payment_name') && $p !== 'bank' ? sx_payment_name($p) : $p;
    if (!empty($f['payments'])) {
        $out['Payment methods (only these)'] = implode(', ', array_map($pname, $f['payments']));
    }
    foreach ($f['withdrawal_minutes'] ?? [] as $pay => $min) {
        if (!empty($f['payments']) && !in_array($pay, $f['payments'], true)) {
            continue;
        }
        $out['Withdrawal time, ' . $pname($pay)] = $min >= 1440 ? round($min / 1440) . ' days' : ($min >= 60 ? round($min / 60, 1) . ' hours' : "about $min minutes");
    }
    if (isset($f['games'])) {
        $out['Casino games'] = $f['games'];
    }
    if (isset($f['live_tables'])) {
        $out['Live tables'] = $f['live_tables'];
    }
    if (is_array($f['support'] ?? null)) {
        $s = $f['support'];
        $out['Support'] = implode(', ', array_filter([!empty($s['chat']) ? 'chat ' . $s['chat'] : null, !empty($s['email']) ? 'email' : null, !empty($s['phone']) ? 'phone' : 'no phone']));
    }
    foreach (['count' => 'Sports covered', 'markets_per_match' => 'Markets on a big match', 'live_events' => 'Live events a day', 'margin' => 'Margin on top football, %'] as $k => $lab) {
        if (isset($f['sports'][$k])) {
            $out[$lab] = $f['sports'][$k];
        }
    }
    if (!empty($brand['facts']['founded'])) {
        $out['Operating since'] = $brand['facts']['founded'];
    }
    if (!empty($brand['license_no'])) {
        $out['Licence number'] = $brand['license_no'];
    }
    foreach (['version' => 'App version', 'updated' => 'App updated'] as $k => $lab) {
        if (!empty($brand['app'][$k])) {
            $out[$lab] = $brand['app'][$k];
        }
    }
    foreach ($brand['app']['size_mb'] ?? [] as $os => $mb) {
        $out["App size, $os"] = "$mb MB";
    }
    foreach ($brand['app']['min_os'] ?? [] as $os => $v) {
        $out["Minimum $os"] = $v;
    }
    $n = 0;
    foreach (gen_content_offers($brand['_id'], $market, $m) as $line) {
        $out['Offer ' . (++$n)] = $line;
    }
    return $out;
}

/** The brand's offers in this market, one line each with every figure the site shows (title, value, code, terms). */
function gen_content_offers(string $id, string $market, array $m): array
{
    global $GEN_ROOT;
    $cur = $m['currency']['symbol'] ?? ($m['currency']['code'] ?? '');
    $l = $m['default_locale'] ?? 'en';
    $out = [];
    foreach (sx_brand_data($GEN_ROOT, "promotions/$id.json")['promotions'] ?? [] as $p) {
        if (!in_array($market, $p['markets'] ?? [$market], true)) {
            continue;
        }
        $title = is_array($p['title'] ?? null) ? ($p['title'][$l] ?? reset($p['title'])) : (string)($p['title'] ?? '');
        $v = $p['value'] ?? [];
        $t = $p['terms'] ?? [];
        $bits = array_filter([
            isset($v['percent']) ? $v['percent'] . '%' . (isset($v['max']) ? " up to {$v['max']} $cur" : '') : null,
            isset($v['count']) ? $v['count'] . ' free spins' : null,
            isset($v['amount']) && !isset($v['percent']) ? "{$v['amount']} $cur" : null,
            !empty($p['code']) ? "code {$p['code']}" : null,
            !empty($t['wagering']) ? "wagering ×{$t['wagering']}" : null,
            !empty($t['min_deposit']) ? "min deposit {$t['min_deposit']} $cur" : null,
            !empty($t['min_odds']) ? "min odds {$t['min_odds']}" : null,
            !empty($t['max_bet']) ? "max bet {$t['max_bet']} $cur" : null,
            !empty($t['expiry_days']) ? "valid {$t['expiry_days']} days" : null,
        ]);
        $out[] = trim("{$p['type']}: $title" . ($bits ? ' (' . implode(', ', $bits) . ')' : ''));
    }
    return $out;
}

/** The request for one group: the prompt, and the sections that may get text below them. */
function gen_content_prompt(array $group, string $below, array $pictures = []): array
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
    if (array_filter(array_keys($fields), fn($p) => str_starts_with($p, 'brand_text/'))) {
        $P[] = 'Fields under brand_text/<brand id>/ are the editorial texts of one compared brand (its summary on ranking cards, verdict, pros, cons and score reasons).'
            . ' Write them in your own words for this site: keep each about that brand and true to what it says, keep the figures it states exactly (they are that brand\'s), keep its brand name, and never mix brands.';
        $P[] = '';
    }
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
    return [implode("\n", $P), $afterOk];
}

/** A model reply for one group, read: the fields and texts below sections asked for. */
function gen_content_answer(array $r, array $group, array $afterOk): array
{
    [$label, $pid] = $group;
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

/** The brand written plain everywhere («BoostWin» → BoostWin), and as the brand in visible text, not the page title or
 *  description, where search spellings may stay: "boostwin" → "BoostWin". */
function gen_content_brand(string $path, string $text, string $brand): string
{
    if ($brand === '') {
        return $text;
    }
    $q = preg_quote($brand, '/');
    $text = preg_replace('/[«"„“]\s*(' . $q . ')\s*[»"“”]/iu', '$1', $text);
    if (preg_match('#^(page|meta/[^/]+)/(title|description)$#', $path)) {
        return $text;
    }
    return preg_replace('/(?<![\p{L}\p{N}])' . $q . '(?![\p{L}\p{N}])/iu', $brand, $text);
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

/** One site's requests: its groups, prompts and context. Throws when the site cannot be written. */
function gen_content_prepare(array $site, string $below, string $projectPart, array $info, array $job = []): array
{
    global $GEN_ROOT;
    if ($site['status'] !== 'built') {
        throw new RuntimeException('The site has no build yet. Build it first.');
    }
    $spec = json_decode($job['spec'] ?? '{}', true) ?: [];
    // always from the template's text (a second run does not rewrite the first run's text)
    $cfg = gen_site_full_config($site, $job['market'] ?? 'xx', $spec['brand_ids'] ?? [], false);
    $bid = $site['brand'] ?: ($cfg['promote'] ?? false ? ($cfg['featured'] ?? null) : null); // a ranking site: the brand it promotes
    $brand = $bid ? (['_id' => $bid] + (sx_brand_data($GEN_ROOT, 'operators.json')[$bid] ?? [])) : [];
    $system = gen_content_system($cfg, $info, $projectPart, $brand);
    $pictures = gen_content_pictures($cfg, $brand['verticals'] ?? []);
    $meta = ['project' => $job['project_id'] ?? null, 'job' => (int)$site['job_id'], 'site' => $site['id'], 'kind' => 'text'];
    $req = [];
    foreach (gen_content_groups($cfg, $site['type']) as $g) {
        [$prompt, $afterOk] = gen_content_prompt($g, $below, $pictures);
        $req[] = ['group' => $g, 'after' => $afterOk, 'call' => ['prompt' => $prompt, 'system' => $system, 'json' => true, 'max' => 16384, 'meta' => $meta]];
    }
    $allowed = gen_content_numbers(preg_replace('/^- No absolute.*$/m', '', $system)); // the figures given (not the rules' examples)
    foreach ([24, 7, 365, (int)date('Y'), (int)date('Y') + 1] as $n) {
        $allowed['any'][(string)$n] = true;
    }
    return ['brand' => $brand['name'] ?? '', 'multi' => !isset(gen_site_sections($site['type'])['']), 'req' => $req, 'allowed' => $allowed, 'system' => $system, 'meta' => $meta];
}

/** The figures a field may state: the site's, and for a compared brand's rating text (brand_text/…) also the ones its
 *  current text states (they are that brand's figures). */
function gen_content_allowed_for(array $allowed, string $path, string $old): array
{
    if (!str_starts_with($path, 'brand_text/')) {
        return $allowed;
    }
    foreach (gen_content_numbers($old) as $k => $set) {
        $allowed[$k] = ($allowed[$k] ?? []) + $set;
    }
    return $allowed;
}

/** A number as compared: separators gone, decimal point, no trailing zeros ("15 700" → "15700", "5,10" → "5.1"). */
function gen_content_num(string $int, string $dec = ''): string
{
    $n = ltrim(preg_replace('/\D/u', '', $int), '0') ?: '0';
    $dec = rtrim($dec, '0');
    return $dec !== '' ? "$n.$dec" : $n;
}

/**
 * The figures of a text: [[raw, kind, values]], kind money | pct | time (values in minutes) | mult (wagering) | count
 * (a number with another unit, or any from 100 up) | plain (a small number without a unit, not a figure).
 */
function gen_content_figures(string $text): array
{
    $text = strip_tags(preg_replace('/\{[\w-]+\}/', '', $text));
    $cur = '₸|тг|тенге|теңге|€|\$|£|R\$|A\$|KZT|EUR|BRL|AUD|USD|reais';
    $time = ['min' => 'мин\p{L}*|минут|min(?:ute)?s?|minutos?', 'h' => 'час\p{L}*|ч(?!\p{L})|сағат\p{L}*|hours?|h(?!\p{L})|horas?', 'd' => 'дн\p{L}*|дня|день|сут\p{L}*|күн\p{L}*|days?|dias?'];
    $count = 'сек\p{L}*|игр\p{L}*|слот\p{L}*|стол\p{L}*|рын\p{L}*|событ\p{L}*|вид\p{L}* спорт\p{L}*|фриспин\p{L}*|спин\p{L}*|вращ\p{L}*|MB|МБ|GB|ГБ'
        . '|games|slots|tables|markets|events|sports|free spins|spins|jogos|mesas|mercados|eventos|giros|ойын\p{L}*|үстел\p{L}*|нарық\p{L}*|оқиға\p{L}*|мың|тыс\p{L}*|thousand';
    $num = fn($g) => '(?<' . $g . '>\d{1,3}(?:[ \x{00A0}\x{202F}.,]\d{3})+|\d+)(?:[.,](?<' . $g . 'd>\d{1,2})(?!\d))?';
    $unit = "$cur|%|" . implode('|', $time) . "|$count";
    preg_match_all('/(?<![\p{L}\d.,])(?<pre>[×x]|' . $cur . ')?\s?' . $num('a') . '(?:\s?(?:–|-|—|до|to|a)\s?' . $num('b') . ')?(?<post>\s?(?:live-|лайв-)?(?:' . $unit . ')(?!\p{L}))?/iu', $text, $mm, PREG_SET_ORDER);
    $out = [];
    foreach ($mm as $x) {
        $vals = [gen_content_num($x['a'], $x['ad'] ?? '')];
        if (($x['b'] ?? '') !== '') {
            $vals[] = gen_content_num($x['b'], $x['bd'] ?? '');
        }
        $pre = trim($x['pre'] ?? '');
        $post = trim($x['post'] ?? '');
        if (preg_match('/^(' . $cur . ')$/iu', $pre) || preg_match('/^(' . $cur . ')$/iu', $post)) {
            $kind = 'money';
        } elseif ($post === '%') {
            $kind = 'pct';
        } elseif ($pre !== '' && preg_match('/^[×x]$/iu', $pre)) {
            $kind = 'mult';
        } elseif ($post !== '' && ($t = array_key_first(array_filter($time, fn($re) => preg_match('/^(' . $re . ')$/iu', $post))))) {
            $kind = 'time';
            $vals = array_map(fn($v) => gen_content_num((string)round((float)$v * ['min' => 1, 'h' => 60, 'd' => 1440][$t])), $vals);
        } elseif ($post !== '' || max(array_map('floatval', $vals)) >= 100) {
            $kind = 'count';
        } else {
            $kind = 'plain';
        }
        $out[] = [trim($x[0]), $kind, $vals];
    }
    return $out;
}

/** The figures a text may state, from the instructions (brand figures, offers, market facts, project information and
 *  documents): [kind => [value => true]], 'any' holding every number. */
function gen_content_numbers(string $text): array
{
    $out = ['any' => [], 'money' => [], 'pct' => [], 'time' => [], 'mult' => []];
    foreach (gen_content_figures($text) as [, $kind, $vals]) {
        foreach ($vals as $v) {
            $out['any'][$v] = true;
            if (isset($out[$kind])) {
                $out[$kind][$v] = true;
            }
        }
    }
    return $out;
}

/** The figures a text states that are not among $allowed: an amount, time, percentage or wagering multiple must be
 *  given with that unit; another count, or any number from 100 up, must be given at all. */
function gen_content_bad_figures(string $text, array $allowed): array
{
    $bad = [];
    foreach (gen_content_figures($text) as [$raw, $kind, $vals]) {
        if ($kind === 'plain') {
            continue;
        }
        $set = in_array($kind, ['money', 'pct', 'time', 'mult'], true) ? ($allowed[$kind] ?? []) : ($allowed['any'] ?? []);
        foreach ($vals as $v) {
            if (!isset($set[$v])) {
                $bad[] = $raw;
                break;
            }
        }
    }
    return array_values(array_unique($bad));
}

/** A text with its sentences that state a figure not allowed taken out, or null when nothing would be left or the text
 *  has markup (it is then not used). */
function gen_content_drop_figures(string $text, array $allowed): ?string
{
    if ($text !== strip_tags($text)) {
        return null;
    }
    $keep = array_filter(preg_split('/(?<=[.!?…])\s+/u', trim($text)), fn($s) => !gen_content_bad_figures($s, $allowed));
    $out = trim(implode(' ', $keep));
    return $out === '' ? null : $out;
}

/** The second request for a site: the fields whose figures were not in the data, with the figures named. */
function gen_content_retry_call(array $prep, array $bad): array
{
    $fields = $notes = [];
    foreach ($bad as $path => $b) {
        $fields[$path] = $b['new'];
        $notes[] = "- $path: " . implode('; ', $b['figs']);
    }
    $P = [];
    $P[] = 'These texts state figures that are not in the brand figures, the project information or its documents, so they cannot be published:';
    $P[] = implode("\n", $notes);
    $P[] = '';
    $P[] = 'Rewrite each text so it says the same without those figures. Use a figure only when it is given above in the instructions, exactly as given; otherwise write the sentence without a number. Keep the HTML tags and {placeholders} as they are.';
    $P[] = '';
    $P[] = '```json';
    $P[] = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $P[] = '```';
    $P[] = '';
    $P[] = 'Answer with JSON only, in this shape: {"fields": {"<path>": "<new text>", …every path above…}}';
    return ['prompt' => implode("\n", $P), 'system' => $prep['system'], 'json' => true, 'max' => 16384, 'meta' => $prep['meta']];
}

/** The second request's answer put into a site's "ai" entry: a text that now keeps to the data is used; one that still
 *  does not loses the sentences with those figures, or is left out (the template's text stays). */
function gen_content_retry_apply(array $prep, array $out, $reply): array
{
    $bad = $out['bad'] ?? [];
    unset($out['bad']);
    $got = [];
    if (is_array($reply) && isset($reply['text'])) {
        $json = trim($reply['text']);
        if (preg_match('/```(?:json)?\s*(.*?)```/s', $json, $mm)) {
            $json = $mm[1];
        }
        $got = (array)(json_decode($json, true)['fields'] ?? []);
        $out['tokens'] += $reply['tokens'] ?? 0;
    }
    foreach ($bad as $path => $b) {
        $new = $b['after'] ? (is_string($got[$path] ?? null) ? gen_content_after($got[$path]) : '') : gen_content_check($b['old'], $got[$path] ?? null);
        if ($new !== null && $new !== '' && !$b['after']) {
            $new = gen_content_brand($path, $new, $prep['brand']);
        }
        $ok = gen_content_allowed_for($prep['allowed'], $path, $b['old']);
        if ($new === null || $new === '' || gen_content_bad_figures($new, $ok)) {
            $new = $b['after'] ? null : gen_content_drop_figures((string)($new ?: $b['new']), $ok);
            $new = $new !== null ? gen_content_check($b['old'], $new) : null;
        }
        if ($new === null || $new === '') {
            $out['skipped']++;
            $out['figures'] = ($out['figures'] ?? 0) + 1;
            continue;
        }
        if ($b['after']) {
            [, $pid, $sid] = $b['where'];
            if ($pid !== null) {
                $out['after'][$pid][$sid] = $new;
            } else {
                $out['after'][$sid] = $new;
            }
        } elseif ($new !== $b['old']) {
            $out['fields'][$path] = $new;
        }
    }
    return $out;
}

/** One site's "ai" entry from the replies to its requests ($replies: [i => result | Throwable]). Throws when one failed. */
function gen_content_assemble(array $prep, array $replies): array
{
    $out = ['fields' => [], 'after' => [], 'model' => '', 'at' => gen_now(), 'tokens' => 0, 'skipped' => 0];
    foreach ($prep['req'] as $i => $q) {
        $r = $replies[$i] ?? new RuntimeException('No reply from Vertex AI.');
        if ($r instanceof Throwable) {
            throw $r;
        }
        $a = gen_content_answer($r, $q['group'], $q['after']);
        $allowed = $prep['allowed'] ?? null;
        foreach ($q['group'][3] as $path => $old) {
            $new = gen_content_check($old, $a['fields'][$path] ?? null);
            $new = $new === null ? null : gen_content_brand($path, $new, $prep['brand']);
            if ($new !== null && $allowed !== null && ($figs = gen_content_bad_figures($new, gen_content_allowed_for($allowed, $path, $old)))) { // asked again with the figures named
                $out['bad'][$path] = ['old' => $old, 'new' => $new, 'figs' => $figs, 'after' => false];
            } elseif ($new === null) {
                $out['skipped']++;
            } elseif ($new !== $old) {
                $out['fields'][$path] = $new;
            }
        }
        foreach ($a['after'] as $sid => $html) {
            if (($h = gen_content_after($html)) !== '' && $allowed !== null && ($figs = gen_content_bad_figures($h, $allowed))) {
                $out['bad']["after/" . ($prep['multi'] && $a['pid'] !== null ? $a['pid'] . '/' : '') . $sid] = ['old' => '', 'new' => $h, 'figs' => $figs, 'after' => true, 'where' => [null, $prep['multi'] ? $a['pid'] : null, $sid]];
            } elseif ($h !== '') {
                if ($prep['multi'] && $a['pid'] !== null) {
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
/** A database write that waits out a busy database (a build or the difference tables writing at the same moment). */
function gen_db_write(PDOStatement $st, array $args): void
{
    for ($try = 1; ; $try++) {
        try {
            $st->execute($args);
            return;
        } catch (PDOException $e) {
            if ($try >= 6 || !str_contains($e->getMessage(), 'locked') && !str_contains($e->getMessage(), 'busy')) {
                throw $e;
            }
            sleep(2 * $try);
        }
    }
}

/** The text step's lock file of a job: held while its run is writing. */
function gen_content_lock(int $jobId): string
{
    return sys_get_temp_dir() . "/satellite-generator-content-$jobId.lock";
}

/** Starts the text step of a job in the background (it exits at once when a run of that job is already writing). */
function gen_content_spawn(int $jobId): void
{
    global $GEN_ROOT;
    gen_mkdir("$GEN_ROOT/data/logs");
    $cmd = gen_php_cmd() . ' ' . escapeshellarg(__DIR__ . '/contentjob.php') . ' ' . $jobId . ' >> ' . escapeshellarg("$GEN_ROOT/data/logs/content-$jobId.log") . ' 2>&1';
    if (PHP_OS_FAMILY === 'Windows') {
        pclose(popen('start "" /B cmd /c "' . $cmd . '"', 'r'));
    } else {
        exec($cmd . ' &');
    }
}

/** Sites of a job waiting for text while no run is writing (a run that died): start one. Called when the job page loads. */
function gen_content_heal(PDO $db, int $jobId): void
{
    $q = $db->prepare("SELECT COUNT(*) FROM sites WHERE job_id = ? AND json_extract(content, '$.state') IN ('queued', 'writing')");
    $q->execute([$jobId]);
    if (!(int)$q->fetchColumn()) {
        return;
    }
    $h = @fopen(gen_content_lock($jobId), 'c');
    if ($h && flock($h, LOCK_EX | LOCK_NB)) { // nobody holds it: no run is alive
        flock($h, LOCK_UN);
        fclose($h);
        gen_content_spawn($jobId);
    }
}

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
    // sites left "writing" by a run that died are this run's now (one run per job: it holds the job's lock)
    gen_db_write($db->prepare("UPDATE sites SET content = json_set(content, '$.state', 'queued') WHERE job_id = ? AND json_extract(content, '$.state') = 'writing'"), [$jobId]);
    $jq = $db->prepare('SELECT market, spec, project_id FROM jobs WHERE id = ?');
    $jq->execute([$jobId]);
    $job = $jq->fetch() ?: [];
    $qn = $db->prepare("SELECT COUNT(*) FROM sites WHERE job_id = ? AND json_extract(content, '$.state') = 'queued'");
    $qn->execute([$jobId]);
    $queued = (int)$qn->fetchColumn();
    $od = $db->prepare('SELECT outdated FROM jobs WHERE id = ?');
    $od->execute([$jobId]);
    if ((int)$od->fetchColumn()) { // the project changed since the job's brand data was made: the text is written from the new data,
        gen_job_brands_reload($jobId); // and the sites not being written get it now (those being written are rebuilt after their text)
        gen_db_write($db->prepare("UPDATE sites SET status = 'refresh', error = NULL WHERE job_id = ? AND status IN ('built', 'failed')
            AND (content IS NULL OR json_extract(content, '$.state') NOT IN ('queued', 'writing'))"), [$jobId]);
        gen_db_write($db->prepare("UPDATE jobs SET outdated = 0, status = 'planned' WHERE id = ? AND status NOT IN ('running', 'checking')"), [$jobId]);
        gen_spawn($jobId);
    }
    if ($queued) { // the brands' own rating texts the project does not give, before the sites that show them
        require_once __DIR__ . '/brandfacts.php';
        gen_btexts_write($db, $jobId, $part);
    }
    $lim = gen_vertex_limits();
    $fail = function (string $id, Throwable $e) use ($set): bool { // one site's failure is that site's: the run goes on
        try {
            gen_db_write($set, [json_encode(['state' => 'error', 'at' => gen_now(), 'error' => $e->getMessage()]), $id]);
            return true;
        } catch (Throwable $e2) {
            fwrite(STDERR, date('c') . " $id: " . $e->getMessage() . ' / ' . $e2->getMessage() . "\n");
            return false; // the database will not take writes: the job page starts a new run later
        }
    };
    while (true) { // a batch of sites at a time («batch size»); all their page requests go out together, within the limits
        $s = $db->prepare("SELECT * FROM sites WHERE job_id = ? AND json_extract(content, '$.state') = 'queued' ORDER BY type, n LIMIT " . (int)$lim['batch']);
        $s->execute([$jobId]);
        $batch = $s->fetchAll();
        if (!$batch) {
            break;
        }
        $preps = $calls = [];
        foreach ($batch as $site) {
            gen_db_write($set, [json_encode(['state' => 'writing', 'at' => gen_now()]), $site['id']]);
            try {
                $preps[$site['id']] = gen_content_prepare($site, $info['below'], $part, $info, $job);
                foreach ($preps[$site['id']]['req'] as $n => $q) {
                    $calls[$site['id'] . '#' . $n] = $q['call'];
                }
            } catch (Throwable $e) {
                if (!$fail($site['id'], $e)) {
                    return;
                }
            }
        }
        $replies = $calls ? gen_vertex_many($calls, null, $lim) : [];
        $done = 0;
        $ais = $again = [];
        foreach ($batch as $site) {
            if (!isset($preps[$site['id']])) {
                continue;
            }
            try {
                $mine = [];
                foreach ($preps[$site['id']]['req'] as $n => $q) {
                    $mine[$n] = $replies[$site['id'] . '#' . $n] ?? null;
                }
                $ais[$site['id']] = gen_content_assemble($preps[$site['id']], $mine);
                if (!empty($ais[$site['id']]['bad'])) {
                    $again[$site['id']] = gen_content_retry_call($preps[$site['id']], $ais[$site['id']]['bad']);
                }
            } catch (Throwable $e) {
                if (!$fail($site['id'], $e)) {
                    return;
                }
            }
        }
        $second = $again ? gen_vertex_many($again, null, $lim) : []; // once more for texts with figures not in the data
        foreach ($batch as $site) {
            if (!isset($ais[$site['id']])) {
                continue;
            }
            try {
                $ai = $ais[$site['id']];
                if (array_key_exists('bad', $ai)) {
                    $ai = gen_content_retry_apply($preps[$site['id']], $ai, $second[$site['id']] ?? null);
                }
                $plan = json_decode($site['plan'], true) ?: [];
                $plan['ai'] = $ai;
                gen_db_write($db->prepare("UPDATE sites SET plan = ?, status = 'refresh', error = NULL WHERE id = ?"), [json_encode($plan, JSON_UNESCAPED_UNICODE), $site['id']]); // new text, same design: a quick refresh
                gen_db_write($set, [json_encode(['state' => 'done', 'at' => gen_now(), 'fields' => count($ai['fields']), 'after' => count(array_filter($ai['after'], 'is_string')) + array_sum(array_map(fn($x) => is_array($x) ? count($x) : 0, $ai['after'])), 'skipped' => $ai['skipped'], 'figures' => $ai['figures'] ?? 0, 'model' => $ai['model'], 'tokens' => $ai['tokens']]), $site['id']]);
                $done++;
            } catch (Throwable $e) {
                if (!$fail($site['id'], $e)) {
                    return;
                }
            }
        }
        if ($done) { // the worker refreshes this batch while the next one is written (one job at a time, as every build)
            gen_db_write($db->prepare("UPDATE jobs SET status = 'planned' WHERE id = ? AND status NOT IN ('running', 'checking')"), [$jobId]);
            gen_spawn($jobId);
        }
    }
}
