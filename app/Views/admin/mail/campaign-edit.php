<?php /** @var array $c @var array $senders @var array $lists @var array $templates @var array $starters @var DateTimeImmutable $suggested */
$isNew = $c['id'] === '';
$action = $isNew ? admin_url('mail/campaigns') : admin_url('mail/campaigns/' . $c['id']);
$selSenders = (array)($c['sender_ids'] ?? []); $selLists = (array)($c['list_ids'] ?? []);
$score = is_array($c['score'] ?? null) ? $c['score'] : null;
$summaries = [];
foreach ($lists as $l) $summaries[$l['id']] = App\Models\Mail::listSummary($l['id']);
$merge = ['{{first_name}}', '{{name}}', '{{company}}', '{{email}}', '{{site_name}}', '{{site_url}}', '{{address}}', '{{unsubscribe_url}}'];
?>
<form method="post" action="<?= e($action) ?>" data-dirty-guard data-campaign-form data-id="<?= e($c['id']) ?>" data-preview-url="<?= e(admin_url('mail/preview')) ?>" data-score-url="<?= $isNew ? '' : e(admin_url('mail/campaigns/' . $c['id'] . '/score')) ?>" data-test-url="<?= $isNew ? '' : e(admin_url('mail/campaigns/' . $c['id'] . '/test')) ?>">
  <?= csrf_field() ?>
  <div class="page-head page-head--sticky">
    <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/campaigns')) ?>"><?= icon('arrow-left', 'rtl-flip') ?><span><?= e(t('mail.campaigns')) ?></span></a>
    <div class="row">
      <?php if (!$isNew): ?><button type="button" class="btn btn--ghost btn--sm" data-score-run><?= icon('shield-check') ?><span><?= e(t('mail.check_score')) ?></span></button><?php endif; ?>
      <button class="btn btn--primary" type="submit"><?= icon('floppy-disk') ?><span><?= e(t('common.save')) ?></span></button>
    </div>
  </div>

  <div class="grid-editor">
    <div class="stack">
      <div class="card"><div class="card__head"><h2><?= icon('paper-plane-tilt') ?><?= e(t('mail.step_setup')) ?></h2></div><div class="card__body stack">
        <?= view('admin/partials/field', ['name' => 'name', 'label' => t('mail.campaign_name'), 'value' => $c['name'], 'required' => true]) ?>
        <?= view('admin/partials/field', ['name' => 'subject', 'label' => t('mail.subject'), 'value' => $c['subject'], 'hint' => t('mail.subject_hint'), 'required' => true, 'attrs' => 'data-count maxlength="200"']) ?>
        <?= view('admin/partials/field', ['name' => 'preheader', 'label' => t('mail.preheader'), 'value' => $c['preheader'], 'hint' => t('mail.preheader_hint'), 'attrs' => 'maxlength="200"']) ?>
      </div></div>

      <div class="card"><div class="card__head"><h2><?= icon('users') ?><?= e(t('mail.step_audience')) ?></h2></div><div class="card__body stack">
        <div class="field"><div class="field__label"><label><?= e(t('mail.senders_pick')) ?></label><span class="hint"><?= e(t('mail.senders_hint')) ?></span></div>
          <?php if (!$senders): ?><p class="empty"><?= e(t('mail.no_senders')) ?> <a class="link" href="<?= e(admin_url('mail/senders')) ?>"><?= e(t('mail.add_sender')) ?></a></p><?php endif; ?>
          <div class="pick-grid">
            <?php foreach ($senders as $s): $d = is_array($s['dns_check']) ? $s['dns_check'] : []; ?>
              <label class="pick"><input type="checkbox" name="sender_ids[]" value="<?= e($s['id']) ?>" <?= in_array($s['id'], $selSenders, true) ? 'checked' : '' ?>><span class="pick__box"><strong><?= e($s['name']) ?></strong><small dir="ltr"><?= e($s['email']) ?></small><span class="pick__tags"><span class="pill <?= $s['last_test_ok'] ? 'pill--ok' : 'pill--warn' ?>">SMTP</span><span class="pill <?= !empty($d['spf']) ? 'pill--ok' : 'pill--warn' ?>">SPF</span><span class="pill <?= (!empty($d['dkim']) || !empty($s['dkim_selector'])) ? 'pill--ok' : 'pill--warn' ?>">DKIM</span><span class="pill <?= !empty($d['dmarc']) ? 'pill--ok' : 'pill--warn' ?>">DMARC</span></span></span></label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="field"><div class="field__label"><label><?= e(t('mail.lists_pick')) ?></label><span class="hint"><?= e(t('mail.lists_hint')) ?></span></div>
          <?php if (!$lists): ?><p class="empty"><?= e(t('mail.no_contacts')) ?> <a class="link" href="<?= e(admin_url('mail/lists')) ?>"><?= e(t('mail.new_list')) ?></a></p><?php endif; ?>
          <div class="pick-grid">
            <?php foreach ($lists as $l): $sm = $summaries[$l['id']]; $tot = (int)($sm['total'] ?? 0); ?>
              <label class="pick"><input type="checkbox" name="list_ids[]" value="<?= e($l['id']) ?>" data-valid="<?= (int)($sm['valid'] ?? 0) ?>" data-risky="<?= (int)($sm['risky'] ?? 0) ?>" data-pending="<?= (int)($sm['pending'] ?? 0) ?>" <?= in_array($l['id'], $selLists, true) ? 'checked' : '' ?>><span class="pick__box"><strong><?= e($l['name']) ?></strong><small><?= e(fmt_num($tot)) ?> <?= e(t('mail.contacts')) ?> · <?= $tot ? round(((int)($sm['valid'] ?? 0)) / $tot * 100) : 0 ?>% <?= e(t('mail.valid_pct')) ?><?= (int)($sm['pending'] ?? 0) > 0 ? ' · ' . (int)$sm['pending'] . ' ' . e(t('mail.st_pending')) : '' ?></small></span></label>
            <?php endforeach; ?>
          </div>
          <p class="hint"><strong data-recipients-count>0</strong> <?= e(t('mail.recipients_preview')) ?></p>
        </div>
        <div class="grid-2">
          <?= view('admin/partials/field', ['name' => 'throttle_per_hour', 'label' => t('mail.throttle'), 'type' => 'number', 'value' => $c['throttle_per_hour'], 'hint' => t('mail.throttle_hint'), 'attrs' => 'min="10" max="2000"']) ?>
          <?= view('admin/partials/field', ['name' => 'skip_risky', 'label' => t('mail.skip_risky'), 'type' => 'toggle', 'value' => $c['skip_risky']]) ?>
        </div>
        <p class="hint"><?= e(t('mail.warmup_hint')) ?></p>
      </div></div>

      <div class="card"><div class="card__head"><h2><?= icon('envelope-open') ?><?= e(t('mail.step_content')) ?></h2>
        <div class="row">
          <select class="input input--sm" data-template-pick>
            <option value=""><?= e(t('mail.template')) ?></option>
            <?php foreach ($templates as $t): ?><option value="tpl:<?= e($t['id']) ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
            <?php foreach ($starters as $k => $st): ?><option value="starter:<?= e($k) ?>"><?= e(t('mail.starter')) ?>: <?= e($st['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="card__body stack">
        <input type="hidden" name="template_id" value="<?= e($c['template_id'] ?? '') ?>">
        <div class="merge-tags"><span class="hint"><?= e(t('mail.merge_tags')) ?>:</span><?php foreach ($merge as $m): ?><button type="button" class="pill pill--muted" data-merge="<?= e($m) ?>"><?= e($m) ?></button><?php endforeach; ?></div>
        <div class="editor-split">
          <div class="field"><div class="field__label"><label><?= e(t('mail.html')) ?></label></div><textarea class="input input--code" name="html" rows="24" data-html-editor dir="ltr" spellcheck="false"><?= e($c['html']) ?></textarea></div>
          <div class="field"><div class="field__label"><label><?= e(t('mail.preview')) ?></label><span class="hint"><?= e(t('mail.preview_hint')) ?></span></div><div class="preview-frame"><iframe title="preview" data-preview-frame sandbox="allow-same-origin"></iframe></div></div>
        </div>
        <template data-starters><?= e(json_encode(array_map(fn($s) => ['subject' => $s['subject'], 'html' => $s['html']], $starters), JSON_UNESCAPED_UNICODE)) ?></template>
        <template data-templates><?= e(json_encode(array_combine(array_column($templates, 'id'), array_map(fn($t) => ['subject' => $t['subject'], 'html' => $t['html']], $templates)) ?: [], JSON_UNESCAPED_UNICODE)) ?></template>
      </div></div>
    </div>

    <div class="stack">
      <div class="card card--score" data-score-panel>
        <div class="card__head"><h2><?= icon('shield-check') ?><?= e(t('mail.score')) ?></h2><span class="muted small"><?= e(t('mail.grade')) ?> <strong data-score-grade><?= e($score['grade'] ?? '—') ?></strong></span></div>
        <div class="card__body">
          <div class="score"><div class="score__ring" style="--p:<?= (int)($score['score'] ?? 0) ?>" data-score-ring><span data-score-value><?= $score ? (int)$score['score'] : '—' ?></span></div><div><p class="hint"><?= e(t('mail.score_hint')) ?></p><?php if ($isNew): ?><p class="hint"><?= e(t('common.save')) ?> →</p><?php else: ?><button type="button" class="btn btn--ghost btn--sm" data-score-run><?= icon('arrows-clockwise') ?><span><?= e(t('mail.check_score')) ?></span></button><?php endif; ?></div></div>
          <div class="check-list" data-score-list style="margin-top:1rem">
            <?php foreach (($score['checks'] ?? []) as $ck): ?>
              <div class="check-item check-item--<?= e($ck['state']) ?>"><?= icon($ck['state'] === 'pass' ? 'check-circle' : ($ck['state'] === 'warn' ? 'warning' : 'x-circle')) ?><div class="check-item__body"><strong><?= e(t('mail.score_checks.' . $ck['key'])) ?></strong><?php if ($ck['state'] !== 'pass' && $ck['tip']): ?><div class="hint"><?= e($ck['tip']) ?></div><?php endif; ?></div></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <?php if (!$isNew): ?>
      <div class="card"><div class="card__head"><h2><?= icon('test-tube') ?><?= e(t('mail.test_send')) ?></h2></div><div class="card__body stack-sm">
        <div class="row"><input class="input input--sm" type="email" value="<?= e($user['email']) ?>" data-test-to dir="ltr" style="flex:1"><button type="button" class="btn btn--ghost btn--sm" data-test-send><?= icon('paper-plane-tilt') ?><span><?= e(t('mail.test_send')) ?></span></button></div>
        <p class="hint" data-test-msg></p>
      </div></div>

      <div class="card"><div class="card__head"><h2><?= icon('lightning') ?><?= e(t('mail.step_send')) ?></h2></div><div class="card__body stack-sm">
        <div class="field"><div class="field__label"><label><?= e(t('mail.schedule')) ?></label><span class="hint"><?= e(t('mail.schedule_hint')) ?></span></div>
          <div class="row"><input class="input" type="datetime-local" name="scheduled_at" form="launch-form" dir="ltr" data-schedule-input><button type="button" class="btn btn--ghost btn--sm" data-use-suggested="<?= e($suggested->format('Y-m-d\TH:i')) ?>"><?= e(t('mail.use_suggested')) ?></button></div>
          <p class="hint"><?= e(t('mail.suggested_time')) ?>: <?= e($suggested->format('D d M, H:i')) ?> (Doha)</p>
        </div>
        <div class="row">
          <button type="submit" form="launch-form" class="btn btn--primary" data-launch><?= icon('play') ?><span><?= e(t('mail.launch')) ?></span></button>
          <label class="check"><input type="checkbox" name="force" value="1" form="launch-form"><span><?= e(t('mail.force_send')) ?></span></label>
        </div>
        <p class="hint"><?= e(t('mail.estimated_time')) ?>: <span data-eta>—</span></p>
      </div></div>
      <?php endif; ?>
    </div>
  </div>
</form>
<?php if (!$isNew): ?><form id="launch-form" method="post" action="<?= e(admin_url('mail/campaigns/' . $c['id'] . '/launch')) ?>" data-confirm="<?= e(t('mail.confirm_launch')) ?>"><?= csrf_field() ?></form><?php endif; ?>
