<?php
$pv = sx_app()['privacy'] ?? null;
if (!$pv) {
    SX::$warnings[] = 'app-privacy: the brand has no app.privacy data; section not rendered.';
    return;
}
$c = fn($el) => cls('privacy', $el);
$name = fn($k) => t("store.data.$k");
$dev = sx_app()['developer'] ?? '';
?>
<section class="<?= cx('privacy', ['privacy', null, $variant]) ?>" id="privacy">
  <div class="<?= cx('container', ['privacy', 'inner']) ?>">
    <h2 class="<?= $c('title') ?>"><?= e($props['title'] ?: t($variant === 'play' ? 'store.safety' : 'store.privacy')) ?></h2>
    <?php if ($variant === 'play'): ?>
      <p class="<?= $c('intro') ?>"><?= e(t('store.safety_intro')) ?></p>
      <ul class="<?= $c('list') ?>">
        <li><?= sx_icon('shield') ?><div><strong><?= e(t(empty($pv['shared']) ? 'store.no_share' : 'store.shares')) ?></strong><?php if (!empty($pv['shared'])): ?><span><?= e(implode(', ', array_map($name, $pv['shared']))) ?></span><?php endif; ?></div></li>
        <li><?= sx_icon('info') ?><div><strong><?= e(t('store.collects')) ?></strong><span><?= e(implode(', ', array_map($name, array_merge($pv['linked'] ?? [], $pv['not_linked'] ?? [])))) ?></span></div></li>
        <?php if (!empty($pv['encrypted'])): ?><li><?= sx_icon('check') ?><div><strong><?= e(t('store.encrypted')) ?></strong></div></li><?php endif; ?>
        <?php if (!empty($pv['deletion'])): ?><li><?= sx_icon('check') ?><div><strong><?= e(t('store.deletion')) ?></strong></div></li><?php endif; ?>
      </ul>
    <?php else: ?>
      <p class="<?= $c('intro') ?>"><?= e(t('store.privacy_intro', ['dev' => $dev])) ?></p>
      <div class="<?= $c('cards') ?>">
        <?php foreach (['linked' => 'store.linked', 'not_linked' => 'store.not_linked'] as $k => $label): if (empty($pv[$k])) { continue; } ?>
          <div class="<?= $c('card') ?>"><?= sx_icon($k === 'linked' ? 'user' : 'shield') ?><h3><?= e(t($label)) ?></h3><p><?= e(t("{$label}_note")) ?></p>
            <ul><?php foreach ($pv[$k] as $d): ?><li><?= e($name($d)) ?></li><?php endforeach; ?></ul></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
