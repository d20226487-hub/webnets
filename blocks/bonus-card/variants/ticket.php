<?php include __DIR__ . '/../_shared.php'; ?>
<article class="<?= cx('card', 'bonus', ['bonus', null, 'ticket'], ['bonus', null, $mode]) ?>">
  <div class="<?= cls('bonus', 'top') ?>">
    <p class="<?= cls('eyebrow') ?>"><?= e($label) ?></p>
    <?php if ($mode === 'offer'): ?>
      <p class="<?= cls('bonus', 'amount') ?>"><?= e($props['amount']) ?></p>
      <?php if ($props['detail']): ?><p class="<?= cls('bonus', 'detail') ?>"><?= e($props['detail']) ?></p><?php endif; ?>
    <?php else: ?>
      <ul class="<?= cls('bonus', 'facts') ?>">
        <?php foreach ($items as $it): ?>
          <li><strong class="<?= cls('num') ?>"><?= e($it['value']) ?></strong><span><?= e($it['text']) ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <div class="<?= cls('bonus', 'stub') ?>">
    <?php if ($mode === 'offer' && $props['code']): ?>
      <p class="<?= cls('bonus', 'code') ?>"><span><?= e(t('bonus.code')) ?></span> <strong class="<?= cls('num') ?>"><?= e($props['code']) ?></strong></p>
    <?php endif; ?>
    <?= sx_cta('card', cx('btn', ['btn', null, 'primary'], ['btn', null, 'block']), true) ?>
    <?php if ($mode === 'offer'): ?>
      <p class="<?= cls('bonus', 'terms') ?>"><?= e($props['terms'] ?: t('terms_apply')) ?></p>
    <?php endif; ?>
  </div>
</article>
