<?php include __DIR__ . '/../_shared.php'; ?>
<div class="<?= cx('compliance', ['compliance', null, 'strip']) ?>" role="note">
  <div class="<?= cx('container', ['compliance', 'inner']) ?>">
    <span class="<?= cls('age') ?>"><?= e($c['age']) ?></span>
    <?php if ($c['tagline']): ?><strong><?= e($c['tagline']) ?></strong><?php endif; ?>
    <span class="<?= cls('compliance', 'help') ?>"><?= e($c['helpline'] ?? $c['age_notice']) ?></span>
  </div>
</div>
