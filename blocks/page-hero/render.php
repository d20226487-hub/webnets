<?php
/** compact: breadcrumbs, H1, answer, CTA on the hero background. answer: the answer and CTA in a highlighted box. */
$crumbs = sx_breadcrumbs(cls('phero', 'crumbs'));
$showCta = $props['show_cta'] ?? true;
$cta = $showCta ? sx_cta('hero', cx('btn', ['btn', null, 'primary'], ['phero', 'cta']), true) : '';
?>
<?php $pic = sx_images_on() ? sx_picture(['slug' => sx_page_id(), 'title' => $props['title'] . ' ' . sx_page_id()], '4/3', cls('phero', 'pic')) : ''; ?>
<section class="<?= cx('phero', ['phero', null, $variant]) ?><?= $pic ? ' ' . cls('phero', null, 'img') : '' ?>">
  <div class="<?= cx('container', ['phero', 'inner']) ?>">
    <?= $pic ?>
    <?= $crumbs ?>
    <h1 class="<?= cls('phero', 'title') ?>"><?= e($props['title']) ?></h1>
    <?php if ($variant === 'answer'): ?>
      <div class="<?= cls('phero', 'box') ?>">
        <p class="<?= cls('phero', 'answer') ?>"><?= sx_icon('check') ?><span><?= e($props['answer']) ?></span></p>
        <?= $cta ?>
      </div>
    <?php else: ?>
      <p class="<?= cls('phero', 'answer') ?>"><?= e($props['answer']) ?></p>
      <?= $cta ?>
    <?php endif; ?>
  </div>
</section>
