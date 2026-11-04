<?php
/** "accordion": native <details>, first one open. "columns": all answers visible in two columns. */
$title = $props['title'] ?: t('faq.title');
sx_jsonld([
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn($it) => [
        '@type' => 'Question',
        'name' => $it['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $it['a']],
    ], $props['items']),
]);
?>
<section class="<?= cx('faq', ['faq', null, $variant]) ?>" id="faq">
  <div class="<?= cx('container', ['faq', 'inner']) ?>">
    <h2 class="<?= cls('faq', 'title') ?>"><?= e($title) ?></h2>
    <?php if ($variant === 'accordion'): ?>
      <div class="<?= cls('faq', 'list') ?>">
        <?php foreach ($props['items'] as $i => $it): ?>
          <details class="<?= cls('faq', 'item') ?>"<?= $i === 0 ? ' open' : '' ?>>
            <summary><span><?= e($it['q']) ?></span><?= sx_icon('chevron') ?></summary>
            <p><?= e($it['a']) ?></p>
          </details>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <dl class="<?= cls('faq', 'list') ?>">
        <?php foreach ($props['items'] as $it): ?>
          <div class="<?= cls('faq', 'item') ?>"><dt><?= e($it['q']) ?></dt><dd><?= e($it['a']) ?></dd></div>
        <?php endforeach; ?>
      </dl>
    <?php endif; ?>
  </div>
</section>
