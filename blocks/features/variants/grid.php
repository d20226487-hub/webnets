<section class="<?= cx('features', ['features', null, 'grid']) ?>">
  <div class="<?= cx('container', ['features', 'inner']) ?>">
    <header class="<?= cls('features', 'head') ?>">
      <?php if ($props['eyebrow']): ?><p class="<?= cls('eyebrow') ?>"><?= e($props['eyebrow']) ?></p><?php endif; ?>
      <h2 class="<?= cls('features', 'title') ?>"><?= e($props['title']) ?></h2>
    </header>
    <ul class="<?= cls('features', 'list') ?>">
      <?php foreach ($props['items'] as $it): ?>
        <li class="<?= cx('card', ['features', 'item']) ?>">
          <span class="<?= cls('features', 'icon') ?>"><?= sx_icon($it['icon'] ?? 'check') ?></span>
          <h3><?= e($it['title']) ?></h3>
          <p><?= e($it['text']) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
