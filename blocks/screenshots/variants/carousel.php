<section class="<?= cx('shots', ['shots', null, 'carousel']) ?>" id="screens">
  <div class="<?= cx('container', ['shots', 'inner']) ?>">
    <header class="<?= cls('shots', 'head') ?>">
      <?php if ($props['eyebrow']): ?><p class="<?= cls('eyebrow') ?>"><?= e($props['eyebrow']) ?></p><?php endif; ?>
      <h2 class="<?= cls('shots', 'title') ?>"><?= e($props['title']) ?></h2>
    </header>
    <ul class="<?= cls('shots', 'strip') ?>">
      <?php foreach ($props['screens'] as $s): ?>
        <li class="<?= cls('shots', 'item') ?>">
          <?= sx_phone(['kind' => $s['kind'] ?? 'home', 'image' => $s['image'] ?? null]) ?>
          <p class="<?= cls('shots', 'caption') ?>"><?= e($s['caption']) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
