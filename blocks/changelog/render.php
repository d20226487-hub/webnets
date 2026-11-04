<?php
$releases = [];
foreach (array_slice(sx_app()['changelog'] ?? [], 0, max(1, (int)$props['limit'])) as $r) {
    $items = $r['items'][sx_ctx('locale')] ?? null;
    if ($items) {
        $releases[] = $r + ['list' => $items];
    }
}
if (!$releases) {
    SX::$warnings[] = "changelog: brand has no release notes in locale '" . sx_ctx('locale') . "'; section not rendered.";
    return;
}
?>
<section class="<?= cx('changes', ['changes', null, $variant]) ?>" id="whats-new">
  <div class="<?= cx('container', ['changes', 'inner']) ?>">
    <h2 class="<?= cls('changes', 'title') ?>"><?= e($props['title'] ?: t('app.whats_new')) ?></h2>
    <ol class="<?= cls('changes', 'list') ?>">
      <?php foreach ($releases as $i => $r): ?>
        <li class="<?= cls('changes', 'release') ?><?= $i === 0 ? ' ' . cls('changes', 'release', 'latest') : '' ?>">
          <p class="<?= cls('changes', 'meta') ?>"><strong class="<?= cls('num') ?>"><?= e(t('app.version')) ?> <?= e($r['version']) ?></strong><span><?= e(sx_date($r['date'])) ?></span></p>
          <ul><?php foreach ($r['list'] as $item): ?><li><?= sx_icon('check') ?><span><?= e($item) ?></span></li><?php endforeach; ?></ul>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
