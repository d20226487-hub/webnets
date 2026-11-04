<?php
/** Shared prep for hero variants. Defines $secondary, $trust (html), $card (html). */

$brand = sx_ctx('brand');
$f = $brand['facts'];

$secondary = $props['secondary'] ?: null;

$trust = '';
if (!empty($props['show_trust'])) {
    ob_start(); ?>
    <ul class="<?= cls('hero', 'trust') ?>">
      <?php foreach (array_slice(array_intersect_key(sx_proof_points(), array_flip(['rating', 'licence', 'payout'])), 0, 3) as $key => [$icon, $value, $label]): ?>
        <li><?= sx_icon($icon) ?><span><?php if ($key === 'licence'): ?><?= e($value) ?><?php else: ?><?= e($label) ?> <strong class="<?= cls('num') ?>"><?= e($value) ?></strong><?php endif; ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php $trust = ob_get_clean();
}

$card = $props['card'] ? sx_block('bonus-card', $props['card']) : '';
