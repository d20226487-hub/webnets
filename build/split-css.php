<?php
/**
 * Dev tool: move variant-specific rules from blocks/<name>/style.css into blocks/<name>/variants/<v>.css,
 * so pages ship only the CSS of variants they render. A rule moves when every selector in it targets
 * the same variant modifier (".sx-<base>--<variant>"). @media blocks are split rule by rule.
 * Shared rules, comments and @keyframes stay in style.css.
 *
 * Usage: php build/split-css.php footer compliance-bar faq    (prints what moved; idempotent)
 */

$root = dirname(__DIR__);

function sx_css_parse(string $css): array
{
    $items = [];
    $i = 0;
    $n = strlen($css);
    while ($i < $n) {
        if (ctype_space($css[$i])) {
            $i++;
            continue;
        }
        if (substr($css, $i, 2) === '/*') {
            $end = strpos($css, '*/', $i) + 2;
            $items[] = ['comment', substr($css, $i, $end - $i)];
            $i = $end;
            continue;
        }
        $brace = strpos($css, '{', $i);
        if ($brace === false) {
            break;
        }
        $head = trim(substr($css, $i, $brace - $i));
        $depth = 0;
        for ($j = $brace; $j < $n; $j++) {
            if ($css[$j] === '{') {
                $depth++;
            } elseif ($css[$j] === '}' && --$depth === 0) {
                break;
            }
        }
        $body = substr($css, $brace + 1, $j - $brace - 1);
        $items[] = str_starts_with($head, '@media') ? ['media', $head, sx_css_parse($body)] : (str_starts_with($head, '@') ? ['raw', substr($css, $i, $j - $i + 1)] : ['rule', $head, trim($body)]);
        $i = $j + 1;
    }
    return $items;
}

function sx_rule_variant(string $selector, array $variants): ?string
{
    $found = null;
    foreach (array_map('trim', explode(',', $selector)) as $sel) {
        if (!preg_match('/\.sx-[a-z0-9-]+?--(' . implode('|', array_map('preg_quote', $variants)) . ')(?![a-z0-9-])/', $sel, $m)) {
            return null;
        }
        if ($found !== null && $found !== $m[1]) {
            return null;
        }
        $found = $m[1];
    }
    return $found;
}

function sx_css_emit(array $items, string $indent = ''): string
{
    $out = '';
    foreach ($items as $it) {
        if ($it[0] === 'comment' || $it[0] === 'raw') {
            $out .= $indent . $it[1] . "\n";
        } elseif ($it[0] === 'rule') {
            $out .= $indent . $it[1] . ' { ' . preg_replace('/\s*\n\s*/', ' ', $it[2]) . " }\n";
        } else {
            $out .= $indent . $it[1] . " {\n" . sx_css_emit($it[2], $indent . '  ') . $indent . "}\n";
        }
    }
    return $out;
}

foreach (array_slice($argv, 1) as $block) {
    $dir = "$root/blocks/$block";
    $variants = json_decode(file_get_contents("$dir/block.json"), true)['sx']['variants'] ?? [];
    $items = sx_css_parse(file_get_contents("$dir/style.css"));
    $keep = [];
    $moved = array_fill_keys($variants, []);
    $count = 0;
    foreach ($items as $it) {
        if ($it[0] === 'rule' && ($v = sx_rule_variant($it[1], $variants))) {
            $moved[$v][] = $it;
            $count++;
        } elseif ($it[0] === 'media') {
            $inner = [];
            $byV = [];
            foreach ($it[2] as $r) {
                if ($r[0] === 'rule' && ($v = sx_rule_variant($r[1], $variants))) {
                    $byV[$v][] = $r;
                    $count++;
                } else {
                    $inner[] = $r;
                }
            }
            if ($inner) {
                $keep[] = ['media', $it[1], $inner];
            }
            foreach ($byV as $v => $rules) {
                $moved[$v][] = ['media', $it[1], $rules];
            }
        } else {
            $keep[] = $it;
        }
    }
    if (!$count) {
        echo "$block: nothing to move\n";
        continue;
    }
    @mkdir("$dir/variants", 0777, true);
    foreach ($moved as $v => $rules) {
        if (!$rules) {
            continue;
        }
        $file = "$dir/variants/$v.css";
        $prev = is_file($file) ? rtrim(file_get_contents($file)) . "\n" : "/* $v */\n";
        file_put_contents($file, $prev . sx_css_emit($rules));
    }
    file_put_contents("$dir/style.css", sx_css_emit($keep));
    printf("%s: moved %d rules into %s\n", $block, $count, implode(', ', array_keys(array_filter($moved))));
}
