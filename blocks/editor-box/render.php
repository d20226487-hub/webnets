<?php
$ed = sx_ctx('editor');
if (!$ed || empty($ed['name'])) {
    SX::$warnings[] = 'editor-box: no "editor" {name, role, bio} in the site config; section not rendered.';
    return;
}
$c = fn($el) => cls('editor', $el);
$lines = [e(t('editor.updated', ['date' => sx_date(sx_ctx('today'))]))];
if (isset(sx_pages()['policy']) && (SX::$ctx['page_id'] ?? '') !== 'policy') {
    $lines[0] .= ' · <a href="' . sx_url(sx_page_url('policy')) . '">' . e(t('editor.policy')) . '</a>';
}
$pid = SX::$ctx['page_id'] ?? 'home';
?>
<section class="<?= cx('editor', ['editor', null, $variant]) ?>">
  <div class="<?= cx('container', ['editor', 'inner']) ?>">
    <?= sx_person_box($ed, t('editor.kicker'), $lines) ?>
    <?php if ($props['share'] ?? true): ?><?= sx_share_bar(sx_abs_url($pid), sx_ctx('site')['name'], $c('share')) ?><?php endif; ?>
  </div>
</section>
