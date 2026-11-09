<?php
/**
 * Full-width hero with CSS-drawn art (no photos): "roulette" wheel, "cards" fan or "rings".
 * A photo can replace the art later via props.image without changing the layout.
 */
include __DIR__ . '/../_shared.php';
$art = $props['art'] ?? 'rings';
$chips = $props['chips'] ?? [];
?>
<section class="<?= cx('hero', ['hero', null, 'bleed']) ?>">
  <div class="<?= cls('hero', 'art') ?>" data-art="<?= e($art) ?>" aria-hidden="true">
    <?php if ($art === 'cards'): ?><span></span><span></span><span></span><?php endif; ?>
  </div>
  <div class="<?= cx('container', ['hero', 'inner']) ?>">
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
    <?php if ($chips): ?>
      <ul class="<?= cls('hero', 'chips') ?>">
        <?php foreach ($chips as $c): ?><li><?= e(is_array($c) ? $c['label'] : $c) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
