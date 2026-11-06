<?php
/** Affiliate disclosure: line (one centred line), box (note with an icon), chip (label + text). Same wording in all. */
$page = $props['link_page'] ?? 'policy';
$policy = isset(sx_pages()[$page]) ? sx_page_url($page) : null;
$text = e(t($props['text'] ?? 'reviews.disclosure'));
$link = $policy ? '<a href="' . sx_url($policy) . '">' . e(t($props['link_text'] ?? 'reviews.policy_link')) . '</a>' : '';
?>
<?php if ($variant === 'box'): ?>
<aside class="<?= cx('disclose', ['disclose', null, 'box']) ?>">
  <div class="<?= cx('container', ['disclose', 'inner']) ?>"><?= sx_icon('info') ?><p><?= $text ?><?= $link ? ' ' . $link : '' ?></p></div>
</aside>
<?php elseif ($variant === 'chip'): ?>
<div class="<?= cx('disclose', ['disclose', null, 'chip']) ?>" role="note">
  <div class="<?= cx('container', ['disclose', 'inner']) ?>"><span class="<?= cls('disclose', 'tag') ?>"><?= e(t('disclosure.tag')) ?></span><span><?= $text ?></span><?= $link ?></div>
</div>
<?php else: ?>
<p class="<?= cx('disclose', ['disclose', null, $variant]) ?>"><span><?= $text ?></span><?php if ($link): ?> <?= $link ?><?php endif; ?></p>
<?php endif; ?>
