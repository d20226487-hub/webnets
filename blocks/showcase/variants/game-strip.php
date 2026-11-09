<?php /** Horizontal strip of game or live-table tiles with CSS art. Casino markets only. */ ?>
<section class="<?= cx('showcase', ['showcase', null, 'games']) ?>" id="games">
  <div class="<?= cx('container', ['showcase', 'inner']) ?>">
    <header class="<?= cls('showcase', 'head') ?>">
      <div>
        <?php if ($props['eyebrow']): ?><p class="<?= cls('eyebrow') ?>"><?= e($props['eyebrow']) ?></p><?php endif; ?>
        <h2 class="<?= cls('showcase', 'title') ?>"><?= e($props['title']) ?></h2>
      </div>
    </header>
    <ul class="<?= cls('showcase', 'strip') ?> <?= cls('swipe') ?>">
      <?php foreach ($props['tiles'] as $i => $g): $c = $g['colors'] ?? ['#444', '#111']; ?>
        <li class="<?= cls('showcase', 'game') ?>">
          <span class="<?= cls('showcase', 'cover') ?>" style="--g1: <?= e($c[0]) ?>; --g2: <?= e($c[1]) ?>" data-motif="<?= e($g['motif'] ?? 'rings') ?>" aria-hidden="true">
            <?php if (!empty($g['tag'])): ?><span class="<?= cls('showcase', 'tag') ?>"><?= e($g['tag']) ?></span><?php endif; ?>
          </span>
          <span class="<?= cls('showcase', 'gname') ?>"><?= e($g['name']) ?></span>
          <span class="<?= cls('showcase', 'gmeta') ?>"><?= e($g['studio'] ?? '') ?><?= !empty($g['meta']) ? ' · ' . e($g['meta']) : '' ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="<?= cls('showcase', 'foot') ?>">
      <p class="<?= cls('showcase', 'note') ?>"><?= sx_icon('info') ?><?= e($props['note']) ?></p>
      <?= sx_cta('mid', cx('btn', ['btn', null, 'primary']), true) ?>
    </div>
  </div>
</section>
