<?php include __DIR__ . '/../_shared.php'; ?>
<article class="<?= cx('card', 'bonus', ['bonus', null, 'panel'], ['bonus', null, $mode]) ?>">
  <header class="<?= cls('bonus', 'head') ?>">
    <span class="<?= cls('bonus', 'mark') ?>"><?= sx_logo() ?></span>
    <p class="<?= cls('eyebrow') ?>"><?= e($label) ?></p>
  </header>
  <?php if ($mode === 'offer'): ?>
    <p class="<?= cls('bonus', 'amount') ?>"><?= e($props['amount']) ?></p>
    <?php if ($props['detail']): ?><p class="<?= cls('bonus', 'detail') ?>"><?= e($props['detail']) ?></p><?php endif; ?>
    <?php if ($props['code']): ?>
      <p class="<?= cls('bonus', 'code') ?>"><span><?= e(t('bonus.code')) ?></span> <strong class="<?= cls('num') ?>"><?= e($props['code']) ?></strong></p>
    <?php endif; ?>
  <?php else: ?>
    <dl class="<?= cls('bonus', 'rows') ?>">
      <?php foreach ($items as $it): ?>
        <div><dt><?= e($it['text']) ?></dt><dd class="<?= cls('num') ?>"><?= e($it['value']) ?></dd></div>
      <?php endforeach; ?>
    </dl>
  <?php endif; ?>
  <?= sx_cta('card', cx('btn', ['btn', null, 'primary'], ['btn', null, 'block'])) ?>
  <?php if ($mode === 'offer'): ?>
    <p class="<?= cls('bonus', 'terms') ?>"><?= e($props['terms'] ?: t('terms_apply')) ?></p>
  <?php endif; ?>
</article>
