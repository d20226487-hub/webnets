<?php
/** "grid": responsive card grid. "strip": horizontal scroll row (good for new releases). "table": facts side by side, one row per game. */
$isNew = $props['mode'] === 'new';
$games = $isNew ? sx_vendor_new_games() : sx_vendor_games();
if ($isNew && count($games) < 2) {
    return; // fewer than two new games: the section adds nothing
}
$games = sx_vendor_multi() && !$isNew ? $games : array_slice($games, 0, max(1, (int)$props['limit'])); // several studios: every game, grouped
$hasRanks = (bool)array_filter(sx_vendor_games(), fn($g) => $g['rank'] !== 999);
$title = $props['title'] ?: t($isNew ? 'vendor.new_on' : ($hasRanks ? 'vendor.popular' : 'vendor.games'));
?>
<section class="<?= cx('ggrid', ['ggrid', null, $variant]) ?>" id="<?= $isNew ? 'new' : 'games' ?>">
  <div class="<?= cx('container', ['ggrid', 'inner']) ?>">
    <header class="<?= cls('ggrid', 'head') ?>">
      <h2 class="<?= cls('ggrid', 'title') ?>"><?= e($title) ?></h2>
      <?php if ($props['intro']): ?><p class="<?= cls('ggrid', 'intro') ?>"><?= e($props['intro']) ?></p><?php endif; ?>
    </header>
    <?php if ($variant === 'table'): ?>
      <table class="<?= cls('ggrid', 'table') ?>">
        <thead><tr><th scope="col"><?= e(t('vendor.game')) ?></th><th scope="col"><?= e(t('vendor.rtp')) ?></th><th scope="col"><?= e(t('vendor.volatility')) ?></th><th scope="col"><?= e(t('vendor.max_win')) ?></th><td></td></tr></thead>
        <tbody>
          <?php foreach ($games as $g): ?>
            <tr>
              <th scope="row"><?= sx_game_cover($g, cls('ggrid', 'thumb')) ?><span><?= e($g['name']) ?><?php if (!empty($g['studio'])): ?><small class="<?= cls('ggrid', 'tstudio') ?>"><?= e($g['studio']) ?></small><?php endif; ?><?php if ($g['is_new']): ?> <em class="<?= cls('ggrid', 'new') ?>"><?= e(t('vendor.new')) ?></em><?php endif; ?></span></th>
              <td data-label="<?= e(t('vendor.rtp')) ?>"><?= e(sx_rtp_label($g)) ?></td>
              <td data-label="<?= e(t('vendor.volatility')) ?>"><?= e(sx_volatility_label($g['volatility'])) ?></td>
              <td data-label="<?= e(t('vendor.max_win')) ?>"><?= e(t('vendor.max_win_v', ['n' => sx_number($g['max_win'])])) ?></td>
              <td><?= sx_game_cta($g, $isNew ? 'game-new' : 'game', cx('btn', ['btn', null, 'primary'], ['btn', null, 'small'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php elseif (!$isNew && sx_vendor_multi()): ?>
      <?php foreach (sx_vendor_ids() as $sid): $sg = array_values(array_filter($games, fn($g) => ($g['studio_id'] ?? '') === $sid)); if (!$sg) { continue; } $sp = sx_ctx('providers')[$sid]; ?>
        <h3 class="<?= cls('ggrid', 'studio') ?>" style="--p1: <?= e($sp['colors'][0]) ?>"><span aria-hidden="true"></span><?= e($sp['name']) ?> <small><?= e(sx_n_games(count($sg))) ?></small></h3>
        <ul class="<?= cls('ggrid', 'list') ?>">
          <?php foreach ($sg as $g): ?><li><?= sx_game_card(array_diff_key($g, ['studio' => 1]), 'game') ?></li><?php endforeach; ?>
        </ul>
      <?php endforeach; ?>
    <?php else: ?>
      <ul class="<?= cls('ggrid', 'list') ?>">
        <?php foreach ($games as $g): ?><li><?= sx_game_card($g, $isNew ? 'game-new' : 'game') ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
