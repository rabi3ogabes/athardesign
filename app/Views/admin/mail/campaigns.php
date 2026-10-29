<?php /** @var array $rows @var array $overview @var array $senders @var array $lists */ ?>
<div class="page-head">
  <p class="page-head__sub"><?= e(t('mail.score_hint')) ?></p>
  <a class="btn btn--primary" href="<?= e(admin_url('mail/campaigns/create')) ?>"><?= icon('plus') ?><span><?= e(t('mail.new_campaign')) ?></span></a>
</div>
<?php if (!$senders || !$lists): ?>
  <div class="alert alert--error" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap">
    <?php if (!$senders): ?><span><?= e(t('mail.no_senders')) ?></span><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/senders')) ?>"><?= e(t('mail.add_sender')) ?></a><?php endif; ?>
    <?php if (!$lists): ?><span><?= e(t('mail.no_contacts')) ?></span><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/lists')) ?>"><?= e(t('mail.new_list')) ?></a><?php endif; ?>
  </div>
<?php endif; ?>
<div class="kpis kpis--4">
  <div class="kpi"><span class="kpi__ico"><?= icon('paper-plane-tilt') ?></span><span class="kpi__label"><?= e(t('mail.overview_sent')) ?></span><strong class="kpi__val"><?= e(fmt_num($overview['sent'])) ?></strong></div>
  <div class="kpi"><span class="kpi__ico"><?= icon('eye') ?></span><span class="kpi__label"><?= e(t('mail.overview_opened')) ?></span><strong class="kpi__val"><?= e(fmt_num($overview['opened'])) ?></strong><span class="kpi__delta"><?= $overview['sent'] ? round($overview['opened'] / $overview['sent'] * 100) . '%' : '—' ?></span></div>
  <div class="kpi"><span class="kpi__ico"><?= icon('cursor-click') ?></span><span class="kpi__label"><?= e(t('mail.overview_clicked')) ?></span><strong class="kpi__val"><?= e(fmt_num($overview['clicked'])) ?></strong><span class="kpi__delta"><?= $overview['sent'] ? round($overview['clicked'] / $overview['sent'] * 100) . '%' : '—' ?></span></div>
  <div class="kpi kpi--accent"><span class="kpi__ico"><?= icon('lightning') ?></span><span class="kpi__label"><?= e(t('mail.overview_active')) ?></span><strong class="kpi__val"><?= e($overview['sending']) ?></strong></div>
</div>
<div class="card" style="margin-top:1.2rem">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th><?= e(t('mail.campaign_name')) ?></th><th><?= e(t('common.status')) ?></th><th><?= e(t('mail.score')) ?></th><th><?= e(t('mail.total')) ?></th><th><?= e(t('mail.sent')) ?></th><th><?= e(t('mail.open_rate')) ?></th><th><?= e(t('mail.click_rate')) ?></th><th><?= e(t('common.date')) ?></th><th></th></tr></thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="9" class="empty"><?= e(t('common.empty')) ?></td></tr><?php endif; ?>
        <?php foreach ($rows as $c): $st = is_array($c['stats']) ? $c['stats'] : []; $sc = is_array($c['score']) ? $c['score'] : null; $sent = (int)($st['sent'] ?? 0); $link = admin_url('mail/campaigns/' . $c['id'] . ($c['status'] === 'draft' ? '/edit' : '')); ?>
          <tr>
            <td><a class="link strong" href="<?= e($link) ?>"><?= e($c['name']) ?></a><br><small class="muted"><?= e(str_limit((string)$c['subject'], 60)) ?></small></td>
            <td><?= view('admin/mail/partials/status', ['status' => $c['status']]) ?></td>
            <td><?php if ($sc): ?><span class="score-pill score-pill--<?= e($sc['grade']) ?>"><?= (int)$sc['score'] ?></span><?php else: ?>—<?php endif; ?></td>
            <td><?= e(fmt_num((int)($st['total'] ?? 0))) ?></td>
            <td><?= e(fmt_num($sent)) ?></td>
            <td><?= $sent ? round((int)($st['opened'] ?? 0) / $sent * 100) . '%' : '—' ?></td>
            <td><?= $sent ? round((int)($st['clicked'] ?? 0) / $sent * 100) . '%' : '—' ?></td>
            <td><time><?= e(fmt_date($c['finished_at'] ?: ($c['started_at'] ?: ($c['scheduled_at'] ?: $c['created_at'])), 'd M Y H:i')) ?></time></td>
            <td class="td-actions">
              <a class="btn btn--ghost btn--sm" href="<?= e($link) ?>"><?= icon($c['status'] === 'draft' ? 'pencil-simple' : 'chart-bar') ?><span><?= e(t('common.view')) ?></span></a>
              <form method="post" action="<?= e(admin_url('mail/campaigns/' . $c['id'] . '/duplicate')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit"><?= icon('copy') ?><span><?= e(t('mail.duplicate')) ?></span></button></form>
              <?php if ($c['status'] !== 'sending'): ?><?= view('admin/partials/delete-button', ['action' => admin_url('mail/campaigns/' . $c['id'] . '/delete')]) ?><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
