<?php /** Asymmetric grid: the first feature is a large lead tile, the rest sit beside it. */ ?>
<section class="<?= cx('features', ['features', null, 'bento']) ?>">
  <div class="<?= cx('container', ['features', 'inner']) ?>">
    <header class="<?= cls('features', 'head') ?>">
      <?php if ($props['eyebrow']): ?><p class="<?= cls('eyebrow') ?>"><?= e($props['eyebrow']) ?></p><?php endif; ?>
      <h2 class="<?= cls('features', 'title') ?>"><?= e($props['title']) ?></h2>
    </header>
    <ul class="<?= cls('features', 'list') ?>">
      <?php foreach ($props['items'] as $i => $it): ?>
        <li class="<?= cx('card', ['features', 'item']) ?><?= $i === 0 ? ' ' . cls('features', 'item', 'lead') : '' ?>">
          <span class="<?= cls('features', 'icon') ?>"><?= sx_icon($it['icon'] ?? 'check') ?></span>
          <h3><?= e($it['title']) ?></h3>
          <p><?= e($it['text']) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
