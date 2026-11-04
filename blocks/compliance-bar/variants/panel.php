<?php include __DIR__ . '/../_shared.php'; ?>
<section class="<?= cx('compliance', ['compliance', null, 'panel']) ?>" aria-label="<?= e(t('compliance.title')) ?>">
  <div class="<?= cls('compliance', 'head') ?>">
    <span class="<?= cls('age') ?>"><?= e($c['age']) ?></span>
    <div>
      <h2 class="<?= cls('compliance', 'title') ?>"><?= e(t('compliance.title')) ?></h2>
      <p><?= e($c['age_notice']) ?></p>
    </div>
  </div>
  <?php if ($c['tagline']): ?>
    <p class="<?= cls('compliance', 'tagline') ?>"><?= e($c['tagline']) ?></p>
  <?php endif; ?>
  <ul class="<?= cls('compliance', 'list') ?>">
    <li><?= sx_icon('shield') ?><span><?= e($c['license']) ?></span></li>
    <?php if ($c['self_exclusion']): ?><li><?= sx_icon('info') ?><span><?= e($c['self_exclusion']) ?></span></li><?php endif; ?>
    <?php if ($c['helpline']): ?><li><?= sx_icon('phone') ?><span><?= e($c['helpline']) ?></span></li><?php endif; ?>
  </ul>
  <?php if ($c['verify']): ?>
    <p class="<?= cls('compliance', 'verify') ?>"><?= e($c['verify']) ?></p>
  <?php endif; ?>
</section>
