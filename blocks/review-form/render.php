<?php
$action = sx_ctx('links')['review_form'] ?? null;
$live = $action && empty(SX::$ctx['sample_reviews']);
$note = !$live && !empty(SX::$ctx['show_sample_notice']); // say it is switched off only when the site shows sample notices
$policy = isset(sx_pages()['policy']) ? sx_page_url('policy') : null;
$c = fn($el) => cls('rform', $el);
$age = sx_ctx('market')['min_age'] ?? 18;
?>
<section class="<?= cx('rform', ['rform', null, $variant]) ?>" id="write">
  <div class="<?= cx('container', ['rform', 'inner']) ?>">
    <form class="<?= cx('card', ['rform', 'form']) ?>" method="post" action="<?= $live ? sx_url($action) : '#write' ?>"<?= $note ? ' aria-describedby="rform-off"' : '' ?><?= $live ? '' : ' data-preview' ?>>
      <?php if ($props['title']): ?><h2 class="<?= $c('title') ?>"><?= e($props['title']) ?></h2><?php endif; ?>
      <fieldset class="<?= $c('rating') ?>"><legend><?= e(t('reviews.form.rating')) ?></legend><div class="<?= $c('stars') ?>">
        <?php for ($i = 5; $i >= 1; $i--): ?>
          <input type="radio" name="rating" id="rf-r<?= $i ?>" value="<?= $i ?>" required><label for="rf-r<?= $i ?>" title="<?= e(t('reviews.score_of', ['v' => $i])) ?>">★<span class="<?= cls('visually-hidden') ?>"><?= e(t('reviews.score_of', ['v' => $i])) ?></span></label>
        <?php endfor; ?>
      </div></fieldset>
      <fieldset class="<?= $c('topics') ?>"><legend><?= e(t('reviews.form.topics')) ?></legend>
        <?php foreach (SX_REVIEW_TOPICS as $t): ?>
          <label><input type="checkbox" name="topics[]" value="<?= e($t) ?>"><span><?= e(sx_topic_label($t)) ?></span></label>
        <?php endforeach; ?>
      </fieldset>
      <div class="<?= $c('row') ?>">
        <label class="<?= $c('field') ?>"><span><?= e(t('reviews.form.name')) ?></span><input name="name" required maxlength="40" autocomplete="given-name"><small><?= e(t('reviews.form.name_help')) ?></small></label>
        <label class="<?= $c('field') ?>"><span><?= e(t('reviews.form.city')) ?></span><input name="city" maxlength="40" autocomplete="address-level2"></label>
      </div>
      <label class="<?= $c('field') ?>"><span><?= e(t('reviews.form.title')) ?></span><input name="title" required maxlength="80"></label>
      <label class="<?= $c('field') ?>"><span><?= e(t('reviews.form.text')) ?></span><textarea name="text" required minlength="40" maxlength="2000" rows="6"></textarea><small><?= e(t('reviews.form.text_help')) ?></small></label>
      <label class="<?= $c('consent') ?>"><input type="checkbox" name="consent" required><span><?= e(t('reviews.form.consent', ['age' => $age])) ?><?php if ($policy): ?> (<a href="<?= sx_url($policy) ?>"><?= e(t('reviews.policy_link')) ?></a>)<?php endif; ?></span></label>
      <button class="<?= cx('btn', ['btn', null, 'primary'], ['rform', 'submit']) ?>" type="submit"<?= $note ? ' disabled' : '' ?>><?= e(t('reviews.form.submit')) ?></button>
      <?php if ($note): ?><p class="<?= $c('off') ?>" id="rform-off"><?= sx_icon('info') ?><?= e(t('reviews.form.off')) ?></p><?php endif; ?>
      <p class="<?= $c('thanks') ?>" role="status" hidden><?= sx_icon('check') ?><?= e(t('reviews.form.thanks')) ?></p>
    </form>
    <?php if (!$live && !$note): ?><script>(function(){var f=document.querySelector('form[data-preview]');if(!f)return;f.addEventListener('submit',function(e){e.preventDefault();var t=f.querySelector('[role=status]');t.hidden=false;f.reset();t.scrollIntoView({block:'nearest'});});})();</script><?php endif; ?>
  </div>
</section>
