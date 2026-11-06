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
