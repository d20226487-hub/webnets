<?php
/**
 * Multi-page sites (brief 05): internal URLs, output files, site-wide checks, sitemap and robots.
 *
 * The page map (SX::$ctx['pages']) comes from sx_expand_site(): [id => slug, nav, blurb, in_nav].
 * URL styles (site config "urls"):
 *   pretty  /payments/  written as payments/index.html. "base" sets a subfolder (default "/").
 *   files   payments.html, relative and flat: works from any folder, file:// and review links.
 */

const SX_PAGE_BUDGET_KB = 64; // HTML only (inline cover art excluded); review lists with long guides need a little more than 60

function sx_pages(): array
{
    return SX::$ctx['pages'] ?? [];
}

function sx_page_id(): string
{
    return SX::$ctx['page_id'] ?? '';
}

/** Internal link to a page of this site. */
function sx_page_url(string $pid): string
{
    $p = sx_pages()[$pid] ?? null;
    if (!$p) {
        SX::$warnings[] = "Link to page '$pid', which this site does not have.";
        return '#';
    }
    if ((SX::$ctx['urls'] ?? 'pretty') === 'files') {
        return (SX::$ctx['file_prefix'] ?? '') . ($p['slug'] === '' ? 'index.html' : str_replace('/', '-', $p['slug']) . '.html');
    }
    return rtrim(SX::$ctx['base'] ?? '/', '/') . '/' . ($p['slug'] === '' ? '' : $p['slug'] . '/');
}

/** Output file of a page, relative to the site's dist folder. */
function sx_page_file(array $p, string $style): string
{
    $pre = $style === 'files' ? (SX::$ctx['file_prefix'] ?? '') : '';
    if ($p['slug'] === '') {
        return $pre . 'index.html';
    }
    return $style === 'files' ? $pre . str_replace('/', '-', $p['slug']) . '.html' : $p['slug'] . '/index.html';
}

/** Logo link: the home page on multi-page sites, the site root otherwise. */
function sx_home_url(): string
{
    return sx_pages() ? sx_page_url('home') : '/';
}

/** Absolute URL of a page (canonical, sitemap, JSON-LD); null without site.url. */
function sx_abs_url(string $pid): ?string
{
    $base = SX::$ctx['site']['url'] ?? null;
    $p = sx_pages()[$pid] ?? null;
    if (!$base || !$p) {
        return null;
    }
    $path = $p['slug'] === '' ? '' : ((SX::$ctx['urls'] ?? 'pretty') === 'files' ? str_replace('/', '-', $p['slug']) . '.html' : $p['slug'] . '/');
    return rtrim($base, '/') . '/' . $path;
}

/** Nav items for header/footer: [label, url, current]. */
function sx_page_nav(): array
{
    $out = [];
    foreach (sx_pages() as $pid => $p) {
        if ($p['in_nav'] && empty($p['paged_of']) && empty($p['item'])) {
            $out[] = ['label' => $p['nav'], 'url' => sx_page_url($pid), 'current' => $pid === sx_page_id()];
        }
    }
    return $out;
}

/**
 * Site-wide checks after every page is rendered. Broken internal links and pages no other page links to
 * stop the build; duplicate titles/descriptions, pages without a CTA and pages over budget are warnings.
 * $out: sx_build_site() entries with 'file', 'html', 'warnings'.
 */
function sx_site_checks(array &$out, string $style, string $base, bool $ctaCheck = true): void
{
    $files = array_column($out, 'file');
    $broken = [];
    $linkedTo = [];
    $seen = ['title' => [], 'description' => []];
    foreach ($out as $i => $p) {
        preg_match_all('/href="([^"]*)"/', $p['html'], $m);
        foreach (array_unique($m[1]) as $href) {
            $href = html_entity_decode($href);
            if ($href === '' || preg_match('#^(https?:|mailto:|tel:|data:|//|\#)#', $href)) {
                continue;
            }
            $path = preg_replace('/[?#].*$/', '', $href);
            if ($style === 'files') {
                $target = ltrim(preg_replace('#^\./#', '', $path), '/');
            } else {
                $b = rtrim($base, '/') . '/';
                $rel = str_starts_with($path, $b) ? substr($path, strlen($b)) : ltrim($path, '/');
                $target = $rel === '' || str_ends_with($rel, '/') ? $rel . 'index.html' : $rel;
            }
            if (!in_array($target, $files, true)) {
                $broken[] = "{$p['file']} → $href";
            } elseif ($target !== $p['file']) {
                $linkedTo[$target] = true;
            }
        }
        if ($ctaCheck && !str_contains($p['html'], 'data-cta=')) {
            $out[$i]['warnings'][] = "Page {$p['file']} has no CTA.";
        }
        // generated cover art stands in for image files, which would not count towards the HTML budget
        $kb = strlen(preg_replace('#<svg [^>]*\bdata-cover\b[^>]*>.*?</svg>#s', '', $p['html'])) / 1024;
        if ($kb > SX_PAGE_BUDGET_KB) {
            $out[$i]['warnings'][] = sprintf('Page %s is %.1f KB (budget %d KB).', $p['file'], $kb, SX_PAGE_BUDGET_KB);
        }
        preg_match('#<title>(.*?)</title>#s', $p['html'], $t);
        preg_match('#<meta name="description" content="([^"]*)"#', $p['html'], $d);
        foreach (['title' => $t[1] ?? '', 'description' => $d[1] ?? ''] as $k => $v) {
            if ($v !== '' && isset($seen[$k][$v])) {
                $out[$i]['warnings'][] = "Page {$p['file']} has the same $k as {$seen[$k][$v]}.";
            }
            $seen[$k][$v] = $p['file'];
        }
    }
    $orphans = array_diff(array_diff($files, ['index.html']), array_keys($linkedTo));
    if ($broken || $orphans) {
        throw new RuntimeException(trim(
            ($broken ? 'Broken internal links: ' . implode(', ', $broken) . '. ' : '')
            . ($orphans ? 'No page links to: ' . implode(', ', $orphans) . '.' : '')
        ));
    }
}

/** sitemap.xml and robots.txt contents; null when site.url is not set. */
function sx_sitemap(): ?array
{
    $base = SX::$ctx['site']['url'] ?? null;
    if (!$base) {
        return null;
    }
    $today = SX::$ctx['today'] ?? date('Y-m-d');
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach (array_keys(sx_pages()) as $pid) {
        $xml .= '  <url><loc>' . htmlspecialchars(sx_abs_url($pid), ENT_XML1) . "</loc><lastmod>$today</lastmod></url>\n";
    }
    $xml .= "</urlset>\n";
    $robots = "User-agent: *\nAllow: /\n\nSitemap: " . rtrim($base, '/') . "/sitemap.xml\n";
    return ['sitemap.xml' => $xml, 'robots.txt' => $robots];
}

/** Breadcrumb nav (Home / current page) + BreadcrumbList JSON-LD; empty on home and one-page sites. */
function sx_breadcrumbs(string $class): string
{
    $pages = sx_pages();
    $pid = sx_page_id();
    if (!$pages || $pid === 'home' || !isset($pages[$pid])) {
        return '';
    }
    $crumbs = [['name' => $pages['home']['nav'], 'url' => sx_page_url('home'), 'abs' => sx_abs_url('home')]];
    if ($of = $pages[$pid]['paged_of'] ?? null) {
        $crumbs[] = ['name' => $pages[$of]['nav'], 'url' => sx_page_url($of), 'abs' => sx_abs_url($of)];
        $label = t('paging.page', ['n' => (int)substr($pid, strrpos($pid, '~') + 1)]);
        $crumbs[] = ['name' => mb_strtoupper(mb_substr($label, 0, 1)) . mb_substr($label, 1), 'url' => null, 'abs' => sx_abs_url($pid)];
    } else {
        if ($parent = $pages[$pid]['parent'] ?? null) {
            $crumbs[] = ['name' => $pages[$parent]['nav'], 'url' => sx_page_url($parent), 'abs' => sx_abs_url($parent)];
        }
        $crumbs[] = ['name' => $pages[$pid]['nav'], 'url' => null, 'abs' => sx_abs_url($pid)];
    }
    if ($crumbs[0]['abs']) {
        SX::$jsonld[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(
            fn($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['abs']], $crumbs, array_keys($crumbs))];
    }
    $items = array_map(fn($c) => $c['url'] ? '<li><a href="' . sx_url($c['url']) . '">' . e($c['name']) . '</a></li>' : '<li aria-current="page">' . e($c['name']) . '</li>', $crumbs);
    return '<nav class="' . e($class) . '" aria-label="' . e(t('breadcrumb')) . '"><ol>' . implode('', $items) . '</ol></nav>';
}

/**
 * Review links only (multi-file Artifacts): the viewer can keep its scroll position when a link loads the next file,
 * so the visitor lands mid-page or at the bottom. Every review page therefore brings its own top into view when it
 * opens (now, on load, and twice shortly after in case the viewer restores its position late). No storage is used:
 * sandboxed viewer frames block it. Links to a #section keep their jump.
 */
/**
 * Review links only: inside the "Mobile view" phone frame (frame name sx-phone, or ?sxm=1) the template-variation bar
 * is hidden, so the frame shows the page as a phone visitor sees it. Runs before the bar, so it never flashes.
 */
function sx_review_phone_guard(): string
{
    return '<script>if(window.name==="sx-phone"||/[?&]sxm=1/.test(location.search))document.documentElement.classList.add("sx-inphone");</script>'
        . '<style>.sx-inphone .vbar{display:none!important}</style>';
}

/**
 * Review links only: on phones the template-variation bar collapses to one row (title + the current choices) that
 * opens the switchers on tap, so a phone visitor sees the page first. Desktop keeps the full bar.
 */
function sx_review_vbar_toggle(string $title, string $summary): string
{
    return '<style>.vbar-toggle{display:none}@media(max-width:640px){.vbar-toggle{display:flex;width:100%;align-items:center;gap:8px;min-height:32px;padding:0;border:0;background:none;color:#E8EAF0;font:600 13px/1.3 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;text-align:left;cursor:pointer}'
        . '.vbar-toggle span{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#9AA3B5;font-weight:500}'
        . '.vbar-toggle i{flex:none;font-style:normal;transition:transform .15s}.vbar.open .vbar-toggle i{transform:rotate(180deg)}.vbar:not(.open) .vbar-row{display:none}}</style>'
        . '<button type="button" class="vbar-toggle" aria-expanded="false" onclick="var o=this.parentNode.classList.toggle(&quot;open&quot;);this.setAttribute(&quot;aria-expanded&quot;,o)">'
        . htmlspecialchars($title) . ' <span>' . htmlspecialchars($summary) . '</span><i aria-hidden="true">▾</i></button>';
}

function sx_review_nav_script(): string
{
    return '<script>(function(){if(location.hash)return;var top=function(){try{window.scrollTo(0,0);'
        . 'document.documentElement.scrollIntoView({block:"start",behavior:"instant"});}catch(e){}};top();'
        . 'addEventListener("DOMContentLoaded",top);addEventListener("pageshow",top);'
        . 'addEventListener("load",function(){top();setTimeout(top,80);setTimeout(top,350);});})();</script>';
}

/**
 * Review links only: a floating "Mobile view" button that opens this same page in a 390px phone frame (an iframe, so
 * the site's real phone layout applies and links work inside it). Esc, ✕ or a click outside closes it. Hidden on
 * narrow screens and inside the frame itself (frame name sx-phone, which survives link clicks). Not part of the site.
 */
function sx_review_device_toggle(string $file, string $locale = 'en'): string
{
    [$open, $close] = ['ru' => ['Мобильная версия', 'Закрыть'], 'pt' => ['Versão mobile', 'Fechar'], 'kk' => ['Мобильді нұсқа', 'Жабу']][$locale] ?? ['Mobile view', 'Close'];
    return '<style>#sx-devtoggle{position:fixed;left:20px;bottom:96px;z-index:2147483600;display:inline-flex;align-items:center;gap:10px;padding:14px 22px 14px 16px;border:2px solid rgba(255,255,255,.85);border-radius:999px;'
        . 'background:linear-gradient(135deg,#7C3AED 0%,#DB2777 100%);color:#fff;font:800 15px/1 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;letter-spacing:.01em;'
        . 'box-shadow:0 10px 30px rgba(124,58,237,.45),0 2px 6px rgba(0,0,0,.25);cursor:pointer;transition:transform .15s ease,box-shadow .15s ease}'
        . '#sx-devtoggle:hover,#sx-devtoggle:focus-visible{transform:translateY(-2px) scale(1.03);box-shadow:0 16px 40px rgba(219,39,119,.5),0 2px 6px rgba(0,0,0,.25);outline:none}'
        . '#sx-devtoggle .ic{display:grid;place-items:center;width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,.22)}#sx-devtoggle svg{width:18px;height:18px}'
        . '#sx-devtoggle::after{content:"";position:absolute;inset:-6px;border-radius:inherit;border:3px solid #DB2777;opacity:0;animation:sxpulse 1.6s ease-out 3;pointer-events:none}'
        . '@keyframes sxpulse{0%{transform:scale(.92);opacity:.85}100%{transform:scale(1.25);opacity:0}}'
        . '@media (prefers-reduced-motion:reduce){#sx-devtoggle::after{animation:none}#sx-devtoggle{transition:none}}'
        . '#sx-devview{position:fixed;inset:0;z-index:2147483601;display:grid;place-items:center;background:rgba(17,19,24,.78);backdrop-filter:blur(4px)}'
        . '#sx-devview .ph{position:relative;width:414px;height:min(868px,calc(100vh - 48px));padding:12px;border-radius:52px;background:#0B0C10;box-shadow:0 0 0 2px #2A2C33,0 30px 80px rgba(0,0,0,.5)}'
        . '#sx-devview iframe{display:block;width:390px;height:100%;border:0;border-radius:40px;background:#fff}'
        . '#sx-devview .x{position:absolute;top:-6px;right:-52px;width:40px;height:40px;border:0;border-radius:50%;background:#fff;color:#111;font:700 18px/40px system-ui;cursor:pointer}'
        . '@media (max-width:700px){#sx-devtoggle{display:none}}</style>'
        . '<button type="button" id="sx-devtoggle" aria-haspopup="dialog"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/></svg></span>'
        . htmlspecialchars($open) . '</button>'
        . '<script>(function(){if(window.name==="sx-phone"||/[?&]sxm=1/.test(location.search)){var b=document.getElementById("sx-devtoggle");if(b)b.remove();return;}'
        . 'var btn=document.getElementById("sx-devtoggle"),view=null,prev="";function close(){if(!view)return;view.remove();view=null;document.documentElement.style.overflow=prev;btn.focus();}'
        . 'btn.addEventListener("click",function(){view=document.createElement("div");view.id="sx-devview";view.setAttribute("role","dialog");view.setAttribute("aria-label",' . json_encode($open) . ');'
        . 'view.innerHTML=\'<div class="ph"><iframe name="sx-phone" title="\'+' . json_encode($open) . '+\'" src="' . htmlspecialchars($file) . '?sxm=1"></iframe><button type="button" class="x" aria-label="\'+' . json_encode($close) . '+\'">✕</button></div>\';'
        . 'prev=document.documentElement.style.overflow;document.documentElement.style.overflow="hidden";document.body.appendChild(view);'
        . 'view.addEventListener("click",function(e){if(e.target===view||e.target.classList.contains("x"))close();});view.querySelector(".x").focus();});'
        . 'document.addEventListener("keydown",function(e){if(e.key==="Escape")close();});})();</script>';
}


/**
 * Review pages only: what a page looks like with another Colours option (the generator's "palette": "neutral" or
 * "brand"), as the CSS to lay over the page's own: the alternative's colour tokens plus any rules its look adds, and its
 * font stylesheet. $baseHtml and $altHtml are the same page built with and without the look.
 */
function sx_review_palette(string $baseHtml, string $altHtml): array
{
    $style = fn(string $h) => preg_match('#<style>(.*?)</style>#s', $h, $m) ? $m[1] : '';
    $split = function (string $css): array {
        preg_match('#^\s*((?::root\{[^}]*\}\s*)+)#', $css, $m);
        return [$m[1] ?? '', substr($css, strlen($m[0] ?? ''))];
    };
    [, $baseRest] = $split($style($baseHtml));
    [$tokens, $altRest] = $split($style($altHtml));
    // the look's own rules sit inside the shared CSS (before its last rules): keep only the part the base page lacks
    $pre = strspn($altRest ^ $baseRest, " ");
    $max = min(strlen($altRest), strlen($baseRest)) - $pre;
    $suf = 0;
    while ($suf < $max && $altRest[strlen($altRest) - 1 - $suf] === $baseRest[strlen($baseRest) - 1 - $suf]) {
        $suf++;
    }
    $extra = $pre + $suf >= strlen($baseRest) ? substr($altRest, $pre, strlen($altRest) - $pre - $suf) : $altRest;
    $font = preg_match('#href="(https://fonts\.googleapis\.com/css[^"]*)"#', $altHtml, $f) ? html_entity_decode($f[1]) : null;
    return ['css' => $tokens . $extra, 'fonts' => $font];
}

/**
 * Review pages only: the switcher rows that work in the browser, with no page of their own. Colours (when $palettes has
 * the page's "neutral" and "brand" versions from sx_review_palette()) and Rows of cards (swipe or stacked: the
 * data-rows attribute). The choice is remembered while moving between the review's pages. $rows: where Rows of cards
 * changes anything ("all", "phone": phones only, null: nowhere, so the row is left out).
 */
function sx_review_live_switches(array $palettes, string $locale, ?string $rows = 'all'): string
{
    $tr = fn(array $m) => $m[$locale] ?? $m['en'];
    $out = '';
    if ($palettes) {
        $links = '';
        foreach (['' => ['en' => 'Site style', 'ru' => 'Стиль сайта', 'pt' => 'Estilo do site'], 'neutral' => ['en' => 'Neutral', 'ru' => 'Нейтральный', 'pt' => 'Neutro'],
            'brand' => ['en' => 'Brand colours', 'ru' => 'Цвета бренда', 'pt' => 'Cores da marca']] as $k => $label) {
            $links .= '<a href="#" role="button" data-sxpal="' . $k . '">' . htmlspecialchars($tr($label)) . '</a>';
        }
        $out .= '<div class="vbar-row"><span>' . htmlspecialchars($tr(['en' => 'Colours', 'ru' => 'Цвета', 'pt' => 'Cores'])) . '</span><nav>' . $links . '</nav></div>';
        foreach ($palettes as $k => $p) {
            $out .= '<template id="sx-pal-' . $k . '"' . ($p['fonts'] ? ' data-fonts="' . htmlspecialchars($p['fonts']) . '"' : '') . '>' . htmlspecialchars($p['css']) . '</template>';
        }
    }
    if ($rows !== null) {
        $links = '';
        foreach (['swipe' => ['en' => 'Swipe', 'ru' => 'Листаются', 'pt' => 'Deslizar'], 'stack' => ['en' => 'Stacked', 'ru' => 'Сеткой', 'pt' => 'Em grade']] as $k => $label) {
            $links .= '<a href="#" role="button" data-sxrows="' . $k . '">' . htmlspecialchars($tr($label)) . '</a>';
        }
        $out .= '<div class="vbar-row"><span>' . htmlspecialchars($tr($rows === 'phone' ? ['en' => 'Rows of cards (phones)', 'ru' => 'Ряды карточек (телефон)', 'pt' => 'Fileiras de cartões (celular)'] : ['en' => 'Rows of cards', 'ru' => 'Ряды карточек', 'pt' => 'Fileiras de cartões'])) . '</span><nav>' . $links . '</nav></div>';
    }
    return $out . '<script>(function(){var K="sx-review-live",st={};try{st=JSON.parse(localStorage.getItem(K)||"{}")}catch(e){}'
        . 'var rows0=document.body.getAttribute("data-rows")==="stack"?"stack":"swipe";'
        . 'function save(){try{localStorage.setItem(K,JSON.stringify(st))}catch(e){}}'
        . 'function apply(){var p=st.pal||"",t=p&&document.getElementById("sx-pal-"+p),s=document.getElementById("sx-pal-css");'
        . 'if(!s){s=document.createElement("style");s.id="sx-pal-css";document.head.appendChild(s);}s.textContent=t?t.content.textContent:"";'
        . 'var f=document.getElementById("sx-pal-font"),fh=t&&t.getAttribute("data-fonts");if(fh){if(!f){f=document.createElement("link");f.id="sx-pal-font";f.rel="stylesheet";document.head.appendChild(f);}f.href=fh;}else if(f){f.remove();}'
        . 'var r=st.rows||rows0;if(r==="stack")document.body.setAttribute("data-rows","stack");else document.body.removeAttribute("data-rows");'
        . '[].forEach.call(document.querySelectorAll("[data-sxpal]"),function(a){if(a.getAttribute("data-sxpal")===p)a.setAttribute("aria-current","true");else a.removeAttribute("aria-current");});'
        . '[].forEach.call(document.querySelectorAll("[data-sxrows]"),function(a){if(a.getAttribute("data-sxrows")===r)a.setAttribute("aria-current","true");else a.removeAttribute("aria-current");});}'
        . 'document.addEventListener("click",function(e){var a=e.target.closest("[data-sxpal],[data-sxrows]");if(!a)return;e.preventDefault();'
        . 'if(a.hasAttribute("data-sxpal"))st.pal=a.getAttribute("data-sxpal");else st.rows=a.getAttribute("data-sxrows");save();apply();});apply();})();</script>';
}

/**
 * Where the Rows of cards switch shows: "all" when a [data-rows=stack] rule applies to the page at any width,
 * "phone" when only inside a max-width media query, null when no such rule matches anything on the page.
 */
function sx_review_rows_scope(string $html): ?string
{
    preg_match_all('#<style[^>]*>(.*?)</style>#s', $html, $m);
    $css = implode("\n", $m[1]);
    $body = preg_replace('#<style.*?</style>|<script.*?</script>|<template.*?</template>#s', '', $html);
    preg_match_all('#class="([^"]*)"#', $body, $cm);
    $have = array_flip(preg_split('/\s+/', implode(' ', $cm[1]), -1, PREG_SPLIT_NO_EMPTY));
    $scope = null;
    $stack = []; // open blocks: true for a max-width @media
    $len = strlen($css);
    $sel = 0;
    for ($i = 0; $i < $len; $i++) {
        $ch = $css[$i];
        if ($ch === '{') {
            $head = trim(substr($css, $sel, $i - $sel));
            if (str_starts_with($head, '@')) {
                $stack[] = str_starts_with($head, '@media') && str_contains($head, 'max-width');
            } else {
                $stack[] = null;
                if (str_contains($head, 'data-rows=stack') || str_contains($head, 'data-rows="stack"')) {
                    foreach (explode(',', $head) as $part) {
                        if (!preg_match('#data-rows="?stack"?\]\s*(.*)#', $part, $pm)) {
                            continue;
                        }
                        preg_match_all('#\.([\w-]+)#', $pm[1], $cl);
                        if ($cl[1] && count(array_intersect_key(array_flip($cl[1]), $have)) === count(array_unique($cl[1]))) {
                            $phone = in_array(true, $stack, true);
                            $scope = !$phone ? 'all' : ($scope ?? 'phone');
                        }
                    }
                }
            }
            $sel = $i + 1;
        } elseif ($ch === '}') {
            array_pop($stack);
            $sel = $i + 1;
        } elseif ($ch === ';' && !$stack) {
            $sel = $i + 1;
        }
    }
    return $scope;
}
