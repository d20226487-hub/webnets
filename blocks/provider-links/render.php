<?php
/** Links use links.provider with {provider} (e.g. the brand's provider lobby or another satellite page); else links.go. */
$others = sx_other_providers();
if (!$others) {
    return;
}
$pattern = sx_ctx('links')['provider'] ?? null;
$providers = sx_ctx('providers');
?>
<section class="<?= cx('plinks', ['plinks', null, $variant]) ?>" id="providers">
  <div class="<?= cx('container', ['plinks', 'inner']) ?>">
    <h2 class="<?= cls('plinks', 'title') ?>"><?= e($props['title'] ?: t('vendor.more')) ?></h2>
    <ul class="<?= cls('plinks', 'list') ?>">
      <?php foreach ($others as $pid => $n): $p = $providers[$pid]; $href = $pattern ? str_replace('{provider}', rawurlencode($pid), $pattern) : sx_ctx('links')['go']; ?>
        <li><a href="<?= sx_url($href) ?>" data-cta="provider" data-provider="<?= e($pid) ?>" rel="nofollow sponsored noopener" target="_blank" style="--p1: <?= e($p['colors'][0]) ?>; --p2: <?= e($p['colors'][1]) ?>">
          <span class="<?= cls('plinks', 'dot') ?>" aria-hidden="true"></span>
          <strong><?= e($p['name']) ?></strong><small><?= e(sx_n_games($n)) ?></small><?= sx_icon('arrow') ?>
        </a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
