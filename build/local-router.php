<?php
/**
 * Router for the local gallery server: `php -S localhost:8080 -t dist build/local-router.php`.
 * /settings/ is the AI settings editor (build/settings.php); /generator/ the website network generator; everything else is served as static files from dist/.
 * Local use only: the settings page writes files in ai/ and starts rebuilds.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/generator' || str_starts_with($path, '/generator/')) { // website network generator (generator/)
    require dirname(__DIR__) . '/generator/index.php';
    return true;
}
if ($path === '/settings' || str_starts_with($path, '/settings/')) {
    require __DIR__ . '/settings.php';
    return true;
}
return false;
