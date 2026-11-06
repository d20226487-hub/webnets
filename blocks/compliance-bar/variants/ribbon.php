<?php include __DIR__ . '/../_shared.php'; ?>
<div class="<?= cx('compliance', ['compliance', null, 'ribbon']) ?>" role="note">
  <ul class="<?= cx('container', ['compliance', 'items']) ?>">
    <li><span class="<?= cls('age') ?>"><?= e($c['age']) ?></span></li>
    <?php if ($c['tagline']): ?><li class="<?= cls('compliance', 'tag') ?>"><?= e($c['tagline']) ?></li><?php endif; ?>
    <li class="<?= cls('compliance', 'help') ?>"><?= e($c['helpline'] ?? $c['age_notice']) ?></li>
  </ul>
</div>
