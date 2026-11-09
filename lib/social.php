<?php
/**
 * Social sharing and people boxes, shared by the news and review site types.
 *   sx_share_bar()   plain share links (no third-party scripts, no tracking pixels) + copy link
 *   sx_person_box()  author / editor card: initials avatar, name, role, bio, extra line
 */

/** Networks in the order people in this market use them. */
function sx_share_networks(): array
{
    $all = ['facebook', 'x', 'whatsapp', 'telegram'];
    $order = array_values(array_intersect(SX::$ctx['market']['share_networks'] ?? $all, $all)); // markets/<id>.json "share_networks"
    return $order ?: $all;
}

function sx_share_bar(?string $url, string $title, string $extra = ''): string
{
    if (!$url) {
        return ''; // no site.url: nothing absolute to share
    }
    SX::$used['_social'] = 1;
    $u = rawurlencode($url);
    $tx = rawurlencode($title);
    $href = [
        'x' => "https://twitter.com/intent/tweet?url=$u&text=$tx",
        'facebook' => "https://www.facebook.com/sharer/sharer.php?u=$u",
        'telegram' => "https://t.me/share/url?url=$u&text=$tx",
        'whatsapp' => 'https://wa.me/?text=' . rawurlencode("$title $url"),
    ];
    $names = ['x' => 'X', 'facebook' => 'Facebook', 'telegram' => 'Telegram', 'whatsapp' => 'WhatsApp'];
    $c = fn($el) => cls('share', $el);
    $out = '<div class="' . e(trim(cls('share') . ' ' . $extra)) . '"><span class="' . $c('label') . '">' . e(t('share.title')) . '</span><ul>';
    foreach (sx_share_networks() as $n) {
        $out .= '<li><a class="' . $c('link') . '" data-net="' . $n . '" href="' . e($href[$n]) . '" target="_blank" rel="noopener nofollow" aria-label="'
            . e(t('share.on', ['net' => $names[$n]])) . '">' . sx_share_icon($n) . '</a></li>';
    }
    $out .= '<li><button type="button" class="' . $c('link') . '" data-net="copy" data-copy="' . e($url) . '" aria-label="' . e(t('share.copy')) . '" data-done="'
        . e(t('share.copied')) . '">' . sx_share_icon('copy') . '<span class="' . $c('copied') . '" aria-live="polite"></span></button></li></ul></div>';
    if (empty(SX::$once['share_js'])) {
        SX::$once['share_js'] = true;
        $out .= '<script>document.addEventListener("click",function(e){var b=e.target.closest&&e.target.closest("[data-copy]");if(!b)return;'
            . 'var u=b.getAttribute("data-copy"),s=b.querySelector("span"),ok=function(){s.textContent=b.getAttribute("data-done");setTimeout(function(){s.textContent=""},1800)};'
            . 'if(navigator.clipboard){navigator.clipboard.writeText(u).then(ok,function(){prompt("",u)})}else{prompt("",u)}});</script>';
    }
    return $out;
}

/** Plain glyphs in the site's colours (not the networks' brand artwork). */
function sx_share_icon(string $n): string
{
    $p = [
        'x' => '<path d="M5 4h3.6l4 5.4L17.4 4H20l-6.1 7.1L20.6 20H17l-4.4-5.9L7.6 20H5l6.4-7.4z"/>',
        'facebook' => '<path d="M14 8.5V7c0-.8.4-1.2 1.3-1.2H17V3h-2.6C11.7 3 11 4.7 11 6.9v1.6H9V11h2v10h3V11h2.5l.5-2.5z"/>',
        'telegram' => '<path d="M21 4 3 11.2l5.4 2 2 6L13.6 15l4.6 3.4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="m8.4 13.2 8.2-5.6-6.2 6.9" fill="none" stroke="currentColor" stroke-width="1.6"/>',
        'whatsapp' => '<path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 8.2c.3-.4.7-.4 1-.1l.8 1.7c.1.3 0 .6-.2.8l-.5.5c.6 1.3 1.6 2.3 2.9 2.9l.5-.5c.2-.2.5-.3.8-.2l1.7.8c.3.3.3.7-.1 1-1 1-2.3 1-3.8.2a9.4 9.4 0 0 1-3.6-3.6C7.8 10.6 8 9.2 9 8.2z"/>',
        'copy' => '<rect x="8" y="8" width="12" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2" fill="none" stroke="currentColor" stroke-width="1.8"/>',
    ][$n];
    return '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">' . $p . '</svg>';
}

/**
 * Person card. $p: name, role?, bio?. $kicker: small label above ("About the author"). $lines: extra small lines
 * (already escaped HTML allowed), e.g. article count or a moderation-policy link.
 */
/** Round initials avatar with a stable colour per name (no photos of real people). */
function sx_avatar(string $name, string $extra = ''): string
{
    SX::$used['_social'] = 1;
    $initials = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)), array_slice(preg_split('/[\s.]+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY), 0, 2)));
    $hue = round(hexdec(substr(md5($name), 0, 2)) * 360 / 255);
    return '<span class="' . e(trim(cls('avatar') . ' ' . $extra)) . '" style="--h:' . $hue . '" aria-hidden="true">' . e($initials) . '</span>';
}

function sx_person_box(array $p, string $kicker, array $lines = [], string $extra = ''): string
{
    SX::$used['_social'] = 1;
    $c = fn($el) => cls('person', $el);
    $initials = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)), array_slice(preg_split('/\s+/u', trim($p['name'])), 0, 2)));
    $hue = hexdec(substr(md5($p['name']), 0, 2)) * 360 / 255;
    return '<aside class="' . e(trim(cls('person') . ' ' . $extra)) . '"><span class="' . $c('avatar') . '" style="--h:' . round($hue) . '" aria-hidden="true">' . e($initials) . '</span>'
        . '<div class="' . $c('body') . '"><p class="' . $c('kicker') . '">' . e($kicker) . '</p><p class="' . $c('name') . '">' . e($p['name']) . '</p>'
        . (!empty($p['role']) ? '<p class="' . $c('role') . '">' . e($p['role']) . '</p>' : '')
        . (!empty($p['bio']) ? '<p class="' . $c('bio') . '">' . e($p['bio']) . '</p>' : '')
        . implode('', array_map(fn($l) => '<p class="' . $c('meta') . '">' . $l . '</p>', $lines)) . '</div></aside>';
}
