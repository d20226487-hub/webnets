<?php
/**
 * AI brief: a Markdown prompt that tells an AI coding tool how to rebuild a template page (or the whole site) in the
 * developer's own stack, with the content, structure, design tokens, the rules it must not break and an acceptance
 * checklist. Instructions are in English; the site's own text stays in its language.
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
 */
function sx_ai_brief(array $pages, ?string $only = null, bool $full = false): string
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
    $L[] = '# Rebuild brief: ' . ($only !== null ? ($one[0]['title'] ?? $only) : $b['site']);
    $L[] = '';
    $L[] = 'You are rebuilding ' . ($only !== null ? 'one page' : 'every page') . " of the **{$b['site']}** template, a {$b['type']} site"
        . ($b['angle'] ? " (angle: {$b['angle']})" : '') . '. Keep the section order, the content and every rule below. '
        . 'Write the page text exactly as given (it is in ' . sx_brief_lang($loc) . '); write code and comments in English.';
    $L[] = '';
    $L[] = '**Target stack:** WordPress with the SX ACF blocks (`sx/<block>` from `wp/sx-blocks.php`). '
        . 'If you use another stack (plain HTML/CSS, React/Next.js, another CMS), build one component per section below with the listed fields as props.';
    $L[] = '';

    $L[] = '## Site context';
    $L[] = '';
    $L[] = "- Site: {$b['site']}" . ($b['url'] ? " ({$b['url']})" : '') . ($b['brand'] && $b['brand'] !== $b['site'] ? ", brand or publisher: {$b['brand']}" : '');
    $L[] = "- Market: {$m['name']} (`{$m['id']}`), language `{$loc}`, text direction `{$m['dir']}`, currency {$m['currency']['code']}";
    $L[] = "- Minimum age: {$m['min_age']}+";
    $vert = $m['verticals'] ?? [];
    $L[] = '- Products allowed: sports betting ' . (!empty($vert['sports']) ? 'yes' : '**no**') . ', online casino ' . (!empty($vert['casino']) ? 'yes' : '**no**');
    if (!empty($m['compliance']['notes'])) {
        $L[] = "- Market note: {$m['compliance']['notes']}";
    }
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

    $rules = [];
    foreach ($m['compliance']['required_blocks'] ?? [] as $rb) {
        $rules[] = match ($rb) {
            'age-gate' => "Every page opens with an age gate ({$m['min_age']}+) before any gambling content.",
            'compliance-bar' => 'Every page has the responsible-gambling panel (usually in the footer) with the market text: age notice, licence line, taglines, self-exclusion and helpline where given. Never type this text yourself; it comes from the market settings.',
            default => "Every page includes the `$rb` block.",
        };
    }
    if (empty($vert['casino'])) {
        $rules[] = 'No casino content anywhere: online casino is not allowed in this market.';
    }
    if (empty($vert['sports'])) {
        $rules[] = 'No sports-betting content: it is not allowed in this market.';
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
    $L[] = '## Rules you must not break';
    $L[] = '';
    foreach ($rules as $r) {
        $L[] = "- $r";
    }
    foreach ($guard as $g => $blk) {
        $L[] = "- $blk: $g";
    }
    $L[] = '';

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

    $L[] = '## Acceptance checklist';
    $L[] = '';
    $L[] = '- [ ] Sections appear in the order above, with the same layout variant (or an equivalent layout).';
    $L[] = '- [ ] All text is used exactly as given; nothing invented (no extra claims, figures, offers or reviews).';
    $L[] = '- [ ] One H1 per page; headings do not skip levels.';
    $L[] = '- [ ] No horizontal scrolling at 375px; tap targets at least 44px.';
    $L[] = '- [ ] Each page is under 64 KB of HTML and CSS (images excluded).';
    $L[] = '- [ ] The structured data listed for each page is present and valid.';
    $L[] = '- [ ] Every internal link opens an existing page.';
    $L[] = '- [ ] Text contrast meets WCAG AA, focus is visible, images have alt text.';
    $L[] = '- [ ] Every rule in "Rules you must not break" holds on every page.';
    if (!$full) {
        $L[] = '';
        $L[] = '_Long text and lists are shortened here (…, "[n items]"). For the full text run `php build/ai-brief.php sites/demos/<config>.json`._';
    }
    return implode("\n", $L) . "\n";
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
