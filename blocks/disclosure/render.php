<?php
$page = $props['link_page'] ?? 'policy';
$policy = isset(sx_pages()[$page]) ? sx_page_url($page) : null;
?>
<p class="<?= cx('disclose', ['disclose', null, $variant]) ?>"><span><?= e(t($props['text'] ?? 'reviews.disclosure')) ?></span><?php if ($policy): ?> <a href="<?= sx_url($policy) ?>"><?= e(t($props['link_text'] ?? 'reviews.policy_link')) ?></a><?php endif; ?></p>
