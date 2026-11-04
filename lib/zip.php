<?php
/**
 * Minimal ZIP writer (deflate via zlib), for exports where PHP has no zip extension.
 * sx_zip(['index.html' => '...', 'payments/index.html' => '...']) returns the archive bytes.
 */

function sx_zip(array $files): string
{
    $data = '';
    $central = '';
    $time = getdate();
    $dosTime = ($time['hours'] << 11) | ($time['minutes'] << 5) | intdiv($time['seconds'], 2);
    $dosDate = (max(0, $time['year'] - 1980) << 9) | ($time['mon'] << 5) | $time['mday'];
    foreach ($files as $name => $content) {
        $name = str_replace('\\', '/', ltrim($name, '/'));
        $crc = crc32($content);
        $deflated = gzdeflate($content, 9);
        [$method, $body] = strlen($deflated) < strlen($content) ? [8, $deflated] : [0, $content];
        $offset = strlen($data);
        // Local file header; flag 0x0800 = file name is UTF-8.
        $data .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, $method, $dosTime, $dosDate, $crc, strlen($body), strlen($content), strlen($name), 0) . $name . $body;
        $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, $method, $dosTime, $dosDate, $crc, strlen($body), strlen($content), strlen($name), 0, 0, 0, 0, 0, $offset) . $name;
    }
    return $data . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), strlen($central), strlen($data), 0);
}
