<?php include __DIR__ . '/../_shared.php'; ?>
<section class="<?= cx('compliance', ['compliance', null, 'cards']) ?>" aria-label="<?= e(t('compliance.title')) ?>">
  <h2 class="<?= cls('compliance', 'title') ?>"><?= e(t('compliance.title')) ?></h2>
  <?php if ($c['tagline']): ?><p class="<?= cls('compliance', 'tagline') ?>"><?= e($c['tagline']) ?></p><?php endif; ?>
  <div class="<?= cls('compliance', 'grid') ?>">
    <article class="<?= cls('compliance', 'card') ?>"><span class="<?= cls('age') ?>"><?= e($c['age']) ?></span><p><?= e($c['age_notice']) ?></p></article>
    <article class="<?= cls('compliance', 'card') ?>"><?= sx_icon('shield') ?><p><?= e($c['license']) ?></p></article>
    <?php if ($c['self_exclusion']): ?><article class="<?= cls('compliance', 'card') ?>"><?= sx_icon('info') ?><p><?= e($c['self_exclusion']) ?></p></article><?php endif; ?>
    <?php if ($c['helpline']): ?><article class="<?= cls('compliance', 'card') ?>"><?= sx_icon('phone') ?><p><?= e($c['helpline']) ?></p></article><?php endif; ?>
  </div>
  <?php if ($c['verify']): ?><p class="<?= cls('compliance', 'verify') ?>"><?= e($c['verify']) ?></p><?php endif; ?>
</section>
