<?php
/**
 * AI brief: a Markdown prompt that tells an AI coding tool how to rebuild a template page (or the whole site) in the
 * developer's own stack. The fixed parts come from editable settings in ai/ (system.md: role, target stack, acceptance
 * checklist; types/<type>.md; markets/<market>.md, edited in the local settings page); the brief adds the site context,
 * design tokens, block rules and every section with its content. Instructions are in English; site text stays in its language.
 *
 * Used by the developer skeleton view (lib/devspec.php: page brief inside the panel, site brief as a published file)
 * and by build/ai-brief.php (full text, written to dist/ai/). Needs pages built with ctx devspec (cfg "_devspec").
 */

/** Shorter field values for the page data: long text cut at $str characters, lists over $json characters of JSON summarised. */
function sx_dev_trim($v, int $str = 220, int $json = 400)
{
    if (is_array($v)) {
        $j = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return strlen($j) <= $json ? $v : ['_items' => count($v)];
    }
    return is_string($v) && mb_strlen($v) > $str ? mb_substr($v, 0, $str) . '…' : $v;
}

/**
 * @param array       $pages sx_build_site() pages (not assets), each with 'dev', 'brief', 'file', 'title', 'html'
 * @param string|null $only  file of the one page to describe in detail (page brief); null = every page (site brief)
 * @param bool        $full  full field values (command line) instead of the shortened ones (review page)
 * @param bool        $withSystem put the system prompt (ai/system.md) first, for a single self-contained file
 */
function sx_ai_brief(array $pages, ?string $only = null, bool $full = false, bool $withSystem = false): string
{
    static $names = null;
    $names ??= json_decode(file_get_contents(__DIR__ . '/devspec-names.json'), true);
    $first = reset($pages);
    $b = $first['brief'];
    $m = $b['market'];
    $loc = $b['locale'];
    $name = fn(string $blk) => $names[$blk][0] ?? $blk;
    $one = $only !== null ? array_values(array_filter($pages, fn($p) => $p['file'] === $only)) : array_values($pages);
    $multi = count($pages) > 1;

    $L = [];
    if ($withSystem) { // one self-contained document: the system prompt first, then the task
        $L[] = sx_ai_system($pages, $only);
        $L[] = '';
        $L[] = '---';
        $L[] = '';
    }
    $L[] = '# Rebuild brief: ' . ($only !== null ? ($one[0]['title'] ?? $only) : $b['site']);
    $L[] = '';
    $L[] = '_Follow the system prompt (role, target stack, acceptance checklist) together with this brief._';
    $L[] = '';

    $type = sx_ai_setting("types/{$b['type']}.md");
    if ($type !== '') {
        $L[] = '## Site type guidance';
        $L[] = '';
        $L[] = $type;
        $L[] = '';
    }
    $L[] = '## Market guidance';
    $L[] = '';
    $L[] = sx_ai_setting("markets/{$m['id']}.md") ?: "- Market: {$m['name']} (`{$m['id']}`), minimum age {$m['min_age']}+";
    $L[] = '';

    $L[] = '## Site context';
    $L[] = '';
    $L[] = "- Site: {$b['site']}" . ($b['url'] ? " ({$b['url']})" : '') . ($b['brand'] && $b['brand'] !== $b['site'] ? ", brand or publisher: {$b['brand']}" : '');
    $L[] = "- Site type: {$b['type']}" . ($b['angle'] ? ", angle {$b['angle']}" : '') . "; language `{$loc}`, text direction `{$m['dir']}`";
    if ($multi) {
        $L[] = '- Pages (' . count($pages) . '):';
        foreach ($pages as $p) {
            $L[] = "  - `{$p['file']}` — {$p['title']}";
        }
    }
    $L[] = '';

    $tok = sx_brief_tokens($first['html']);
    if ($tok['vars']) {
        $L[] = '## Design tokens';
        $L[] = '';
        if ($tok['fonts']) {
            $L[] = 'Fonts (Google Fonts): ' . implode(', ', $tok['fonts']) . '.';
            $L[] = '';
        }
        $L[] = 'Use these CSS custom properties (they are the whole palette, type scale and radius set):';
        $L[] = '';
        $L[] = '```css';
        $L[] = ':root {';
        foreach ($tok['vars'] as $k => $v) {
            $L[] = "  $k: $v;";
        }
        $L[] = '}';
        $L[] = '```';
        $L[] = '';
        $L[] = 'Layout is mobile-first: it must work from 375px to 1440px wide.';
        $L[] = '';
    }

    $guard = [];
    $used = [];
    foreach ($one as $p) {
        foreach ($p['dev'] as $d) {
            $used[$d['b']][$d['v']] = true;
        }
    }
    foreach ($used as $blk => $vs) {
        $sx = sx_block_meta($blk)['block']['sx'] ?? [];
        foreach ($sx['guardrails'] ?? [] as $g) {
            // "variant: rule" applies only where that variant is on the page
            if (preg_match('/^([\w-]+):\s/', $g, $gm) && in_array($gm[1], $sx['variants'] ?? [], true) && empty($vs[$gm[1]])) {
                continue;
            }
            $guard[$g] = $name($blk);
        }
    }
    if ($guard) {
        $L[] = '## Block rules';
        $L[] = '';
        foreach ($guard as $g => $blk) {
            $L[] = "- $blk: $g";
        }
        $L[] = '';
    }

    foreach ($one as $p) {
        $info = sx_brief_page_info($p['html']);
        $L[] = $multi ? "## Page `{$p['file']}`" : '## Page';
        $L[] = '';
        $L[] = "- Title tag: {$info['title']}";
        if ($info['desc'] !== '') {
            $L[] = "- Meta description: {$info['desc']}";
        }
        if ($info['h1'] !== '') {
            $L[] = "- The only H1: {$info['h1']}";
        }
        if ($info['ld']) {
            $L[] = '- Structured data (JSON-LD): ' . implode(', ', $info['ld']);
        }
        if ($info['links']) {
            $L[] = '- Links to: ' . implode(', ', array_map(fn($l) => "`$l`", $info['links']));
        }
        $L[] = '';
        $L[] = '### Sections, in order';
        $L[] = '';
        $n = 0;
        foreach ($p['dev'] as $d) {
            $meta = sx_block_meta($d['b']);
            $role = $meta['block']['sx']['role'] ?? 'content';
            $indent = str_repeat('  ', $d['n']);
            if ($d['n'] === 0) {
                $n++;
                $L[] = "$n. **{$name($d['b'])}** — block `{$meta['block']['name']}`, variant `{$d['v']}`, role `$role`";
            } else {
                $L[] = "$indent- inside it: **{$name($d['b'])}** — block `{$meta['block']['name']}`, variant `{$d['v']}`";
            }
            if (!empty($meta['block']['description']) && $d['n'] === 0) {
                $L[] = "$indent   Purpose: {$meta['block']['description']}";
            }
            foreach ($d['p'] as $k => $v) {
                if ($k === 'variant') {
                    continue;
                }
                $v = $full ? $v : sx_dev_trim($v, 400, 1500);
                $L[] = "$indent   - `$k`: " . sx_brief_value($v);
            }
        }
        $L[] = '';
    }
    if (!$full) {
        $L[] = '_Long text and lists are shortened here (…, "[n items]"). For the full text run `php build/ai-brief.php sites/demos/<config>.json`._';
    }
    return implode("\n", $L) . "\n";
}

/** One AI settings file (ai/<rel>, edited in the local settings page) without its HTML comments; '' when missing. */
function sx_ai_setting(string $rel): string
{
    $f = dirname(__DIR__) . "/ai/$rel";
    return is_file($f) ? trim(preg_replace('/<!--.*?-->/s', '', file_get_contents($f))) : '';
}

/** The system prompt (ai/system.md) with its placeholders filled for these pages. */
function sx_ai_system(array $pages, ?string $only = null): string
{
    $b = reset($pages)['brief'];
    return strtr(sx_ai_setting('system.md'), [
        '{scope}' => $only !== null || count($pages) === 1 ? 'one page' : 'every page',
        '{site}' => $b['site'],
        '{type}' => $b['type'],
        '{angle}' => $b['angle'] ? " (angle: {$b['angle']})" : '',
        '{language}' => sx_brief_lang($b['locale']),
    ]);
}

function sx_brief_lang(string $loc): string
{
    return ['en' => 'English', 'ru' => 'Russian', 'pt' => 'Portuguese', 'kk' => 'Kazakh'][$loc] ?? $loc;
}

function sx_brief_value($v): string
{
    if (is_bool($v)) {
        return $v ? 'true' : 'false';
    }
    if (is_array($v)) {
        return isset($v['_items']) ? "[{$v['_items']} items, see the review page]" : '`' . json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '`';
    }
    $v = trim(preg_replace('/\s+/', ' ', (string)$v));
    return str_contains($v, '<') ? '`' . $v . '` (HTML)' : '"' . $v . '"';
}

/** Title, description, H1, JSON-LD types and internal links of a built page. */
function sx_brief_page_info(string $html): array
{
    $tx = fn($s) => trim(html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    preg_match('#<title>(.*?)</title>#s', $html, $t);
    preg_match('#<meta name="description" content="([^"]*)"#', $html, $d);
    preg_match('#<h1[^>]*>(.*?)</h1>#s', $html, $h);
    $ld = [];
    foreach (preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $mm) ? $mm[1] : [] as $j) {
        $o = json_decode($j, true);
        foreach (isset($o['@type']) ? [$o] : (array)$o as $x) {
            if (isset($x['@type'])) {
                $ld[] = is_array($x['@type']) ? implode('/', $x['@type']) : $x['@type'];
            }
        }
    }
    preg_match_all('#href="([\w-]+\.html)"#', $html, $ln);
    return ['title' => $tx($t[1] ?? ''), 'desc' => $tx($d[1] ?? ''), 'h1' => $tx($h[1] ?? ''), 'ld' => array_values(array_unique($ld)),
        'links' => array_values(array_unique($ln[1] ?? []))];
}

/** CSS custom properties of the first :root rule(s) and the Google Fonts families of a built page. */
function sx_brief_tokens(string $html): array
{
    $vars = [];
    preg_match('#<style>(.*?)</style>#s', $html, $st);
    foreach (preg_match_all('#:root\s*\{([^}]*)\}#', $st[1] ?? '', $mm) ? $mm[1] : [] as $body) {
        foreach (preg_match_all('#(--[\w-]+)\s*:\s*([^;]+)#', $body, $vv, PREG_SET_ORDER) ? $vv : [] as $v) {
            $vars[$v[1]] = trim($v[2]);
        }
    }
    preg_match('#fonts\.googleapis\.com/css2\?([^"]+)"#', $html, $f);
    $fonts = [];
    foreach (preg_match_all('#family=([^:&]+)#', html_entity_decode($f[1] ?? ''), $ff) ? $ff[1] : [] as $fam) {
        $fonts[] = str_replace('+', ' ', urldecode($fam));
    }
    return ['vars' => $vars, 'fonts' => array_values(array_unique($fonts))];
}
