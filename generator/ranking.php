<?php
/**
 * Casino rankings of a job (spec "rank"): the job brand's score range and the range of positions it takes, and the
 * competitors, each with a score range (named by the user, or the made-up rivals). Every casino review site gets its
 * own draw: a position in the range, the brand's score, and each competitor's score in its range, so rankings differ
 * from site to site. The scores keep the order honest: a competitor placed above the brand scores higher than it
 * on the overall score and on every criterion, one placed below scores lower, so every list of the site (whatever it
 * ranks by) shows the brand at the same position and every list is in score order.
 *
 * spec rank: {own: [min, max] | null, pos: [min, max] | null, rivals: [{name, domain, min, max}]}
 */

const GEN_RANK_GAP = 0.2;               // the least a brand above or below differs from the job brand, overall
const GEN_RANK_DEFAULT = [6.5, 9.4];    // the made-up rivals' scores when no competitors are named
const GEN_RANK_RIVALS = 16;

/** A score from the form (8.5 or 8,5), or null. */
function gen_rank_num($v): ?float
{
    $v = str_replace(',', '.', trim((string)$v));
    return $v === '' || !is_numeric($v) ? null : round((float)$v, 1);
}

/**
 * Positions the job brand can take with score $s: [lowest, highest] (1 = top), or null when a competitor's range
 * sits too close to $s to be clearly above or below it.
 */
function gen_rank_room(float $s, array $ranges): ?array
{
    $above = $flex = 0;
    foreach ($ranges as [$min, $max]) {
        $up = $max >= $s + GEN_RANK_GAP - 1e-9;
        $down = $min <= $s - GEN_RANK_GAP + 1e-9;
        if (!$up && !$down) {
            return null;
        }
        if ($up && !$down) {
            $above++;
        } elseif ($up) {
            $flex++;
        }
    }
    return [$above + 1, $above + $flex + 1];
}

/** Every (score, position) the settings allow: [[score, position], …]. */
function gen_rank_options(array $own, array $pos, array $ranges): array
{
    $out = [];
    for ($s = $own[0]; $s <= $own[1] + 1e-9; $s = round($s + 0.1, 1)) {
        if ($room = gen_rank_room($s, $ranges)) {
            for ($p = max($pos[0], $room[0]); $p <= min($pos[1], $room[1]); $p++) {
                $out[] = [$s, $p];
            }
        }
    }
    return $out;
}

/**
 * The job form's ranking settings, checked: [rank or null, error or null]. $sample: how many made-up rivals the job
 * lists when no competitors are named.
 */
function gen_rank_input($in, int $sample): array
{
    if (!is_array($in)) {
        return [null, null];
    }
    $rivals = [];
    foreach (array_slice((array)($in['rivals'] ?? []), 0, GEN_RANK_RIVALS) as $r) {
        $name = mb_substr(trim((string)($r['name'] ?? '')), 0, 60);
        if ($name === '') {
            continue;
        }
        $min = gen_rank_num($r['min'] ?? '') ?? GEN_RANK_DEFAULT[0];
        $max = gen_rank_num($r['max'] ?? '') ?? GEN_RANK_DEFAULT[1];
        if ($min < 1 || $max > 10 || $min > $max) {
            return [null, "«{$name}»: a score range from 1 to 10, the first number not above the second."];
        }
        $rivals[] = ['name' => $name, 'domain' => mb_substr(trim((string)($r['domain'] ?? '')), 0, 120), 'min' => $min, 'max' => $max];
    }
    $own = [gen_rank_num($in['own'][0] ?? ''), gen_rank_num($in['own'][1] ?? '')];
    $pos = [(int)($in['pos'][0] ?? 0), (int)($in['pos'][1] ?? 0)];
    $hasOwn = $own[0] !== null || $own[1] !== null;
    $hasPos = $pos[0] > 0 || $pos[1] > 0;
    if (!$rivals && !$hasOwn && !$hasPos) {
        return [null, null];
    }
    if ($hasOwn) {
        $own = [$own[0] ?? $own[1], $own[1] ?? $own[0]];
        if ($own[0] < 1 || $own[1] > 10 || $own[0] > $own[1]) {
            return [null, 'Your brand’s score: from 1 to 10, the first number not above the second.'];
        }
    }
    $n = 1 + ($rivals ? count($rivals) : $sample);
    if ($hasPos) {
        $pos = [max(1, $pos[0] ?: $pos[1]), $pos[1] ?: $pos[0]];
        if ($pos[0] > $pos[1] || $pos[1] > $n) {
            return [null, "Your brand’s position: from 1 to $n (the number of brands in the ranking), the first number not above the second."];
        }
    }
    $rank = ['own' => $hasOwn ? $own : null, 'pos' => $hasPos ? $pos : null, 'rivals' => $rivals];
    if ($hasOwn) {
        $ranges = $rivals ? array_map(fn($r) => [$r['min'], $r['max']], $rivals) : array_fill(0, $sample, GEN_RANK_DEFAULT);
        if (!gen_rank_options($own, $hasPos ? $pos : [1, $n], $ranges)) {
            return [null, 'With these score ranges your brand cannot take these positions: competitors that must score higher (or lower) leave no room, or a range is closer than ' . GEN_RANK_GAP . ' to your brand’s score. Widen a range or the positions.'];
        }
    }
    return [$rank, null];
}

/** The competitors named in the job form as brands to create (they get example data, like the made-up rivals). */
function gen_rank_rival_brands(array $spec): array
{
    $products = $spec['brands'][0]['products'] ?? 'both';
    $out = [];
    foreach ($spec['rank']['rivals'] ?? [] as $r) {
        $out[] = ['name' => $r['name'], 'products' => $products] + ($r['domain'] !== '' ? ['domain' => $r['domain']] : []);
    }
    return $out ? array_map(fn($b) => $b + ['sample' => true], gen_clean_brands($out)) : [];
}

/** The rivals a job lists in its rankings: the competitors named in the job form, else $n made-up ones. */
function gen_job_rivals(array $spec, int $jobId): array
{
    return gen_rank_rival_brands($spec) ?: gen_sample_brands($spec['brands'], (int)($spec['sample_brands'] ?? 0), $jobId);
}

/** The criterion scores of a site's listed brands from their data: [brand id => [criterion => score]] (its product's view). */
function gen_rank_base(array $cfg, string $market): array
{
    global $GEN_ROOT;
    $ratings = sx_brand_data($GEN_ROOT, "ratings/$market.json")['brands'] ?? [];
    $out = [];
    foreach ($cfg['brands'] ?? [] as $id) {
        $r = $ratings[$id] ?? [];
        $sc = $r['scores'] ?? [];
        if (isset($cfg['product']) && is_array($r['products'][$cfg['product']]['scores'] ?? null)) {
            $sc = array_merge($sc, $r['products'][$cfg['product']]['scores']);
        }
        $out[$id] = array_map(fn($x) => (float)($x['score'] ?? 0), array_filter($sc, 'is_array'));
    }
    return $out;
}

/** A site's criteria and their weights, as the site will score them (lib/page.php, lib/casinos.php sx_criteria). */
function gen_rank_criteria(array $cfg): array
{
    global $GEN_ROOT;
    $bp = sx_json("$GEN_ROOT/blueprints/{$cfg['type']}.json");
    $crit = $bp['products'][$cfg['product'] ?? '']['criteria'] ?? $bp['criteria'] ?? [];
    $m = sx_json("$GEN_ROOT/markets/{$cfg['market']}.json");
    $promo = (bool)array_filter($m['promotions'] ?? [], fn($v) => $v === 'allowed');
    $out = [];
    foreach ($crit as $c => $d) {
        if (is_array($d) && (!empty($d['requires_promo']) && !$promo) === false) {
            $out[$c] = (float)($d['weight'] ?? 1);
        }
    }
    return $out;
}

/** Every criterion a casino ranking may show (any product), so every list keeps the order. */
function gen_rank_all_criteria(string $type): array
{
    global $GEN_ROOT;
    $bp = sx_json("$GEN_ROOT/blueprints/$type.json");
    $all = array_keys(array_filter($bp['criteria'] ?? [], 'is_array'));
    foreach ($bp['products'] ?? [] as $p) {
        if (is_array($p['criteria'] ?? null)) {
            $all = array_merge($all, array_keys($p['criteria']));
        }
    }
    return array_values(array_unique($all));
}

/**
 * A casino review site's draw: [brand id => [criterion => score]] for every listed brand, or [] when the job has no
 * ranking settings (or the site lists no brands). $base: [brand id => [criterion => score]] from the brands' data.
 */
function gen_rank_scores(array $cfg, array $spec, array $base): array
{
    $rank = $spec['rank'] ?? null;
    $own = $cfg['featured'] ?? null;
    $ids = $cfg['brands'] ?? [];
    if (!$rank || !$own || !in_array($own, $ids, true)) {
        return [];
    }
    $w = gen_rank_criteria($cfg);
    $all = gen_rank_all_criteria($cfg['type']);
    if (!$w) {
        return [];
    }
    $avg = function (array $sc) use ($w): float {
        $t = 0;
        foreach ($w as $c => $x) {
            $t += $x * ($sc[$c] ?? 0);
        }
        return $t / array_sum($w);
    };
    $seed = (string)$cfg['id'];
    $rnd = function (string $salt, float $lo, float $hi) use ($seed): float { // a seeded number in [lo, hi], on 0.1 steps
        $steps = (int)round(($hi - $lo) * 10);
        return round($lo + ($steps > 0 ? crc32("$seed|$salt") % ($steps + 1) : 0) / 10, 1);
    };
    // each competitor's range: a named competitor's own, another job brand's the same as the brand's, a made-up rival's the default
    $named = [];
    foreach ($rank['rivals'] ?? [] as $r) {
        $named['u' . preg_replace('/^u(\d+)-.*/', '$1', $own) . '-' . gen_slug($r['name'])] = [$r['min'], $r['max']];
    }
    $ownRange = $rank['own'] ?? [round($avg($base[$own] ?? []), 1), round($avg($base[$own] ?? []), 1)];
    $ranges = [];
    foreach ($ids as $id) {
        if ($id !== $own) {
            $ranges[$id] = $named[$id] ?? (in_array($id, $spec['brand_ids'] ?? [], true) ? $ownRange : GEN_RANK_DEFAULT);
        }
    }
    $opts = gen_rank_options($ownRange, $rank['pos'] ?? [1, count($ids)], array_values($ranges));
    if (!$opts) {
        return [];
    }
    $positions = array_values(array_unique(array_column($opts, 1)));
    $p = $positions[crc32("$seed|rankpos") % count($positions)];
    $scores = array_values(array_filter(array_column(array_filter($opts, fn($o) => $o[1] === $p), 0)));
    $s = $scores[crc32("$seed|rankscore") % count($scores)];
    // who is above: those that cannot be below, then others in a seeded order until p - 1
    $must = $flex = [];
    foreach ($ranges as $id => [$min, $max]) {
        $up = $max >= $s + GEN_RANK_GAP - 1e-9;
        $down = $min <= $s - GEN_RANK_GAP + 1e-9;
        if ($up && !$down) {
            $must[] = $id;
        } elseif ($up) {
            $flex[] = $id;
        }
    }
    usort($flex, fn($a, $b) => crc32("$seed|$a") <=> crc32("$seed|$b"));
    $above = array_merge($must, array_slice($flex, 0, $p - 1 - count($must)));
    // the brand's criteria: its data moved to score $s, with room above it when it is not first
    $cap = $above ? 9.7 : 10.0;
    $floor = count($above) < count($ranges) ? 1.3 : 1.0;
    $mine = [];
    foreach ($all as $c) {
        $mine[$c] = (float)($base[$own][$c] ?? $s);
    }
    for ($i = 0; $i < 4; $i++) {
        $shift = $s - $avg($mine);
        foreach ($mine as $c => $v) {
            $mine[$c] = round(max($floor, min($cap, $v + $shift)), 1);
        }
    }
    $sAct = round($avg($mine), 1);
    $out = [$own => $mine];
    foreach ($ranges as $id => [$min, $max]) {
        $up = in_array($id, $above, true);
        $t = $up ? $rnd("t$id", max($min, $sAct + GEN_RANK_GAP), max($max, $sAct + GEN_RANK_GAP)) : $rnd("t$id", min($min, $sAct - GEN_RANK_GAP), min($max, $sAct - GEN_RANK_GAP));
        $d = $t - $sAct;
        // criterion by criterion around the brand's, each on the same side of it, spread a little (weighted mean kept)
        $u = [];
        foreach ($all as $c) {
            $u[$c] = ((crc32("$seed|$id|$c") % 101) / 100 - 0.5); // -0.5 … 0.5
        }
        $mu = array_sum(array_map(fn($c, $x) => $x * ($u[$c] ?? 0), array_keys($w), $w)) / array_sum($w);
        $sc = [];
        foreach ($all as $c) {
            $v = $mine[$c] + $d * (1 + $u[$c] - $mu);
            $v = $up ? max($v, $mine[$c] + 0.1) : min($v, $mine[$c] - 0.1);
            $sc[$c] = round(max(1.0, min(10.0, $v)), 1);
        }
        // a criterion held at 10 or 1 pulls the average away from the drawn score: the others make it up, on their side of the brand's
        for ($i = 0; $i < 6 && abs($gap = $t - $avg($sc)) >= 0.05; $i++) {
            $free = array_filter(array_keys($w), fn($c) => $gap > 0 ? $sc[$c] < 10.0 && ($up || $sc[$c] < $mine[$c] - 0.1) : $sc[$c] > 1.0 && (!$up || $sc[$c] > $mine[$c] + 0.1));
            if (!$free) {
                break;
            }
            $step = $gap * array_sum($w) / array_sum(array_map(fn($c) => $w[$c], $free));
            foreach ($free as $c) {
                $v = $sc[$c] + $step;
                $v = $up ? max($v, $mine[$c] + 0.1) : min($v, $mine[$c] - 0.1);
                $sc[$c] = round(max(1.0, min(10.0, $v)), 1);
            }
        }
        $out[$id] = $sc;
    }
    return $out;
}
