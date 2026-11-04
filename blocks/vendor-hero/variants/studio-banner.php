<?php
/** The studio as the headline: its name large over CSS art in its own colours, one facts line, lobby CTA. */
$v = sx_vendor();
$games = sx_vendor_games();
?>
<section class="<?= cx('vhero', ['vhero', null, 'studio-banner']) ?>" style="--p1: <?= e($v['colors'][0]) ?>; --p2: <?= e($v['colors'][1]) ?>">
  <div class="<?= cx('container', ['vhero', 'inner']) ?>">
    <p class="<?= cls('vhero', 'mark') ?>" aria-hidden="true"><?= e($v['name']) ?></p>
    <div class="<?= cls('vhero', 'copy') ?>">
      <h1 class="<?= cls('vhero', 'title') ?>"><?= e($props['title']) ?></h1>
      <?php if ($props['subtitle']): ?><p class="<?= cls('vhero', 'subtitle') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
      <ul class="<?= cls('vhero', 'line') ?>">
        <li><strong class="<?= cls('num') ?>"><?= e(t('vendor.games_count', ['n' => count($games)])) ?></strong></li>
        <li><?= e(t('vendor.since', ['year' => $v['founded']])) ?></li>
        <li><?= e(tm($v['signature'])) ?></li>
      </ul>
      <?= sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['vhero', 'cta']), true) ?>
    </div>
  </div>
</section>
