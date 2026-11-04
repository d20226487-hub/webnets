<?php
/** "table": comparison table (a real table, scrolls on phones). "tiles": one card per method. */
$allowed = sx_ctx('market')['payments'];
$rows = [];
foreach ($props['rows'] as $r) {
    if (!in_array($r['method'], $allowed, true)) {
        SX::$warnings[] = "payments: '{$r['method']}' is not a payment method of market " . sx_ctx('market')['id'] . '; removed.';
        continue;
    }
    $r['name'] = ($r['label'] ?? '') ?: sx_payment_name($r['method']);
    $rows[] = $r;
}
?>
<section class="<?= cx('pay', ['pay', null, $variant]) ?>" id="payments">
  <div class="<?= cx('container', ['pay', 'inner']) ?>">
    <header class="<?= cls('pay', 'head') ?>">
      <?php if ($props['eyebrow']): ?><p class="<?= cls('eyebrow') ?>"><?= e($props['eyebrow']) ?></p><?php endif; ?>
      <h2 class="<?= cls('pay', 'title') ?>"><?= e($props['title']) ?></h2>
    </header>
    <?php if ($variant === 'table'): ?>
      <div class="<?= cx('card', ['pay', 'wrap']) ?>">
        <table class="<?= cls('pay', 'table') ?>">
          <thead><tr><th scope="col"><?= e(t('payments.method')) ?></th><th scope="col"><?= e(t('payments.deposit')) ?></th><th scope="col"><?= e(t('payments.withdraw')) ?></th><th scope="col"><?= e(t('payments.min')) ?></th></tr></thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <th scope="row"><span class="<?= cls('pay', 'mark') ?>"><?= e($r['name']) ?></span></th>
                <td><?= e($r['deposit']) ?></td>
                <td><strong class="<?= cls('num') ?>"><?= e($r['withdraw']) ?></strong></td>
                <td class="<?= cls('num') ?>"><?= e($r['min']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <ul class="<?= cls('pay', 'tiles') ?>">
        <?php foreach ($rows as $r): ?>
          <li class="<?= cx('card', ['pay', 'tile']) ?>">
            <span class="<?= cls('pay', 'mark') ?>"><?= e($r['name']) ?></span>
            <dl>
              <div><dt><?= e(t('payments.withdraw')) ?></dt><dd class="<?= cls('num') ?>"><?= e($r['withdraw']) ?></dd></div>
              <div><dt><?= e(t('payments.deposit')) ?></dt><dd><?= e($r['deposit']) ?></dd></div>
              <div><dt><?= e(t('payments.min')) ?></dt><dd class="<?= cls('num') ?>"><?= e($r['min']) ?></dd></div>
            </dl>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <?php if ($props['note']): ?><p class="<?= cls('pay', 'note') ?>"><?= sx_icon('info') ?><?= e($props['note']) ?></p><?php endif; ?>
  </div>
</section>
