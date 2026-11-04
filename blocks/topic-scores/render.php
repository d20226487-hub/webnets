<?php $topics = sx_topic_stats(); if (!$topics) { return; } ?>
<section class="<?= cx('tscores', ['tscores', null, $variant]) ?>" id="topics">
  <div class="<?= cx('container', ['tscores', 'inner']) ?>">
    <h2 class="<?= cls('tscores', 'title') ?>"><?= e($props['title'] ?: t('reviews.topics_title')) ?></h2>
    <ul class="<?= cls('tscores', 'list') ?>">
      <?php foreach ($topics as $t => $s): ?>
        <li><a href="<?= sx_url(sx_topic_url($t)) ?>">
          <span class="<?= cls('tscores', 'name') ?>"><?= e(sx_topic_label($t)) ?></span>
          <span class="<?= cls('tscores', 'bar') ?>" aria-hidden="true"><span style="width: <?= round($s['avg'] / 5 * 100) ?>%"></span></span>
          <strong class="<?= cls('num') ?>"><?= e(sx_score($s['avg'])) ?></strong>
          <small><?= e(sx_plural('reviews.count', $s['count'])) ?></small>
        </a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
