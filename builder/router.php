<?php
/**
 * Site Builder (brief 10): local web app over the satellite engine.
 *
 * Run from the satellite-system folder:   php -S 127.0.0.1:8800 builder/router.php
 * then open http://127.0.0.1:8800
 *
 *   /api/*            JSON API (builder/api.php)
 *   /site/<id>/...    built sites from dist/sites (folder URLs resolve to index.html)
 *   /shots/<file>     gallery screenshots (dist/gallery/shots)
 *   everything else   the UI in builder/public
 */

$root = dirname(__DIR__);
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// Local tool only: refuse requests that do not come from this machine's own host name (DNS-rebinding guard).
$host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
if (!in_array($host, ['127.0.0.1', 'localhost', '[::1]'], true)) {
    http_response_code(403);
    exit('The builder only answers on 127.0.0.1 or localhost.');
}

if (str_starts_with($uri, '/api/')) {
    require __DIR__ . '/api.php';
    exit;
}

/** Send a file from inside $base, refusing anything that resolves outside it. */
function sx_serve(string $base, string $rel): void
{
    $base = realpath($base);
    $file = realpath($base . '/' . ltrim($rel, '/'));
    if ($file && is_dir($file)) {
        $file = realpath($file . '/index.html');
    }
    if (!$base || !$file || !str_starts_with($file, $base) || !is_file($file)) {
        http_response_code(404);
        exit('Not found');
    }
    $types = ['html' => 'text/html; charset=utf-8', 'css' => 'text/css', 'js' => 'text/javascript', 'json' => 'application/json',
        'png' => 'image/png', 'svg' => 'image/svg+xml', 'xml' => 'application/xml', 'txt' => 'text/plain; charset=utf-8', 'ico' => 'image/x-icon'];
    header('Content-Type: ' . ($types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? 'application/octet-stream'));
    header('Cache-Control: no-store');
    readfile($file);
    exit;
}

if (preg_match('#^/site/([a-z0-9-]+)(/.*)?$#', $uri, $m)) {
    // Remember the open built site: its pretty links are root-relative (/payments/), see the fallback below.
    setcookie('sx_site', $m[1], ['path' => '/', 'samesite' => 'Strict']);
    sx_serve("$root/dist/sites/{$m[1]}", $m[2] ?? '/');
}
if (str_starts_with($uri, '/shots/')) {
    sx_serve("$root/dist/gallery/shots", substr($uri, 7));
}
// A link followed inside a built site (e.g. /payments/, or the home link /) while browsing /site/<id>/:
// the referrer is a page path, not the builder's own root, and the URL is not a builder file.
$site = preg_replace('/[^a-z0-9-]/', '', $_COOKIE['sx_site'] ?? '');
$ref = $_SERVER['HTTP_REFERER'] ?? '';
if ($site && preg_match('#^https?://[^/]+/[^\#]#', $ref) && !preg_match('#^https?://[^/]+/(index\.html)?(\#|$)#', $ref) && !is_file(__DIR__ . '/public' . $uri)) {
    sx_serve("$root/dist/sites/$site", $uri);
}
sx_serve(__DIR__ . '/public', $uri === '/' ? 'index.html' : $uri);
