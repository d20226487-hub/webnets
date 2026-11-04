<?php
/** Both variants share markup; the variant only changes the modifier class (card vs full-bleed). */
$age = sx_ctx('market')['min_age'];
$preview = !empty($props['preview']);
$id = 'age-' . substr(md5(sx_ctx('seed')), 0, 6);
?>
<div class="<?= cx('agegate', ['agegate', null, $variant]) ?><?= $preview ? ' ' . cls('agegate', null, 'preview') : '' ?>"
     id="<?= e($id) ?>" role="dialog" aria-modal="<?= $preview ? 'false' : 'true' ?>" aria-labelledby="<?= e($id) ?>-t"<?= $preview ? '' : ' hidden' ?>>
  <div class="<?= cls('agegate', 'box') ?>">
    <?= sx_logo() ?>
    <span class="<?= cls('age') ?>"><?= e($age) ?>+</span>
    <h2 id="<?= e($id) ?>-t" class="<?= cls('agegate', 'title') ?>"><?= e(t('age_gate.title')) ?></h2>
    <p><?= e(t('age_gate.body')) ?></p>
    <div class="<?= cls('agegate', 'actions') ?>">
      <button type="button" class="<?= cx('btn', ['btn', null, 'primary'], ['btn', null, 'block']) ?>" data-age-yes><?= e(t('age_gate.yes')) ?></button>
      <a class="<?= cx('btn', ['btn', null, 'ghost'], ['btn', null, 'block']) ?>" href="<?= sx_url($props['leave_url']) ?>" rel="nofollow"><?= e(t('age_gate.no')) ?></a>
    </div>
  </div>
</div>
<?php if (!$preview): ?>
<script>
(function () {
  var el = document.getElementById(<?= json_encode($id) ?>), key = <?= json_encode(sx_ctx('prefix') . 'age') ?>, ok = false;
  try { ok = Number(localStorage.getItem(key)) > Date.now(); } catch (e) {}
  ok = ok || (' ' + window.name + ' ').indexOf(' ' + key + ' ') > -1; // storage blocked (sandboxed frames): remember for this tab
  if (ok || !el) return;
  el.hidden = false;
  document.documentElement.style.overflow = 'hidden';
  el.querySelector('[data-age-yes]').focus({ preventScroll: true });
  el.querySelector('[data-age-yes]').addEventListener('click', function () {
    try { localStorage.setItem(key, String(Date.now() + 30 * 864e5)); } catch (e) {}
    try { window.name = (window.name + ' ' + key).trim(); } catch (e) {}
    el.hidden = true;
    document.documentElement.style.overflow = '';
  });
})();
</script>
<?php endif; ?>
