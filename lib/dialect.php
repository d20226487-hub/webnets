<?php
/**
 * Code dialects: the same design written differently per site, so sites built from one template do not share
 * their code (footprint reduction beyond the class prefix). A final, seeded transform of each rendered page;
 * what the page looks like does not change.
 *
 * Choices (picked per site from its seed; a site config can pin any of them, or switch it all off):
 *   "dialect": false                        no transform
 *   "dialect": {"classes": "short", ...}    pin some choices, the rest stay seeded
 *
 *   classes      bem (hero__title--x) | camel (heroTitle_x) | flat (hero-title-x) | short (hashed, e.g. qkzvt)
 *   vars         keep | short          CSS custom property names (--c-bg -> --qv)
 *   colors       hex | HEX | rgb | rgb-comma
 *   units        px | rem              spacing, type and radius only; 1-3px stay px (borders, hairlines)
 *   numbers      lead (0.5) | bare (.5)
 *   breakpoints  offset in px added to every @media min/max-width (0, 8, 16, 24)
 *   order        keep | reverse | sorted   declaration order, only in rules where order cannot matter
 *   attrs        keep | sorted | reverse   attribute order in tags
 *   wrappers     none | some | many    neutral <div> wrappers (display: contents) inside safe containers: the DOM tree
 *                                      changes, the layout does not (see sx_dialect_structure for what "safe" means)
 *   lists        keep | aria           component lists as <div role="list"> / <div role="listitem">
 * The class prefix stays in front of every renamed class and id (it keeps them apart from unprefixed classes).
 * Text, URLs, data-* values and JSON-LD are never touched.
 */

const SX_DIALECT_OPTIONS = [
    'classes' => ['bem', 'camel', 'flat', 'short'],
    'vars' => ['keep', 'short'],
    'colors' => ['hex', 'HEX', 'rgb', 'rgb-comma'],
    'units' => ['px', 'rem'],
    'numbers' => ['lead', 'bare'],
    'breakpoints' => [0, 8, 16, 24],
    'order' => ['keep', 'reverse', 'sorted'],
    'attrs' => ['keep', 'sorted', 'reverse'],
    'wrappers' => ['none', 'some', 'many'],
    'lists' => ['keep', 'aria'],
];

/** This site's dialect (null = off). */
function sx_dialect_resolve($cfg): ?array
{
    if ($cfg === false || $cfg === 'off') {
        return null;
    }
    $pin = is_array($cfg) ? $cfg : [];
    $d = [];
    foreach (SX_DIALECT_OPTIONS as $k => $opts) {
        if (isset($pin[$k])) {
            if (!in_array($pin[$k], $opts, true)) {
                throw new RuntimeException("dialect.$k must be one of: " . implode(', ', array_map('strval', $opts)) . '.');
            }
            $d[$k] = $pin[$k];
        } else {
            $d[$k] = sx_pick($opts, "dialect|$k");
        }
    }
    return $d;
}

/** Deterministic short lowercase name for $key on this site (letters only, so it is a valid class/ident start). */
function sx_dialect_hash(string $key, int $len): string
{
    $h = md5(SX::$ctx['seed'] . '|dialect|' . $key);
    $out = '';
    for ($i = 0; strlen($out) < $len; $i += 2) {
        $out .= chr(97 + hexdec(substr($h . md5($h), $i, 2)) % 26);
    }
    return $out;
}

/** New name for a prefixed class (without the prefix), by scheme. */
function sx_dialect_class(string $name, string $scheme): string
{
    if ($scheme === 'bem') {
        return $name;
    }
    if ($scheme === 'short') {
        return sx_dialect_hash("c|$name", 5);
    }
    [$be, $mod] = array_pad(explode('--', $name, 2), 2, null);
    [$b, $el] = array_pad(explode('__', $be, 2), 2, null);
    $camel = fn($s) => lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $s))));
    if ($scheme === 'camel') {
        return $camel($b) . ($el !== null ? ucfirst($camel($el)) : '') . ($mod !== null ? '_' . $camel($mod) : '');
    }
    return $b . ($el !== null ? "-$el" : '') . ($mod !== null ? "-$mod" : ''); // flat
}

/** Injective rename map for $names: first candidate that is free (the original name is the last resort). */
function sx_dialect_map(array $names, callable ...$fns): array
{
    $map = $used = [];
    foreach ($names as $n) {
        $used[$n] = true; // originals stay reserved, so a fallback to the original is always unique
    }
    foreach ($names as $n) {
        $new = $n;
        foreach ($fns as $fn) {
            $c = $fn($n);
            if ($c === $n || !isset($used[$c])) {
                $new = $c;
                break;
            }
        }
        if ($new !== $n) {
            $used[$new] = true;
        }
        $map[$n] = $new;
    }
    return $map;
}

/** Apply the site's dialect to one rendered page. */
function sx_dialect_apply(string $html, ?array $d, string $prefix): string
{
    if (!$d || $prefix === '') {
        return $html;
    }
    // first: it reads the authored class names. Wrappers cost bytes: near the page budget use fewer, or none.
    $before = $html;
    $html = sx_dialect_structure($before, $d, $prefix);
    $limit = (defined('SX_PAGE_BUDGET_KB') ? SX_PAGE_BUDGET_KB : 64) * 1024 - 1024;
    foreach (['some', 'none'] as $fewer) {
        if (strlen($html) <= $limit || $d['wrappers'] === 'none' || ($fewer === 'some' && $d['wrappers'] === 'some')) {
            break;
        }
        $html = sx_dialect_structure($before, ['wrappers' => $fewer] + $d, $prefix);
    }
    $p = preg_quote($prefix, '/');
    $tok = '[A-Za-z0-9_-]+';

    // 1. names: classes (class attributes + CSS selectors), ids (id attributes), custom properties (CSS)
    preg_match_all('/\sclass="([^"]*)"/', $html, $m);
    $classes = [];
    foreach ($m[1] as $list) {
        foreach (preg_split('/\s+/', trim($list)) as $c) {
            if (str_starts_with($c, $prefix)) {
                $classes[substr($c, strlen($prefix))] = true;
            }
        }
    }
    preg_match_all('#<style[^>]*>(.*?)</style>#s', $html, $styles);
    $css = implode("\n", $styles[1]);
    preg_match_all('/\.' . $p . "($tok)/", $css, $m);
    foreach ($m[1] as $c) {
        $classes[$c] = true;
    }
    $cmap = sx_dialect_map(array_keys($classes), fn($n) => sx_dialect_class($n, $d['classes']), fn($n) => sx_dialect_class($n, 'camel'), fn($n) => sx_dialect_hash("c|$n", 6));
    preg_match_all('/\sid="' . $p . "($tok)\"/", $html, $m);
    $imap = sx_dialect_map(array_unique($m[1]), fn($n) => $d['classes'] === 'short' ? sx_dialect_hash("i|$n", 5) : sx_dialect_class($n, $d['classes']));
    $vmap = [];
    if ($d['vars'] === 'short') {
        preg_match_all('/(?<![\w-])--([a-zA-Z][\w-]*)\s*:/', $css . ' ' . implode(' ', sx_dialect_style_attrs($html)), $m);
        $vmap = sx_dialect_map(array_values(array_unique($m[1])), fn($n) => sx_dialect_hash("v|$n", 3));
    }
    $cls = fn($c) => $prefix . ($cmap[$c] ?? $c);
    $ids = fn($i) => $prefix . ($imap[$i] ?? $i);
    $vars = function (string $s) use ($vmap): string {
        return $vmap ? preg_replace_callback('/(?<![\w-])--([a-zA-Z][\w-]*)/', fn($x) => '--' . ($vmap[$x[1]] ?? $x[1]), $s) : $s;
    };
    $values = fn(string $v) => sx_dialect_values($vars($v), $d);

    // 2. walk the document: <style>, <script>, comments and tags are handled apart; text is left alone
    return preg_replace_callback('#<style([^>]*)>(.*?)</style>|<script([^>]*)>(.*?)</script>|<!--.*?-->|<[a-zA-Z][^>]*>#s',
        function ($x) use ($d, $p, $tok, $cls, $ids, $vars, $values, $cmap, $imap, $prefix) {
            $all = $x[0];
            if (($x[1] ?? '') !== '' || str_starts_with($all, '<style')) {
                // [class*="__card"] etc. (looks) match authored BEM names: expand them to the renamed classes they matched
                $c = $d['classes'] === 'bem' ? $x[2] : preg_replace_callback('/\[class([*^$]?)="([^"]*)"\]/', function ($y) use ($cmap, $prefix, $cls) {
                    $hit = [];
                    foreach (array_keys($cmap) as $n) {
                        $full = $prefix . $n;
                        if (match ($y[1]) { '*' => str_contains($full, $y[2]), '^' => str_starts_with($full, $y[2]), '$' => str_ends_with($full, $y[2]), default => $full === $y[2] }) {
                            $hit[] = '.' . $cls($n);
                        }
                    }
                    return $hit ? ':is(' . implode(',', $hit) . ')' : $y[0];
                }, $x[2]);
                $c = preg_replace_callback('/\.' . $p . "($tok)/", fn($y) => '.' . $cls($y[1]), $c);
                $c = preg_replace_callback('/#' . $p . "($tok)/", fn($y) => '#' . $ids($y[1]), $c);
                return '<style' . $x[1] . '>' . sx_dialect_css($vars($c), $d) . '</style>';
            }
            if (str_starts_with($all, '<script')) {
                if (str_contains($x[3], 'ld+json')) {
                    return $all;
                }
                // exact class/id strings used by scripts (e.g. getElementById) follow the rename
                $js = preg_replace_callback('/(?<![A-Za-z0-9_-])' . $p . "($tok)(?![A-Za-z0-9_-])/", function ($y) use ($cmap, $imap, $prefix) {
                    return $prefix . ($imap[$y[1]] ?? $cmap[$y[1]] ?? $y[1]);
                }, $x[4]);
                return '<script' . $x[3] . '>' . $js . '</script>';
            }
            if (str_starts_with($all, '<!--')) {
                return $all;
            }
            return sx_dialect_tag($all, $d, $p, $tok, $cls, $ids, $values);
        }, $html);
}

/** style="" attribute values of a page. */
function sx_dialect_style_attrs(string $html): array
{
    preg_match_all('/\sstyle="([^"]*)"/', $html, $m);
    return $m[1];
}

/** One start tag: rename classes/ids/references, transform style values, reorder attributes. */
function sx_dialect_tag(string $tag, array $d, string $p, string $tok, callable $cls, callable $ids, callable $values): string
{
    if (!preg_match('#^<([a-zA-Z][\w:-]*)((?:\s+[a-zA-Z_:@][-\w:.@]*(?:="[^"]*")?)*)\s*(/?)>$#s', $tag, $t)) {
        // unusual tag: no reordering, but names must still follow the CSS
        $tag = preg_replace_callback('/(\sclass=")([^"]*)"/', fn($y) => $y[1] . implode(' ', array_map(fn($c) => preg_match('/^' . $p . "($tok)$/", $c, $z) ? $cls($z[1]) : $c, preg_split('/\s+/', $y[2]))) . '"', $tag);
        $tag = preg_replace_callback('/(\s(?:id|for|aria-controls|aria-labelledby|aria-describedby)=")' . $p . "($tok)\"/", fn($y) => $y[1] . $ids($y[2]) . '"', $tag);
        return preg_replace_callback('/(\s(?:xlink:)?href="#)' . $p . "($tok)\"/", fn($y) => $y[1] . $ids($y[2]) . '"', $tag);
    }
    preg_match_all('/\s+([a-zA-Z_:@][-\w:.@]*)(?:="([^"]*)")?/', $t[2], $am, PREG_SET_ORDER);
    $attrs = [];
    foreach ($am as $a) {
        [$name, $val] = [$a[1], $a[2] ?? null];
        if ($val !== null) {
            if ($name === 'class') {
                $val = implode(' ', array_map(fn($c) => preg_match('/^' . $p . "($tok)$/", $c, $y) ? $cls($y[1]) : $c, preg_split('/\s+/', $val)));
            } elseif (in_array($name, ['id', 'for', 'aria-controls', 'aria-labelledby', 'aria-describedby', 'aria-owns', 'list', 'form'], true)) {
                $val = preg_replace_callback('/(?<![A-Za-z0-9_-])' . $p . "($tok)/", fn($y) => $ids($y[1]), $val);
            } elseif (in_array($name, ['href', 'xlink:href'], true) && str_starts_with($val, '#')) {
                $val = preg_replace_callback('/^#' . $p . "($tok)$/", fn($y) => '#' . $ids($y[1]), $val);
            } elseif ($name === 'style') {
                $val = $values($val);
            } elseif (in_array($name, ['fill', 'stroke', 'clip-path', 'mask', 'filter', 'marker-end'], true)) {
                $val = preg_replace_callback('/url\(#' . $p . "($tok)\)/", fn($y) => 'url(#' . $ids($y[1]) . ')', $val);
            }
        }
        $attrs[] = [$name, $val];
    }
    if ($d['attrs'] === 'sorted') {
        usort($attrs, fn($a, $b) => strcmp($a[0], $b[0]));
    } elseif ($d['attrs'] === 'reverse') {
        $attrs = array_reverse($attrs);
    }
    $out = '<' . $t[1];
    foreach ($attrs as [$n, $v]) {
        $out .= ' ' . $n . ($v === null ? '' : '="' . $v . '"');
    }
    return $out . ($t[3] ? ' /' : '') . '>';
}

/** CSS of a <style> block: values in every rule, declaration order where safe, @media breakpoints. */
function sx_dialect_css(string $css, array $d): string
{
    if ($d['breakpoints']) {
        $css = preg_replace_callback('/@media[^{]*/', fn($m) => preg_replace_callback('/((?:min|max)-width\s*:\s*)(\d+)px/',
            fn($x) => $x[1] . ((int)$x[2] + $d['breakpoints']) . 'px', $m[0]), $css);
    }
    $css = preg_replace_callback('/\{([^{}]*)\}/', function ($m) use ($d) {
        return '{' . sx_dialect_order(sx_dialect_values($m[1], $d), $d['order']) . '}';
    }, $css);
    return $css;
}

/** Colours, numbers and units inside declaration values (never selectors). */
function sx_dialect_values(string $decls, array $d): string
{
    $decls = preg_replace_callback('/#([0-9a-fA-F]{8}|[0-9a-fA-F]{6}|[0-9a-fA-F]{3,4})(?![0-9a-zA-Z_-])/', function ($m) use ($d) {
        $h = $m[1];
        if ($d['colors'] === 'hex') {
            return '#' . strtolower($h);
        }
        if ($d['colors'] === 'HEX') {
            return '#' . strtoupper($h);
        }
        if (strlen($h) <= 4) {
            $h = implode('', array_map(fn($c) => $c . $c, str_split($h)));
        }
        [$r, $g, $b] = [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2))];
        $a = strlen($h) === 8 ? round(hexdec(substr($h, 6, 2)) / 255, 3) : null;
        return $d['colors'] === 'rgb'
            ? "rgb($r $g $b" . ($a !== null ? " / $a" : '') . ')'
            : ($a !== null ? "rgba($r,$g,$b,$a)" : "rgb($r,$g,$b)");
    }, $decls);
    if ($d['units'] === 'rem') {
        $decls = preg_replace_callback('/(^|;)(\s*(?:font-size|padding[\w-]*|margin[\w-]*|gap|row-gap|column-gap|border-radius|letter-spacing)\s*:)([^;]*)/',
            fn($m) => $m[1] . $m[2] . preg_replace_callback('/(?<![\w.#-])(\d+(?:\.\d+)?)px\b/', function ($x) {
                $px = (float)$x[1];
                if ($px <= 3) {
                    return $x[0];
                }
                $rem = round($px / 16, 4);
                return rtrim(rtrim(number_format($rem, 4, '.', ''), '0'), '.') . 'rem';
            }, $m[3]), $decls);
    }
    $decls = $d['numbers'] === 'bare'
        ? preg_replace('/(?<![\w.#-])0\.(\d)/', '.$1', $decls)
        : preg_replace('/(?<![\w.#-])\.(\d)/', '0.$1', $decls);
    return $decls;
}

/** Reorder declarations when no two of them touch the same property family (so order cannot matter). */
function sx_dialect_order(string $decls, string $order): string
{
    if ($order === 'keep' || !str_contains($decls, ';')) {
        return $decls;
    }
    $parts = preg_split('/;(?![^(]*\))/', $decls); // ; inside url(...) or similar stays
    $fam = [];
    foreach ($parts as $x) {
        if (!preg_match('/^\s*(-?-?[a-zA-Z][\w-]*)\s*:/', $x, $m)) {
            return $decls;
        }
        $prop = strtolower($m[1]);
        if (str_starts_with($prop, '--')) {
            continue; // custom properties resolve after the cascade
        }
        $base = preg_replace('/^-(webkit|moz|ms)-/', '', $prop);
        $f = explode('-', $base)[0];
        $f = match (true) {
            in_array($f, ['top', 'right', 'bottom', 'left', 'inset'], true) => 'inset',
            in_array($base, ['place-items', 'align-items', 'justify-items', 'place-content', 'align-content', 'justify-content', 'place-self', 'align-self', 'justify-self'], true) => 'place',
            in_array($f, ['gap', 'row', 'column', 'columns'], true) => 'gap',
            $f === 'all' => '*',
            default => $f,
        };
        if (isset($fam[$f]) || isset($fam['*']) || ($f === '*' && $fam)) {
            return $decls;
        }
        $fam[$f] = true;
    }
    if ($order === 'reverse') {
        $parts = array_reverse($parts);
    } else {
        sort($parts);
    }
    return implode(';', $parts);
}

/* ---------------------------------------------------------------------------------------------------------------
 * Structural dialect: change the DOM tree, not the layout.
 *
 *  wrappers  An element E gets a neutral <div class="...w..."> around all of its children, styled display: contents
 *            !important, so the children still lay out as E's children (grid/flex items, gaps, order). E qualifies when
 *            - it is a plain flow container (div, section, article, aside, header, footer, nav, main, form, li, dd)
 *              with a component class and no role, outside SVG, with at least one element child;
 *            - no selector uses E's class or tag on the left of ">" or ">"/"+"/"~" inside :has() (the wrapper would
 *              cut the parent/child link);
 *            - no rule that could match a bare <div> (subject is div, *, or pseudo-classes only) and sets an
 *              inherited property or a custom property applies at E or above (the wrapper would pass it on).
 *            Sibling selectors and :nth-child keep working: all children move together.
 *  lists     <ul class="..."> whose children are all <li> become <div role="list"> / <div role="listitem"> (same
 *            meaning for assistive tech). Selectors on ul/li become :is(ul,div:where([role=list])), which has the
 *            same specificity. Skipped where a rule targets bare <div> elements in that scope.
 * Wrappers are seeded per element, so every site gets its own tree.
 * ------------------------------------------------------------------------------------------------------------- */

const SX_DIALECT_WRAP_TAGS = ['div', 'section', 'article', 'aside', 'header', 'footer', 'nav', 'main', 'form', 'li', 'dd'];
const SX_DIALECT_VOID = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr', 'param'];
const SX_DIALECT_INHERITED = ['color', 'font', 'line-height', 'letter-spacing', 'word-spacing', 'text-align', 'text-indent', 'text-transform',
    'text-shadow', 'white-space', 'visibility', 'cursor', 'direction', 'list-style', 'quotes', 'hyphens', 'overflow-wrap', 'word-break',
    'tab-size', 'caret-color', 'accent-color', 'fill', 'stroke', 'paint-order', 'text-rendering', 'writing-mode', 'orphans', 'widows',
    'image-rendering', '-webkit-text-size-adjust', 'text-size-adjust', 'pointer-events', 'user-select', '-webkit-font-smoothing', 'text-wrap',
    'color-scheme', 'text-decoration-skip-ink', 'text-underline-offset', 'text-underline-position'];

/** Selectors of a stylesheet as [selector, declarations]. */
function sx_dialect_rules(string $css): array
{
    $out = [];
    preg_match_all('/([^{}]+)\{([^{}]*)\}/', preg_replace('/@(media|supports|container|layer)[^{]*\{/', '', $css), $m, PREG_SET_ORDER);
    foreach ($m as [$_, $sels, $decls]) {
        if (str_starts_with(trim($sels), '@') || preg_match('/^\s*(from|to|\d+%)/', $sels)) {
            continue; // @font-face, @keyframes steps
        }
        foreach (preg_split('/,(?![^(]*\))/', $sels) as $sel) {
            if (trim($sel) !== '') {
                $out[] = [trim($sel), $decls];
            }
        }
    }
    return $out;
}

/** A selector as compounds with the combinator before each: [[compound, combinator], ...]. */
function sx_dialect_compounds(string $sel): array
{
    $sel = preg_replace('/\s*([>+~])\s*/', '$1', trim($sel));
    $parts = [];
    $cur = '';
    $comb = ' ';
    $depth = 0;
    foreach (str_split($sel) as $ch) {
        if ($ch === '(' || $ch === '[') {
            $depth++;
        } elseif ($ch === ')' || $ch === ']') {
            $depth--;
        }
        if ($depth === 0 && ($ch === ' ' || $ch === '>' || $ch === '+' || $ch === '~')) {
            if ($cur !== '') {
                $parts[] = [$cur, $comb];
                $cur = '';
            }
            $comb = $ch;
            continue;
        }
        $cur .= $ch;
    }
    if ($cur !== '') {
        $parts[] = [$cur, $comb];
    }
    return $parts;
}

/** [classes, tag, plain] a compound requires; plain = no class, id or attribute (negations, pseudos ignored). */
function sx_dialect_needs(string $compound): array
{
    $c = preg_replace('/:(not|where|is|has)\(.*\)|::?[a-z-]+(\([^)]*\))?/', '', $compound);
    preg_match_all('/\.([\w-]+)/', $c, $cls);
    preg_match('/^([a-zA-Z][\w-]*)/', $c, $tag);
    return [$cls[1], strtolower($tag[1] ?? ''), !preg_match('/[.#\[]/', $c)];
}

/** Record where a risky rule applies: the classes or tag its nearest ancestor needs, or everywhere. */
function sx_dialect_mark(array &$risk, ?string $anc): void
{
    [$cls, $tag] = $anc !== null ? sx_dialect_needs($anc) : [[], ''];
    if ($anc === null || (!$cls && ($tag === '' || $tag === '*'))) {
        $risk['all'] = true;
    } elseif ($cls) {
        array_push($risk['cls'], ...$cls);
    } else {
        $risk['tag'][] = $tag;
    }
}

function sx_dialect_structure(string $html, array $d, string $prefix): string
{
    if ($d['wrappers'] === 'none' && $d['lists'] === 'keep') {
        return $html;
    }
    preg_match_all('#<style[^>]*>(.*?)</style>#s', $html, $st);

    // 1. what the CSS depends on
    $noWrap = ['cls' => [], 'tag' => [], 'all' => false];
    $inheritRisk = ['cls' => [], 'tag' => [], 'all' => false];
    $divRisk = ['cls' => [], 'tag' => [], 'all' => false];
    $inheritedRe = '/(^|;)\s*(--[\w-]+|' . implode('|', array_map(fn($p) => preg_quote($p, '/'), SX_DIALECT_INHERITED)) . ')[\w-]*\s*:/';
    foreach (sx_dialect_rules(implode("\n", $st[1])) as [$sel, $decls]) {
        $cs = sx_dialect_compounds($sel);
        foreach ($cs as $i => [$comp]) {
            if (($cs[$i + 1][1] ?? null) === '>' || preg_match('/:has\(\s*[>+~]/', $comp)) {
                [$cls, $tag] = sx_dialect_needs($comp);
                if ($cls) {
                    array_push($noWrap['cls'], ...$cls);
                } elseif ($tag !== '' && $tag !== '*') {
                    $noWrap['tag'][] = $tag;
                } else {
                    $noWrap['all'] = true;
                }
            }
        }
        [$scomp, $scomb] = end($cs);
        [, $stag, $splain] = sx_dialect_needs($scomp);
        if (!$splain || !in_array($stag, ['', '*', 'div'], true) || $scomb === '+' || $scomb === '~'
            || preg_match('/:(root|host|target|checked|disabled|enabled|placeholder-shown|invalid|valid|required|optional|read-only|read-write|link|visited|any-link|focus|focus-visible|empty|default|indeterminate|autofill|modal|open)(?![\w-])/', $scomp)) {
            continue; // cannot match a classless, empty-of-state wrapper div (or needs a sibling it never has)
        }
        $anc = count($cs) > 1 ? $cs[count($cs) - 2][0] : null;
        if (preg_match('/::?(before|after|marker)(?![\w-])/', $scomp)) {
            // a display: contents element still gets ::before/::after boxes when "content" is set
            if (preg_match('/(^|;)\s*content\s*:/', $decls)) {
                sx_dialect_mark($inheritRisk, $anc);
                sx_dialect_mark($divRisk, $anc);
            }
            continue;
        }
        if ($stag === 'div') {
            sx_dialect_mark($divRisk, $anc); // a converted list (a real box) would pick this up
        }
        if (preg_match($inheritedRe, $decls)) {
            sx_dialect_mark($inheritRisk, $anc);
        }
    }
    if ($noWrap['all'] || $inheritRisk['all']) {
        $d['wrappers'] = 'none';
    }
    if ($divRisk['all']) {
        $d['lists'] = 'keep';
    }
    $noWrapCls = array_flip($noWrap['cls']);
    $noWrapTag = array_flip($noWrap['tag']);
    $inh = [array_flip($inheritRisk['cls']), array_flip($inheritRisk['tag'])];
    $div = [array_flip($divRisk['cls']), array_flip($divRisk['tag'])];
    $share = ['none' => 0, 'some' => 35, 'many' => 75][$d['wrappers']];
    if (!$share && $d['lists'] === 'keep') {
        return $html;
    }

    // 2. walk the tags; scripts, styles, templates and comments are skipped whole
    preg_match_all('#<!--.*?-->|<(script|style|template|textarea)\b[^>]*>.*?</\1\s*>|<(/?)([a-zA-Z][\w:-]*)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>#s',
        $html, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    $stack = [];
    $lists = [];
    $edits = []; // [position, length replaced, text]
    $n = 0;
    $wrapCls = $prefix . 'w' . sx_dialect_hash('wrap', 3);
    $authoredCls = fn($c) => substr($c, strlen($prefix));
    foreach ($tags as $t) {
        if (!isset($t[3]) || $t[3][1] < 0) {
            continue;
        }
        [$all, $pos] = $t[0];
        $name = strtolower($t[3][0]);
        $attrs = $t[4][0] ?? '';
        if ($t[2][0] !== '/') {
            if ($stack) {
                $stack[count($stack) - 1]['kids'][] = ['tag' => $name, 'pos' => $pos, 'role' => (bool)preg_match('/\srole=/', $attrs)];
            }
            if (in_array($name, SX_DIALECT_VOID, true) || str_ends_with(rtrim($attrs), '/')) {
                continue;
            }
            preg_match('/\sclass="([^"]*)"/', $attrs, $cm);
            $cls = isset($cm[1]) ? array_values(array_filter(preg_split('/\s+/', trim($cm[1])), fn($c) => str_starts_with($c, $prefix))) : [];
            $stack[] = ['tag' => $name, 'cls' => array_map($authoredCls, $cls), 'role' => (bool)preg_match('/\srole=/', $attrs),
                'openEnd' => $pos + strlen($all), 'open' => $pos, 'kids' => [], 'svg' => $name === 'svg' || ($stack && end($stack)['svg']), 'bad' => false];
            continue;
        }
        while ($stack && end($stack)['tag'] !== $name) { // unexpected nesting: leave everything on the way alone
            array_pop($stack);
            if ($stack) {
                $stack[count($stack) - 1]['bad'] = true;
            }
        }
        if (!$stack) {
            continue;
        }
        $e = array_pop($stack);
        $n++;
        $chain = array_merge([$e], $stack);
        $inScope = function (array $risk) use ($chain, $prefix): bool {
            foreach ($chain as $x) {
                if (isset($risk[1][$x['tag']])) {
                    return true;
                }
                foreach ($x['cls'] as $c) {
                    if (isset($risk[0][$prefix . $c])) {
                        return true;
                    }
                }
            }
            return false;
        };
        $ok = $e['cls'] && !$e['role'] && !$e['bad'] && !$e['svg'] && $e['kids'];
        if ($ok && $d['lists'] === 'aria' && $name === 'ul' && !array_filter($e['kids'], fn($k) => $k['tag'] !== 'li' || $k['role']) && !$inScope($div)) {
            $edits[] = [$e['open'], 3, '<div role="list"'];
            $edits[] = [$pos, strlen($all), '</div>'];
            foreach ($e['kids'] as $k) {
                $edits[] = [$k['pos'], 3, '<div role="listitem"'];
            }
            $lists[] = [$e['open'], $pos];
        }
        $blocked = false;
        foreach ($e['cls'] as $c) {
            $blocked = $blocked || isset($noWrapCls[$prefix . $c]);
        }
        if ($ok && $share && in_array($name, SX_DIALECT_WRAP_TAGS, true) && !$blocked && !isset($noWrapTag[$name]) && !$inScope($inh)
            && crc32(SX::$ctx['seed'] . "|wrap|$n|" . implode(' ', $e['cls'])) % 100 < $share) {
            $edits[] = [$e['openEnd'], 0, '<div class="' . $wrapCls . '">'];
            $edits[] = [$pos, 0, '</div>'];
        }
    }
    // the converted lists' own </li> (depth 1 inside each converted <ul>)
    foreach ($lists as [$from, $to]) {
        $depth = 0;
        foreach ($tags as $t) {
            if (!isset($t[3]) || $t[3][1] < 0 || $t[0][1] <= $from || $t[0][1] >= $to || strtolower($t[3][0]) !== 'li') {
                continue;
            }
            if ($t[2][0] === '/') {
                if (--$depth === 0) {
                    $edits[] = [$t[0][1], strlen($t[0][0]), '</div>'];
                }
            } else {
                $depth++;
            }
        }
    }
    if (!$edits) {
        return $html;
    }
    // apply from the end; at one position, insertions (len 0) go after replacements so </div> wrappers close outside
    usort($edits, fn($a, $b) => $b[0] <=> $a[0] ?: $b[1] <=> $a[1]);
    foreach ($edits as [$at, $len, $text]) {
        $html = substr_replace($html, $text, $at, $len);
    }

    // 3. CSS: the wrapper rule; ul/li selectors also match converted lists, at the same specificity
    $done = false;
    return preg_replace_callback('#(<style[^>]*>)(.*?)(</style>)#s', function ($m) use ($d, $wrapCls, &$done, $lists) {
        $css = $m[2];
        if (!empty($lists)) {
            $css = preg_replace_callback('/(^|[{}])([^{}@]+)\{/', function ($r) {
                $sel = preg_replace_callback('/(^|[\s>+~,(])(ul|li)(?=$|[\s>+~,.:\[#)])/', fn($x) => $x[1] . ($x[2] === 'ul'
                    ? ':is(ul,div:where([role=list]))' : ':is(li,div:where([role=listitem]))'), $r[2]);
                return $r[1] . $sel . '{';
            }, $css);
        }
        if (!$done) {
            $css .= '.' . $wrapCls . '{display:contents!important}';
            $done = true;
        }
        return $m[1] . $css . $m[3];
    }, $html);
}
