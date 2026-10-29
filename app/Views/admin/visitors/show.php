<?php /** @var array $v @var array $timeline */ ?>
<div class="page-head"><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('visitors')) ?>"><?= icon('arrow-left', 'rtl-flip') ?><span><?= e(t('common.back')) ?></span></a></div>
<div class="grid-side">
  <div class="card">
    <div class="card__head"><h2><?= e(t('visitors.details')) ?></h2></div>
    <div class="card__body">
      <dl class="dl">
        <div><dt><?= e(t('visitors.ip')) ?></dt><dd dir="ltr"><?= e($v['ip'] ?? '') ?></dd></div>
        <div><dt><?= e(t('visitors.location')) ?></dt><dd><?= e(trim(flag_emoji($v['country_code']) . ' ' . ($v['city'] ? $v['city'] . ', ' : '') . ($v['country'] ?? '—'))) ?></dd></div>
        <div><dt><?= e(t('visitors.device')) ?></dt><dd><?= e($v['device'] ?? '') ?></dd></div>
        <div><dt><?= e(t('visitors.browser')) ?></dt><dd><?= e($v['browser'] ?? '') ?></dd></div>
        <div><dt><?= e(t('visitors.os')) ?></dt><dd><?= e($v['os'] ?? '') ?></dd></div>
        <div><dt><?= e(t('visitors.language')) ?></dt><dd><?= e($v['lang'] ?? '') ?></dd></div>
        <div><dt><?= e(t('visitors.referrer')) ?></dt><dd dir="ltr"><?= e($v['referrer'] ?: t('common.direct')) ?></dd></div>
        <div><dt><?= e(t('visitors.landing')) ?></dt><dd dir="ltr"><?= e($v['landing_page'] ?? '') ?></dd></div>
        <div><dt><?= e(t('visitors.first_seen')) ?></dt><dd><?= e(fmt_date($v['first_seen'])) ?></dd></div>
        <div><dt><?= e(t('visitors.last_seen')) ?></dt><dd><?= e(fmt_date($v['last_seen'])) ?></dd></div>
        <div><dt><?= e(t('visitors.visits')) ?></dt><dd><?= e($v['visits_count']) ?></dd></div>
        <div><dt><?= e(t('visitors.bot')) ?></dt><dd><?= $v['is_bot'] ? e(t('common.yes')) : e(t('common.no')) ?></dd></div>
        <div><dt><?= e(t('visitors.user_agent')) ?></dt><dd class="small" dir="ltr"><?= e($v['user_agent'] ?? '') ?></dd></div>
      </dl>
    </div>
  </div>
  <div class="card">
    <div class="card__head"><h2><?= e(t('visitors.timeline')) ?></h2></div>
    <div class="card__body">
      <?php if (!$timeline): ?><p class="empty"><?= e(t('common.empty')) ?></p><?php endif; ?>
      <ol class="timeline">
        <?php $lastSession = null; foreach ($timeline as $t): ?>
          <?php if ($t['session_id'] !== $lastSession): $lastSession = $t['session_id']; ?><li class="timeline__session"><?= e(t('visitors.session')) ?> · <?= e(fmt_date($t['created_at'])) ?></li><?php endif; ?>
          <li class="timeline__item timeline__item--<?= e($t['kind']) ?>">
            <?php if ($t['kind'] === 'click'): ?>
              <span class="pill pill--<?= e($t['event']) ?>"><?= e($t['event']) ?></span>
              <span><?= e(t('visitors.clicked')) ?> <span dir="ltr"><?= e($t['label'] ?: $t['target']) ?></span> <small dir="ltr"><?= e($t['path']) ?></small></span>
            <?php else: ?>
              <span class="pill pill--muted"><?= icon('eye') ?></span>
              <span><?= e(t('visitors.visited')) ?> <span dir="ltr"><?= e($t['path']) ?></span> <small><?= e($t['label']) ?></small></span>
            <?php endif; ?>
            <time><?= e(fmt_date($t['created_at'], 'H:i:s')) ?></time>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>
</div>
