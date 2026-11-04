<?php
/** Sanitised text (sx_safe_html: basic tags, page:<id> links, H2 ids) with an optional table of contents. */
$toc = [];
$html = sx_safe_html((string)$props['html'], $toc);
$showToc = !empty($props['toc']) && count($toc) >= 3;
?>
<section class="<?= cx('prose', ['prose', null, $variant]) ?>">
  <div class="<?= cx('container', ['prose', 'inner']) ?>">
    <?php if ($showToc): ?>
      <nav class="<?= cls('prose', 'toc') ?>" aria-label="<?= e(t('toc')) ?>">
        <p class="<?= cls('eyebrow') ?>"><?= e(t('toc')) ?></p>
        <ol><?php foreach ($toc as $id => $text): ?><li><a href="#<?= e($id) ?>"><?= e($text) ?></a></li><?php endforeach; ?></ol>
      </nav>
    <?php endif; ?>
    <div class="<?= cls('prose', 'body') ?>"><?= $html ?></div>
  </div>
</section>
