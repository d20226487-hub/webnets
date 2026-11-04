<?php
$id = sx_page_item() ?? sx_casino_ids()[0];
$b = sx_casino($id);
$f = $b['f'];
// Studios the brand carries in this market (Phase 4 catalog), if any.
$catFile = SX::$ctx['root'] . "/brands/catalog/$id.json";
$studios = [];
if (is_file($catFile)) {
    foreach (array_keys(sx_json($catFile)['markets'][SX::$ctx['market']['id']]['providers'] ?? []) as $pid) {
        $studios[] = SX::$ctx['providers'][$pid]['name'] ?? $pid;
    }
}
$wd = implode(' · ', array_map(fn($m, $min) => sx_payment_name($m) . ' ' . t('casinos.minutes', ['m' => $min >= 1440 ? round($min / 1440) . 'd' : $min]), array_keys($f['withdrawal_minutes']), $f['withdrawal_minutes']));
$support = trim(t('casinos.chat', ['h' => $f['support']['chat']]) . ($f['support']['email'] ? ' · e-mail' : '') . ($f['support']['phone'] ? ' · ' . t('casinos.phone') : ''));
$rows = [
    ['casinos.f.licence', t('casinos.kf.licence', ['no' => $b['license_no']])],
    ['casinos.f.domain', $f['domain']],
    ['casinos.f.min_deposit', money($f['min_deposit'])],
    ['casinos.f.withdrawal', $wd],
    ['casinos.f.payments', implode(' · ', array_map('sx_payment_name', $f['payments']))],
    ['casinos.f.support', $support],
    ['casinos.f.games', sx_number($f['games'])],
    ['casinos.f.live', sx_number($f['live_tables'])],
    ['casinos.f.app', $f['app'] ? t('casinos.app_yes') : t('casinos.app_no')],
];
if ($studios) {
    $rows[] = ['casinos.f.studios', implode(' · ', $studios)];
}
$rows[] = ['casinos.f.checked', sx_date($f['checked_at'])];
?>
<section class="<?= cx('ftable', ['ftable', null, $variant]) ?>" id="facts">
  <div class="<?= cx('container', ['ftable', 'inner']) ?>">
    <h2 class="<?= cls('ftable', 'title') ?>"><?= e($props['title'] ?: t('casinos.facts')) ?></h2>
    <table class="<?= cls('ftable', 'table') ?>"><tbody>
      <?php foreach ($rows as [$k, $v]): ?><tr><th scope="row"><?= e(t($k)) ?></th><td><?= e($v) ?></td></tr><?php endforeach; ?>
    </tbody></table>
  </div>
</section>
