<?php
/**
 * Self-test for lib/qr.php against published reference values from the QR specification.
 * Usage: php build/test-qr.php   (exit 1 on failure)
 */

require dirname(__DIR__) . '/lib/qr.php';

$fail = 0;
$check = function (string $name, $got, $want) use (&$fail) {
    $ok = $got === $want;
    $fail += $ok ? 0 : 1;
    printf("%s %s\n", $ok ? 'ok  ' : 'FAIL', $name . ($ok ? '' : ': got ' . json_encode($got) . ', want ' . json_encode($want)));
};

// Format strings for level M, masks 0-7 (ISO 18004 table C.1).
$formatM = ['101010000010010', '101000100100101', '101111001111100', '101101101001011',
            '100010111111001', '100000011001110', '100111110010111', '100101010100000'];
foreach ($formatM as $mask => $want) {
    $check("format M mask $mask", str_pad(decbin(sx_qr_format_bits($mask)), 15, '0', STR_PAD_LEFT), $want);
}

// Version information for versions 7 and 10 (ISO 18004 table D.1).
$check('version 7 bits', str_pad(decbin(sx_qr_version_bits(7)), 18, '0', STR_PAD_LEFT), '000111110010010100');
$check('version 10 bits', str_pad(decbin(sx_qr_version_bits(10)), 18, '0', STR_PAD_LEFT), '001010010011010011');

// Reed-Solomon: "HELLO WORLD" 1-M data codewords -> 10 EC codewords (well-known worked example).
$data = [32, 91, 11, 120, 209, 114, 220, 77, 67, 64, 236, 17, 236, 17, 236, 17];
$check('RS 1-M HELLO WORLD', sx_rs_remainder($data, 10), [196, 35, 39, 119, 235, 215, 231, 226, 93, 23]);

// Structural checks on a real payload.
foreach (['https://vantro.example/au/app?src=sat-a' => 3, str_repeat('x', 150) => 8] as $text => $wantVersion) {
    $m = sx_qr_matrix($text);
    $size = count($m);
    $check('version for ' . strlen($text) . ' bytes', ($size - 17) / 4, $wantVersion);
    $check('dark module', $m[$size - 8][8], true);
    $finder = implode('', array_map(fn($b) => $b ? 1 : 0, array_slice($m[0], 0, 7)));
    $check('finder top row', $finder, '1111111');
    $timing = implode('', array_map(fn($b) => $b ? 1 : 0, array_slice($m[6], 8, $size - 16)));
    $check('timing row', $timing, substr(str_repeat('10', $size), 0, $size - 16));
}

exit($fail ? 1 : 0);
