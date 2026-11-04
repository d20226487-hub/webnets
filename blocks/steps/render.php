<?php /** "row": three columns with large numerals. "timeline": vertical list beside the heading. */ ?>
<section class="<?= cx('steps', ['steps', null, $variant]) ?>" id="how">
  <div class="<?= cx('container', ['steps', 'inner']) ?>">
    <header class="<?= cls('steps', 'head') ?>">
      <?php if ($props['eyebrow']): ?><p class="<?= cls('eyebrow') ?>"><?= e($props['eyebrow']) ?></p><?php endif; ?>
      <h2 class="<?= cls('steps', 'title') ?>"><?= e($props['title']) ?></h2>
      <?php if ($variant === 'timeline' && $props['show_cta']): ?><?= sx_cta('mid', cx('btn', ['btn', null, 'primary'], ['steps', 'cta']), true) ?><?php endif; ?>
    </header>
    <ol class="<?= cls('steps', 'list') ?>">
      <?php foreach ($props['items'] as $i => $it): ?>
        <li class="<?= cls('steps', 'item') ?>">
          <span class="<?= cx('num', ['steps', 'n']) ?>" aria-hidden="true"><?= $i + 1 ?></span>
          <div class="<?= cls('steps', 'copy') ?>">
            <h3><?= e($it['title']) ?></h3>
            <p><?= e($it['text']) ?></p>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
    <?php if ($variant === 'row' && $props['show_cta']): ?>
      <div class="<?= cls('steps', 'foot') ?>"><?= sx_cta('mid', cx('btn', ['btn', null, 'primary'], ['steps', 'cta']), true) ?></div>
    <?php endif; ?>
  </div>
</section>
