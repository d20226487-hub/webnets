<?php
/** Shared prep for app-hero variants. Defines $app, $facts, and registers MobileApplication JSON-LD. */

$app = sx_app();
$brand = sx_ctx('brand');
$market = sx_ctx('market');
$ps = sx_app_platforms();
$sizes = array_filter([in_array('ios', $ps, true) ? ($app['size_mb']['ios'] ?? null) : null, array_intersect(['android', 'apk'], $ps) ? ($app['size_mb']['android'] ?? null) : null]);
$sizeText = $sizes ? t('app.mb', ['n' => min($sizes) === max($sizes) ? min($sizes) : min($sizes) . '–' . max($sizes)]) : '';

$facts = array_values(array_filter([
    [$app['age_rating'] ?? ($market['min_age'] . '+'), t('app.age')],
    $sizeText ? [$sizeText, t('app.size')] : null,
    [$app['version'] ?? '', t('app.version')],
    [t('app.free'), t('app.price')],
], fn($f) => $f && $f[0] !== ''));

$os = [];
if (in_array('ios', $ps, true)) {
    $os[] = 'iOS';
}
if (array_intersect(['android', 'apk'], $ps)) {
    $os[] = 'Android';
}
sx_jsonld([
    '@type' => 'MobileApplication',
    'name' => $app['name'] ?? $brand['name'],
    'operatingSystem' => implode(', ', $os),
    'applicationCategory' => $brand['kind'] === 'casino' ? 'GameApplication' : 'SportsApplication',
    'softwareVersion' => $app['version'] ?? null,
    'fileSize' => $sizeText,
    'author' => ['@type' => 'Organization', 'name' => $app['developer'] ?? $brand['name']],
    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => $market['currency']['code']],
]);
