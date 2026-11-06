<?php
/**
 * Image mode ("images": true in the site config) and generated cover art.
 *
 * A picture is the item's own "image" (URL) when it has one; otherwise a generated illustration: an inline SVG built
 * from the site's colour tokens (so it follows the preset), a seeded composition and a theme pictogram picked from the
 * title (football, hockey, payments, phone, licence, odds, limits, casino, star...). No external requests, no files.
 */

function sx_images_on(): bool
{
    return !empty(SX::$ctx['images']);
}

/** Theme for an item: explicit "image_theme", else keywords in the text (en / pt / ru). */
function sx_art_theme(string $text, ?string $explicit = null): string
{
    if ($explicit) {
        return $explicit;
    }
    $t = mb_strtolower($text);
    $map = [
        'hockey' => ['hockey', 'хокке', 'nhl', 'khl', 'кхл'],
        'football' => ['football', 'footy', 'futebol', 'brasileir', 'футбол', 'afl', 'nrl', 'soccer', 'série a', 'netball', 'cricket', 'críquete', 'спорт'],
        'payments' => ['pix', 'kaspi', 'payid', 'withdraw', 'saque', 'вывод', 'deposit', 'depósito', 'payout', 'выплат', 'card', 'cartão', 'карт', 'statement'],
        'phone' => ['app', 'face id', 'приложен', 'aplicativo', 'phone', 'iphone', 'android', 'bet slip', 'купон'],
        'shield' => ['licen', 'лиценз', 'regra', 'rules', 'law', 'ban ', 'proib', 'запрет', 'regul', 'bet.br', 'legal', 'верифик', 'verif', 'moderat', 'модерац'],
        'chart' => ['odds', 'коэфф', 'multi', 'múltipla', 'stats', 'estatíst', 'статист', 'ranking', 'compare', 'сравн'],
        'limits' => ['limit', 'лимит', 'betstop', 'self-ex', 'самоисключ', 'autoexclus', 'responsib', 'ответствен', 'responsável', 'break'],
        'casino' => ['casino', 'cassino', 'казино', 'live', 'mesa', 'стол', 'dealer', 'дилер', 'slot'],
        'chat' => ['support', 'suporte', 'atendimento', 'поддержк', 'chat', 'чат'],
        'star' => ['review', 'opini', 'avalia', 'отзыв', 'rating', 'оценк'],
        'pen' => ['write', 'escrev', 'напис', 'deixe'],
    ];
    foreach ($map as $theme => $words) {
        foreach ($words as $w) {
            if (str_contains($t, $w)) {
                return $theme;
            }
        }
    }
    return 'spark';
}

/** Pictogram paths on a 100x100 grid, drawn in white. */
function sx_art_glyph(string $theme): string
{
    return [
        'football' => '<circle cx="50" cy="50" r="34" fill="none" stroke="#fff" stroke-width="5"/><path d="m50 30 13 9-5 16H42l-5-16z" fill="#fff"/><path d="M50 16v14M63 39l17-6M58 55l10 15M42 55 32 70M37 39l-17-6" stroke="#fff" stroke-width="4"/>',
        'hockey' => '<path d="M28 18 54 70c3 6 9 8 15 6l12-4" fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round"/><ellipse cx="40" cy="78" rx="14" ry="6" fill="#fff"/>',
        'payments' => '<rect x="16" y="28" width="68" height="44" rx="7" fill="none" stroke="#fff" stroke-width="5"/><path d="M16 42h68" stroke="#fff" stroke-width="6"/><rect x="24" y="56" width="20" height="7" rx="2" fill="#fff"/><circle cx="74" cy="72" r="14" fill="#fff"/><path d="M74 64v16M69 68h7a3 3 0 0 1 0 6h-4a3 3 0 0 0 0 6h7" stroke="currentColor" stroke-width="2.4" fill="none" style="color:rgba(0,0,0,.45)"/>',
        'phone' => '<rect x="32" y="12" width="36" height="76" rx="8" fill="none" stroke="#fff" stroke-width="5"/><path d="M44 20h12" stroke="#fff" stroke-width="4" stroke-linecap="round"/><rect x="39" y="30" width="22" height="9" rx="2" fill="#fff"/><rect x="39" y="44" width="22" height="9" rx="2" fill="#fff" opacity=".7"/><rect x="39" y="58" width="22" height="9" rx="2" fill="#fff" opacity=".45"/>',
        'shield' => '<path d="M50 12 80 24v22c0 20-13 34-30 42C33 80 20 66 20 46V24z" fill="none" stroke="#fff" stroke-width="5" stroke-linejoin="round"/><path d="m37 50 9 9 18-20" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>',
        'chart' => '<path d="M16 84h70" stroke="#fff" stroke-width="5"/><rect x="22" y="56" width="12" height="24" rx="2" fill="#fff" opacity=".6"/><rect x="42" y="40" width="12" height="40" rx="2" fill="#fff" opacity=".8"/><rect x="62" y="24" width="12" height="56" rx="2" fill="#fff"/><path d="m20 44 22-14 16 8 24-20" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"/>',
        'limits' => '<circle cx="50" cy="52" r="32" fill="none" stroke="#fff" stroke-width="5"/><path d="M50 52V32M50 52l14 10" stroke="#fff" stroke-width="6" stroke-linecap="round"/><path d="M42 12h16" stroke="#fff" stroke-width="5" stroke-linecap="round"/>',
        'casino' => '<rect x="20" y="22" width="36" height="52" rx="6" fill="none" stroke="#fff" stroke-width="5" transform="rotate(-10 38 48)"/><rect x="44" y="26" width="36" height="52" rx="6" fill="#fff" transform="rotate(8 62 52)"/><path d="M62 42c6 6 10 9 10 14a6 6 0 0 1-10 3 6 6 0 0 1-10-3c0-5 4-8 10-14z" fill="rgba(0,0,0,.4)"/>',
        'chat' => '<path d="M16 22h56a8 8 0 0 1 8 8v28a8 8 0 0 1-8 8H42L26 80V66H16a8 8 0 0 1-8-8V30a8 8 0 0 1 8-8z" fill="none" stroke="#fff" stroke-width="5" stroke-linejoin="round"/><circle cx="32" cy="44" r="4.5" fill="#fff"/><circle cx="46" cy="44" r="4.5" fill="#fff"/><circle cx="60" cy="44" r="4.5" fill="#fff"/>',
        'star' => '<path d="m50 12 11 24 26 3-19 18 5 26-23-13-23 13 5-26-19-18 26-3z" fill="none" stroke="#fff" stroke-width="5" stroke-linejoin="round"/><path d="m50 34 5 11 12 1-9 8 2 12-10-6-10 6 2-12-9-8 12-1z" fill="#fff"/>',
        'pen' => '<path d="M22 78 28 58 64 22l14 14-36 36z" fill="none" stroke="#fff" stroke-width="5" stroke-linejoin="round"/><path d="M56 30l14 14" stroke="#fff" stroke-width="5"/><path d="M22 86h56" stroke="#fff" stroke-width="5" stroke-linecap="round"/>',
        'gift' => '<rect x="18" y="40" width="64" height="44" rx="5" fill="none" stroke="#fff" stroke-width="5"/><rect x="14" y="28" width="72" height="14" rx="4" fill="#fff"/><path d="M50 28v56" stroke="#fff" stroke-width="5"/><path d="M50 28c-6-12-22-14-22-4 0 6 12 6 22 4zM50 28c6-12 22-14 22-4 0 6-12 6-22 4z" fill="none" stroke="#fff" stroke-width="4"/>',
        'spark' => '<path d="M50 10 58 42 90 50 58 58 50 90 42 58 10 50 42 42z" fill="#fff"/><circle cx="78" cy="22" r="6" fill="#fff" opacity=".7"/>',
    ][$theme] ?? '';
}

/**
 * Cover art as inline SVG (640x360 design, fills its box). Colours come from the site's tokens, so the same article
 * looks native on every preset. $seed picks the composition.
 */
function sx_cover_art(string $seed, string $theme, string $label = '', bool $glyphRight = false): string
{
    SX::$used['_art'] = 1;
    $h = crc32($seed);
    $r = fn(int $i, int $min, int $max) => $min + ((($h >> ($i * 3)) & 0xFFFF) % ($max - $min + 1));
    $id = 'g' . dechex($h & 0xFFFFFF);
    $pattern = ['rings', 'stripes', 'dots', 'blocks'][$h % 4];
    $angle = $r(1, 110, 160);
    $deco = match ($pattern) {
        'rings' => '<circle cx="' . $r(2, 430, 600) . '" cy="' . $r(3, 40, 140) . '" r="170" fill="none" stroke="#fff" stroke-opacity=".14" stroke-width="40"/><circle cx="' . $r(4, 40, 200) . '" cy="' . $r(5, 260, 360) . '" r="90" fill="#fff" fill-opacity=".08"/>',
        'stripes' => '<g stroke="#fff" stroke-opacity=".1" stroke-width="26">' . implode('', array_map(fn($i) => '<path d="M' . ($i * 90 - 200) . ' 380 ' . ($i * 90 + 60) . ' -20"/>', range(0, 9))) . '</g>',
        'dots' => '<g fill="#fff" fill-opacity=".16">' . implode('', array_map(fn($i) => '<circle cx="' . (40 + ($i % 12) * 52) . '" cy="' . (30 + intdiv($i, 12) * 52) . '" r="5"/>', range(0, 83))) . '</g>',
        'blocks' => '<rect x="' . $r(2, 360, 470) . '" y="-40" width="260" height="260" rx="40" fill="#fff" fill-opacity=".1" transform="rotate(18 520 80)"/><rect x="' . $r(4, -60, 60) . '" y="230" width="220" height="220" rx="36" fill="#000" fill-opacity=".12" transform="rotate(-12 80 330)"/>',
    };
    $gx = $glyphRight ? 540 : $r(6, 200, 440); // right: keeps the pictogram clear of text laid over the picture
    $glyph = '<g transform="translate(' . ($gx - 70) . ' 110) scale(1.4)" opacity=".95">' . sx_art_glyph($theme) . '</g>';
    $tag = $label !== '' ? '<g transform="translate(28 300)"><rect width="' . (16 + mb_strlen($label) * 11) . '" height="34" rx="17" fill="#fff" fill-opacity=".92"/><text x="16" y="23" font-size="15" font-weight="700" font-family="system-ui,sans-serif" letter-spacing="1" style="fill:var(--c-accent)">' . e(mb_strtoupper($label)) . '</text></g>' : '';
    return '<svg class="' . cls('art') . '" data-cover viewBox="0 0 640 360" preserveAspectRatio="xMidYMid slice" role="presentation" aria-hidden="true">'
        . '<defs><linearGradient id="' . $id . '" gradientTransform="rotate(' . $angle . ' .5 .5)"><stop offset="0" style="stop-color:var(--c-accent)"/>'
        . '<stop offset="1" style="stop-color:var(--c-accent-2, var(--c-accent))"/></linearGradient></defs>'
        . '<rect width="640" height="360" fill="url(#' . $id . ')"/><rect width="640" height="360" fill="#000" fill-opacity=".12"/>' . $deco . $glyph . $tag . '</svg>';
}

/** A picture box: the item's own image, else generated art. $ratio like "16/9". */
function sx_picture(array $item, string $ratio = '16/9', string $extra = '', string $label = '', bool $glyphRight = false): string
{
    SX::$used['_art'] = 1;
    $inner = !empty($item['image'])
        ? '<img src="' . sx_url($item['image']) . '" alt="' . e($item['image_alt'] ?? '') . '" loading="lazy" decoding="async">'
        : sx_cover_art($item['slug'] ?? $item['title'] ?? 'x', sx_art_theme(($item['title'] ?? '') . ' ' . ($item['slug'] ?? ''), $item['image_theme'] ?? null), $label, $glyphRight);
    return '<span class="' . e(trim(cls('pic') . ' ' . $extra)) . '" style="aspect-ratio:' . e($ratio) . '">' . $inner . '</span>';
}
