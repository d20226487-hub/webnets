<?php $cols = array_column($props['columns'], 'label'); ?>
<section class="<?= cx('showcase', ['showcase', null, 'odds']) ?>" id="markets">
  <div class="<?= cx('container', ['showcase', 'inner']) ?>">
    <header class="<?= cls('showcase', 'head') ?>">
      <div>
        <?php if ($props['eyebrow']): ?><p class="<?= cls('eyebrow') ?>"><?= e($props['eyebrow']) ?></p><?php endif; ?>
        <h2 class="<?= cls('showcase', 'title') ?>"><?= e($props['title']) ?></h2>
      </div>
      <?php if ($props['tabs']): ?>
        <ul class="<?= cls('showcase', 'tabs') ?>" aria-hidden="true">
          <?php foreach ($props['tabs'] as $i => $tab): ?><li<?= $i === 0 ? ' data-on' : '' ?>><?= e($tab['label']) ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </header>
    <div class="<?= cx('card', ['showcase', 'board']) ?>">
      <div class="<?= cls('showcase', 'row') ?> <?= cls('showcase', 'colhead') ?>" aria-hidden="true">
        <span></span><span></span>
        <span class="<?= cls('showcase', 'prices') ?>"><?php foreach ($cols as $c): ?><span><?= e($c) ?></span><?php endforeach; ?></span>
      </div>
      <ul>
        <?php foreach ($props['rows'] as $r): ?>
          <li class="<?= cls('showcase', 'row') ?>">
            <span class="<?= cls('showcase', 'when') ?>">
              <?php if (!empty($r['live'])): ?><span class="<?= cls('showcase', 'live') ?>"><?= e(t('live')) ?></span><?php endif; ?>
              <span class="<?= cls('num') ?>"><?= e($r['when']) ?></span>
            </span>
            <span class="<?= cls('showcase', 'match') ?>">
              <span class="<?= cls('showcase', 'comp') ?>"><?= e($r['competition'] ?? '') ?></span>
              <span><?= e($r['home']) ?></span>
              <span><?= e($r['away']) ?></span>
            </span>
            <span class="<?= cls('showcase', 'prices') ?>">
              <?php foreach ($r['odds'] as $i => $o): ?>
                <span class="<?= cls('showcase', 'price') ?>"><span class="<?= cls('showcase', 'pick') ?>"><?= e($cols[$i] ?? '') ?></span><strong class="<?= cls('num') ?>"><?= e($o['price']) ?></strong></span>
              <?php endforeach; ?>
            </span>
            <?php if (!empty($r['more'])): ?><span class="<?= cls('showcase', 'more') ?>"><?= e(t('odds.more', ['n' => $r['more']])) ?></span><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="<?= cls('showcase', 'foot') ?>">
      <p class="<?= cls('showcase', 'note') ?>"><?= sx_icon('info') ?><?= e($props['note']) ?></p>
      <?= sx_cta('mid', cx('btn', ['btn', null, 'primary']), true) ?>
    </div>
  </div>
</section>
