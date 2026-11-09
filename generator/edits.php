<?php
/**
 * Editing generated sites (phase 3): edits per site type in a job (job spec "edits".<type>) and per site (plan
 * "edits"), the site's own edits winning key by key. Applied on top of everything the generator picks, every build.
 *
 * Edits:
 *   colors   { primary, secondary, accent }               fixed colours (as in the job form)
 *   style    { preset, flip }                             a fixed preset, and light / dark swapped or not
 *   nav      "top" | "left" | "right" | "hub"             navigation (hub: satellites only)
 *   menu     { side, style, skip }                        phone menu (skip: one-page sites only)
 *   look     "panel" | "rail" | "cards"                   desktop sidebar look
 *   rows     "swipe" | "stack"                            rows of cards (bonuses, games, screenshots, reviews)
 *   disclosure "top" | "footer" | "off"                   affiliate disclosure (review, ranking, news sites): strip, footer or none
 *   pages    { <page id, "" on one-page types>: { on: bool, sections: { <id>: bool }, order: [ids], variants: { <id>: variant } } }
 *            on: false removes the page (multi-page types; not home, the per-item pages or the policy pages).
 *            sections: optional sections on or off; a required section can be removed (false) unless it is the first
 *            screen or carries compliance (disclosure, offer terms, reviews).
 * Sections outside the type's blueprint cannot be added: the generator only has copy for the blueprint's sections.
 * A key that is absent stays automatic.
 */

/** Blocks a required section keeps however it is edited: disclosure, offer terms, the reviews (negative ones included). */
const GEN_KEEP_BLOCKS = ['disclosure', 'compliance-bar', 'promo-terms', 'review-list', 'store-reviews'];
/** Pages that stay: the review site's moderation policy. Market-mandated pages stay through the engine. */
const GEN_KEEP_PAGES = ['policy'];

/** The edits that apply to a site: its type's in the job, then its own on top. */
function gen_site_edits(array $jobSpec, string $type, array $plan): array
{
    return gen_merge_edits($jobSpec['edits'][$type] ?? [], $plan['edits'] ?? []);
}

function gen_merge_edits(array $a, array $b): array
{
    foreach ($b as $k => $v) {
        $a[$k] = is_array($v) && isset($a[$k]) && is_array($a[$k]) && !array_is_list($v) ? gen_merge_edits($a[$k], $v) : $v;
    }
    return $a;
}

/** Apply edits to a site config (the last step of gen_site_config()). */
function gen_apply_edits(array $cfg, string $type, array $e): array
{
    $tok = [];
    foreach (['primary' => ['color', 'accent'], 'secondary' => ['color', 'accent2'], 'accent' => ['decor', 'heroBg']] as $k => [$g, $key]) {
        if (!empty($e['colors'][$k]) && preg_match('/^#[0-9a-fA-F]{6}$/', $e['colors'][$k])) {
            $tok[$g][$key] = $e['colors'][$k];
        }
    }
    if ($tok) {
        $cfg['tokens'] = gen_merge($cfg['tokens'] ?? [], $tok);
    }
    if (!empty($e['style']['preset'])) {
        $cfg['preset'] = $e['style']['preset'];
    }
    if (isset($e['style']['flip'])) {
        $cfg['style'] = ['hue' => (int)($cfg['style']['hue'] ?? 0), 'flip' => (bool)$e['style']['flip']];
    }
    if (!empty($e['nav'])) {
        if ($e['nav'] === 'top') {
            unset($cfg['nav_style'], $cfg['nav_side']);
        } elseif ($e['nav'] === 'hub') {
            $cfg['nav_style'] = 'hub';
        } else {
            $cfg['nav_style'] = 'sidebar';
            $cfg['nav_side'] = $e['nav'] === 'right' ? 'right' : 'left';
        }
    }
    foreach (['side', 'style', 'skip'] as $k) {
        if (isset($e['menu'][$k])) {
            $cfg['menu'][$k] = $e['menu'][$k];
        }
    }
    if (gen_multi_page($type)) {
        $cfg['menu']['skip'] = false;
    }
    if (!empty($e['look'])) {
        $cfg['sidebar_look'] = $e['look'];
    }
    if (in_array($e['rows'] ?? '', ['swipe', 'stack'], true)) {
        $cfg['rows'] = $e['rows'];
    }
    if (in_array($e['disclosure'] ?? '', ['top', 'footer', 'off'], true)) {
        $cfg['disclosure'] = $e['disclosure'];
    }
    $multi = gen_multi_page($type);
    $schema = gen_editor_schema($type);
    $req = [];
    foreach ($schema['pages'] as $sp) {
        $req[$sp['id']] = ['page' => $sp['rule'] === 'required', 'sections' => array_column(array_filter($sp['sections'], fn($x) => $x['rule'] !== 'optional'), 'id')];
    }
    foreach ($e['pages'] ?? [] as $pid => $p) {
        if ($multi && isset($p['on'])) {
            $cfg['pages_on'][$pid] = (bool)$p['on'];
            if (!$p['on'] && !empty($req[$pid]['page'])) {
                $cfg['removable_pages'][] = $pid;
            }
        }
        foreach ($p['sections'] ?? [] as $id => $on) {
            $multi ? $cfg['sections'][$pid][$id] = (bool)$on : $cfg['sections'][$id] = (bool)$on;
            if (!$on && in_array($id, $req[$pid]['sections'] ?? [], true)) {
                $multi ? $cfg['removable'][$pid][] = $id : $cfg['removable'][] = $id;
            }
        }
        if (!empty($p['order'])) {
            $multi ? $cfg['order'][$pid] = array_values($p['order']) : $cfg['order'] = array_values($p['order']);
        }
        foreach ($p['variants'] ?? [] as $id => $v) {
            $multi ? $cfg['variants'][$pid][$id] = $v : $cfg['variants'][$id] = $v;
        }
    }
    return $cfg;
}

/**
 * What the editor offers for a type: its pages with their sections (zone, rule, the layouts the block has, whether
 * the source demo has content for it), the order rules, and the navigation, menu, look and style choices.
 */
function gen_editor_schema(string $type): array
{
    global $GEN_ROOT;
    $bp = sx_json("$GEN_ROOT/blueprints/$type.json");
    $demo = sx_json("$GEN_ROOT/sites/demos/" . gen_catalog()[$type]['source'] . '.json');
    $multi = isset($bp['pages']);
    $pages = [];
    foreach ($multi ? $bp['pages'] : ['' => $bp] as $pid => $p) {
        $content = $multi ? ($demo['content'][$pid] ?? []) : ($demo['content'] ?? []);
        $secs = [];
        foreach ($p['sections'] ?? [] as $s) {
            $meta = is_file("$GEN_ROOT/blocks/{$s['block']}/block.json") ? sx_json("$GEN_ROOT/blocks/{$s['block']}/block.json") : [];
            $keep = $s['rule'] === 'optional' ? null : ($s['zone'] === 'top' ? 'first screen' : (in_array($s['block'], GEN_KEEP_BLOCKS, true) ? 'compliance' : null));
            $secs[] = ['id' => $s['id'], 'block' => $s['block'], 'label' => preg_replace('/^SX /', '', $meta['title'] ?? $s['block']),
                'zone' => $s['zone'], 'rule' => $s['rule'], 'variants' => array_values($s['variants'] ?? ($meta['sx']['variants'] ?? [])),
                'locked' => !empty($s['variant']), 'content' => isset($content[$s['id']]), 'removable' => $s['rule'] !== 'optional' && !$keep, 'keep' => $keep];
        }
        $keepPage = !$multi ? 'one page' : ($pid === 'home' ? 'home page' : (isset($p['generate']) ? 'one page per item' : (in_array($pid, GEN_KEEP_PAGES, true) ? 'policy' : null)));
        $pages[] = ['id' => (string)$pid, 'slug' => $p['slug'] ?? '', 'generated' => isset($p['generate']), 'rule' => $p['rule'] ?? 'required',
            'required_in' => $p['required_in'] ?? [], 'keep' => $keepPage, 'sections' => $secs, 'order_rules' => $p['order_rules'] ?? []];
    }
    $navs = ['top', 'left', 'right'];
    if (in_array('nav', gen_catalog()[$type]['auto'] ?? [], true)) {
        $navs[] = 'hub';
    }
    return ['type' => $type, 'multi' => $multi, 'pages' => $pages, 'navs' => $navs, 'menu_styles' => GEN_MENU_STYLES, 'looks' => GEN_SIDE_LOOKS,
        'presets' => array_map(fn($f) => basename($f, '.json'), glob("$GEN_ROOT/tokens/presets/*.json"))];
}

/** Check and clean edits posted by the editor. Returns [edits, error]. */
function gen_clean_edits(array $in, string $type): array
{
    $schema = gen_editor_schema($type);
    $out = [];
    foreach (['primary', 'secondary', 'accent'] as $k) {
        if (preg_match('/^#[0-9a-fA-F]{6}$/', (string)($in['colors'][$k] ?? ''))) {
            $out['colors'][$k] = $in['colors'][$k];
        }
    }
    if (in_array($in['style']['preset'] ?? '', $schema['presets'], true)) {
        $out['style']['preset'] = $in['style']['preset'];
    }
    if (isset($in['style']['flip']) && $in['style']['flip'] !== '' && $in['style']['flip'] !== null) {
        $out['style']['flip'] = (bool)$in['style']['flip'];
    }
    if (in_array($in['nav'] ?? '', $schema['navs'], true)) {
        $out['nav'] = $in['nav'];
    }
    if (in_array($in['menu']['side'] ?? '', ['left', 'right'], true)) {
        $out['menu']['side'] = $in['menu']['side'];
    }
    if (in_array($in['menu']['style'] ?? '', GEN_MENU_STYLES, true)) {
        $out['menu']['style'] = $in['menu']['style'];
    }
    if (!$schema['multi'] && isset($in['menu']['skip']) && $in['menu']['skip'] !== '' && $in['menu']['skip'] !== null) {
        $out['menu']['skip'] = (bool)$in['menu']['skip'];
    }
    if (in_array($in['look'] ?? '', GEN_SIDE_LOOKS, true)) {
        $out['look'] = $in['look'];
    }
    if (in_array($in['rows'] ?? '', ['swipe', 'stack'], true)) {
        $out['rows'] = $in['rows'];
    }
    if (in_array($in['disclosure'] ?? '', ['top', 'footer', 'off'], true)) {
        $out['disclosure'] = $in['disclosure'];
    }
    foreach ($schema['pages'] as $p) {
        $pin = $in['pages'][$p['id']] ?? [];
        $byId = array_column($p['sections'], null, 'id');
        $e = [];
        if ($schema['multi'] && isset($pin['on']) && is_bool($pin['on']) && !$p['keep']) {
            $e['on'] = $pin['on'];
        }
        foreach ($pin['sections'] ?? [] as $id => $on) {
            if (isset($byId[$id]) && ($byId[$id]['rule'] === 'optional' || $byId[$id]['removable']) && is_bool($on)) {
                $e['sections'][$id] = $on;
            }
        }
        foreach ($pin['variants'] ?? [] as $id => $v) {
            if (isset($byId[$id]) && !$byId[$id]['locked'] && in_array($v, $byId[$id]['variants'], true)) {
                $e['variants'][$id] = $v;
            }
        }
        if (!empty($pin['order']) && is_array($pin['order'])) {
            $middle = array_column(array_filter($p['sections'], fn($s) => $s['zone'] === 'middle'), 'id');
            $order = array_values(array_intersect(array_map('strval', $pin['order']), $middle));
            $order = array_merge($order, array_values(array_diff($middle, $order)));
            if (!sx_order_ok($order, $p['order_rules'])) {
                $broken = array_filter($p['order_rules'], fn($r) => !sx_order_ok($order, [$r]));
                $label = array_column($p['sections'], 'label', 'id');
                $rules = array_map(fn($r) => $r['rule'] === 'before' ? "“{$label[$r['a']]}” must come before “{$label[$r['b']]}”" : "“{$label[$r['a']]}” cannot be first", $broken);
                return [null, 'This order breaks a rule of the ' . ($p['id'] === '' ? 'page' : "“{$p['id']}” page") . ': ' . implode(', ', $rules) . '.'];
            }
            $e['order'] = $order;
        }
        if ($e) {
            $out['pages'][$p['id']] = $e;
        }
    }
    return [$out, null];
}

/**
 * What a built site has now, read from its developer data: { <page id>: { <section id>: layout } }. A page that is
 * missing was left out. Pages of older builds (no page ids) are matched by their file name.
 */
function gen_site_current(string $siteId, array $schema): ?array
{
    global $GEN_ROOT;
    $html = @file_get_contents("$GEN_ROOT/dist/generated/$siteId/index.html");
    if (!$html || !preg_match('#<script type="application/json" id="sx-devdata">(.*?)</script>#s', $html, $m)) {
        return null;
    }
    $built = json_decode($m[1], true)['pages'] ?? [];
    $out = [];
    foreach ($schema['pages'] as $sp) {
        $pid = $sp['id'];
        $slug = trim(preg_replace('/\{[a-z]+\}.*$/', '', $sp['slug']), '/-');
        $page = null;
        foreach ($built as $b) {
            $file = preg_replace('#(/index)?\.html$#', '', $b['f']);
            $hit = !$schema['multi'] || (isset($b['i']) ? $b['i'] === $pid || ($sp['generated'] && str_starts_with((string)$b['i'], "$pid-"))
                : ($pid === 'home' ? $file === 'index' : ($slug !== '' && ($file === $slug || str_starts_with($file, "$slug-") || str_starts_with($file, "$slug/")))));
            if ($hit) {
                $page = $b;
                break;
            }
        }
        if (!$page) {
            continue;
        }
        $have = [];
        foreach ($page['b'] as $bv) {
            [$blk, $v] = explode(':', $bv, 2) + [1 => ''];
            $have[$blk][] = $v;
        }
        $out[$pid] = (object)[];
        foreach ($sp['sections'] as $s) {
            if (!empty($have[$s['block']])) {
                $out[$pid]->{$s['id']} = array_shift($have[$s['block']]);
            }
        }
    }
    return $out;
}
