<?php /** @var array $list @var array $summary @var array $rows @var array $pager @var string $status @var string $q */
$total = (int)($summary['total'] ?? 0); $valid = (int)($summary['valid'] ?? 0); $pending = (int)($summary['pending'] ?? 0);
$probeAllowed = (bool)setting('mail_smtp_probe', true);
?>
<div class="page-head">
  <div class="row"><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/lists')) ?>"><?= icon('arrow-left', 'rtl-flip') ?><span><?= e(t('mail.lists')) ?></span></a><span class="muted small"><?= e($list['description'] ?? '') ?></span></div>
  <div class="row">
    <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/lists/' . $list['id'] . '/export')) ?>"><?= icon('export') ?><span><?= e(t('mail.export')) ?></span></a>
    <button type="button" class="btn btn--ghost btn--sm" data-modal-open="#list-modal"><?= icon('pencil-simple') ?><span><?= e(t('common.edit')) ?></span></button>
  </div>
</div>

<div class="kpis kpis--6">
  <div class="kpi"><span class="kpi__label"><?= e(t('mail.contacts')) ?></span><strong class="kpi__val" data-sum="total"><?= e(fmt_num($total)) ?></strong></div>
  <div class="kpi"><span class="kpi__label"><?= e(t('mail.st_valid')) ?></span><strong class="kpi__val text-ok" data-sum="valid"><?= e(fmt_num($valid)) ?></strong></div>
  <div class="kpi"><span class="kpi__label"><?= e(t('mail.st_risky')) ?></span><strong class="kpi__val" data-sum="risky"><?= (int)($summary['risky'] ?? 0) ?></strong></div>
  <div class="kpi"><span class="kpi__label"><?= e(t('mail.st_invalid')) ?></span><strong class="kpi__val text-danger" data-sum="invalid"><?= (int)($summary['invalid'] ?? 0) ?></strong></div>
  <div class="kpi"><span class="kpi__label"><?= e(t('mail.st_pending')) ?></span><strong class="kpi__val" data-sum="pending"><?= $pending ?></strong></div>
  <div class="kpi kpi--accent"><span class="kpi__label"><?= e(t('mail.avg_score')) ?></span><strong class="kpi__val" data-sum="avg_score"><?= (int)($summary['avg_score'] ?? 0) ?></strong></div>
</div>

<div class="grid-main" style="margin-top:1.2rem">
  <div class="stack">
    <div class="card" data-verify-card data-url="<?= e(admin_url('mail/lists/' . $list['id'] . '/verify')) ?>">
      <div class="card__head"><h2><?= icon('shield-check') ?><?= e(t('mail.verify')) ?></h2><span class="muted small" data-verify-label><?= $pending > 0 ? $pending . ' ' . e(t('mail.st_pending')) : e(t('mail.verified_all')) ?></span></div>
      <div class="card__body stack-sm">
        <div class="progress"><i style="width:<?= $total ? round(($total - $pending) / $total * 100) : 0 ?>%" data-verify-bar></i></div>
        <p class="hint"><?= e(t('mail.verify_hint')) ?></p>
        <div class="row">
          <button type="button" class="btn btn--primary btn--sm" data-verify-start <?= $pending === 0 ? 'disabled' : '' ?>><?= icon('play') ?><span><?= e(t('mail.verify')) ?></span></button>
          <button type="button" class="btn btn--ghost btn--sm" data-verify-start data-reset="1"><?= icon('arrows-clockwise') ?><span><?= e(t('mail.reset_verification')) ?></span></button>
          <?php if ($probeAllowed): ?><label class="check"><input type="checkbox" data-verify-smtp><span><?= e(t('mail.verify_smtp')) ?></span></label><?php endif; ?>
        </div>
        <?php if ($probeAllowed): ?><p class="hint"><?= e(t('mail.verify_smtp_hint')) ?></p><?php endif; ?>
        <div class="row">
          <form method="post" action="<?= e(admin_url('mail/lists/' . $list['id'] . '/clean')) ?>" class="inline" data-confirm="<?= e(t('mail.clean_invalid')) ?>?"><?= csrf_field() ?><input type="hidden" name="status" value="invalid"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= icon('trash') ?><span><?= e(t('mail.clean_invalid')) ?></span></button></form>
          <form method="post" action="<?= e(admin_url('mail/lists/' . $list['id'] . '/clean')) ?>" class="inline" data-confirm="<?= e(t('mail.clean_risky')) ?>?"><?= csrf_field() ?><input type="hidden" name="status" value="risky"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= icon('trash') ?><span><?= e(t('mail.clean_risky')) ?></span></button></form>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card__head"><h2><?= icon('users') ?><?= e(t('mail.contacts')) ?></h2>
        <form method="get" class="row"><div class="seg seg--sm"><button type="submit" name="status" value="" class="<?= $status === '' ? 'is-active' : '' ?>"><?= e(t('common.all')) ?></button><?php foreach (['valid', 'risky', 'invalid', 'pending', 'unsubscribed', 'bounced'] as $s): ?><button type="submit" name="status" value="<?= $s ?>" class="<?= $status === $s ? 'is-active' : '' ?>"><?= e(t('mail.st_' . $s)) ?></button><?php endforeach; ?></div><input class="input input--sm" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('common.search')) ?>"></form>
      </div>
      <div class="table-wrap"><table class="table">
        <thead><tr><th><?= e(t('mail.email')) ?></th><th><?= e(t('fields.name')) ?></th><th><?= e(t('common.status')) ?></th><th><?= e(t('mail.score_col')) ?></th><th><?= e(t('mail.checks')) ?></th><th></th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="6" class="empty"><?= e(t('mail.no_contacts')) ?></td></tr><?php endif; ?>
          <?php foreach ($rows as $c): $ck = is_array($c['checks']) ? $c['checks'] : []; ?>
            <tr>
              <td dir="ltr"><?= e($c['email']) ?><?php if (!empty($ck['typo'])): ?><br><small class="text-danger"><?= e(t('mail.typo_suggest')) ?> <?= e($ck['typo']) ?>?</small><?php endif; ?></td>
              <td><?= e($c['name'] ?? '') ?><?= $c['company'] ? ' <small class="muted">' . e($c['company']) . '</small>' : '' ?></td>
              <td><span class="pill pill--<?= ['valid' => 'ok', 'risky' => 'warn', 'invalid' => 'high', 'pending' => 'muted', 'unsubscribed' => 'muted', 'bounced' => 'high'][$c['status']] ?>"><?= e(t('mail.st_' . $c['status'])) ?></span></td>
              <td><?= $c['score'] !== null ? '<span class="score-pill score-pill--' . ((int)$c['score'] >= 80 ? 'A' : ((int)$c['score'] >= 60 ? 'B' : 'D')) . '">' . (int)$c['score'] . '</span>' : '—' ?></td>
              <td class="small muted"><?php if ($ck): ?><?= $ck['mx'] ? 'MX ✓' : 'MX ✗' ?><?= $ck['disposable'] ? ' · disposable' : '' ?><?= $ck['role'] ? ' · role' : '' ?><?= $ck['free'] ? ' · free' : '' ?><?= isset($ck['smtp']) && $ck['smtp'] !== 'skipped' ? ' · smtp:' . e($ck['smtp']) : '' ?><?php endif; ?></td>
              <td class="td-actions">
                <?php if (in_array($c['status'], ['risky', 'invalid'], true)): ?><form method="post" action="<?= e(admin_url('mail/lists/' . $list['id'] . '/contacts/' . $c['id'])) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="valid"><button class="btn btn--ghost btn--sm" type="submit" title="<?= e(t('mail.mark_valid')) ?>"><?= icon('check-fat') ?><span><?= e(t('mail.mark_valid')) ?></span></button></form><?php endif; ?>
                <form method="post" action="<?= e(admin_url('mail/lists/' . $list['id'] . '/contacts/' . $c['id'])) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit" title="<?= e(t('common.delete')) ?>"><?= icon('trash') ?><span><?= e(t('common.delete')) ?></span></button></form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody></table></div>
      <?= view('admin/partials/pager', ['pager' => $pager]) ?>
    </div>
  </div>

  <div class="card"><div class="card__head"><h2><?= icon('upload-simple') ?><?= e(t('mail.import')) ?></h2></div><div class="card__body">
    <form method="post" action="<?= e(admin_url('mail/lists/' . $list['id'] . '/import')) ?>" enctype="multipart/form-data" class="stack"><?= csrf_field() ?>
      <p class="hint"><?= e(t('mail.import_hint')) ?></p>
      <textarea class="input input--code" name="contacts" rows="10" placeholder="<?= e(t('mail.paste_here')) ?>" dir="ltr"></textarea>
      <label class="dropzone" style="padding:1.2rem"><?= icon('upload-simple') ?><span><?= e(t('mail.upload_file')) ?></span><input type="file" name="file" accept=".csv,.txt,text/csv,text/plain" hidden data-file-name></label>
      <button class="btn btn--primary" type="submit"><?= icon('plus') ?><span><?= e(t('mail.import')) ?></span></button>
    </form>
  </div></div>
</div>

<div class="modal" id="list-modal" aria-hidden="true"><div class="modal__backdrop" data-modal-close></div><div class="modal__panel" role="dialog" aria-modal="true">
  <form method="post" action="<?= e(admin_url('mail/lists/' . $list['id'])) ?>"><?= csrf_field() ?>
    <div class="modal__head"><h3><?= e(t('common.edit')) ?></h3><button type="button" class="modal__close" data-modal-close><?= icon('x') ?></button></div>
    <div class="modal__body stack"><?= view('admin/partials/field', ['name' => 'name', 'label' => t('mail.list_name'), 'value' => $list['name'], 'required' => true]) ?><?= view('admin/partials/field', ['name' => 'description', 'label' => t('mail.list_description'), 'type' => 'textarea', 'rows' => 2, 'value' => $list['description']]) ?></div>
    <div class="modal__foot"><button type="button" class="btn btn--ghost" data-modal-close><?= e(t('common.cancel')) ?></button><button type="submit" class="btn btn--primary"><?= e(t('common.save')) ?></button></div>
  </form>
</div></div>
