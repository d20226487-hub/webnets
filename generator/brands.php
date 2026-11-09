<?php
/**
 * User brands for the website network generator.
 *
 * The job form takes the user's own brands (name, optional domain and colour, casino / sport / both). Each one is
 * written to the brand data overlay (data/overlay/brands/, same layout as brands/) as a full brand: operator entry,
 * letter-mark logo, promotions, game catalog, reviews, market facts and an editorial rating. Everything except the
 * name, domain and colour is cloned from donor brands with its copy replaced by placeholders, so every site type has
 * the data it needs in any market.
 */

const GEN_DONOR = 'nyxa';
const GEN_BRAND_COLORS = ['#E4572E', '#2E86AB', '#7B2CBF', '#F2A541', '#17A398', '#D7263D', '#3F88C5', '#6A994E'];

function gen_slug(string $s): string
{
    $t = function_exists('iconv') ? (string)@iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) : $s;
    $t = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $t)), '-');
    return $t !== '' ? substr($t, 0, 24) : 'b' . dechex(crc32($s));
}

/** Clean the brands posted with a job: [{name, domain, color, products}], names unique. */
function gen_clean_brands(array $in): array
{
    $out = [];
    foreach ($in as $b) {
        if (!is_array($b) || ($name = trim(mb_substr((string)($b['name'] ?? ''), 0, 40))) === '') {
            continue;
        }
        $domain = strtolower(trim((string)($b['domain'] ?? '')));
        $domain = (string)(parse_url(preg_match('~^https?://~', $domain) ? $domain : "https://$domain", PHP_URL_HOST) ?? '');
        $out[mb_strtolower($name)] = [
            'name' => $name,
            'domain' => preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain) ? $domain : '',
            'color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)($b['color'] ?? '')) ? $b['color'] : '',
            'products' => in_array($b['products'] ?? '', ['casino', 'sports'], true) ? $b['products'] : 'both',
        ];
    }
    return array_values($out);
}

function gen_overlay_dir(): string
{
    global $GEN_ROOT;
    return sx_brand_overlay($GEN_ROOT);
}

/** Read-modify-write one overlay JSON file under a lock. */
function gen_overlay_update(string $rel, callable $fn): void
{
    $file = gen_overlay_dir() . "/$rel";
    is_dir(dirname($file)) || mkdir(dirname($file), 0777, true);
    // writers take turns on a lock file; the new content is written aside and renamed over the old file, so a build
    // reading brand data at the same moment (it takes no lock) sees the old file or the new one, never a half-written one
    $h = fopen("$file.lock", 'c');
    flock($h, LOCK_EX);
    $data = is_file($file) ? (json_decode((string)file_get_contents($file), true) ?: []) : [];
    $data = $fn($data);
    $tmp = "$file." . getmypid() . '.tmp';
    file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    rename($tmp, $file);
    flock($h, LOCK_UN);
    fclose($h);
}

/** Host of a brand's links: its own domain, else <slug> on the market's required suffix (e.g. .bet.br) or .example. */
function gen_brand_host(array $b, string $market): string
{
    global $GEN_ROOT;
    if (!empty($b['domain'])) {
        return $b['domain'];
    }
    $tld = sx_json("$GEN_ROOT/markets/$market.json")['compliance']['brand_domain_suffix'] ?? '.example';
    return gen_slug($b['name']) . (str_starts_with($tld, '.') ? $tld : ".$tld");
}

/** Letter-mark logo: a rounded tile in the brand colour (currentColor) with the brand's initials cut out. */
function gen_logo_svg(string $name, string $id): string
{
    $words = preg_split('/\s+/u', trim($name));
    $ini = mb_strtoupper(count($words) > 1 ? mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1) : mb_substr($name, 0, 2));
    $shape = crc32($id) % 3;
    $tile = ['<rect x="2" y="2" width="28" height="28" rx="8"/>', '<circle cx="16" cy="16" r="14"/>', '<path d="M16 2 28.1 9v14L16 30 3.9 23V9z"/>'][$shape];
    $size = mb_strlen($ini) > 1 ? 12 : 16;
    $mask = 'm' . substr(md5($id), 0, 6);
    return '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><mask id="' . $mask . '"><rect width="32" height="32" fill="#fff"/>'
        . '<text x="16" y="16" dy=".35em" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-weight="700" font-size="' . $size . '" fill="#000">'
        . htmlspecialchars($ini, ENT_XML1) . '</text></mask><g fill="currentColor" mask="url(#' . $mask . ')">' . $tile . '</g></svg>';
}

/**
 * Copy for brand data: the donor's copy with its name swapped for the brand's ($GLOBALS['GEN_BRAND_NAMES'] set), else
 * placeholders. Ids, numbers, dates and settings are kept either way.
 */
function gen_brand_copy($v, string $path)
{
    $names = $GLOBALS['GEN_BRAND_NAMES'] ?? null;
    return $names !== null ? gen_swap_names($v, $names) : gen_placeholders($v, 'brand-data', $path);
}

/**
 * Write the user's brands for a job to the overlay. Returns their ids (u<job>-<slug>), in form order.
 * $brands: cleaned brands from gen_clean_brands().
 */
function gen_materialise_brands(array $brands, string $market, int $jobId, string $copy = 'demo'): array
{
    global $GEN_ROOT;
    $ops = sx_json("$GEN_ROOT/brands/operators.json");
    $donor = $ops[GEN_DONOR];
    $m = sx_json("$GEN_ROOT/markets/$market.json");
    $locales = $m['locales'];
    $markets = array_map(fn($f) => basename($f, '.json'), glob("$GEN_ROOT/markets/*.json"));
    $ids = [];
    foreach ($brands as $i => $b) {
        $id = "u$jobId-" . gen_slug($b['name']);
        $ids[] = $id;
        $GLOBALS['GEN_BRAND_NAMES'] = $copy === 'demo' ? [] : null;
        if ($copy === 'demo') {
            foreach (sx_json("$GEN_ROOT/brands/operators.json") as $did => $o) {
                if ($did !== '_note') {
                    $GLOBALS['GEN_BRAND_NAMES'][$o['name']] = $b['name'];
                }
            }
        }
        $host = gen_brand_host($b, $market);
        $color = $b['color'] ?: GEN_BRAND_COLORS[crc32($b['name']) % count(GEN_BRAND_COLORS)];
        $verticals = ['casino' => ['casino', 'live-casino'], 'sports' => ['sports', 'live'], 'both' => ['sports', 'live', 'casino', 'live-casino']][$b['products']];
        $tag = [];
        foreach (array_unique(array_merge(array_keys($donor['tagline']), $locales)) as $l) {
            $tag[$l] = $copy === 'demo' && isset($donor['tagline'][$l]) ? $donor['tagline'][$l] : gen_filler(28, "$id.tagline");
        }
        $app = $donor['app'];
        $app['name'] = $b['name'];
        $app['developer'] = "{$b['name']} Ltd";
        $app['changelog'] = gen_brand_copy($app['changelog'], "$id.changelog");
        unset($app['store']['_note']);
        $op = [
            'name' => $b['name'],
            'kind' => ['casino' => 'casino', 'sports' => 'sportsbook', 'both' => 'hybrid'][$b['products']],
            'personality' => 'User brand added in the website network generator.',
            'verticals' => $verticals,
            'markets' => $markets,
            'brand_color' => $color,
            'suggested_presets' => $donor['suggested_presets'],
            'logo' => "logos/$id.svg",
            'license_no' => 'LIC-' . strtoupper(substr(md5($id), 0, 6)),
            'facts' => $donor['facts'],
            'tagline' => $tag,
            'app' => $app,
            'domain' => $host,
            'user_brand' => true,
        ];
        gen_overlay_update('operators.json', fn($d) => array_merge($d, [$id => $op]));
        is_dir(gen_overlay_dir() . '/logos') || mkdir(gen_overlay_dir() . '/logos', 0777, true);
        $logo = gen_overlay_dir() . "/logos/$id.svg";
        file_put_contents("$logo." . getmypid() . '.tmp', gen_logo_svg($b['name'], $id));
        rename("$logo." . getmypid() . '.tmp', $logo);

        // promotions: one of each type found in the donor feeds, for this market
        $promos = [];
        foreach (glob("$GEN_ROOT/brands/promotions/*.json") as $f) {
            if (basename($f) === '_template.json') {
                continue;
            }
            foreach (sx_json($f)['promotions'] ?? [] as $p) {
                $promos[$p['type']] ??= $p;
            }
        }
        $n = 0;
        $feed = [];
        foreach ($promos as $p) {
            $n++;
            $p = gen_brand_copy($p, "$id.promo.$n");
            $p['id'] = "$id-$n";
            $p['markets'] = [$market];
            if (!empty($p['code'])) {
                $p['code'] = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', gen_slug($b['name'])), 0, 6)) . (10 * $n + 5);
            }
            if (!empty($p['terms_url'])) {
                $p['terms_url'] = "https://$host/promotions/terms";
            }
            $feed[] = $p;
        }
        gen_overlay_update("promotions/$id.json", fn($d) => ['promotions' => array_merge(array_values(array_filter($d['promotions'] ?? [], fn($p) => !in_array($market, $p['markets'] ?? [], true))), $feed)]);

        // game catalog: the donor's studios, carried in this market
        $cat = sx_json("$GEN_ROOT/brands/catalog/" . GEN_DONOR . '.json')['markets'];
        $here = $cat[$market] ?? reset($cat);
        gen_overlay_update("catalog/$id.json", function ($d) use ($market, $here) {
            $d['markets'][$market] = $here;
            return $d;
        });

        // reviews: the donor's seeded reviews, as this market's
        $revs = [];
        foreach (sx_json("$GEN_ROOT/brands/reviews/" . GEN_DONOR . '.json')['reviews'] as $k => $r) {
            $same = in_array($r['locale'] ?? '', $locales, true); // the review's language is one of the market's
            $r = $same && $copy === 'demo' ? gen_brand_copy($r, "$id.review.$k") : gen_placeholders($r, 'brand-data', "$id.review.$k");
            $r['market'] = $market;
            $r['locale'] = $same && $copy === 'demo' ? $r['locale'] : $m['default_locale'];
            if (!$same || $copy !== 'demo') {
                $r['name'] = 'Reader ' . chr(65 + $k % 26) . '.';
                $r['city'] = 'City';
            }
            $revs[] = $r;
        }
        gen_overlay_update("reviews/$id.json", fn($d) => ['reviews' => array_merge(array_values(array_filter($d['reviews'] ?? [], fn($r) => ($r['market'] ?? '') !== $market)), $revs)]);

        // market facts and editorial rating, as for the donor (this market's entry, completed from the demo market's)
        $donorEntry = function (string $rel) use ($GEN_ROOT) {
            $x = sx_json("$GEN_ROOT/brands/" . preg_replace('~/[^/]+$~', '/xx.json', $rel))['brands'][GEN_DONOR];
            $h = is_file("$GEN_ROOT/brands/$rel") ? (sx_json("$GEN_ROOT/brands/$rel")['brands'][GEN_DONOR] ?? []) : [];
            $e = $h + $x;
            foreach (['scores', 'products', 'sports', 'support'] as $k) {
                if (is_array($x[$k] ?? null)) {
                    $e[$k] = ($h[$k] ?? []) + $x[$k];
                }
            }
            return $e;
        };
        // placeholder numbers vary per brand (seeded by its id), so rankings and tables do not tie
        $vary = fn(string $k, float $v, float $spread) => $v * (1 + ((crc32("$id|$k") % 1000) / 1000 - .5) * 2 * $spread);
        $facts = $donorEntry("facts/$market.json");
        $facts['domain'] = $host;
        foreach (['games', 'live_tables', 'min_deposit'] as $k) {
            if (is_numeric($facts[$k] ?? null)) {
                $facts[$k] = (int)round($vary($k, $facts[$k], .4) / ($k === 'min_deposit' ? 1 : 10)) * ($k === 'min_deposit' ? 1 : 10);
            }
        }
        foreach ($facts['withdrawal_minutes'] ?? [] as $k => $v) {
            $facts['withdrawal_minutes'][$k] = max(5, (int)round($vary("w$k", $v, .6) / 5) * 5);
        }
        foreach (['count', 'markets_per_match', 'live_events'] as $k) {
            if (is_numeric($facts['sports'][$k] ?? null)) {
                $facts['sports'][$k] = (int)round($vary("s$k", $facts['sports'][$k], .35));
            }
        }
        gen_overlay_update("facts/$market.json", function ($d) use ($id, $facts) {
            $d['brands'][$id] = $facts;
            return $d;
        });
        $rfiles = ["ratings/$market.json"];
        foreach ($locales as $l) {
            if (is_file("$GEN_ROOT/brands/ratings/$market.$l.json")) {
                $rfiles[] = "ratings/$market.$l.json";
            }
        }
        foreach ($rfiles as $rf) {
            $rating = gen_brand_copy($donorEntry($rf), "$id.rating");
            foreach ($rating['scores'] ?? [] as $k => $sc) {
                $rating['scores'][$k]['score'] = max(5, min(10, round($vary("r$k", $sc['score'], .15) * 2) / 2));
            }
            foreach ($rating['products'] ?? [] as $p => $pr) {
                foreach ($pr['scores'] ?? [] as $k => $sc) {
                    $rating['products'][$p]['scores'][$k]['score'] = max(5, min(10, round($vary("r$p$k", $sc['score'], .15) * 2) / 2));
                }
            }
            gen_overlay_update($rf, function ($d) use ($id, $rating) {
                $d['brands'][$id] = $rating;
                return $d;
            });
        }
    }
    unset($GLOBALS['GEN_BRAND_NAMES']);
    return $ids;
}

/**
 * Made-up operator names for the sample brands that fill casino rankings next to the job's own brands (invented for this
 * tool; a name that turns out to be a real operator's should be replaced here).
 */
const GEN_SAMPLE_NAMES = ['Arvento', 'Brisklane', 'Coralis', 'Dunmore Play', 'Elkora', 'Fenwick Bet', 'Galvora', 'Halcyra', 'Istrella',
    'Jadeport', 'Kovalo', 'Lumetra', 'Morvane', 'Nortessa', 'Orlavo', 'Pellaro'];

/**
 * Sample brands for a job's casino rankings: the first $n of one fixed set of made-up operators (the same in every job,
 * so they never make one job's sites differ from another's), offering what the job's first brand offers, so a ranking
 * shows a full set of cards. Brand data is placeholder, as for the job's own brands; they are never featured.
 */
function gen_sample_brands(array $userBrands, int $n, int $jobId): array
{
    $taken = array_map(fn($b) => mb_strtolower($b['name']), $userBrands);
    $names = array_values(array_filter(GEN_SAMPLE_NAMES, fn($x) => !in_array(mb_strtolower($x), $taken, true)));
    $out = [];
    foreach (array_slice($names, 0, $n) as $name) {
        $out[] = ['name' => $name, 'products' => $userBrands[0]['products'] ?? 'both'];
    }
    return gen_clean_brands($out);
}

/** Brands a probe build uses: three placeholder brands offering casino and sport. */
function gen_probe_brands(string $market): array
{
    static $done = [];
    return $done[$market] ??= gen_materialise_brands(gen_clean_brands([
        ['name' => 'Probe Alpha', 'products' => 'both'], ['name' => 'Probe Beta', 'products' => 'both'], ['name' => 'Probe Gamma', 'products' => 'both'],
    ]), $market, 0);
}

/**
 * News articles for a generated news site: the source demo's articles with placeholder copy, and every brand they
 * mention (PR sponsor, brand news) swapped for the job's brands in turn. Returns the folder, relative to the root.
 */
function gen_site_articles(string $srcDir, string $siteId, array $brandIds, ?array $names = null): string
{
    global $GEN_ROOT;
    $rel = "data/content/$siteId/articles";
    $dir = "$GEN_ROOT/$rel";
    is_dir($dir) || mkdir($dir, 0777, true);
    $ops = sx_brand_data($GEN_ROOT, 'operators.json');
    $written = [];
    $map = [];
    $donors = array_keys(array_diff_key(sx_json("$GEN_ROOT/brands/operators.json"), ['_note' => 1]));
    foreach (glob("$GEN_ROOT/$srcDir/*.json") as $f) {
        $a = sx_json($f);
        $slug = $a['slug'] ?? basename($f, '.json');
        if ($brandIds) { // the brand field and any demo brand named in the slug (brand news) become the job's brands
            foreach ($donors as $old) {
                if ($old === ($a['brand'] ?? null) || preg_match('/(^|-)' . preg_quote($old, '/') . '(-|$)/', $slug)) {
                    $map[$old] ??= $brandIds[count($map) % count($brandIds)];
                    $slug = preg_replace('/(^|-)' . preg_quote($old, '/') . '(-|$)/', '${1}' . gen_slug($ops[$map[$old]]['name'] ?? $map[$old]) . '${2}', $slug);
                    if (($a['brand'] ?? null) === $old) {
                        $a['brand'] = $map[$old];
                    }
                }
            }
        }
        $text = array_diff_key($a, ['slug' => 1, 'brand' => 1, 'sponsor' => 1]);
        $a = ($names !== null ? gen_swap_names($text, $names) : gen_placeholders($text, 'article', "$siteId.$slug")) + $a;
        $a['slug'] = $slug;
        if (!empty($a['sponsor'])) {
            $a['sponsor'] = $ops[$a['brand'] ?? '']['name'] ?? 'Partner';
        }
        // each article replaced in place (written aside, then renamed), never deleted first: a build reading the folder
        // at the same moment always finds every article
        $tmp = "$dir/$slug.json." . getmypid() . '.tmp';
        file_put_contents($tmp, json_encode($a, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        rename($tmp, "$dir/$slug.json");
        $written["$slug.json"] = true;
    }
    foreach (glob("$dir/*.json") as $f) { // then articles this site no longer has
        if (!isset($written[basename($f)])) {
            @unlink($f);
        }
    }
    return $rel;
}
