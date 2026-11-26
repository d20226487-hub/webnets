<?php
/**
 * Sample user reviews of a job's own brands, written by Vertex AI from the brand's master review (Мастер обзор) and
 * the project information, in the job's main language. They replace the example reviews borrowed from a demo brand,
 * stay marked as sample reviews ("source": "seed": demo only, with the sample banner) and keep a realistic share of
 * low ratings. «Translate» translates them like the offers (tr_cache); each language version shows its own copy.
 */

/** The job form's defaults: reviews per site (each site its own random number in the range) and the share per star. */
const GEN_SREVIEWS_DEFAULT = ['min' => 12, 'max' => 30, 'stars' => ['5' => 40, '4' => 25, '3' => 15, '2' => 10, '1' => 10]];
const GEN_SREVIEWS_MAX = 60;

/** The job form's review settings, checked: [settings, error or null]. */
function gen_sreviews_input($in): array
{
    $in = is_array($in) ? $in : [];
    $min = (int)($in['min'] ?? GEN_SREVIEWS_DEFAULT['min']);
    $max = (int)($in['max'] ?? GEN_SREVIEWS_DEFAULT['max']);
    $stars = [];
    foreach (['5', '4', '3', '2', '1'] as $s) {
        $stars[$s] = max(0, min(100, (int)($in['stars'][$s] ?? GEN_SREVIEWS_DEFAULT['stars'][$s])));
    }
    $set = ['min' => $min, 'max' => $max, 'stars' => $stars];
    return [$set, match (true) {
        $min < 1 || $max > GEN_SREVIEWS_MAX || $min > $max => 'Reviews per site: from 1 to ' . GEN_SREVIEWS_MAX . ', the first number not above the second.',
        array_sum($stars) !== 100 => 'The shares of reviews by stars must add up to 100%.',
        $stars['3'] + $stars['2'] + $stars['1'] < 100 * SX_REVIEW_MIN_LOW_SHARE => 'At least 20% of reviews must have 1–3 stars, so the reviews look real and negative ones stay visible.',
        default => null,
    }];
}

/** A job's review settings (the defaults for a job from before they existed). */
function gen_sreviews_settings(array $spec): array
{
    return is_array($spec['reviews'] ?? null) ? $spec['reviews'] : GEN_SREVIEWS_DEFAULT;
}

/** How many reviews a site shows and their shares by stars: a number in the job's range, its own per site. */
function gen_sreviews_pick(array $spec, string $siteId): array
{
    $s = gen_sreviews_settings($spec);
    return ['n' => $s['min'] + crc32("$siteId|reviews") % ($s['max'] - $s['min'] + 1), 'stars' => $s['stars'], 'seed' => $siteId];
}
const GEN_SREVIEWS_BLOCKS = ['review-list', 'store-reviews', 'rating-summary']; // site sections that show user reviews

function gen_sreviews_table(PDO $db): void
{
    $db->exec('CREATE TABLE IF NOT EXISTS brand_reviews (brand_id TEXT PRIMARY KEY, job_id INTEGER NOT NULL REFERENCES jobs(id) ON DELETE CASCADE,
        lang TEXT NOT NULL, src TEXT NOT NULL, data TEXT NOT NULL, updated TEXT NOT NULL)');
}

/** The reviews written for a job brand: ['lang' => main language, 'src' => input hash, 'reviews' => [...]], or null. */
function gen_sreviews_saved(PDO $db, string $id): ?array
{
    gen_sreviews_table($db);
    $q = $db->prepare('SELECT lang, src, data FROM brand_reviews WHERE brand_id = ?');
    $q->execute([$id]);
    $r = $q->fetch();
    $q->closeCursor();
    return $r ? ['lang' => $r['lang'], 'src' => $r['src'], 'reviews' => json_decode($r['data'], true) ?: []] : null;
}

/** Whether a job has sites that show user reviews (brand review sites, app pages). */
function gen_sreviews_needed(array $spec): bool
{
    foreach (array_keys($spec['types'] ?? []) as $type) {
        foreach (gen_site_sections($type) as $secs) {
            if (array_intersect($secs, GEN_SREVIEWS_BLOCKS)) {
                return true;
            }
        }
    }
    return false;
}

/** The job's main language: its first language (multi-language jobs), else its market's. */
function gen_job_main_lang(array $spec, array $m): string
{
    $l = gen_job_langs($spec);
    return $l ? array_key_first($l) : ($m['default_locale'] ?? 'en');
}

/**
 * Write the sample reviews of the job's own brands that have none yet, or whose master review or project information
 * changed since. Called by the text step before the sites are written.
 */
function gen_sreviews_write(PDO $db, int $jobId, string $projectPart): void
{
    global $GEN_ROOT;
    require_once __DIR__ . '/content.php';
    require_once __DIR__ . '/brandfacts.php';
    $q = $db->prepare('SELECT market, spec, project_id FROM jobs WHERE id = ?');
    $q->execute([$jobId]);
    $job = $q->fetch() ?: [];
    $q->closeCursor();
    $spec = json_decode($job['spec'] ?? '{}', true) ?: [];
    if (!$job || !gen_sreviews_needed($spec)) {
        return;
    }
    gen_sreviews_table($db);
    $m = sx_json("$GEN_ROOT/markets/{$job['market']}.json");
    $lang = gen_job_main_lang($spec, $m);
    $ops = sx_brand_data($GEN_ROOT, 'operators.json');
    foreach ($spec['brand_ids'] ?? [] as $id) {
        if (empty($ops[$id]['user_brand']) || !empty($ops[$id]['sample'])) {
            continue;
        }
        $name = $ops[$id]['name'];
        $facts = gen_content_brand_facts(['_id' => $id] + $ops[$id], $job['market'], $m);
        $flines = implode("\n", array_map(fn($k, $x) => "- $k: $x", array_keys($facts), $facts));
        $set = gen_sreviews_settings($spec);
        $counts = sx_review_split($set['max'], $set['stars']); // the brand's set: as many as the most a site shows, in the job's shares
        $src = md5($lang . "\n" . json_encode($counts) . "\n" . $flines . "\n" . $projectPart);
        $have = gen_sreviews_saved($db, $id);
        if ($have && $have['src'] === $src && $have['lang'] === $lang) {
            continue;
        }
        $country = is_array($m['name'] ?? null) ? ($m['name']['en'] ?? reset($m['name'])) : ($m['name'] ?? strtoupper($m['id']));
        $P = [];
        $P[] = 'Write ' . $set['max'] . " sample player reviews of the online gambling brand $name for a demo of a review website in $country, in " . sx_brief_lang($lang) . " ($lang).";
        $P[] = 'They are clearly labelled sample content on the demo, standing in for real reviews later; write them as varied, believable players would.';
        $P[] = '';
        $P[] = '- Exactly this many reviews per rating: ' . implode(', ', array_map(fn($s, $n) => "$n at $s stars", array_keys($counts), $counts)) . '. Low ratings name real weak points the figures and project information show (for example no phone line, a verification step, a minimum withdrawal) or ordinary frustrations (a slow evening, a lost bonus wager), never invented scandals.';
        $P[] = '- Each review: a first name and the initial of the last name usual in ' . $country . ', a city in ' . $country . ', a short title (up to 60 characters) and a text of 1–4 sentences (up to 400 characters), in the voice of different players.';
        $P[] = '- topics: 1–2 of payouts, verification, support, app, odds, deposits, matching what the review is about.';
        $P[] = '- About ' . max(1, (int)round($set['max'] * .4)) . ' reviews get a short, polite reply from the brand (up to 250 characters): thanks, an explanation, an offer to help through support; no promises or bonuses.';
        $P[] = '- days_ago: when it was written, 1 to 120 days ago, spread out.';
        $P[] = "- Write the brand exactly as $name. Use a figure only when it is in the figures or the project information below, exactly as given; otherwise write without a figure. No promises of winnings, no guaranteed claims, nothing about other operators.";
        $P[] = '';
        $P[] = "## $name figures";
        $P[] = $flines ?: '- none given';
        $P[] = '';
        $P[] = sx_ai_market_facts($m, $lang, []);
        $P[] = '';
        $P[] = $projectPart !== '' ? $projectPart : "## Project information\n\nNone given.";
        $P[] = '';
        $P[] = 'Answer with JSON only: [{"name": "…", "city": "…", "rating": 1-5, "topics": ["…"], "title": "…", "text": "…", "reply": "…" or null, "days_ago": n}, …]';
        $allowed = gen_content_numbers($flines . "\n" . $projectPart);
        foreach ([24, 7, 365, (int)date('Y')] as $n) {
            $allowed['any'][(string)$n] = true;
        }
        try {
            $r = gen_vertex_generate(implode("\n", $P), 'You write believable sample player reviews for a clearly labelled demo, from the data given. You never invent facts or figures.', true,
                gen_vertex_max('tok_reviews'), null, ['project' => (int)$job['project_id'] ?: null, 'job' => $jobId, 'kind' => 'text', 'limit' => 'tok_reviews']);
            $json = trim($r['text']);
            if (preg_match('/```(?:json)?\s*(.*?)```/s', $json, $mm)) {
                $json = $mm[1];
            }
            $a = json_decode($json, true);
            $out = [];
            foreach (is_array($a) ? $a : [] as $x) {
                if (!is_array($x) || !is_string($x['title'] ?? null) || !is_string($x['text'] ?? null)) {
                    continue;
                }
                $title = mb_substr(trim(strip_tags($x['title'])), 0, 80);
                $text = mb_substr(trim(strip_tags($x['text'])), 0, 600);
                $reply = is_string($x['reply'] ?? null) ? mb_substr(trim(strip_tags($x['reply'])), 0, 400) : '';
                if ($title === '' || $text === '' || gen_content_bad_figures("$title $text $reply", $allowed)) { // a figure the data does not give: the review is left out
                    continue;
                }
                $out[] = [
                    'name' => mb_substr(trim((string)($x['name'] ?? '')), 0, 40) ?: '—',
                    'city' => mb_substr(trim((string)($x['city'] ?? '')), 0, 40),
                    'rating' => max(1, min(5, (int)($x['rating'] ?? 3))),
                    'topics' => array_values(array_intersect((array)($x['topics'] ?? []), SX_REVIEW_TOPICS)) ?: ['support'],
                    'title' => gen_content_brand('', $title, $name),
                    'text' => gen_content_brand('', $text, $name),
                    'reply' => $reply !== '' ? $reply : null,
                    'days' => max(1, min(365, (int)($x['days_ago'] ?? 30))),
                ];
            }
            $low = count(array_filter($out, fn($x) => $x['rating'] <= 3));
            if (count($out) < min(8, $set['min']) || $low / count($out) < SX_REVIEW_MIN_LOW_SHARE) { // too few, or too few low ratings: the example reviews stay
                throw new RuntimeException("The sample reviews of $name came back incomplete (" . count($out) . " usable, $low at 1–3 stars).");
            }
            gen_db_write($db->prepare('INSERT OR REPLACE INTO brand_reviews (brand_id, job_id, lang, src, data, updated) VALUES (?, ?, ?, ?, ?, ?)'),
                [$id, $jobId, $lang, $src, json_encode($out, JSON_UNESCAPED_UNICODE), gen_now()]);
            gen_sreviews_apply($db, $id, $job['market']);
        } catch (Throwable $e) {
            fwrite(STDERR, date('c') . " sample reviews $id: " . $e->getMessage() . "\n");
        }
    }
}

/**
 * A job brand's written reviews replace its example reviews in the market: in the main language, and in each other
 * job language its translation once translated (tr_cache). The newest is dated like the example reviews' newest, so
 * a demo with a fixed date shows no review from its future.
 */
function gen_sreviews_apply(PDO $db, string $id, string $market): void
{
    $have = gen_sreviews_saved($db, $id);
    if (!$have || !$have['reviews'] || !preg_match('/^u(\d+)-/', $id, $jm)) {
        return;
    }
    $q = $db->prepare('SELECT spec FROM jobs WHERE id = ?');
    $q->execute([(int)$jm[1]]);
    $langs = array_keys(gen_job_langs(json_decode((string)$q->fetchColumn(), true) ?: []));
    $q->closeCursor();
    $main = $have['lang'];
    gen_overlay_update("reviews/$id.json", function ($d) use ($have, $market, $langs, $main, $db) {
        // the dates count back from the example reviews' newest (kept in the file once they are replaced)
        $old = array_filter($d['reviews'] ?? [], fn($r) => ($r['market'] ?? '') === $market && empty($r['ai']));
        $anchor = $d['anchor'][$market] ?? ($old ? max(array_column($old, 'date')) : date('Y-m-d'));
        $d['anchor'][$market] = $anchor;
        $keep = array_values(array_filter($d['reviews'] ?? [], fn($r) => ($r['market'] ?? '') !== $market)); // the market's reviews are all replaced
        $out = [];
        foreach ($have['reviews'] as $i => $x) {
            $base = ['source' => 'seed', 'status' => 'published', 'market' => $market, 'ai' => true, 'name' => $x['name'], 'city' => $x['city'],
                'date' => date('Y-m-d', strtotime("$anchor -" . ($x['days'] - 1) . ' days')), 'rating' => $x['rating'], 'topics' => $x['topics'], 'verified' => $i % 4 !== 3];
            foreach (array_unique(array_merge([$main], $langs)) as $l) {
                $tr = fn($t) => $l === $main || $t === null ? $t : gen_tr_cached($db, $l, $t);
                $title = $tr($x['title']);
                $text = $tr($x['text']);
                if ($title === null || $text === null) { // not translated yet: that language shows the main language's
                    continue;
                }
                $reply = $x['reply'] !== null ? ($tr($x['reply']) ?? $x['reply']) : null;
                // the reviewer's name and city as that language writes them (Latin letters in English and Portuguese)
                $who = ['name' => $tr($x['name']) ?? $x['name'], 'city' => $x['city'] !== '' ? ($tr($x['city']) ?? $x['city']) : ''];
                $out[] = ['id' => 'ai' . ($i + 1) . ($l === $main ? '' : "-$l"), 'locale' => $l] + $who + $base + ['title' => $title, 'text' => $text]
                    + ($reply !== null ? ['reply' => ['date' => date('Y-m-d', strtotime("$anchor -" . max(0, $x['days'] - 3) . ' days')), 'text' => $reply]] : []);
            }
        }
        return ['reviews' => array_merge($keep, $out)] + $d;
    });
}

/** The texts of a job's written reviews to translate: [md5 => text]. */
function gen_sreviews_texts(PDO $db, array $spec): array
{
    $texts = [];
    foreach ($spec['brand_ids'] ?? [] as $id) {
        foreach (gen_sreviews_saved($db, $id)['reviews'] ?? [] as $x) {
            foreach (['name', 'city', 'title', 'text', 'reply'] as $k) {
                if (is_string($x[$k] ?? null) && $x[$k] !== '') {
                    $texts[md5($x[$k])] = $x[$k];
                }
            }
        }
    }
    return $texts;
}
