<?php /** @var array $c @var array $stats @var array $rows @var array $pager @var string $status @var array $senders @var array $lists @var array $events */
$total = (int)($stats['total'] ?? 0); $sent = (int)($stats['sent'] ?? 0); $done = $total ? round(($sent + (int)$stats['failed'] + (int)$stats['skipped']) / $total * 100) : 0;
$score = is_array($c['score']) ? $c['score'] : null;
$cronUrl = base_url() . '/cron/mail/' . substr(hash('sha256', 'mail-cron|' . (string)env('APP_SECRET', '')), 0, 32);
$active = in_array($c['status'], ['sending', 'scheduled', 'paused'], true);
?>
<div class="page-head">
  <div class="row"><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/campaigns')) ?>"><?= icon('arrow-left', 'rtl-flip') ?><span><?= e(t('mail.campaigns')) ?></span></a><?= view('admin/mail/partials/status', ['status' => $c['status']]) ?><span class="muted small"><?= e($c['subject']) ?></span></div>
  <div class="row">
    <?php if ($c['status'] === 'draft'): ?><a class="btn btn--primary btn--sm" href="<?= e(admin_url('mail/campaigns/' . $c['id'] . '/edit')) ?>"><?= icon('pencil-simple') ?><span><?= e(t('common.edit')) ?></span></a><?php endif; ?>
    <?php if (in_array($c['status'], ['sending', 'scheduled', 'paused'], true)): ?>
      <form method="post" action="<?= e(admin_url('mail/campaigns/' . $c['id'] . '/pause')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit"><?= icon($c['status'] === 'paused' ? 'play' : 'pause') ?><span><?= e($c['status'] === 'paused' ? t('mail.resume') : t('mail.pause')) ?></span></button></form>
      <form method="post" action="<?= e(admin_url('mail/campaigns/' . $c['id'] . '/cancel')) ?>" class="inline" data-confirm="<?= e(t('mail.cancel')) ?>?"><?= csrf_field() ?><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= icon('prohibit') ?><span><?= e(t('mail.cancel')) ?></span></button></form>
    <?php endif; ?>
    <?php if ($c['status'] === 'sent'): ?><form method="post" action="<?= e(admin_url('mail/campaigns/' . $c['id'] . '/resend')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit"><?= icon('arrows-clockwise') ?><span><?= e(t('mail.resend_unopened')) ?></span></button></form><?php endif; ?>
    <form method="post" action="<?= e(admin_url('mail/campaigns/' . $c['id'] . '/duplicate')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit"><?= icon('copy') ?><span><?= e(t('mail.duplicate')) ?></span></button></form>
  </div>
</div>

<div class="kpis kpis--6" data-campaign-stats>
  <?php foreach (['total', 'sent', 'opened', 'clicked', 'failed', 'unsubscribed'] as $k): $v = (int)($stats[$k] ?? 0); ?>
    <div class="kpi"><span class="kpi__label"><?= e(t('mail.' . $k)) ?></span><strong class="kpi__val" data-stat="<?= $k ?>"><?= e(fmt_num($v)) ?></strong><?php if (in_array($k, ['opened', 'clicked'], true)): ?><span class="kpi__delta"><?= $sent ? round($v / $sent * 100) . '%' : '—' ?></span><?php endif; ?></div>
  <?php endforeach; ?>
</div>

<div class="grid-main" style="margin-top:1.2rem">
  <div class="stack">
    <div class="card"><div class="card__head"><h2><?= icon('lightning') ?><?= e(t('mail.progress')) ?></h2><span class="muted small" data-progress-label><?= $done ?>%</span></div><div class="card__body stack-sm">
      <div class="progress"><i style="width:<?= $done ?>%" data-progress-bar></i></div>
      <?php if ($c['status'] === 'scheduled'): ?><p class="hint"><?= e(t('mail.status_scheduled')) ?>: <?= e(fmt_date($c['scheduled_at'])) ?></p><?php endif; ?>
      <?php if ($active): ?>
        <div class="row">
          <form method="post" action="<?= e(admin_url('mail/campaigns/' . $c['id'] . '/process')) ?>" data-process-form><?= csrf_field() ?><button class="btn btn--primary btn--sm" type="submit" <?= $c['status'] === 'paused' ? 'disabled' : '' ?>><?= icon('paper-plane-tilt') ?><span><?= e(t('mail.process_batch')) ?></span></button></form>
          <label class="check"><input type="checkbox" data-auto-send <?= $c['status'] === 'sending' ? 'checked' : '' ?>><span><?= e(t('mail.auto_send')) ?></span></label>
          <span class="hint" data-process-msg></span>
        </div>
        <p class="hint"><?= e(t('mail.auto_send_hint')) ?></p>
      <?php endif; ?>
      <details class="details"><summary class="hint"><?= icon('clock') ?> <?= e(t('mail.cron_hint')) ?></summary>
        <pre class="code-block">* * * * * php <?= e(BASE_PATH) ?>/tools/mail-worker.php</pre>
        <p class="hint"><?= e(t('mail.cron_url')) ?>: <code dir="ltr" style="user-select:all"><?= e($cronUrl) ?></code></p>
      </details>
    </div></div>

    <div class="card">
      <div class="card__head"><h2><?= icon('users') ?><?= e(t('mail.queue')) ?></h2>
        <form method="get" class="seg seg--sm"><button type="submit" name="status" value="" class="<?= $status === '' ? 'is-active' : '' ?>"><?= e(t('common.all')) ?></button><?php foreach (['queued', 'sent', 'failed', 'skipped'] as $s): ?><button type="submit" name="status" value="<?= $s ?>" class="<?= $status === $s ? 'is-active' : '' ?>"><?= e(t('mail.' . $s)) ?></button><?php endforeach; ?></form>
      </div>
      <div class="table-wrap"><table class="table">
        <thead><tr><th><?= e(t('mail.recipient')) ?></th><th><?= e(t('common.status')) ?></th><th><?= e(t('mail.opened')) ?></th><th><?= e(t('mail.clicked')) ?></th><th><?= e(t('mail.sent')) ?></th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="5" class="empty"><?= e(t('common.empty')) ?></td></tr><?php endif; ?>
          <?php foreach ($rows as $q): ?>
            <tr><td dir="ltr"><?= e($q['email']) ?><?= $q['name'] ? ' <small class="muted">' . e($q['name']) . '</small>' : '' ?></td>
              <td><span class="pill pill--<?= ['queued' => 'muted', 'sent' => 'ok', 'failed' => 'high', 'skipped' => 'warn'][$q['status']] ?>"><?= e(t('mail.' . $q['status'])) ?></span><?= $q['error'] ? '<br><small class="muted" dir="ltr">' . e(str_limit($q['error'], 80)) . '</small>' : '' ?></td>
              <td><?= $q['opened_at'] ? icon('check-circle', 'text-ok') : '—' ?></td><td><?= $q['clicked_at'] ? icon('check-circle', 'text-ok') : '—' ?></td>
              <td><time><?= $q['sent_at'] ? e(fmt_date($q['sent_at'], 'd M H:i')) : '—' ?></time></td></tr>
          <?php endforeach; ?>
        </tbody></table></div>
      <?= view('admin/partials/pager', ['pager' => $pager]) ?>
    </div>
  </div>

  <div class="stack">
    <?php if ($score): ?>
    <div class="card"><div class="card__head"><h2><?= icon('shield-check') ?><?= e(t('mail.score')) ?></h2><span class="muted small"><?= e(t('mail.grade')) ?> <strong><?= e($score['grade']) ?></strong></span></div><div class="card__body">
      <div class="score"><div class="score__ring" style="--p:<?= (int)$score['score'] ?>"><span><?= (int)$score['score'] ?></span></div><div class="stack-sm">
        <?php $bad = array_filter($score['checks'], fn($x) => $x['state'] !== 'pass'); if (!$bad): ?><span class="pill pill--ok"><?= e(t('mail.all_good')) ?></span><?php else: ?><strong class="small"><?= e(t('mail.fixes')) ?></strong><?php foreach (array_slice($bad, 0, 5) as $b): ?><span class="pill pill--<?= $b['state'] === 'warn' ? 'warn' : 'high' ?>"><?= e(t('mail.score_checks.' . $b['key'])) ?></span><?php endforeach; endif; ?>
      </div></div>
    </div></div>
    <?php endif; ?>
    <div class="card"><div class="card__head"><h2><?= icon('identification-badge') ?><?= e(t('mail.senders_pick')) ?></h2></div><div class="card__body stack-sm">
      <?php foreach ($senders as $s): ?><div class="stat-row"><span><?= e($s['name']) ?> <small class="muted" dir="ltr"><?= e($s['email']) ?></small></span><span class="small muted"><?= e($s['hourly_limit']) ?><?= e(t('mail.per_hour')) ?></span></div><?php endforeach; ?>
      <?php foreach ($lists as $l): ?><div class="stat-row"><span><?= icon('address-book') ?> <?= e($l['name']) ?></span><strong><?= e(fmt_num((int)($l['summary']['total'] ?? 0))) ?></strong></div><?php endforeach; ?>
    </div></div>
    <div class="card"><div class="card__head"><h2><?= icon('clock-counter-clockwise') ?><?= e(t('mail.events')) ?></h2></div><div class="card__body list">
      <?php if (!$events): ?><p class="empty"><?= e(t('common.empty')) ?></p><?php endif; ?>
      <?php foreach ($events as $ev): ?><div class="list__row"><span class="pill pill--<?= ['open' => 'status-new', 'click' => 'ok', 'unsubscribe' => 'warn', 'bounce' => 'high', 'complaint' => 'high'][$ev['event']] ?? 'muted' ?>"><?= e(t('mail.event_' . $ev['event'])) ?></span><span class="list__main"><?= e($ev['url'] ? str_limit($ev['url'], 48) : ($ev['user_agent'] ? str_limit($ev['user_agent'], 48) : '')) ?></span><time><?= e(time_ago($ev['created_at'])) ?></time></div><?php endforeach; ?>
    </div></div>
  </div>
</div>
