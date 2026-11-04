<section class="<?= cx('features', ['features', null, 'rows']) ?>">
  <div class="<?= cx('container', ['features', 'inner']) ?>">
    <header class="<?= cls('features', 'head') ?>">
      <?php if ($props['eyebrow']): ?><p class="<?= cls('eyebrow') ?>"><?= e($props['eyebrow']) ?></p><?php endif; ?>
      <h2 class="<?= cls('features', 'title') ?>"><?= e($props['title']) ?></h2>
    </header>
    <ul class="<?= cls('features', 'list') ?>">
      <?php foreach ($props['items'] as $it): ?>
        <li class="<?= cls('features', 'item') ?>">
          <span class="<?= cls('features', 'tile') ?>" aria-hidden="true"><?= sx_icon($it['icon'] ?? 'check') ?></span>
          <div class="<?= cls('features', 'copy') ?>">
            <h3><?= e($it['title']) ?></h3>
            <p><?= e($it['text']) ?></p>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
