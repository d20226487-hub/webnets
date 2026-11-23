<?php
/**
 * Multi-language sites: every language version is the same site in its own folder (the main language at the root,
 * the others in /<code>/). The site config lists them ("langs": [{code, name, hreflang}], the main one first) and
 * says where the switcher goes and how it looks ("lang_ui": {place, look}). Links to the other versions are written
 * as sxlang:<code> (sxlangabs:<code> for hreflang); the exporter turns them into the same page in that language.
 */

const SX_LANG_NAMES = ['en' => 'English', 'ru' => 'Русский', 'kk' => 'Қазақша', 'pt' => 'Português'];
const SX_LANG_LOOKS = ['header' => ['dropdown', 'codes', 'pills'], 'top' => ['names', 'codes', 'dropdown'], 'footer' => ['names', 'pills', 'dropdown'], 'float' => ['dropdown', 'pills']];

function sx_langs(): array
{
    $l = SX::$ctx['langs'] ?? [];
    return count($l) > 1 ? $l : [];
}

function sx_lang_globe(): string
{
    return '<svg class="' . cls('lang', 'globe') . '" viewBox="0 0 24 24" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">'
        . '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 3.9 5.6 3.9 9s-1.3 6.4-3.9 9c-2.6-2.6-3.9-5.6-3.9-9S9.4 5.6 12 3z"/></g></svg>';
}

/** The language switcher when this site places it at $where (header, top, footer, float), else ''. */
function sx_lang_switch(string $where): string
{
    $langs = sx_langs();
    $ui = SX::$ctx['lang_ui'] ?? [];
    if (!$langs || ($ui['place'] ?? 'header') !== $where) {
        return '';
    }
    SX::$used['_lang'] = 1;
    $look = in_array($ui['look'] ?? '', SX_LANG_LOOKS[$where] ?? [], true) ? $ui['look'] : (SX_LANG_LOOKS[$where][0] ?? 'codes');
    $cur = SX::$ctx['locale'];
    $label = t('lang.switch');
    $full = in_array($look, ['names', 'dropdown'], true);
    $items = '';
    foreach ($langs as $l) {
        $items .= '<li><a href="sxlang:' . e($l['code']) . '" hreflang="' . e($l['hreflang']) . '" lang="' . e($l['hreflang']) . '"'
            . ($l['code'] === $cur ? ' aria-current="true"' : '') . ($full ? '' : ' title="' . e($l['name']) . '"') . '>'
            . e($full ? $l['name'] : mb_strtoupper($l['code'])) . '</a></li>';
    }
    $class = cx('lang', ['lang', null, $look], ['lang', null, $where]);
    if ($look === 'dropdown') {
        $html = '<details class="' . $class . '"><summary aria-label="' . e($label) . '">' . sx_lang_globe() . '<span>' . e(mb_strtoupper($cur)) . '</span></summary><ul>' . $items . '</ul></details>';
    } else {
        $html = '<nav class="' . $class . '" aria-label="' . e($label) . '">' . (in_array($where, ['top', 'footer'], true) ? '<span class="' . cls('lang', 'label') . '">' . sx_lang_globe() . e($label) . '</span>' : '')
            . '<ul>' . $items . '</ul></nav>';
    }
    if ($where === 'top') {
        return '<div class="' . cls('lang', 'bar') . '"><div class="' . cls('container') . '">' . $html . '</div></div>';
    }
    if ($where === 'footer') {
        return '<div class="' . cx('container', ['lang', 'foot']) . '">' . $html . '</div>';
    }
    return $html;
}

/** <link rel="alternate" hreflang> for every language version (the main one is also x-default). */
function sx_lang_head(): string
{
    $out = '';
    foreach (sx_langs() as $i => $l) {
        $out .= '<link rel="alternate" hreflang="' . e($l['hreflang']) . '" href="sxlangabs:' . e($l['code']) . '">';
        if ($i === 0) {
            $out .= '<link rel="alternate" hreflang="x-default" href="sxlangabs:' . e($l['code']) . '">';
        }
    }
    return $out;
}

/** The switcher put into the page's slots (the header block places its own; a page without a header gets the bar above). */
function sx_lang_place(array $slots): array
{
    if (!sx_langs()) {
        return $slots;
    }
    $place = SX::$ctx['lang_ui']['place'] ?? 'header';
    if ($place === 'header' && !isset(SX::$used['header'])) {
        SX::$ctx['lang_ui']['place'] = $place = 'top';
    }
    if ($place === 'top') {
        $slots['top'] = sx_lang_switch('top') . $slots['top'];
    } elseif ($place === 'footer') {
        $sw = sx_lang_switch('footer');
        $at = strrpos($slots['footer'], '</footer>');
        $slots['footer'] = $at === false ? $slots['footer'] . $sw : substr($slots['footer'], 0, $at) . $sw . substr($slots['footer'], $at);
    } elseif ($place === 'float') {
        $slots['overlay'] .= sx_lang_switch('float');
    }
    return $slots;
}

/**
 * The exporter's last step: sxlang:<code> becomes the same page in that language (its home page when that language
 * has no such page), sxlangabs:<code> that page's absolute URL. $files: [code => [page key => file]]; $main: the main
 * language (at the site's root); $from: the language of the page being written.
 */
function sx_lang_links(string $html, string $pageKey, string $from, string $main, array $files, string $siteUrl): string
{
    $dir = fn(string $c) => $c === $main ? '' : "$c/";
    return preg_replace_callback('/(href=")sxlang(abs)?:([a-z]{2,3})"/', function ($m) use ($pageKey, $from, $main, $files, $siteUrl, $dir) {
        $c = $m[3];
        $file = $files[$c][$pageKey] ?? 'index.html';
        if ($m[2] !== '') {
            return $m[1] . htmlspecialchars(rtrim($siteUrl, '/') . '/' . $dir($c) . ($file === 'index.html' ? '' : $file)) . '"';
        }
        return $m[1] . htmlspecialchars(($from === $main ? '' : '../') . $dir($c) . $file) . '"';
    }, $html);
}
