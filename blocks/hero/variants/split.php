<?php include __DIR__ . '/../_shared.php'; ?>
<section class="<?= cx('hero', ['hero', null, 'split']) ?><?= $card ? '' : ' ' . cls('hero', null, 'solo') ?>">
  <div class="<?= cx('container', ['hero', 'inner']) ?>">
    <div class="<?= cls('hero', 'copy') ?>">
      <?php if ($props['eyebrow']): ?><p class="<?= cls('hero', 'eyebrow') ?>"><?= e($props['eyebrow']) ?></p><?php endif; ?>
      <h1 class="<?= cls('hero', 'title') ?>"><?= e($props['title']) ?></h1>
      <?php if ($props['subtitle']): ?><p class="<?= cls('hero', 'subtitle') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
      <div class="<?= cls('hero', 'actions') ?>">
        <?= sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['hero', 'cta']), true) ?>
        <?php if ($secondary): ?><a class="<?= cls('hero', 'secondary') ?>" href="<?= sx_url($secondary['url']) ?>"><?= e($secondary['label']) ?></a><?php endif; ?>
      </div>
      <?= $trust ?>
    </div>
    <?php if ($card): ?><div class="<?= cls('hero', 'card') ?>"><?= $card ?></div><?php endif; ?>
  </div>
</section>
