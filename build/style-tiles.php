<?php
/**
 * Phase 0 review page: every preset x every demo locale, rendered through the real block pipeline,
 * plus market compliance, age gates, gating examples and the brand kit.
 *
 * Usage: php build/style-tiles.php   ->  dist/style-tiles.html
 */

$root = dirname(__DIR__);
require "$root/lib/site.php";

$presets = sx_all_presets($root);
$operators = sx_json("$root/brands/operators.json");
unset($operators['_note']);
$providers = sx_json("$root/brands/providers.json");
unset($providers['_note']);

$locales = [
    'en' => ['market' => 'au', 'label' => 'EN', 'place' => 'Australia'],
    'ru' => ['market' => 'kz', 'label' => 'RU', 'place' => 'Kazakhstan'],
    'kk' => ['market' => 'kz', 'label' => 'KK', 'place' => 'Kazakhstan'],
    'pt' => ['market' => 'br', 'label' => 'PT', 'place' => 'Brazil'],
];

$preferredBrand = [
    'arena' => 'vantro', 'clean-sportsbook' => 'vantro', 'sunburst' => 'vantro', 'editorial' => 'vantro',
    'midnight-neon' => 'nyxa', 'terminal' => 'nyxa', 'fresh-minimal' => 'nyxa', 'candy-pop' => 'nyxa',
    'luxury-gold' => 'marlowe', 'velvet-club' => 'marlowe',
];

// Demo hero copy. {payout} is filled from brand facts. Real content comes from the content pipeline later.
$copy = [
    'sports' => [
        'en' => ['Racing & sports', 'Every match, live odds, one tap away', 'Same-game multis, live streams of major leagues and withdrawals in {payout}.', 'How it works'],
        'ru' => ['Спорт и лайв', 'Каждый матч — с живыми коэффициентами', 'Экспрессы, лайв-ставки на топ-лиги и вывод средств за {payout}.', 'Как это работает'],
        'kk' => ['Спорт және тікелей', 'Әр матч — тікелей коэффициенттермен', 'Экспресстер, үздік лигаларға тікелей бәстер және {payout} ішінде ақша шығару.', 'Бұл қалай жұмыс істейді'],
        'pt' => ['Esportes ao vivo', 'Cada jogo com odds ao vivo, na palma da mão', 'Apostas múltiplas, transmissões das principais ligas e saques via PIX em {payout}.', 'Como funciona'],
    ],
    'casino' => [
        'pt' => ['Cassino ao vivo', 'Mesas ao vivo com crupiês reais, 24 horas', 'Roleta, blackjack e bacará em estúdio, com saques via PIX em {payout}.', 'Conheça as mesas'],
    ],
];

// Closures so heredocs can call them inline.
$fnE = fn($s) => e($s);
$fnJoin = fn(array $a) => $a ? implode(', ', $a) : 'none';
$fnLocales = fn(array $m) => implode(', ', $m['locales']);

function tile_brand(string $preferred, array $market, array $operators): string
{
    $b = $operators[$preferred];
    $ok = in_array($market['id'], $b['markets'], true) && array_filter($b['verticals'], fn($v) => !empty($market['verticals'][['live' => 'sports', 'live-casino' => 'casino'][$v] ?? $v]));
    return $ok ? $preferred : 'nyxa';
}

function ctx_for(string $brand, string $marketId, string $locale, string $seed, string $root): void
{
    [$ctx] = sx_site_context(['brand' => $brand, 'market' => $marketId, 'locale' => $locale, 'preset' => 'fresh-minimal', 'seed' => $seed, 'prefix' => 'sx-'], $root);
    sx_context($ctx);
}

/* ---------- Preset tiles ---------- */

$headerVariants = ['inline', 'centered', 'minimal'];
$heroVariants = ['split', 'split', 'centered'];
$cardVariants = ['ticket', 'panel'];

$tilesHtml = '';
$i = 0;
foreach ($presets as $id => $p) {
    $hv = $headerVariants[$i % 3];
    $herov = $heroVariants[$i % 3];
    $cv = $cardVariants[$i % 2];
    $frames = '';
    $brandsShown = [];

    foreach ($locales as $loc => $L) {
        $market = sx_market($L['market'], $root);
        $brandId = tile_brand($preferredBrand[$id], $market, $operators);
        $brandsShown[$brandId][] = $L['label'];
        ctx_for($brandId, $L['market'], $loc, "$id-$loc", $root);
        $brand = sx_ctx('brand');
        $kind = $brand['kind'] === 'casino' ? 'casino' : 'sports';
        [$eyebrow, $title, $subtitle, $secondary] = $copy[$kind][$loc];
        $payout = t('trust.minutes', ['n' => $brand['facts']['payout_minutes']]);

        $frames .= '<div class="loc" data-locale="' . $loc . '" lang="' . sx_html_lang($loc, $L['market']) . '">'
            . sx_block('compliance-bar', ['variant' => 'strip'])
            . sx_block('header', ['variant' => $hv, 'sticky' => false, 'note' => t('trust.licensed')])
            . sx_block('hero', [
                'variant' => $herov,
                'eyebrow' => $eyebrow,
                'title' => $title,
                'subtitle' => str_replace('{payout}', $payout, $subtitle),
                'secondary' => ['label' => $secondary, 'url' => '#how'],
                'card' => ['variant' => $cv, 'mode' => 'feature'],
            ])
            . '</div>';
    }

    $report = sx_contrast_report($p);
    $passed = count(array_filter($report, fn($r) => $r['pass']));
    $minRatio = min(array_column($report, 'ratio'));
    $sw = '';
    foreach (['bg' => 'Background', 'surface' => 'Surface', 'text' => 'Text', 'accent' => 'Accent', 'accent2' => 'Accent 2'] as $k => $label) {
        $sw .= '<li><span class="sw" style="background:' . e($p['color'][$k]) . '"></span>' . e($label) . ' <code>' . e($p['color'][$k]) . '</code></li>';
    }
    $fits = implode('', array_map(fn($f) => '<li>' . e($f) . '</li>', $p['fits']));
    $brandNote = implode(' · ', array_map(fn($b, $ls) => $operators[$b]['name'] . ' (' . implode(', ', $ls) . ')', array_keys($brandsShown), $brandsShown));
    $fontsLine = e($p['fonts']['display']['family']) . ' / ' . e($p['fonts']['body']['family']) . (isset($p['fonts']['mono']) ? ' / ' . e($p['fonts']['mono']['family']) : '');
    $shape = sprintf('Radius %s · Button %s · %s · Density %s', $p['shape']['radius'], $p['shape']['buttonRadius'] === '999px' ? 'pill' : $p['shape']['buttonRadius'], ucfirst($p['surface']['style']), $p['space']['density']);

    $tilesHtml .= <<<HTML
<article class="tile" id="$id">
  <div class="tile-meta">
    <div class="tile-title">
      <h3>{$p['name']}</h3>
      <p>{$p['mood']}</p>
    </div>
    <dl class="tile-facts">
      <div><dt>Type</dt><dd>$fontsLine</dd></div>
      <div><dt>Shape</dt><dd>$shape</dd></div>
      <div><dt>Variants shown</dt><dd>header: $hv · hero: $herov · card: $cv</dd></div>
      <div><dt>Contrast</dt><dd><span class="pass">$passed/6 AA</span> lowest $minRatio:1</dd></div>
      <div><dt>Demo brand</dt><dd>$brandNote</dd></div>
    </dl>
    <ul class="swatches">$sw</ul>
    <ul class="chips" aria-label="Fits">$fits</ul>
  </div>
  <div class="frame" data-preset="$id">
    $frames
    <p class="specimen" aria-label="Glyph coverage">Әә Ғғ Ққ Ңң Өө Ұұ Үү Һһ Іі <span>Ação · Saque · Ставка · ₸ R$ A$ 0123456789</span></p>
  </div>
</article>

HTML;
    $i++;
}

/* ---------- Markets ---------- */

$marketCols = '';
foreach (['kz' => 'ru', 'au' => 'en', 'br' => 'pt'] as $mid => $loc) {
    $market = sx_market($mid, $root);
    $brandId = tile_brand('vantro', $market, $operators);
    ctx_for($brandId, $mid, $loc, "market-$mid", $root);
    $panel = sx_block('compliance-bar', ['variant' => 'panel', 'show_verify' => true]);
    $allowed = array_keys(array_filter($market['verticals']));
    $blocked = array_keys(array_filter($market['verticals'], fn($v) => !$v));
    $moneyEx = money(12500);
    $pay = implode(', ', $market['payments']);
    $marketCols .= <<<HTML
<article class="market">
  <header><h3>{$market['name']}</h3><span class="code">{$mid} · {$market['currency']['code']} · {$market['min_age']}+</span></header>
  <dl class="tile-facts">
    <div><dt>Locales</dt><dd>{$fnLocales($market)}</dd></div>
    <div><dt>Verticals on</dt><dd>{$fnJoin($allowed)}</dd></div>
    <div><dt>Verticals off</dt><dd>{$fnJoin($blocked)}</dd></div>
    <div><dt>Welcome bonus</dt><dd>{$market['promotions']['welcome_bonus']}</dd></div>
    <div><dt>Money format</dt><dd>$moneyEx</dd></div>
    <div><dt>Payments</dt><dd>$pay</dd></div>
  </dl>
  <p class="note">{$fnE($market['compliance']['notes'])}</p>
  <div class="frame frame--pad" data-preset="fresh-minimal" lang="{$loc}">$panel</div>
</article>

HTML;
}

/* ---------- Age gates ---------- */

$gates = '';
foreach ([['kz', 'ru', 'midnight-neon', 'modal'], ['au', 'en', 'arena', 'modal'], ['br', 'pt', 'sunburst', 'fullscreen']] as [$mid, $loc, $preset, $variant]) {
    ctx_for(tile_brand('vantro', sx_market($mid, $root), $operators), $mid, $loc, "gate-$mid", $root);
    $gates .= '<figure class="gate"><div class="frame" data-preset="' . $preset . '" lang="' . $loc . '">'
        . sx_block('age-gate', ['variant' => $variant, 'preview' => true])
        . '</div><figcaption>' . strtoupper($mid) . ' · ' . $variant . ' · ' . $presets[$preset]['name'] . '</figcaption></figure>';
}

/* ---------- Gating demo ---------- */

ctx_for('vantro', 'br', 'pt', 'gating', $root);
SX::$warnings = [];
$gatedCard = sx_block('bonus-card', ['variant' => 'ticket', 'mode' => 'offer', 'amount' => 'R$ 500', 'detail' => 'Bônus de boas-vindas']);
$gatingWarning = e(end(SX::$warnings) ?: '');
ctx_for('vantro', 'br', 'en', 'gating-offer', $root);
$offerCard = sx_block('bonus-card', ['variant' => 'panel', 'mode' => 'offer', 'allow_offer' => true, 'amount' => '100% up to 500', 'detail' => 'First deposit · wagering 30x', 'code' => 'SXDEMO', 'terms' => 'New customers only. Placeholder terms.']);

/* ---------- Brand kit ---------- */

$brandCards = '';
foreach ($operators as $bid => $b) {
    ctx_for($bid, $b['markets'][0], sx_market($b['markets'][0], $root)['default_locale'], "brand-$bid", $root);
    $logo = sx_logo();
    $mk = strtoupper(implode(' · ', $b['markets']));
    $vert = implode(', ', $b['verticals']);
    $sp = implode(', ', array_map(fn($x) => $presets[$x]['name'], $b['suggested_presets']));
    $tag = e($b['tagline']['en']);
    $brandCards .= <<<HTML
<article class="brand">
  <div class="brand-logo" data-preset="{$b['suggested_presets'][0]}">$logo</div>
  <h3>{$b['name']}</h3>
  <p>{$fnE($b['personality'])} <em>“{$tag}”</em></p>
  <dl class="tile-facts">
    <div><dt>Markets</dt><dd>$mk</dd></div>
    <div><dt>Verticals</dt><dd>$vert</dd></div>
    <div><dt>Brand colour</dt><dd><span class="sw" style="background:{$b['brand_color']}"></span><code>{$b['brand_color']}</code></dd></div>
    <div><dt>Presets</dt><dd>$sp</dd></div>
  </dl>
</article>

HTML;
}

$vol = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'very-high' => 'Very high'];
$providerCards = '';
foreach ($providers as $pid => $pr) {
    $rows = '';
    foreach (array_slice($pr['games'], 0, 4) as $g) {
        $tags = in_array('new', $g['tags'], true) ? ' <span class="tag">new</span>' : '';
        $rows .= '<tr><td>' . e($g['name']) . $tags . '</td><td class="n">' . number_format($g['rtp'], 1) . '%</td><td>' . $vol[$g['volatility']] . '</td><td class="n">' . number_format($g['max_win']) . '×</td></tr>';
    }
    $providerCards .= <<<HTML
<article class="provider" style="--p1:{$pr['colors'][0]};--p2:{$pr['colors'][1]}">
  <div class="provider-band"><span>{$pr['name']}</span></div>
  <p>{$fnE($pr['personality'])}</p>
  <p class="small">Founded {$pr['founded']} · {$pr['games_total']} games · {$fnE(is_array($pr['signature']) ? $pr['signature']['en'] : $pr['signature'])}</p>
  <div class="table-wrap"><table>
    <thead><tr><th>Game</th><th class="n">RTP</th><th>Volatility</th><th class="n">Max win</th></tr></thead>
    <tbody>$rows</tbody>
  </table></div>
</article>

HTML;
}

/* ---------- Block inventory ---------- */

$inventory = '';
$combos = count($presets);
$variantCount = 0;
foreach (['header', 'hero', 'bonus-card', 'compliance-bar', 'footer', 'age-gate'] as $bn) {
    $meta = sx_block_meta($bn)['block'];
    $vars = $meta['sx']['variants'];
    $variantCount += count($vars);
    if (in_array($bn, ['header', 'hero', 'bonus-card', 'footer'], true)) {
        $combos *= count($vars);
    }
    $status = isset($meta['sx']['status']) ? 'Preview, expanded in Phase 1' : 'Ready';
    $inventory .= '<tr><td><code>' . e($bn) . '</code></td><td>' . e(implode(', ', $vars)) . '</td><td>' . e($status) . '</td><td>' . e($meta['sx']['guardrails'][0]) . '</td></tr>';
}

/* ---------- CSS ---------- */

$blockCss = sx_collect_css(['header', 'hero', 'bonus-card', 'compliance-bar', 'footer', 'age-gate']);
$presetCss = file_get_contents("$root/dist/tokens/presets.css");
$presetCss = preg_replace('#/\*.*?\*/#s', '', $presetCss);
$fonts = sx_google_fonts_url($presets);
$chromeFonts = 'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap';
$chromeCss = file_get_contents(__DIR__ . '/style-tiles.css');
$sprite = sx_sprite();
$presetCount = count($presets);
$generated = date('Y-m-d');

$page = <<<HTML
<title>Satellite Style Tiles</title>
<meta name="claude:viewport-fit-sides" content="cover">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="$chromeFonts">
<link rel="stylesheet" href="$fonts">
<style>
$blockCss
$presetCss
$chromeCss
</style>
<div class="page" data-loc="en" id="page">
  <header class="intro">
    <p class="kicker">Phase 0 · Foundation · generated $generated</p>
    <h1>Satellite Style Tiles</h1>
    <p class="lede">Every tile below is rendered by the real PHP blocks, the same templates WordPress (ACF) and the static builder use. Switch the language to check long strings, Kazakh glyphs and market rules. Hero copy is placeholder text; content comes later.</p>
    <ul class="stats">
      <li><strong>$presetCount</strong> token presets</li>
      <li><strong>6</strong> blocks, $variantCount variants</li>
      <li><strong>3</strong> markets, 4 languages</li>
      <li><strong>$combos</strong> look combinations from Phase 0 alone</li>
    </ul>
  </header>

  <div class="toolbar" role="toolbar" aria-label="Preview language">
    <span class="toolbar-label">Preview language</span>
    <div class="seg">
      <button type="button" id="loc-en" data-loc="en" aria-pressed="true">EN <small>AU</small></button>
      <button type="button" id="loc-ru" data-loc="ru" aria-pressed="false">RU <small>KZ</small></button>
      <button type="button" id="loc-kk" data-loc="kk" aria-pressed="false">KK <small>KZ</small></button>
      <button type="button" id="loc-pt" data-loc="pt" aria-pressed="false">PT <small>BR</small></button>
    </div>
    <nav class="jump" aria-label="Sections"><a href="#presets">Presets</a><a href="#markets">Markets</a><a href="#gating">Gating</a><a href="#brands">Brand kit</a><a href="#blocks">Blocks</a></nav>
  </div>

  <section class="section" id="presets">
    <div class="section-head">
      <h2>Token presets</h2>
      <p>Ten visual directions. Each changes type, shape, surface style, density and hero treatment, not only colour. Demo brands swap when the preferred brand is not licensed in that market (Marlowe Club is casino-only, so KZ and AU fall back to Nyxa).</p>
    </div>
    $tilesHtml
  </section>

  <section class="section" id="markets">
    <div class="section-head">
      <h2>Markets</h2>
      <p>One compliance block, three outputs. All copy below comes from <code>markets/&lt;id&gt;.json</code> and is placeholder text until compliance signs it off.</p>
    </div>
    <div class="markets">$marketCols</div>
    <h3 class="sub">Age gate</h3>
    <p class="sub-lede">Minimum age is read from the market: 21 in Kazakhstan, 18 in Australia and Brazil.</p>
    <div class="gates">$gates</div>
  </section>

  <section class="section" id="gating">
    <div class="section-head">
      <h2>Offer gating</h2>
      <p>Welcome bonuses are restricted in all three demo markets. A page that asks for an offer card in Brazil gets a feature card instead, and the build prints a warning. Offer mode renders only when compliance sets <code>allow_offer</code>.</p>
    </div>
    <div class="gating">
      <figure>
        <div class="frame frame--pad" data-preset="sunburst" lang="pt-BR">$gatedCard</div>
        <figcaption>Requested: offer "R$ 500" in BR. Rendered: feature card.<br><code>$gatingWarning</code></figcaption>
      </figure>
      <figure>
        <div class="frame frame--pad" data-preset="candy-pop">$offerCard</div>
        <figcaption>Offer mode layout, for markets where an offer is approved. Not used in KZ, AU or BR.</figcaption>
      </figure>
    </div>
  </section>

  <section class="section" id="brands">
    <div class="section-head">
      <h2>Brand kit</h2>
      <p>Fictional operators and game providers used in every demo. Names are not cleared for public use.</p>
    </div>
    <h3 class="sub">Operators</h3>
    <div class="brands">$brandCards</div>
    <h3 class="sub">Game providers</h3>
    <div class="providers">$providerCards</div>
  </section>

  <section class="section" id="blocks">
    <div class="section-head">
      <h2>Block inventory</h2>
      <p>Shared blocks are ready to use. Hero and bonus card are previews that Phase 1 expands with more variants.</p>
    </div>
    <div class="table-wrap"><table class="inventory">
      <thead><tr><th>Block</th><th>Variants</th><th>Status</th><th>Key guardrail</th></tr></thead>
      <tbody>$inventory</tbody>
    </table></div>
  </section>
</div>
$sprite
<script>
(function () {
  var page = document.getElementById('page');
  var buttons = page.querySelectorAll('.seg button');
  function set(loc) {
    page.setAttribute('data-loc', loc);
    buttons.forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.loc === loc)); });
    try { localStorage.setItem('tiles-loc', loc); } catch (e) {}
  }
  buttons.forEach(function (b) { b.addEventListener('click', function () { set(b.dataset.loc); }); });
  var saved = null;
  try { saved = localStorage.getItem('tiles-loc'); } catch (e) {}
  if (saved && page.querySelector('.seg [data-loc="' + saved + '"]')) set(saved);
})();
</script>

HTML;

@mkdir("$root/dist", 0777, true);
file_put_contents("$root/dist/style-tiles.html", $page);
printf("dist/style-tiles.html written (%s KB)\n", round(strlen($page) / 1024));
foreach (array_unique(SX::$warnings) as $w) {
    echo "  note: $w\n";
}
