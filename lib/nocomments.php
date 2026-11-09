<?php
/**
 * No comments in shipped code: HTML comments are dropped and inline scripts lose their // and /* *\/ comments.
 * CSS comments are already stripped by sx_minify_css(). JSON-LD and other non-JavaScript scripts are left as they are.
 */

function sx_strip_comments(string $html): string
{
    return preg_replace_callback('#<script([^>]*)>(.*?)</script>|<style([^>]*)>(.*?)</style>|<!--.*?-->#s', function ($m) {
        if (str_starts_with($m[0], '<!--')) {
            return '';
        }
        if (str_starts_with($m[0], '<style')) {
            return '<style' . $m[3] . '>' . preg_replace('#/\*.*?\*/#s', '', $m[4]) . '</style>';
        }
        if (preg_match('/type="(?!text\/javascript|module)[^"]+"/', $m[1])) {
            return $m[0]; // application/ld+json, templates and the like
        }
        return '<script' . $m[1] . '>' . sx_strip_js_comments($m[2]) . '</script>';
    }, $html);
}

/** Remove JS comments, keeping strings, template literals and regular expression literals intact. */
function sx_strip_js_comments(string $js): string
{
    $out = '';
    $n = strlen($js);
    $last = ''; // last significant character written, to tell a regex literal from a division
    for ($i = 0; $i < $n; $i++) {
        $ch = $js[$i];
        $next = $js[$i + 1] ?? '';
        if ($ch === '"' || $ch === "'" || $ch === '`') {
            $j = $i + 1;
            while ($j < $n && $js[$j] !== $ch) {
                $j += $js[$j] === '\\' ? 2 : 1;
            }
            $out .= substr($js, $i, $j - $i + 1);
            $i = $j;
            $last = $ch;
            continue;
        }
        if ($ch === '/' && $next === '/') {
            $j = strpos($js, "\n", $i);
            $i = $j === false ? $n : $j - 1;
            continue;
        }
        if ($ch === '/' && $next === '*') {
            $j = strpos($js, '*/', $i + 2);
            $i = $j === false ? $n : $j + 1;
            $out .= ' ';
            continue;
        }
        if ($ch === '/' && ($last === '' || strpos('(,=:[!&|?{};+-*%<>~^', $last) !== false)) {
            $j = $i + 1;
            $class = false;
            while ($j < $n && ($js[$j] !== '/' || $class)) {
                if ($js[$j] === '\\') {
                    $j++;
                } elseif ($js[$j] === '[') {
                    $class = true;
                } elseif ($js[$j] === ']') {
                    $class = false;
                } elseif ($js[$j] === "\n") {
                    break; // not a regex after all
                }
                $j++;
            }
            $out .= substr($js, $i, $j - $i + 1);
            $i = $j;
            $last = '/';
            continue;
        }
        $out .= $ch;
        if (!ctype_space($ch)) {
            $last = $ch;
        }
    }
    return preg_replace("/[ \t]+\n/", "\n", $out);
}
