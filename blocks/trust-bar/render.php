<?php
/** Both variants share markup; "strip" is a full-width band, "badges" a centred row of pills. */
$pp = sx_proof_points();
$items = [];
foreach ((array)$props['items'] as $key) {
    if (isset($pp[$key])) {
        $items[$key] = $pp[$key];
    }
}
$items = array_slice($items, 0, 4, true);
?>
<section class="<?= cx('trust', ['trust', null, $variant]) ?>" aria-label="<?= e(t('trust.licensed')) ?>">
  <ul class="<?= cx('container', ['trust', 'list']) ?>">
    <?php foreach ($items as $key => [$icon, $value, $label]): ?>
      <li class="<?= cls('trust', 'item') ?>">
        <?= sx_icon($icon) ?>
        <span class="<?= cls('trust', 'text') ?>">
          <?php if ($key === 'since'): /* "Operating since 2019" reads label-first in every locale */ ?>
            <span class="<?= cls('trust', 'label') ?>"><?= e($label) ?></span>
            <strong class="<?= cls('trust', 'value') ?>"><?= e($value) ?></strong>
          <?php else: ?>
            <strong class="<?= cls('trust', 'value') ?>"><?= e($value) ?></strong>
            <?php if ($key !== 'licence'): ?><span class="<?= cls('trust', 'label') ?>"><?= e($label) ?></span><?php endif; ?>
          <?php endif; ?>
        </span>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
