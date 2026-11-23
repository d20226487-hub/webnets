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
/**
 * Remove what deleted jobs leave behind: their brands in the overlay (operators, facts, ratings, promotions, catalog,
 * reviews, logos; ids u<job>-...), their sites' articles (data/content/j<job>-...) and their logs. Brands of job 0 (the
 * market checks' probe brands) stay. Returns how many items were removed.
 */
function gen_purge_deleted_jobs(): int
{
    global $GEN_ROOT;
    $live = array_flip(array_map('intval', array_column(gen_db()->query('SELECT id FROM jobs')->fetchAll(), 'id')));
    $gone = fn(string $name, string $p = 'u') => preg_match('/^' . $p . '(\d+)-/', $name, $m) && (int)$m[1] !== 0 && !isset($live[(int)$m[1]]);
    $n = 0;
    $dir = gen_overlay_dir();
    foreach (array_merge(['operators.json'], array_map(fn($f) => substr($f, strlen($dir) + 1), array_merge(glob("$dir/facts/*.json"), glob("$dir/ratings/*.json")))) as $rel) {
        if (!is_file("$dir/$rel")) {
            continue;
        }
        gen_overlay_update($rel, function ($d) use ($gone, &$n) {
            $list = $d['brands'] ?? null;
            foreach (array_keys(is_array($list) ? $list : $d) as $id) {
                if ($gone((string)$id)) {
                    $n++;
                    if (is_array($list)) {
                        unset($d['brands'][$id]);
                    } else {
                        unset($d[$id]);
                    }
                }
            }
            return $d;
        });
    }
    foreach (['promotions', 'catalog', 'reviews', 'logos'] as $sub) {
        foreach (glob("$dir/$sub/u*") as $f) {
            if ($gone(basename($f))) {
                @unlink($f);
                $n++;
            }
        }
    }
    foreach (glob("$GEN_ROOT/data/content/j*", GLOB_ONLYDIR) as $d) {
        if ($gone(basename($d), 'j')) {
            gen_rmtree($d);
            $n++;
        }
    }
    foreach (glob("$GEN_ROOT/data/logs/job-*.log") as $f) {
        if (preg_match('/job-(\d+)\.log$/', $f, $m) && !isset($live[(int)$m[1]])) {
            @unlink($f);
            $n++;
        }
    }
    return $n;
}

function gen_rmtree(string $dir): void
{
    foreach (glob("$dir/{,.}[!.,!..]*", GLOB_BRACE) ?: [] as $f) {
        is_dir($f) && !is_link($f) ? gen_rmtree($f) : @unlink($f);
    }
    @rmdir($dir);
}

function gen_overlay_update(string $rel, callable $fn): void
{
    $file = gen_overlay_dir() . "/$rel";
    gen_mkdir(dirname($file));
    // writers take turns on a lock file; the new content is written aside and renamed over the old file, so a build
    // reading brand data at the same moment (it takes no lock) sees the old file or the new one, never a half-written one
    $h = gen_lock_open("$file.lock");
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
    $pay = gen_payment_map($m); // donor data names payment methods of the demo market
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
            $GLOBALS['GEN_BRAND_NAMES'] += $pay['names'];
        }
        $host = gen_brand_host($b, $market);
        $did = !empty($b['sample']) ? gen_sample_donor($b) : GEN_DONOR; // a made-up rival takes another demo brand's figures and texts
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
        ] + (!empty($b['sample']) ? ['sample' => true] : []);
        gen_overlay_update('operators.json', fn($d) => array_merge($d, [$id => $op]));
        gen_mkdir(gen_overlay_dir() . '/logos');
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
                // one per type, preferring an offer written in the market's language (else the site shows another one's)
                if (!isset($promos[$p['type']]) || (!isset($promos[$p['type']]['title'][$m['default_locale']]) && isset($p['title'][$m['default_locale']]))) {
                    $promos[$p['type']] = $p;
                }
            }
        }
        $n = 0;
        $feed = [];
        foreach ($promos as $p) {
            $n++;
            $p = gen_fit_promo_money($p, $market);
            $p = gen_brand_copy($p, "$id.promo.$n");
            $p['id'] = "$id-$n";
            $p['markets'] = [$market];
            if (!empty($p['code'])) {
                $code = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', gen_slug($b['name'])), 0, 6)) . (10 * $n + 5);
                $p = gen_swap_names($p, [$p['code'] => $code]); // the old code named in the title and terms too
                $p['code'] = $code;
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
        $donorEntry = function (string $rel, string $who = GEN_DONOR) use ($GEN_ROOT) {
            $x = sx_json("$GEN_ROOT/brands/" . preg_replace('~/[^/]+$~', '/xx.json', $rel))['brands'][$who];
            $h = is_file("$GEN_ROOT/brands/$rel") ? (sx_json("$GEN_ROOT/brands/$rel")['brands'][$who] ?? []) : [];
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
        $facts = $donorEntry("facts/$market.json", $did) + ($did !== GEN_DONOR ? $donorEntry("facts/$market.json") : []); // what the rival's donor lacks (sports figures) from the usual one
        $facts = gen_fit_fact_payments($facts, $pay['ids']);
        if (isset($facts['min_deposit']) && !is_file("$GEN_ROOT/brands/facts/$market.json")) { // the demo market's amount, in this market's currency
            $facts['min_deposit'] = gen_fit_promo_money(['markets' => ['xx'], 'terms' => ['min_deposit' => $facts['min_deposit']]], $market)['terms']['min_deposit'];
        }
        $facts['domain'] = $host;
        foreach (['games', 'live_tables', 'min_deposit'] as $k) {
            if (is_numeric($facts[$k] ?? null)) {
                $v = $vary($k, $facts[$k], .4);
                $facts[$k] = $k === 'min_deposit' ? (int)($v < 10 ? max(1, round($v)) : ($v < 100 ? round($v / 5) * 5 : round($v, -((int)floor(log10($v)) - 1)))) : (int)round($v / 10) * 10; // a round amount
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
        // editorial rating in each of the market's languages: ratings/<market>.json in its default language, plus
        // ratings/<market>.<lang>.json for other languages there is donor text in (the demo market's file is English)
        $rfiles = [];
        foreach ($locales as $l) {
            $rfiles[$l === $m['default_locale'] ? "ratings/$market.json" : "ratings/$market.$l.json"] = $l;
        }
        foreach ($rfiles as $rf => $l) {
            $donorRating = gen_rating_donor($market, $l, $m['default_locale']);
            if ($donorRating === null) { // no text in this language: the site falls back to ratings/<market>.json
                continue;
            }
            if ($did !== GEN_DONOR && ($alt = gen_rating_donor($market, $l, $m['default_locale'], $did)) !== null) {
                $donorRating = gen_rating_mix($alt, $donorRating);
            }
            $rating = gen_brand_copy(gen_rating_drop_names($donorRating, $did), "$id.rating");
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
    require_once __DIR__ . '/brandfacts.php';
    foreach ($ids as $id) { // the facts the user confirmed replace the example data, every time it is made again
        gen_bfacts_apply(gen_db(), $id, $market);
        gen_poffers_apply(gen_db(), $id, $market); // the project's offers replace the example ones
        gen_rating_sync($id, $market); // the rating's figures follow the brand's facts and offers
        gen_btexts_apply(gen_db(), $id, $market); // the brand's own texts (project, else AI) replace the example ones
        gen_reviews_sync(gen_db(), $id, $market); // and so do the example reviews' (rating and tone kept)
    }
    return $ids;
}

/** Rating copy written from a brand's facts, per language: key => [lang => text]. */
const GEN_RATING_TPL = [
    'payouts' => ['en' => '{pay} withdrawals in {t}.', 'ru' => 'Вывод через {pay} — {t}.', 'pt' => 'Saques por {pay} em {t}.', 'kk' => '{pay} арқылы шығару — {t}.'],
    'payouts2' => ['en' => '{pay} withdrawals in {t}, {pay2} in {t2}.', 'ru' => 'Вывод через {pay} — {t}, через {pay2} — {t2}.', 'pt' => 'Saques por {pay} em {t}, por {pay2} em {t2}.', 'kk' => '{pay} арқылы шығару — {t}, {pay2} арқылы — {t2}.'],
    'games' => ['en' => 'About {n} games with clear filters.', 'ru' => 'Около {n} игр в каталоге, понятные фильтры.', 'pt' => 'Cerca de {n} jogos, filtros fáceis.', 'kk' => 'Каталогта шамамен {n} ойын, ыңғайлы сүзгілер.'],
    'live' => ['en' => '{n} live tables.', 'ru' => '{n} live-столов.', 'pt' => '{n} mesas ao vivo.', 'kk' => 'Live-үстелдер: {n}.'],
    'odds' => ['en' => 'Around {n}% margin on top football.', 'ru' => 'Маржа около {n}% на топ-футболе.', 'pt' => 'Margem de cerca de {n}% no futebol principal.', 'kk' => 'Топ-футболда маржа шамамен {n}%.'],
    'markets' => ['en' => 'About {n} markets on a big match.', 'ru' => 'Около {n} рынков на крупный матч.', 'pt' => 'Cerca de {n} mercados em um jogo grande.', 'kk' => 'Ірі матчта шамамен {n} нарық.'],
    'live_bet' => ['en' => 'About {n} live events a day.', 'ru' => 'Около {n} live-событий в день.', 'pt' => 'Cerca de {n} eventos ao vivo por dia.', 'kk' => 'Күніне шамамен {n} live-оқиға.'],
    'bonuses' => ['en' => 'Welcome offer with {n}× wagering.', 'ru' => 'Приветственный бонус с отыгрышем ×{n}.', 'pt' => 'Bônus de boas-vindas com rollover de {n}×.', 'kk' => 'Сәлемдесу бонусы: айналым ×{n}.'],
    'deposit' => ['en' => 'Minimum deposit {n}', 'ru' => 'Минимальный депозит {n}', 'pt' => 'Depósito mínimo de {n}', 'kk' => 'Ең аз депозит {n}'],
    'payout_pro' => ['en' => '{pay} withdrawals in {t}', 'ru' => 'Вывод через {pay} — {t}', 'pt' => 'Saques por {pay} em {t}', 'kk' => '{pay} арқылы шығару — {t}'],
    'games_pro' => ['en' => 'About {n} games', 'ru' => 'Около {n} игр в каталоге', 'pt' => 'Cerca de {n} jogos', 'kk' => 'Шамамен {n} ойын'],
    'live_pro' => ['en' => '{n} live tables', 'ru' => '{n} live-столов', 'pt' => '{n} mesas ao vivo', 'kk' => 'Live-үстелдер: {n}'],
    'live_bet_pro' => ['en' => 'About {n} live events a day', 'ru' => 'Около {n} live-событий в день', 'pt' => 'Cerca de {n} eventos ao vivo por dia', 'kk' => 'Күніне шамамен {n} live-оқиға'],
    'markets_pro' => ['en' => 'About {n} markets on a big match', 'ru' => 'Около {n} рынков на крупный матч', 'pt' => 'Cerca de {n} mercados por jogo grande', 'kk' => 'Ірі матчта шамамен {n} нарық'],
    'chat24' => ['en' => '24h chat', 'ru' => 'круглосуточный чат', 'pt' => 'chat 24h', 'kk' => 'тәулік бойы чат'],
    'chat' => ['en' => 'chat', 'ru' => 'чат', 'pt' => 'chat', 'kk' => 'чат'],
    'email' => ['en' => 'email', 'ru' => 'e-mail', 'pt' => 'e-mail', 'kk' => 'e-mail'],
    'phone' => ['en' => 'phone line', 'ru' => 'телефон', 'pt' => 'telefone', 'kk' => 'телефон'],
    'nophone' => ['en' => 'no phone line', 'ru' => 'телефона нет', 'pt' => 'sem telefone', 'kk' => 'телефон жоқ'],
    'and' => ['en' => ' and ', 'ru' => ' и ', 'pt' => ' e ', 'kk' => ' және '],
    'has' => ['en' => '{name} has ', 'ru' => 'У {name} ', 'pt' => '{name} tem ', 'kk' => '{name}: '],
];

/** Text that states a figure: digits, or a time written in words. */
const GEN_FIGURE_RE = '/\d|(?<!\p{L})(минут\p{L}*|час(а|ов)?|четверть часа|сут(ки|ок)|minutes?|hours?|quarter of an hour|minutos?|horas?|сағат\p{L}*)(?!\p{L})/iu';

/** What a phrase is about, for the copy that replaces its figure. */
const GEN_FIGURE_TOPIC = [
    'deposit' => '/депозит|deposit|depósito/iu',
    'live_bet' => '/live-событ|live[ -]event|eventos ao vivo|live-оқиға/iu',
    'markets' => '/рын(ок|к)|market|mercado|нарық/iu',
    'odds' => '/марж|margin|margem/iu',
    'bonuses' => '/отыгрыш|wagering|rollover|айналым|×/iu',
    'live' => '/стол|tables?(?!\p{L})|mesas|үстел/iu',
    'payout' => '/вывод|выплат|выигрыш|withdraw|payout|saque|шығар/iu',
    'games' => '/игр|games|jogos|ойын|слот|slot/iu',
];

/**
 * A job brand's editorial rating (every language file of the market) made consistent with its facts and offers: the
 * reasons for payouts, games, live, support, odds, markets, live betting and bonuses are written from the facts; other
 * phrases that state a figure are rewritten from the facts when their topic is known, else dropped. Idempotent.
 */
function gen_rating_sync(string $id, string $market): void
{
    global $GEN_ROOT;
    $m = sx_json("$GEN_ROOT/markets/$market.json");
    $f = sx_brand_data($GEN_ROOT, "facts/$market.json")['brands'][$id] ?? null;
    $op = sx_brand_data($GEN_ROOT, 'operators.json')[$id] ?? null;
    if (!$f || !$op) {
        return;
    }
    $c = $m['currency'] ?? ['symbol' => '', 'decimal' => '.', 'thousands' => ',', 'space' => true, 'position' => 'after'];
    $num = fn($n) => number_format((float)$n, 0, $c['decimal'] ?? '.', ($c['thousands'] ?? ',') === ' ' ? "\u{202F}" : ($c['thousands'] ?? ','));
    $money = fn($n) => ($c['position'] ?? 'after') === 'before' ? $c['symbol'] . (!empty($c['space']) ? "\u{00A0}" : '') . $num($n) : $num($n) . (!empty($c['space']) ? "\u{00A0}" : '') . $c['symbol'];
    $payName = fn(string $p) => $p === 'bank' ? 'Bank' : sx_payment_name($p);
    $methods = $f['withdrawal_minutes'] ?? [];
    if (!empty($f['payments'])) {
        $methods = array_intersect_key($methods, array_flip($f['payments'])) ?: $methods;
    }
    asort($methods);
    $offer = null;
    foreach (sx_brand_data($GEN_ROOT, "promotions/$id.json")['promotions'] ?? [] as $p) {
        if (in_array($market, $p['markets'] ?? [$market], true) && !empty($p['terms']['wagering']) && ($p['value']['kind'] ?? '') === 'match'
            && ($offer === null || in_array($p['type'], ['welcome', 'promo_code'], true) && !in_array($offer['type'], ['welcome'], true))) {
            $offer = $p;
        }
    }
    $time = function (int $min, string $l): string {
        $ru = fn($n, $a, $b, $c) => $n % 10 === 1 && $n % 100 !== 11 ? $a : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? $b : $c);
        if ($min < 60) {
            return ['en' => "about $min minutes", 'ru' => "около $min " . $ru($min, 'минуты', 'минут', 'минут'), 'pt' => "cerca de $min minutos", 'kk' => "шамамен $min минут"][$l] ?? "~$min min";
        }
        if ($min < 1440) {
            $h = (int)max(1, round($min / 60));
            return ['en' => "about $h " . ($h === 1 ? 'hour' : 'hours'), 'ru' => "около $h " . $ru($h, 'часа', 'часов', 'часов'), 'pt' => "cerca de $h " . ($h === 1 ? 'hora' : 'horas'), 'kk' => "шамамен $h сағат"][$l] ?? "~$h h";
        }
        $d = (int)round($min / 1440);
        return ['en' => "$d " . ($d === 1 ? 'day' : 'days'), 'ru' => "$d " . $ru($d, 'день', 'дня', 'дней'), 'pt' => "$d " . ($d === 1 ? 'dia' : 'dias'), 'kk' => "$d күн"][$l] ?? "{$d}d";
    };
    $files = [];
    foreach ($m['locales'] as $l) {
        $files[$l === $m['default_locale'] ? "ratings/$market.json" : "ratings/$market.$l.json"] = $l;
    }
    foreach ($files as $rf => $l) {
        if (!is_array(sx_brand_data($GEN_ROOT, $rf)['brands'][$id] ?? null)) {
            continue;
        }
        $tpl = function (string $k, array $v = []) use ($l): ?string {
            $s = GEN_RATING_TPL[$k][$l] ?? GEN_RATING_TPL[$k]['en'] ?? null;
            return $s === null ? null : strtr($s, array_combine(array_map(fn($x) => '{' . $x . '}', array_keys($v)), array_values($v)) ?: []);
        };
        $pays = array_keys($methods);
        $copy = [ // the text per topic: [reason, pro], null where the facts do not say
            'payout' => $pays ? [count($pays) > 1 && $methods[end($pays)] !== $methods[$pays[0]]
                ? $tpl('payouts2', ['pay' => $payName($pays[0]), 't' => $time($methods[$pays[0]], $l), 'pay2' => $payName(end($pays)), 't2' => $time($methods[end($pays)], $l)])
                : $tpl('payouts', ['pay' => $payName($pays[0]), 't' => $time($methods[$pays[0]], $l)]), $tpl('payout_pro', ['pay' => $payName($pays[0]), 't' => $time($methods[$pays[0]], $l)])] : null,
            'games' => isset($f['games']) ? [$tpl('games', ['n' => $num($f['games'])]), $tpl('games_pro', ['n' => $num($f['games'])])] : null,
            'live' => isset($f['live_tables']) ? [$tpl('live', ['n' => $num($f['live_tables'])]), $tpl('live_pro', ['n' => $num($f['live_tables'])])] : null,
            'odds' => isset($f['sports']['margin']) ? [$tpl('odds', ['n' => rtrim(rtrim(number_format((float)$f['sports']['margin'], 1, $c['decimal'] ?? '.', ''), '0'), '.,')]), null] : null,
            'markets' => isset($f['sports']['markets_per_match']) ? [$tpl('markets', ['n' => $num(round($f['sports']['markets_per_match'], -1))]), $tpl('markets_pro', ['n' => $num(round($f['sports']['markets_per_match'], -1))])] : null,
            'live_bet' => isset($f['sports']['live_events']) ? [$tpl('live_bet', ['n' => $num(round($f['sports']['live_events'], -1))]), $tpl('live_bet_pro', ['n' => $num(round($f['sports']['live_events'], -1))])] : null,
            'bonuses' => $offer ? [$tpl('bonuses', ['n' => $offer['terms']['wagering']]), null] : null,
            'deposit' => isset($f['min_deposit']) ? [null, $tpl('deposit', ['n' => $money($f['min_deposit'])])] : null,
        ];
        $s = $f['support'] ?? null;
        if (is_array($s)) {
            $have = array_values(array_filter([!empty($s['chat']) ? $tpl(($s['chat'] === '24h' ? 'chat24' : 'chat')) : null, !empty($s['email']) ? $tpl('email') : null, !empty($s['phone']) ? $tpl('phone') : null]));
            $txt = implode($tpl('and'), $have) . (empty($s['phone']) ? ($have ? '; ' : '') . $tpl('nophone') : '');
            $copy['support'] = [mb_strtoupper(mb_substr($txt, 0, 1)) . mb_substr($txt, 1) . '.', null];
        }
        $topic = function (string $t): ?string {
            foreach (GEN_FIGURE_TOPIC as $k => $re) {
                if (preg_match($re, $t)) {
                    return $k;
                }
            }
            return null;
        };
        $item = function (string $t) use ($topic, $copy): ?string { // a pro or con: kept, rewritten from the facts, or dropped
            if (!preg_match(GEN_FIGURE_RE, $t)) {
                return $t;
            }
            $k = $topic($t);
            return $k !== null ? ($copy[$k][1] ?? null) : null;
        };
        $name = (string)($op['name'] ?? '');
        $has = $tpl('has', ['name' => $name]);
        $prose = function (string $t) use ($topic, $copy, $name, $has): string { // summary or verdict: sentences with a figure rewritten or dropped
            $out = [];
            foreach (preg_split('/(?<=[.!?])\s+/u', trim($t)) as $sen) {
                if (!preg_match(GEN_FIGURE_RE, $sen)) {
                    $out[] = $sen;
                    continue;
                }
                $k = $topic($sen);
                $rep = $k !== null ? ($copy[$k][0] ?? null) : null;
                $head = preg_match('/^(.*?):\s+(.*)$/u', $sen, $mm) && !preg_match(GEN_FIGURE_RE, $mm[1]) ? $mm[1] : null;
                if ($rep !== null && $head !== null && str_contains($rep, ':')) { // «…ради live-казино: live-столов: 180» reads badly: the head alone
                    $out[] = $head . '.';
                } elseif ($rep !== null && $head !== null) {
                    $out[] = $head . ': ' . mb_strtolower(mb_substr($rep, 0, 1)) . mb_substr($rep, 1);
                } elseif ($rep !== null && $name !== '' && str_contains($sen, $name) && !str_contains($rep, $name)) { // the brand the sentence was about stays named
                    $out[] = $has . mb_strtolower(mb_substr($rep, 0, 1)) . mb_substr($rep, 1);
                } elseif ($rep !== null) {
                    $out[] = $rep;
                } elseif ($head !== null) {
                    $out[] = $head . '.';
                }
            }
            return implode(' ', array_unique($out));
        };
        $list = fn(array $xs) => array_values(array_unique(array_filter(array_map($item, $xs), fn($x) => $x !== null && $x !== '')));
        gen_overlay_update($rf, function ($d) use ($id, $copy, $topic, $prose, $list) {
            $r = $d['brands'][$id] ?? null;
            if (!is_array($r)) {
                return $d;
            }
            $reasonTopic = ['payouts' => 'payout', 'games' => 'games', 'live' => 'live', 'support' => 'support', 'odds' => 'odds', 'markets' => 'markets', 'live_bet' => 'live_bet', 'bonuses' => 'bonuses'];
            $fix = function (array $e) use ($reasonTopic, $copy, $prose, $list): array {
                foreach ($e['scores'] ?? [] as $k => $sc) {
                    if (!isset($sc['reason'])) {
                        continue;
                    }
                    $new = isset($reasonTopic[$k]) ? ($copy[$reasonTopic[$k]][0] ?? null) : null;
                    $e['scores'][$k]['reason'] = $new ?? ($prose($sc['reason']) ?: $sc['reason']);
                }
                foreach (['summary', 'verdict'] as $k) {
                    if (is_string($e[$k] ?? null)) {
                        $e[$k] = $prose($e[$k]) ?: $e[$k];
                    }
                }
                foreach (['pros', 'cons'] as $k) {
                    if (is_array($e[$k] ?? null)) {
                        $e[$k] = $list($e[$k]);
                    }
                }
                return $e;
            };
            $r = $fix($r);
            foreach ($r['products'] ?? [] as $p => $pr) {
                if (is_array($pr)) {
                    $r['products'][$p] = $fix($pr);
                }
            }
            $d['brands'][$id] = $r;
            return $d;
        });
    }
}

/**
 * A donor offer's amounts (value max / amount, minimum deposit, maximum bet) in $market's currency: they are written in
 * the currency of the offer's own market, converted at SX_DEMO_EUR_RATES and rounded like the copy's amounts.
 */
function gen_fit_promo_money(array $p, string $market): array
{
    global $GEN_ROOT;
    $code = fn(string $mk) => is_file("$GEN_ROOT/markets/$mk.json") ? (sx_json("$GEN_ROOT/markets/$mk.json")['currency']['code'] ?? null) : null;
    $from = $code($p['markets'][0] ?? 'xx');
    $to = $code($market);
    if (!$from || !$to || $from === $to || !isset(SX_DEMO_EUR_RATES[$from], SX_DEMO_EUR_RATES[$to])) {
        return $p;
    }
    $conv = function ($v) use ($from, $to) {
        if (!is_numeric($v) || $v <= 0) {
            return $v;
        }
        $v = $v / SX_DEMO_EUR_RATES[$from] * SX_DEMO_EUR_RATES[$to];
        return (int)($v < 10 ? max(1, round($v)) : ($v < 100 ? round($v / 5) * 5 : round($v, -((int)floor(log10($v)) - 1))));
    };
    foreach (['max', 'amount'] as $k) {
        if (isset($p['value'][$k])) {
            $p['value'][$k] = $conv($p['value'][$k]);
        }
    }
    foreach (['min_deposit', 'max_bet'] as $k) {
        if (isset($p['terms'][$k])) {
            $p['terms'][$k] = $conv($p['terms'][$k]);
        }
    }
    return $p;
}

/**
 * The donor's editorial rating written in $lang, for $market: the market's own entry in that language completed from the
 * demo market's (scores, products), or null when no rating text in that language exists. A ratings/<id>.json file is
 * written in market <id>'s default language, ratings/<id>.<lang>.json in <lang>.
 */
function gen_rating_donor(string $market, string $lang, string $default, string $donor = GEN_DONOR): ?array
{
    global $GEN_ROOT;
    $dir = "$GEN_ROOT/brands/ratings";
    $entry = function (string $f) use ($donor): ?array {
        return is_file($f) ? (sx_json($f)['brands'][$donor] ?? null) : null;
    };
    $xxDefault = sx_json("$GEN_ROOT/markets/xx.json")['default_locale'] ?? 'en';
    $base = $entry("$dir/xx.$lang.json") ?? ($lang === $xxDefault ? $entry("$dir/xx.json") : null);
    $own = $entry("$dir/$market.$lang.json") ?? ($lang === $default ? $entry("$dir/$market.json") : null);
    if ($base === null && $own === null) {
        return null;
    }
    $e = ($own ?? []) + ($base ?? []);
    foreach (['scores', 'products'] as $k) {
        if (is_array($base[$k] ?? null) && is_array($own[$k] ?? null)) {
            $e[$k] = array_replace_recursive($base[$k], $own[$k]);
        }
    }
    return $e;
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
    return array_map(fn($b) => $b + ['sample' => true], gen_clean_brands($out)); // made-up rivals: the project's facts are not theirs
}

/**
 * The demo brand a sample rival is made from: one that offers what it offers, other than the job brands' donor, in turn
 * by the rival's place in GEN_SAMPLE_NAMES, so the cards of a ranking do not repeat one text.
 */
function gen_sample_donor(array $b): string
{
    global $GEN_ROOT;
    $want = ['casino' => ['casino'], 'sports' => ['sports'], 'both' => ['casino', 'sports']][$b['products'] ?? 'both'] ?? ['casino', 'sports'];
    $ratings = sx_json("$GEN_ROOT/brands/ratings/xx.json")['brands'] ?? [];
    $c = [];
    foreach (sx_json("$GEN_ROOT/brands/operators.json") as $id => $o) {
        if ($id === '_note' || $id === GEN_DONOR || !isset($ratings[$id])) {
            continue;
        }
        $has = array_intersect(['casino', 'sports'], $o['verticals'] ?? []);
        if ($want === ['sports'] ? in_array('sports', $has, true) : in_array('casino', $has, true)) { // a casino text for casino and both, a sports one for sports
            $c[] = $id;
        }
    }
    if (!$c) {
        return GEN_DONOR;
    }
    if (count($want) === 2) { // a hybrid ranking: hybrid brands first, so their texts name both products
        usort($c, fn($x, $y) => (int)!isset($ratings[$x]['products']['both']) <=> (int)!isset($ratings[$y]['products']['both']));
    }
    $i = array_search($b['name'], GEN_SAMPLE_NAMES, true);
    return $c[($i === false ? crc32($b['name']) : $i) % count($c)];
}

/** A rival's rating: the texts and scores of its donor's rating ($alt), with the usual donor's ($base) filling what the
 *  other lacks (scores of products it does not offer, product views it has none for). */
function gen_rating_mix(array $alt, array $base): array
{
    $text = ['summary' => 1, 'verdict' => 1, 'pros' => 1, 'cons' => 1];
    $r = $alt + $base;
    $r['scores'] = ($alt['scores'] ?? []) + ($base['scores'] ?? []);
    foreach ($base['products'] ?? [] as $p => $pr) {
        $own = $alt['products'][$p] ?? array_intersect_key($alt, $text);
        $r['products'][$p] = $own + $pr;
        $r['products'][$p]['scores'] = ($own['scores'] ?? []) + ($pr['scores'] ?? []);
    }
    return $r;
}

/** A demo rating without the sentences that compare with another demo brand (after the names are swapped they would
 *  name the brand itself): a sentence keeps its part before a dash when only the rest names one, else goes. */
function gen_rating_drop_names(array $r, string $self): array
{
    global $GEN_ROOT;
    $names = [];
    foreach (sx_json("$GEN_ROOT/brands/operators.json") as $id => $o) {
        if ($id !== '_note' && $id !== $self) {
            $names[] = preg_quote($o['name'], '/');
            $names[] = preg_quote(explode(' ', $o['name'])[0], '/');
        }
    }
    $re = '/(?<!\p{L})(' . implode('|', array_unique($names)) . ')(?!\p{L})/u';
    $prose = function (string $t) use ($re): string {
        $out = [];
        foreach (preg_split('/(?<=[.!?])\s+/u', trim($t)) as $sen) {
            if (!preg_match($re, $sen)) {
                $out[] = $sen;
            } elseif (preg_match('/^(.+?)\s[—–]\s/u', $sen, $mm) && !preg_match($re, $mm[1])) {
                $out[] = rtrim($mm[1], ',;:') . '.';
            }
        }
        return implode(' ', $out);
    };
    $fix = function (array $e) use ($prose, $re): array {
        foreach (['summary', 'verdict'] as $k) {
            if (is_string($e[$k] ?? null)) {
                $e[$k] = $prose($e[$k]) ?: $e[$k];
            }
        }
        foreach ($e['scores'] ?? [] as $k => $sc) {
            if (is_string($sc['reason'] ?? null)) {
                $e['scores'][$k]['reason'] = $prose($sc['reason']) ?: $sc['reason'];
            }
        }
        foreach (['pros', 'cons'] as $k) {
            if (is_array($e[$k] ?? null)) {
                $e[$k] = array_values(array_filter($e[$k], fn($x) => !is_string($x) || !preg_match($re, $x))) ?: $e[$k];
            }
        }
        return $e;
    };
    $r = $fix($r);
    foreach ($r['products'] ?? [] as $p => $pr) {
        if (is_array($pr)) {
            $r['products'][$p] = $fix($pr);
        }
    }
    return $r;
}

/** Whether a job's brands are the user's own (brand objects), not demo brand ids. */
function gen_user_brands(array $spec): bool
{
    return !empty($spec['brands']) && is_array($spec['brands'][0] ?? null);
}

/** A job's brand data made again (same ids) with the project's current facts, offers, licence and texts, and its
 *  sample rivals with it. */
function gen_job_brands_reload(int $jobId): void
{
    $j = gen_db()->prepare('SELECT market, spec FROM jobs WHERE id = ?');
    $j->execute([$jobId]);
    $row = $j->fetch() ?: throw new RuntimeException('No such job.');
    $spec = json_decode($row['spec'], true) ?: [];
    if (!gen_user_brands($spec)) {
        return;
    }
    gen_materialise_brands($spec['brands'], $row['market'], $jobId, $spec['copy'] ?? 'demo');
    $n = (int)($spec['sample_brands'] ?? 0); // same names, so the same ids
    if ($n > 0 && !empty($spec['sample_ids'])) {
        gen_materialise_brands(gen_sample_brands($spec['brands'], $n, $jobId), $row['market'], $jobId, $spec['copy'] ?? 'demo');
    }
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
    gen_mkdir($dir);
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
