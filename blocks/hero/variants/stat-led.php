<?php
/** Hero led by one big number (payout minutes, markets, years). Fintech-style; good for fast-payout angles. */
include __DIR__ . '/../_shared.php';
$s = $props['stat'] ?? null;
?>
<section class="<?= cx('hero', ['hero', null, 'stat']) ?>">
  <div class="<?= cx('container', ['hero', 'inner']) ?>">
    <?php if ($s): ?>
      <p class="<?= cls('hero', 'stat') ?>">
        <span class="<?= cls('hero', 'figure') ?>"><span class="<?= cls('hero', 'value') ?>"><?= e($s['value']) ?></span><span class="<?= cls('hero', 'unit') ?>"><?= e($s['unit']) ?></span></span>
        <span class="<?= cls('hero', 'caption') ?>"><?= e($s['caption']) ?></span>
      </p>
    <?php endif; ?>
    <div class="<?= cls('hero', 'copy') ?>">
      <?php if ($props['eyebrow']): ?><p class="<?= cls('hero', 'eyebrow') ?>"><?= e($props['eyebrow']) ?></p><?php endif; ?>
      <h1 class="<?= cls('hero', 'title') ?>"<?= sx_fit((string)$props['title']) ?>><?= e($props['title']) ?></h1>
      <?php if ($props['subtitle']): ?><p class="<?= cls('hero', 'subtitle') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
      <div class="<?= cls('hero', 'actions') ?>">
        <?= sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['hero', 'cta']), true) ?>
        <?php if ($secondary): ?><a class="<?= cls('hero', 'secondary') ?>" href="<?= sx_url($secondary['url']) ?>"><?= e($secondary['label']) ?></a><?php endif; ?>
      </div>
      <?= $trust ?>
    </div>
  </div>
</section>
