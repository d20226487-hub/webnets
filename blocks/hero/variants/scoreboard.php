<?php
/** Hero with a live match card beside the copy. Sports only. The card is illustrative, not live data. */
include __DIR__ . '/../_shared.php';
$m = $props['match'] ?? null;
?>
<section class="<?= cx('hero', ['hero', null, 'scoreboard']) ?>">
  <div class="<?= cx('container', ['hero', 'inner']) ?>">
    <div class="<?= cls('hero', 'copy') ?>">
      <?php if ($props['eyebrow']): ?><p class="<?= cls('hero', 'eyebrow') ?>"><span class="<?= cls('hero', 'pulse') ?>" aria-hidden="true"></span><?= e($props['eyebrow']) ?></p><?php endif; ?>
      <h1 class="<?= cls('hero', 'title') ?>"><?= e($props['title']) ?></h1>
      <?php if ($props['subtitle']): ?><p class="<?= cls('hero', 'subtitle') ?>"><?= e($props['subtitle']) ?></p><?php endif; ?>
      <div class="<?= cls('hero', 'actions') ?>">
        <?= sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['hero', 'cta']), true) ?>
        <?php if ($secondary): ?><a class="<?= cls('hero', 'secondary') ?>" href="<?= sx_url($secondary['url']) ?>"><?= e($secondary['label']) ?></a><?php endif; ?>
      </div>
      <?= $trust ?>
    </div>
    <?php if ($m): ?>
    <div class="<?= cls('hero', 'card') ?>">
      <article class="<?= cx('card', 'score') ?>" aria-label="<?= e($m['league']) ?>">
        <header class="<?= cls('score', 'head') ?>">
          <span class="<?= cls('score', 'live') ?>"><span aria-hidden="true"></span><?= e(t('live')) ?></span>
          <span class="<?= cls('score', 'league') ?>"><?= e($m['league']) ?></span>
          <span class="<?= cx('num', ['score', 'clock']) ?>"><?= e($m['clock']) ?></span>
        </header>
        <ul class="<?= cls('score', 'teams') ?>">
          <?php foreach (['home', 'away'] as $side): $tm = $m[$side]; ?>
            <li class="<?= cls('score', 'team') ?>">
              <span class="<?= cls('score', 'crest') ?>" style="--team: <?= e($tm['color']) ?>"><?= e($tm['short']) ?></span>
              <span class="<?= cls('score', 'name') ?>"><?= e($tm['name']) ?></span>
              <strong class="<?= cx('num', ['score', 'pts']) ?>"><?= e($tm['score']) ?></strong>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="<?= cls('score', 'markets') ?>">
          <?php foreach ($m['markets'] as $mk): ?>
            <div class="<?= cls('score', 'market') ?>">
              <p class="<?= cls('score', 'label') ?>"><?= e($mk['label']) ?></p>
              <div class="<?= cls('score', 'odds') ?>">
                <?php foreach ($mk['odds'] as $o): ?>
                  <span class="<?= cls('score', 'odd') ?>"><span><?= e($o['pick']) ?></span><strong class="<?= cls('num') ?>"><?= e($o['price']) ?></strong></span>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if (!empty($m['note'])): ?><p class="<?= cls('score', 'note') ?>"><?= e($m['note']) ?></p><?php endif; ?>
      </article>
    </div>
    <?php endif; ?>
  </div>
</section>
