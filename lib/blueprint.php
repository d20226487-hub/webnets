<?php
/**
 * Blueprint expansion: a site config with "type" (+ angle, content, optional overrides) becomes a
 * page block list. Applies the blueprint's required/optional rules, variant locks, angle variant
 * lists and middle-zone ordering rules, all seeded so the same config always gives the same page.
 *
 * Site config keys used:
 *   type       blueprint id (blueprints/<type>.json)
 *   angle      angle id; omitted = seeded pick among angles the market allows
 *   content    { <section id>: props }        (also "header", "footer")
 *   variants   { <section id>: "variant" }    pin a variant; omitted = seeded pick
 *   sections   { <optional id>: true|false }  force an optional section on/off
 *   order      [ids]                          pin the middle-zone order
 *   page       { title, description }
 */

function sx_blueprint(string $type, string $root): array
{
    $file = "$root/blueprints/$type.json";
    if (!is_file($file)) {
        throw new RuntimeException("Unknown site type '$type'");
    }
    return json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
}

/** Blocks that ask the visitor to act (sign up, claim, install): never two of them next to each other on a page. */
const SX_CONVERSION_BLOCKS = ['cta-band', 'promo-list', 'bonus-card', 'download-panel'];

/**
 * Section designs painted on a tinted or coloured band (block => its designs, '*' = every design): two of them next to
 * each other read as one heavy slab, so the order avoids it. Kept in step with the blocks' CSS (section-level backgrounds).
 */
const SX_TONED_DESIGNS = ['changelog' => '*', 'showcase' => '*', 'promo-list' => ['cards', 'banners'], 'game-grid' => ['strip'],
    'app-info' => ['chips'], 'rtp-explainer' => ['inline'], 'review-list' => ['verified'], 'article-list' => ['rail'], 'cta-band' => ['solid']];

/** How many rhythm rules a page order breaks: adjacent conversion blocks, adjacent tinted bands. $seq: [[block, design], ...]. */
function sx_rhythm_breaks(array $seq): int
{
    $toned = fn(array $x) => isset(SX_TONED_DESIGNS[$x[0]]) && (SX_TONED_DESIGNS[$x[0]] === '*' || in_array($x[1], SX_TONED_DESIGNS[$x[0]], true));
    $n = 0;
    for ($i = 1; $i < count($seq); $i++) {
        $a = $seq[$i - 1];
        $b = $seq[$i];
        $n += in_array($a[0], SX_CONVERSION_BLOCKS, true) && in_array($b[0], SX_CONVERSION_BLOCKS, true) ? 1 : 0;
        $n += $toned($a) && $toned($b) ? 1 : 0;
    }
    return $n;
}

function sx_permutations(array $items): array
{
    if (count($items) <= 1) {
        return [$items];
    }
    $out = [];
    foreach ($items as $i => $item) {
        $rest = $items;
        unset($rest[$i]);
        foreach (sx_permutations(array_values($rest)) as $p) {
            $out[] = array_merge([$item], $p);
        }
    }
    return $out;
}

function sx_order_ok(array $order, array $rules): bool
{
    $pos = array_flip($order);
    foreach ($rules as $r) {
        if ($r['rule'] === 'before' && isset($pos[$r['a']], $pos[$r['b']]) && $pos[$r['a']] > $pos[$r['b']]) {
            return false;
        }
        if ($r['rule'] === 'not_first' && ($order[0] ?? null) === $r['a']) {
            return false;
        }
    }
    return true;
}

/** Variants that exist in the block AND are allowed by the list (if any). */
function sx_allowed_variants(string $block, ?array $list): array
{
    $have = sx_market_variants($block);
    if (!$list) {
        return $have;
    }
    $ok = array_values(array_intersect($list, $have));
    $exist = array_values(array_intersect($list, sx_block_meta($block)['block']['sx']['variants'] ?? []));
    if (!$ok && $exist) { // the section's variants all need a vertical the market does not allow
        $need = implode(', ', array_unique(array_map(fn($v) => sx_block_meta($block)['block']['sx']['variant_requires'][$v] ?? '?', $exist)));
        if (sx_soft_block("Section '$block' as " . implode(' / ', $exist) . " needs the '$need' vertical, which market '" . SX::$ctx['market']['id'] . "' does not allow.")) {
            return $exist;
        }
    }
    if (!$ok) {
        SX::$warnings[] = "Block '$block': none of [" . implode(', ', $list) . "] exist yet; using any variant.";
        return $have;
    }
    return $ok;
}

function sx_choose_variant(string $id, string $block, ?array $list, ?string $pinned, ?string $salt = null): string
{
    $allowed = sx_allowed_variants($block, $list);
    if ($pinned && $pinned !== 'auto') {
        $req = sx_block_meta($block)['block']['sx']['variant_requires'][$pinned] ?? null;
        if (!in_array($pinned, $allowed, true) && $req && empty(SX::$ctx['market']['verticals'][$req])
            && sx_soft_block("Section '$id' as '$pinned' needs the '$req' vertical, which market '" . SX::$ctx['market']['id'] . "' does not allow.")) {
            return $pinned;
        }
        if (!in_array($pinned, $allowed, true) && !empty(SX::$ctx['pins_over_angle']) && in_array($pinned, sx_market_variants($block), true)) {
            return $pinned; // generated sites: the generator spreads every layout the block has over a type's sites
        }
        if (!in_array($pinned, $allowed, true)) {
            SX::$warnings[] = "Section '$id': variant '$pinned' is not allowed here (allowed: " . implode(', ', $allowed) . ').';
            return sx_pick($allowed, $salt ?? "bp:$id");
        }
        return $pinned;
    }
    return sx_pick($allowed, $salt ?? "bp:$id");
}

/**
 * Check content against a block's fields.json: required, maxlength, repeater min/max (recursive).
 * Problems become build warnings so automated content can be rejected before it ships.
 */
function sx_validate_props(string $where, array $fields, array $props): void
{
    foreach ($fields as $f) {
        $name = $f['name'];
        if ($name === 'variant') {
            continue;
        }
        $v = $props[$name] ?? null;
        $empty = $v === null || $v === '' || $v === [];
        if (!empty($f['required']) && $empty) {
            SX::$warnings[] = "$where.$name is required.";
            continue;
        }
        if ($empty) {
            continue;
        }
        if (isset($f['maxlength']) && is_string($v) && mb_strlen($v) > $f['maxlength']) {
            SX::$warnings[] = "$where.$name is " . mb_strlen($v) . " characters (max {$f['maxlength']}).";
        }
        if ($f['type'] === 'repeater' && is_array($v)) {
            if (isset($f['min']) && count($v) < $f['min']) {
                SX::$warnings[] = "$where.$name has " . count($v) . " items (min {$f['min']}).";
            }
            if (!empty($f['max']) && count($v) > $f['max']) {
                SX::$warnings[] = "$where.$name has " . count($v) . " items (max {$f['max']}).";
            }
            foreach (array_values($v) as $i => $row) {
                if (is_array($row)) {
                    sx_validate_props($where . '.' . $name . '[' . $i . ']', $f['fields'] ?? [], $row);
                }
            }
        }
        if ($f['type'] === 'group' && is_array($v)) {
            sx_validate_props("$where.$name", $f['fields'] ?? [], $v);
        }
    }
}

/**
 * Returns [page array for sx_render_page, summary array]. Requires sx_context() to be set.
 * Multi-page types call this once per page with that page's own mini-blueprint ($bp) and $pageId,
 * which keeps seeded variant picks independent per page (see sx_expand_site()).
 */
function sx_expand_blueprint(array $cfg, string $root, ?array $bp = null, string $pageId = ''): array
{
    $bp = $bp ?? sx_blueprint($cfg['type'], $root);
    $market = sx_ctx('market');

    // A look with a store layout (blueprint "layouts") replaces the section list and fixes order and variants.
    if (($layout = $bp['layouts'][$cfg['layout'] ?? ''] ?? $bp['layouts'][$cfg['look'] ?? ''] ?? null) && is_array($layout) && array_is_list($layout)) {
        $bp['sections'] = $layout;
        $bp['order_rules'] = [];
        $cfg['order'] = array_column(array_filter($layout, fn($s) => $s['zone'] === 'middle'), 'id');
        $cfg['variants'] = array_merge($cfg['variants'] ?? [], array_column(array_filter($layout, fn($s) => isset($s['variant'])), 'variant', 'id'));
        foreach ($bp['angles'] as &$an) {
            $an['variants'] = array_diff_key($an['variants'] ?? [], array_flip(array_column($layout, 'id')));
        }
        unset($an);
    }

    // Type-level gates: legal facts (vertical) and required data (provider in the brand catalog) fail hard.
    if (!empty($bp['requires_vertical']) && empty($market['verticals'][$bp['requires_vertical']])) {
        sx_legal_block("Site type '{$cfg['type']}' needs the '{$bp['requires_vertical']}' vertical, which market '{$market['id']}' does not allow. "
            . 'This is a legal block, not a setting: use another site type there.');
    }
    if (!empty($bp['requires_provider'])) {
        $pid = sx_ctx('provider')['id'] ?? throw new RuntimeException("Site type '{$cfg['type']}' needs \"provider\" in the site config.");
        if (!sx_vendor_games()) {
            $brandId = sx_ctx('brand')['id'] ?? '';
            throw new RuntimeException("Brand '$brandId' carries no '$pid' games in market '{$market['id']}' (brands/catalog/$brandId.json). "
                . 'Add them to the catalog or choose a provider the brand has there.');
        }
    }

    // Angle
    // Only site types with APK-dependent rules look at install paths (avoids link warnings on landings).
    $usesApk = (bool)array_filter($bp['sections'], fn($s) => $s['rule'] === 'apk')
        || (bool)array_filter($bp['angles'], fn($a) => !empty($a['requires_apk']));
    $hasApk = $usesApk && in_array('apk', sx_app_platforms(), true);
    $angles = array_filter($bp['angles'], fn($a) => (empty($a['requires_vertical']) || !empty($market['verticals'][$a['requires_vertical']]) || !empty(SX::$ctx['override_blocks']))
        && (empty($a['requires_apk']) || $hasApk)
        && (empty($a['requires_promo']) || array_intersect($a['requires_promo'], sx_promo_allowed_types())));
    // Offer-led types: a market that allows no promotion type cannot have this site type at all.
    if (!$angles && array_filter($bp['angles'], fn($a) => !empty($a['requires_promo']))) {
        sx_legal_block("Site type '{$cfg['type']}' cannot be built in market '{$market['id']}': its promotion policy allows none of the "
            . "promotion types this type needs. Use another site type there, or ask compliance to update markets/{$market['id']}.json.");
        SX::$ctx['promo_override'] = true; // only offer-led types, and only when overriding: their offers are the page
        $angles = array_filter($bp['angles'], fn($a) => empty($a['requires_apk']) || $hasApk);
    }
    $angleId = $cfg['angle'] ?? sx_pick(array_keys($angles), 'angle');
    if (!isset($bp['angles'][$angleId])) {
        throw new RuntimeException("Unknown angle '$angleId' for type '{$cfg['type']}'");
    }
    if (!isset($angles[$angleId])) {
        SX::$warnings[] = "Angle '$angleId' needs a vertical, install path (APK) or promotion type this site does not have.";
    }
    if ($pageId === '') {
        SX::$trace['_site']['angle'] = ['id' => $angleId, 'picked' => isset($cfg['angle']) ? 'pinned' : 'seed', 'available' => array_keys($angles), 'all' => array_keys($bp['angles'])];
    }
    // Offer-led types need at least one live offer; never ship a promo page with nothing to claim.
    if (!empty($bp['angles'][$angleId]['requires_promo']) && !sx_promos()) {
        $brandId = sx_ctx('brand')['id'] ?? '';
        throw new RuntimeException("No active promotion for brand '$brandId' in market '{$market['id']}' (expired, not yet valid, or none in the feed). "
            . "Update brands/promotions/$brandId.json before building this page.");
    }
    $angle = $bp['angles'][$angleId];

    // Sections
    $force = $cfg['sections'] ?? [];
    $removable = $cfg['removable'] ?? []; // required sections the site's editor removed (generator phase 3)
    $sections = [];
    foreach ($bp['sections'] as $s) {
        // "apk": required when the site offers a direct Android download, absent otherwise.
        $required = $s['rule'] === 'required' || ($s['rule'] === 'apk' && $hasApk);
        $on = $required || ($s['rule'] === 'optional' && in_array($s['id'], $angle['optional'], true));
        if (array_key_exists($s['id'], $force)) {
            if ($required && !$force[$s['id']] && !in_array($s['id'], $removable, true)) {
                SX::$warnings[] = "Section '{$s['id']}' is required and cannot be turned off.";
            } else {
                $on = (bool)$force[$s['id']];
            }
        }
        if ($on && !is_dir("$root/blocks/{$s['block']}")) {
            SX::$warnings[] = "Section '{$s['id']}' skipped: block '{$s['block']}' is not built yet.";
            $on = false;
        }
        $why = match (true) {
            $s['rule'] === 'required' => 'required by the blueprint',
            $s['rule'] === 'apk' => $hasApk ? 'required: the site offers an APK download' : 'off: no APK download for this brand and market',
            in_array($s['id'], $angle['optional'], true) => "optional; the '$angleId' angle turns it on",
            default => "optional; the '$angleId' angle leaves it off",
        };
        if (array_key_exists($s['id'], $force) && !($required && !$force[$s['id']] && !in_array($s['id'], $removable, true))) {
            $why = 'set ' . ($force[$s['id']] ? 'on' : 'off') . ' in the site config';
        }
        if (!is_dir("$root/blocks/{$s['block']}")) {
            $why = 'block not built yet';
        }
        SX::$trace[$pageId]['sections'][$s['id']] = ['block' => $s['block'], 'zone' => $s['zone'], 'rule' => $s['rule'], 'on' => $on, 'why' => $why];
        if ($on) {
            $sections[$s['id']] = $s;
        }
    }

    // Middle-zone order
    $middle = array_keys(array_filter($sections, fn($s) => $s['zone'] === 'middle'));
    if (!empty($cfg['order'])) {
        $order = array_values(array_intersect($cfg['order'], $middle));
        if (count($order) !== count($middle) || !sx_order_ok($order, $bp['order_rules'])) {
            SX::$warnings[] = 'Pinned order is incomplete or breaks an order rule; using a seeded order.';
            $order = null;
        }
    }
    $orderPinned = !empty($order);
    // Each section's design, before its place: below the first screen a section keeps one design across the site's pages
    // (one site, one pattern), while two sections of one kind (three article lists) can differ; the first screen keeps a
    // pick per page.
    $pins = $cfg['variants'] ?? [];
    $variantOf = [];
    foreach ($sections as $id => $s) {
        $variantOf[$id] = sx_choose_variant($pageId ? "$pageId.$id" : $id, $s['block'], $angle['variants'][$id] ?? $s['variants'] ?? null, $pins[$id] ?? null,
            $s['zone'] === 'top' ? null : "site:$id:{$s['block']}"); // a section (FAQ, related links...) looks the same on every page
    }
    if (empty($order)) {
        $valid = array_values(array_filter(sx_permutations($middle), fn($o) => sx_order_ok($o, $bp['order_rules'])));
        if ($valid) {
            // Rhythm: the fewest adjacent conversion blocks and adjacent tinted bands over the whole page.
            $top = array_keys(array_filter($sections, fn($s) => $s['zone'] === 'top'));
            $bottom = array_keys(array_filter($sections, fn($s) => $s['zone'] === 'bottom'));
            $seq = fn(array $o) => array_map(fn($id) => [$sections[$id]['block'], $variantOf[$id]], array_merge($top, $o, $bottom));
            $breaks = array_map(fn($o) => sx_rhythm_breaks($seq($o)), $valid);
            $valid = array_values(array_intersect_key($valid, array_filter($breaks, fn($b) => $b === min($breaks))));
            // One site, one pattern: each block kind has a seeded rank for the whole site; every page takes the allowed
            // order closest to it (fewest pairs out of rank order), so the site's pages read alike while sites differ.
            $rank = fn(string $id) => crc32(SX::$ctx['seed'] . '|rank|' . $sections[$id]['block']);
            $inv = function (array $o) use ($rank): int {
                $n = 0;
                for ($i = 0; $i < count($o); $i++) {
                    for ($j = $i + 1; $j < count($o); $j++) {
                        $n += $rank($o[$i]) > $rank($o[$j]) ? 1 : 0;
                    }
                }
                return $n;
            };
            $scores = array_map($inv, $valid);
            $valid = array_values(array_intersect_key($valid, array_filter($scores, fn($x) => $x === min($scores))));
        }
        $order = $valid ? sx_pick($valid, 'order') : $middle;
    }
    SX::$trace[$pageId]['order'] = ['ids' => $order, 'picked' => $orderPinned ? 'pinned' : 'seed', 'rules' => $bp['order_rules'] ?? []];
    $ids = array_merge(
        array_keys(array_filter($sections, fn($s) => $s['zone'] === 'top')),
        $order,
        array_keys(array_filter($sections, fn($s) => $s['zone'] === 'bottom'))
    );

    // Blocks
    $content = $cfg['content'] ?? [];
    $blocks = [];
    $summary = ['angle' => $angleId, 'sections' => []];

    $footerDisclosure = null; // the affiliate disclosure, when the site shows it in the footer
    foreach ($bp['frame']['top'] ?? [] as $f) {
        if (($f['when'] ?? '') === 'market.strip' && ($market['compliance']['strip'] ?? 'optional') !== 'required' && empty($cfg['sections']['strip'])) {
            continue;
        }
        $props = $f['props'] ?? [];
        if ($f['block'] === 'disclosure' && (SX::$ctx['disclosure'] ?? 'top') === 'footer') { // the footer shows it instead (footer/_shared.php)
            $footerDisclosure = $props;
            continue;
        }
        if ($f['block'] === 'disclosure' && (SX::$ctx['disclosure'] ?? 'top') === 'off') {
            SX::$warnings[] = 'No affiliate disclosure on this site: it earns from the brands it lists without saying so. Most gambling advertising rules require the disclosure.';
            continue;
        }
        if (!empty($f['variants'])) { // e.g. compliance strip / ribbon, disclosure line / box / chip
            $tv = sx_choose_variant('top.' . $f['block'], $f['block'], $f['variants'], $pins[$f['block']] ?? null);
            SX::$trace[$pageId]['frame'][$f['block']] = ['block' => $f['block'], 'variant' => $tv, 'allowed' => sx_allowed_variants($f['block'], $f['variants']), 'picked' => ($pins[$f['block']] ?? null) === $tv ? 'pinned' : 'seed'];
            $props = ['variant' => $tv] + $props;
            $summary['sections'][] = "{$f['block']}:$tv";
        }
        $blocks[] = ['block' => $f['block'], 'props' => $props];
    }
    $h = $bp['frame']['header'];
    $hv = sx_choose_variant('header', $h['block'], $h['variants'] ?? null, $pins['header'] ?? null);
    SX::$trace[$pageId]['frame']['header'] = ['block' => $h['block'], 'variant' => $hv, 'allowed' => sx_allowed_variants($h['block'], $h['variants'] ?? null), 'picked' => ($pins['header'] ?? null) === $hv ? 'pinned' : 'seed'];
    $blocks[] = ['block' => $h['block'], 'props' => ['variant' => $hv] + ($content['header'] ?? []) + ($h['props'] ?? [])];
    $summary['sections'][] = "header:$hv";

    foreach ($ids as $id) {
        $s = $sections[$id];
        sx_validate_props($pageId ? "content.$pageId.$id" : "content.$id", sx_block_meta($s['block'])['fields'], $content[$id] ?? []);
        $v = $variantOf[$id]; // chosen above: the angle's list, else the section's own
        SX::$trace[$pageId]['sections'][$id] += ['variant' => $v, 'allowed' => sx_allowed_variants($s['block'], $angle['variants'][$id] ?? null),
            'picked' => !empty($pins[$id]) && $pins[$id] === $v ? 'pinned' : 'seed'];
        // Props: content first, then the angle's section props (e.g. a lead topic), then the blueprint's defaults.
        $blocks[] = ['block' => $s['block'], 'props' => ['variant' => $v] + ($content[$id] ?? []) + ($angle['props'][$id] ?? []) + ($s['props'] ?? [])];
        $summary['sections'][] = "$id:$v";
    }

    $f = $bp['frame']['footer'];
    $fv = sx_choose_variant('footer', $f['block'], $f['variants'] ?? null, $pins['footer'] ?? null);
    SX::$trace[$pageId]['frame']['footer'] = ['block' => $f['block'], 'variant' => $fv, 'allowed' => sx_allowed_variants($f['block'], $f['variants'] ?? null), 'picked' => ($pins['footer'] ?? null) === $fv ? 'pinned' : 'seed'];
    $blocks[] = ['block' => $f['block'], 'props' => ['variant' => $fv] + ($footerDisclosure !== null ? ['disclosure' => $footerDisclosure] : []) + ($content['footer'] ?? [])];
    $summary['sections'][] = "footer:$fv";

    foreach ($bp['frame']['overlay'] ?? [] as $o) {
        $ov = sx_choose_variant($o['block'], $o['block'], $o['variants'] ?? null, $pins[$o['block']] ?? null);
        SX::$trace[$pageId]['frame'][$o['block']] = ['block' => $o['block'], 'variant' => $ov, 'allowed' => sx_allowed_variants($o['block'], $o['variants'] ?? null), 'picked' => ($pins[$o['block']] ?? null) === $ov ? 'pinned' : 'seed'];
        $blocks[] = ['block' => $o['block'], 'props' => ['variant' => $ov] + ($o['props'] ?? []) + ($content[$o['block']] ?? [])];
        $summary['sections'][] = "{$o['block']}:$ov";
    }

    $page = ($cfg['page'] ?? []) + ['slug' => 'index'];
    if (!empty($angle['lead'])) {
        $page['lead'] = $angle['lead']; // which promotion leads the page (promo types)
    }
    $page['blocks'] = $blocks;
    return [$page, $summary];
}

/**
 * Multi-page types (blueprint has "pages"): pick the site angle once, decide the page set from page rules,
 * then expand each page like a one-page blueprint. Returns [list of page arrays, summary, site page map].
 *
 * Page rules: "required" | "optional" (on when the angle lists it, or forced via cfg.pages_on).
 * Conditions on any page: requires_vertical, requires_app (brand has an app in the market),
 * requires_promo (market allows at least one promotion type), required_in (market ids where the page is mandatory; markets
 * also list such pages themselves: markets/<id>.json "required_pages").
 *
 * Site config keys per page: content.<page>, variants.<page>, order.<page>, sections.<page>, meta.<page>.
 * Top-level content.header/footer and string pins in variants (header, footer, sticky-cta) apply to every page.
 */
function sx_expand_site(array $cfg, string $root): array
{
    $bp = sx_blueprint($cfg['type'], $root);
    $locSlugs = $bp['slug_locales'][$cfg['locale'] ?? ''] ?? $bp['slug_locales']['en'] ?? [];
    foreach (($cfg['slugs'] ?? []) + $locSlugs as $pid => $slug) { // per-site slugs win, then the blueprint's slugs for the site's language
        if (!isset($bp['pages'][$pid])) {
            throw new RuntimeException("slugs: unknown page '$pid' for type '{$cfg['type']}'.");
        }
        $bp['pages'][$pid]['slug'] = $slug;
    }
    $market = sx_ctx('market');
    foreach (sx_bp_verticals($bp, $cfg) as $v) {
        if (empty($market['verticals'][$v])) {
            sx_legal_block("Site type '{$cfg['type']}' needs the '$v' vertical, which market '{$market['id']}' does not allow.");
        }
    }

    $angles = array_filter($bp['angles'], fn($a) => empty($a['requires_vertical']) || !empty($market['verticals'][$a['requires_vertical']]) || !empty(SX::$ctx['override_blocks']));
    $angleId = $cfg['angle'] ?? sx_pick(array_keys($angles), 'angle');
    if (!isset($bp['angles'][$angleId])) {
        throw new RuntimeException("Unknown angle '$angleId' for type '{$cfg['type']}'");
    }
    if (!isset($angles[$angleId])) {
        SX::$warnings[] = "Angle '$angleId' needs a vertical this market does not allow.";
    }
    SX::$trace['_site']['angle'] = ['id' => $angleId, 'picked' => isset($cfg['angle']) ? 'pinned' : 'seed', 'available' => array_keys($angles), 'all' => array_keys($bp['angles'])];
    $angle = $bp['angles'][$angleId];

    // Page set
    $force = $cfg['pages_on'] ?? [];
    // Only types with app pages look at install paths (avoids link warnings on sites without one).
    $hasApp = array_filter($bp['pages'], fn($p) => !empty($p['requires_app'])) ? (bool)sx_app_platforms() : false;
    // Platform pages (app sites): "requires_platform": "ios" | "android" (Play or APK) drops the page when the site has no such install path.
    $plats = array_filter($bp['pages'], fn($p) => !empty($p['requires_platform'])) ? sx_app_platforms() : [];
    $set = [];
    foreach ($bp['pages'] as $pid => $p) {
        $why = null;
        if (!empty($p['requires_vertical']) && empty($market['verticals'][$p['requires_vertical']]) && empty(SX::$ctx['override_blocks'])) {
            $why = "market '{$market['id']}' does not allow {$p['requires_vertical']}";
        } elseif (!empty($p['requires_app']) && !$hasApp) {
            $why = 'the brand has no app in this market';
        } elseif (!empty($p['requires_platform']) && !array_intersect($plats, $p['requires_platform'] === 'ios' ? ['ios'] : ['android', 'apk'])) {
            $why = "the site has no {$p['requires_platform']} install path";
        } elseif (!empty($p['requires_promo']) && !sx_promo_allowed_types()) {
            $why = "market '{$market['id']}' allows no promotion type";
        }
        $requiredHere = in_array($market['id'], $p['required_in'] ?? [], true) || in_array($pid, $market['required_pages'] ?? [], true);
        $mandatory = $p['rule'] === 'required' || $requiredHere;
        $wanted = in_array($pid, $angle['pages'] ?? [], true);
        $on = $mandatory || $wanted;
        if (array_key_exists($pid, $force)) {
            if ($mandatory && !$force[$pid] && !($p['rule'] === 'required' && $pid !== 'home' && !$requiredHere && in_array($pid, $cfg['removable_pages'] ?? [], true))) {
                SX::$warnings[] = "Page '$pid' is required here and cannot be turned off.";
            } else {
                $on = (bool)$force[$pid];
            }
        }
        if ($on && $why) {
            if ((!empty($force[$pid]) || $wanted || $mandatory) && empty($p['requires_platform'])) { // a single-platform app site simply has no page for the other platform
                SX::$warnings[] = "Page '$pid' left out: $why.";
            }
            $on = false;
        }
        SX::$trace['_site']['pages'][$pid] = ['on' => $on, 'why' => match (true) {
            $why !== null => "off: $why",
            array_key_exists($pid, $force) && ($on || !$mandatory || in_array($pid, $cfg['removable_pages'] ?? [], true)) => 'set ' . ($force[$pid] ? 'on' : 'off') . ' in the site config',
            $p['rule'] === 'required' => 'required by the blueprint',
            $mandatory => "required in market '{$market['id']}'",
            $wanted => "the '$angleId' angle turns it on",
            default => "optional; the '$angleId' angle leaves it off",
        }, 'generate' => $p['generate'] ?? null, 'paginate' => $p['paginate'] ?? null];
        if ($on) {
            $set[$pid] = $p;
        }
    }
    if (!isset($set['home'])) {
        throw new RuntimeException("Multi-page type '{$cfg['type']}' needs a 'home' page.");
    }

    // Generated pages: "generate": "brands" (one page per compared brand, in score order) or "articles" (one page
    // per published article, newest first). Id "<page>-<item>"; {item} in the slug is the brand id / article slug,
    // {section} the article's section; {item} in content and meta is the brand's name / article title.
    // "parents": {kind: page id} puts an article page under its section page (breadcrumbs).
    $gen = [];
    foreach ($set as $pid => $p) {
        $source = $p['generate'] ?? null;
        if (!$source) {
            $gen[$pid] = $p;
            continue;
        }
        $items = match ($source) {
            'brands' => array_combine(sx_casino_rank(), sx_casino_rank()),
            'articles' => sx_articles(),
            default => throw new RuntimeException("Page '$pid' generates from unknown source '$source'."),
        };
        foreach ($items as $iid => $item) {
            $a = $source === 'articles' ? $item : null;
            $gen["$pid-$iid"] = [
                'generated_of' => $pid, 'item' => (string)$iid, 'source' => $source, 'in_nav' => false,
                'slug' => str_replace(['{item}', '{section}'], [$iid, $a ? SX_ARTICLE_SECTIONS[$a['kind']] : ''], $p['slug'] ?? "$pid/{item}"),
                'parent' => $a ? ($p['parents'][$a['kind']] ?? null) : null,
                'item_name' => $a ? $a['title'] : sx_casino($iid)['name'],
                'cta_brand' => $a ? ($a['kind'] === 'pr' ? ($a['brand'] ?? null) : null) : $iid,
            ] + $p;
        }
    }
    $set = $gen;
    // Parents that the site does not have are dropped (e.g. no partner-content page).
    foreach ($set as &$p) {
        if (!empty($p['parent']) && !isset($set[$p['parent']])) {
            $p['parent'] = null;
        }
    }
    unset($p);

    // Pagination: a page with "paginate": {"section": "<id>", "per_page": n} becomes pages 1..N. Page 1 keeps the
    // page's id and slug; page n is "<id>~n" at "<slug>/n". cfg.paging.<id> overrides per_page.
    $paged = [];
    foreach ($set as $pid => $p) {
        if (empty($p['paginate'])) {
            continue;
        }
        $sec = array_values(array_filter($p['sections'], fn($s) => $s['id'] === $p['paginate']['section']))[0] ?? null;
        if (!$sec) {
            throw new RuntimeException("Page '$pid' paginates section '{$p['paginate']['section']}', which it does not have.");
        }
        $per = max(1, (int)($cfg['paging'][$pid] ?? $p['paginate']['per_page'] ?? 20));
        $total = sx_paginate_count($sec, ($cfg['content'][$pid][$sec['id']] ?? []) + ($sec['props'] ?? []));
        $count = max(1, (int)ceil($total / $per));
        for ($n = 1; $n <= $count; $n++) {
            $id = $n === 1 ? $pid : "$pid~$n";
            $pp = $p;
            $pp['sections'] = array_map(fn($s) => $s['id'] === $sec['id']
                ? $s + ['props' => []] : $s, $p['sections']);
            foreach ($pp['sections'] as &$s) {
                if ($s['id'] === $sec['id']) {
                    $s['props'] = ['page_no' => $n, 'page_count' => $count, 'per_page' => $per, 'page_base' => $pid] + ($s['props'] ?? []);
                }
            }
            unset($s);
            if ($n > 1) {
                $pp['paged_of'] = $pid;
                $pp['page_no'] = $n;
                $pp['slug'] = ($p['slug'] ?? $pid) . "/$n";
                $pp['in_nav'] = false;
            }
            $paged[$id] = $pp;
        }
    }
    if ($paged) {
        $merged = [];
        foreach ($set as $pid => $p) {
            if (isset($paged[$pid])) {
                foreach ($paged as $id => $pp) {
                    if ($id === $pid || ($pp['paged_of'] ?? null) === $pid) {
                        $merged[$id] = $pp;
                    }
                }
            } else {
                $merged[$pid] = $p;
            }
        }
        $set = $merged;
    }

    // Site page map (URLs, nav labels, blurbs); sx_build_site() puts it into the context before rendering.
    $map = [];
    foreach ($set as $pid => $p) {
        $base = $p['paged_of'] ?? $pid;
        $label = $cfg['nav'][$base] ?? $p['nav'] ?? $base;
        $label = $p['item_name'] ?? (isset(SX::$ctx['strings'][$label]) ? t($label) : $label);
        $map[$pid] = [
            'id' => $pid,
            'slug' => $pid === 'home' ? '' : ($p['slug'] ?? $pid),
            'nav' => isset($p['paged_of']) ? $label . ' · ' . t('paging.page', ['n' => $p['page_no']]) : $label,
            'paged_of' => $p['paged_of'] ?? null,
            'item' => $p['item'] ?? null,
            'base' => $p['paged_of'] ?? $p['generated_of'] ?? $pid,
            'source' => $p['source'] ?? null,
            'parent' => $p['parent'] ?? null,
            'blurb' => isset($p['blurb']) ? t($p['blurb']) : '',
            'in_nav' => $p['in_nav'] ?? ($pid !== 'home'),
            'topic' => $p['topic'] ?? null,
        ];
    }

    $shared = array_filter($cfg['variants'] ?? [], 'is_string');
    $sharedContent = array_intersect_key($cfg['content'] ?? [], array_flip(['header', 'footer', 'sticky-cta', 'age-gate']));
    $pages = [];
    $summary = ['angle' => $angleId, 'pages' => []];
    foreach ($set as $pid => $p) {
        $base = $p['paged_of'] ?? $p['generated_of'] ?? $pid; // paged and generated pages share content, pins and seed
        // hide_frame: frame parts a page drops, e.g. the sticky CTA on the responsible-gambling page.
        $frame = $bp['frame'];
        $frame['overlay'] = array_values(array_filter($frame['overlay'] ?? [], fn($o) => !in_array($o['block'], $p['hide_frame'] ?? [], true)));
        $pbp = [
            'frame' => $frame,
            'sections' => $p['sections'],
            'order_rules' => $p['order_rules'] ?? [],
            'angles' => [$angleId => ($p['angles'][$angleId] ?? []) + ['optional' => [], 'variants' => []]],
            'layouts' => $p['layouts'] ?? [], // e.g. the app home page as a store listing for look "play" / "appstore"
        ];
        $pcfg = [
            'type' => $cfg['type'],
            'angle' => $angleId,
            'content' => ($cfg['content'][$base] ?? []) + $sharedContent,
            'variants' => ($cfg['variants'][$base] ?? []) + $shared,
            'sections' => $cfg['sections'][$base] ?? [],
            'removable' => $cfg['removable'][$base] ?? [],
            'order' => $cfg['order'][$base] ?? null,
            'page' => $cfg['meta'][$base] ?? [],
            'look' => $cfg['look'] ?? null,
            'layout' => $cfg['layout'] ?? null,
        ];
        if (isset($p['item'])) {
            // Generated page: {item} in its content and meta becomes the brand's name or the article's title.
            $name = $p['item_name'];
            array_walk_recursive($pcfg['content'], function (&$v) use ($name) {
                $v = is_string($v) ? str_replace('{item}', $name, $v) : $v;
            });
            array_walk_recursive($pcfg['page'], function (&$v) use ($name) {
                $v = is_string($v) ? str_replace('{item}', $name, $v) : $v;
            });
        }
        if (isset($p['paged_of'])) {
            // Unique title and description per page: "<title> · page 2".
            $suffix = ' · ' . t('paging.page', ['n' => $p['page_no']]);
            foreach (['title', 'description'] as $k) {
                if (!empty($pcfg['page'][$k])) {
                    $pcfg['page'][$k] .= $suffix;
                }
            }
        }
        [$page, $ps] = sx_expand_blueprint($pcfg, $root, $pbp, $base);
        $page['id'] = $pid;
        $page['item'] = $p['item'] ?? null;
        $page['cta_brand'] = $p['cta_brand'] ?? null;
        $page['ctx'] = $p['ctx'] ?? []; // context for this page only, e.g. the iPhone page shows App Store buttons only
        $page['slug'] = $map[$pid]['slug'];
        if (empty($page['title']) && $base === $pid) {
            SX::$warnings[] = "meta.$pid.title is required (each page needs its own title).";
        }
        if (empty($page['description']) && $base === $pid) {
            SX::$warnings[] = "meta.$pid.description is required.";
        }
        $pages[] = $page;
        $summary['pages'][$pid] = $ps['sections'];
    }
    return [$pages, $summary, $map];
}

/** How many items a paginated section shows in total. */
function sx_paginate_count(array $section, array $props): int
{
    return match ($section['block']) {
        'review-list' => count(sx_review_list_items($props + ['mode' => 'all'])),
        'article-list' => count(sx_article_list_items($props)),
        default => throw new RuntimeException("Block '{$section['block']}' cannot be paginated."),
    };
}

/**
 * Verticals a site needs: the product's (blueprint "products", picked by "product" in the site config, e.g. casino
 * reviews as casino / sports / both) or the blueprint's single "requires_vertical".
 */
function sx_bp_verticals(array $bp, array $cfg): array
{
    if (isset($cfg['product'], $bp['products'])) {
        return $bp['products'][$cfg['product']]['verticals']
            ?? throw new RuntimeException("Unknown product '{$cfg['product']}' for type '{$cfg['type']}' (" . implode(', ', array_keys($bp['products'])) . ').');
    }
    return empty($bp['requires_vertical']) ? [] : [$bp['requires_vertical']];
}

