<?php
/**
 * Minimal QR Code encoder (ISO/IEC 18004): byte mode, error correction level M, versions 1-10
 * (up to 213 bytes, plenty for a tracked URL). No dependencies. Output is an inline SVG.
 *
 *   echo sx_qr_svg('https://example.com/app?src=sat');
 *
 * Structure follows the reference algorithm: data bits -> Reed-Solomon blocks -> interleave ->
 * function patterns -> zigzag placement -> best of 8 masks -> format/version info.
 */

/** Per version, level M: [EC codewords per block, [[block count, data codewords per block], ...]] */
const SX_QR_M = [
    1 => [10, [[1, 16]]],
    2 => [16, [[1, 28]]],
    3 => [26, [[1, 44]]],
    4 => [18, [[2, 32]]],
    5 => [24, [[2, 43]]],
    6 => [16, [[4, 27]]],
    7 => [18, [[4, 31]]],
    8 => [22, [[2, 38], [2, 39]]],
    9 => [22, [[3, 36], [2, 37]]],
    10 => [26, [[4, 43], [1, 44]]],
];
const SX_QR_ALIGN = [1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30], 6 => [6, 34],
    7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50]];

function sx_gf_mul(int $x, int $y): int
{
    $z = 0;
    for ($i = 7; $i >= 0; $i--) {
        $z = ($z << 1) ^ (($z >> 7) * 0x11D);
        $z ^= (($y >> $i) & 1) * $x;
    }
    return $z & 0xFF;
}

function sx_rs_divisor(int $degree): array
{
    $r = array_fill(0, $degree, 0);
    $r[$degree - 1] = 1;
    $root = 1;
    for ($i = 0; $i < $degree; $i++) {
        for ($j = 0; $j < $degree; $j++) {
            $r[$j] = sx_gf_mul($r[$j], $root);
            if ($j + 1 < $degree) {
                $r[$j] ^= $r[$j + 1];
            }
        }
        $root = sx_gf_mul($root, 0x02);
    }
    return $r;
}

/** Reed-Solomon error correction codewords for one block. */
function sx_rs_remainder(array $data, int $degree): array
{
    $div = sx_rs_divisor($degree);
    $r = array_fill(0, $degree, 0);
    foreach ($data as $b) {
        $factor = $b ^ array_shift($r);
        $r[] = 0;
        for ($i = 0; $i < $degree; $i++) {
            $r[$i] ^= sx_gf_mul($div[$i], $factor);
        }
    }
    return $r;
}

/** 15-bit format string for level M (bits 00) and a mask. */
function sx_qr_format_bits(int $mask): int
{
    $data = (0 << 3) | $mask;
    $rem = $data;
    for ($i = 0; $i < 10; $i++) {
        $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
    }
    return (($data << 10) | ($rem & 0x3FF)) ^ 0x5412;
}

function sx_qr_version_bits(int $v): int
{
    $rem = $v;
    for ($i = 0; $i < 12; $i++) {
        $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
    }
    return ($v << 12) | ($rem & 0xFFF);
}

/** Returns a square bool matrix [row][col]. */
function sx_qr_matrix(string $text): array
{
    $bytes = array_values(unpack('C*', $text));
    $n = count($bytes);
    $version = null;
    foreach (SX_QR_M as $v => [$ec, $groups]) {
        $cap = array_sum(array_map(fn($g) => $g[0] * $g[1], $groups));
        $bits = 4 + ($v < 10 ? 8 : 16) + 8 * $n;
        if ($bits <= $cap * 8) {
            $version = $v;
            break;
        }
    }
    if ($version === null) {
        throw new RuntimeException('QR payload too long (max ~213 bytes)');
    }
    [$ecLen, $groups] = SX_QR_M[$version];
    $dataCap = array_sum(array_map(fn($g) => $g[0] * $g[1], $groups));

    // 1. Bit stream: mode 0100, length, bytes, terminator, pad.
    $bits = [];
    $push = function (int $val, int $len) use (&$bits) {
        for ($i = $len - 1; $i >= 0; $i--) {
            $bits[] = ($val >> $i) & 1;
        }
    };
    $push(0b0100, 4);
    $push($n, $version < 10 ? 8 : 16);
    foreach ($bytes as $b) {
        $push($b, 8);
    }
    $push(0, min(4, $dataCap * 8 - count($bits)));
    $push(0, (8 - count($bits) % 8) % 8);
    for ($pad = 0xEC; count($bits) < $dataCap * 8; $pad ^= 0xEC ^ 0x11) {
        $push($pad, 8);
    }
    $codewords = array_map(fn($c) => bindec(implode('', $c)), array_chunk($bits, 8));

    // 2. Split into blocks, add EC, interleave.
    $blocks = [];
    $off = 0;
    foreach ($groups as [$count, $len]) {
        for ($i = 0; $i < $count; $i++) {
            $d = array_slice($codewords, $off, $len);
            $off += $len;
            $blocks[] = ['d' => $d, 'e' => sx_rs_remainder($d, $ecLen)];
        }
    }
    $final = [];
    $maxD = max(array_map(fn($b) => count($b['d']), $blocks));
    for ($i = 0; $i < $maxD; $i++) {
        foreach ($blocks as $b) {
            if (isset($b['d'][$i])) {
                $final[] = $b['d'][$i];
            }
        }
    }
    for ($i = 0; $i < $ecLen; $i++) {
        foreach ($blocks as $b) {
            $final[] = $b['e'][$i];
        }
    }

    // 3. Function patterns.
    $size = 17 + 4 * $version;
    $m = array_fill(0, $size, array_fill(0, $size, false));
    $fn = array_fill(0, $size, array_fill(0, $size, false));
    $set = function (int $x, int $y, bool $dark) use (&$m, &$fn) {
        $m[$y][$x] = $dark;
        $fn[$y][$x] = true;
    };
    for ($i = 0; $i < $size; $i++) {
        $set(6, $i, $i % 2 === 0);
        $set($i, 6, $i % 2 === 0);
    }
    foreach ([[3, 3], [$size - 4, 3], [3, $size - 4]] as [$cx, $cy]) {
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $x = $cx + $dx;
                $y = $cy + $dy;
                if ($x >= 0 && $x < $size && $y >= 0 && $y < $size) {
                    $dist = max(abs($dx), abs($dy));
                    $set($x, $y, $dist !== 2 && $dist !== 4);
                }
            }
        }
    }
    $al = SX_QR_ALIGN[$version];
    $na = count($al);
    for ($i = 0; $i < $na; $i++) {
        for ($j = 0; $j < $na; $j++) {
            if (($i === 0 && $j === 0) || ($i === 0 && $j === $na - 1) || ($i === $na - 1 && $j === 0)) {
                continue;
            }
            for ($dy = -2; $dy <= 2; $dy++) {
                for ($dx = -2; $dx <= 2; $dx++) {
                    $set($al[$i] + $dx, $al[$j] + $dy, max(abs($dx), abs($dy)) !== 1);
                }
            }
        }
    }
    $drawFormat = function (int $mask) use ($set, $size) {
        $b = sx_qr_format_bits($mask);
        $bit = fn($i) => (($b >> $i) & 1) === 1;
        for ($i = 0; $i <= 5; $i++) {
            $set(8, $i, $bit($i));
        }
        $set(8, 7, $bit(6));
        $set(8, 8, $bit(7));
        $set(7, 8, $bit(8));
        for ($i = 9; $i < 15; $i++) {
            $set(14 - $i, 8, $bit($i));
        }
        for ($i = 0; $i < 8; $i++) {
            $set($size - 1 - $i, 8, $bit($i));
        }
        for ($i = 8; $i < 15; $i++) {
            $set(8, $size - 15 + $i, $bit($i));
        }
        $set(8, $size - 8, true);
    };
    $drawFormat(0); // reserve
    if ($version >= 7) {
        $vb = sx_qr_version_bits($version);
        for ($i = 0; $i < 18; $i++) {
            $dark = (($vb >> $i) & 1) === 1;
            $a = $size - 11 + $i % 3;
            $b = intdiv($i, 3);
            $set($a, $b, $dark);
            $set($b, $a, $dark);
        }
    }

    // 4. Zigzag data placement.
    $i = 0;
    $total = count($final) * 8;
    for ($right = $size - 1; $right >= 1; $right -= 2) {
        if ($right === 6) {
            $right = 5;
        }
        for ($vert = 0; $vert < $size; $vert++) {
            for ($j = 0; $j < 2; $j++) {
                $x = $right - $j;
                $upward = (($right + 1) & 2) === 0;
                $y = $upward ? $size - 1 - $vert : $vert;
                if (!$fn[$y][$x] && $i < $total) {
                    $m[$y][$x] = (($final[$i >> 3] >> (7 - ($i & 7))) & 1) === 1;
                    $i++;
                }
            }
        }
    }

    // 5. Pick the mask with the lowest penalty.
    $masks = [
        fn($x, $y) => ($x + $y) % 2 === 0,
        fn($x, $y) => $y % 2 === 0,
        fn($x, $y) => $x % 3 === 0,
        fn($x, $y) => ($x + $y) % 3 === 0,
        fn($x, $y) => (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0,
        fn($x, $y) => ($x * $y) % 2 + ($x * $y) % 3 === 0,
        fn($x, $y) => (($x * $y) % 2 + ($x * $y) % 3) % 2 === 0,
        fn($x, $y) => ((($x + $y) % 2) + ($x * $y) % 3) % 2 === 0,
    ];
    $best = null;
    $bestScore = PHP_INT_MAX;
    $base = $m;
    foreach ($masks as $k => $f) {
        $m = $base;
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (!$fn[$y][$x] && $f($x, $y)) {
                    $m[$y][$x] = !$m[$y][$x];
                }
            }
        }
        $drawFormat($k);
        $score = sx_qr_penalty($m);
        if ($score < $bestScore) {
            $bestScore = $score;
            $best = $m;
        }
    }
    return $best;
}

/** Penalty rules N1-N4 (adjacent runs, 2x2 blocks, finder-like patterns, dark balance). */
function sx_qr_penalty(array $m): int
{
    $size = count($m);
    $p = 0;
    $lines = [];
    for ($i = 0; $i < $size; $i++) {
        $lines[] = $m[$i];
        $lines[] = array_column($m, $i);
    }
    foreach ($lines as $line) {
        $run = 1;
        for ($i = 1; $i <= $size; $i++) {
            if ($i < $size && $line[$i] === $line[$i - 1]) {
                $run++;
            } else {
                if ($run >= 5) {
                    $p += 3 + ($run - 5);
                }
                $run = 1;
            }
        }
        $s = implode('', array_map('intval', $line));
        $p += 40 * (substr_count($s, '10111010000') + substr_count($s, '00001011101'));
    }
    $dark = 0;
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $dark += $m[$y][$x] ? 1 : 0;
            if ($x < $size - 1 && $y < $size - 1) {
                $c = $m[$y][$x];
                if ($c === $m[$y][$x + 1] && $c === $m[$y + 1][$x] && $c === $m[$y + 1][$x + 1]) {
                    $p += 3;
                }
            }
        }
    }
    $total = $size * $size;
    $k = (int)ceil(abs($dark * 20 - $total * 10) / $total) - 1;
    return $p + max(0, $k) * 10;
}

/** Inline SVG (currentColor modules on a transparent ground, 4-module quiet zone). */
function sx_qr_svg(string $text, string $label = ''): string
{
    $m = sx_qr_matrix($text);
    $size = count($m);
    $q = 4;
    // One rectangle per horizontal run of dark modules keeps the path about 3x smaller.
    $d = '';
    foreach ($m as $y => $row) {
        for ($x = 0; $x < $size; $x++) {
            if (!$row[$x]) {
                continue;
            }
            $start = $x;
            while ($x + 1 < $size && $row[$x + 1]) {
                $x++;
            }
            $w = $x - $start + 1;
            $d .= 'M' . ($start + $q) . ' ' . ($y + $q) . 'h' . $w . 'v1h-' . $w . 'z';
        }
    }
    $vb = $size + 2 * $q;
    $path = '<path fill="currentColor" d="' . $d . '"/>';
    $aria = $label !== '' ? ' role="img" aria-label="' . htmlspecialchars($label, ENT_QUOTES) . '"' : ' aria-hidden="true"';
    // Inside a page render, store the code once in the sprite so repeats (hero + download panel) are cheap.
    if (class_exists('SX') && !empty(SX::$ctx)) {
        $id = SX::$ctx['prefix'] . 'qr-' . substr(md5($text), 0, 8);
        SX::$symbols[$id] ??= ["0 0 $vb $vb", $path];
        return '<svg viewBox="0 0 ' . $vb . ' ' . $vb . '" shape-rendering="crispEdges"' . $aria . '><use href="#' . $id . '"/></svg>';
    }
    return '<svg viewBox="0 0 ' . $vb . ' ' . $vb . '" xmlns="http://www.w3.org/2000/svg" shape-rendering="crispEdges"' . $aria . '>' . $path . '</svg>';
}
