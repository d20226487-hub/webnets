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
        $kb = strlen(preg_replace('#<svg class="[^"]*-art"[^>]*>.*?</svg>#s', '', $p['html'])) / 1024;
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

