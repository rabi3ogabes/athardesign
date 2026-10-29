<?php /** @var array $rows @var array|null $editing */
$s = $editing ?: ['id' => '', 'name' => '', 'email' => '', 'reply_to' => '', 'smtp_host' => '', 'smtp_port' => 587, 'smtp_user' => '', 'smtp_secure' => 'tls', 'hourly_limit' => 100, 'daily_limit' => 500, 'dkim_selector' => '', 'dkim_private_key' => '', 'is_active' => true, 'dns_check' => null, 'sort_order' => 100];
$action = $editing ? admin_url('mail/senders/' . $editing['id']) : admin_url('mail/senders');
$dns = is_array($s['dns_check']) ? $s['dns_check'] : null;
$domain = $s['email'] ? substr(strrchr((string)$s['email'], '@') ?: '', 1) : 'your-domain.com';
$dkimTxt = '';
if (!empty($s['dkim_private_key'])) { $k = @openssl_pkey_get_private($s['dkim_private_key']); if ($k) { $d = openssl_pkey_get_details($k); $dkimTxt = 'v=DKIM1; k=rsa; p=' . preg_replace('/-----[^-]+-----|\s+/', '', (string)($d['key'] ?? '')); } }
?>
<div class="page-head"><p class="page-head__sub"><?= e(t('mail.dns_hint')) ?></p><?php if ($editing): ?><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/senders')) ?>"><?= icon('plus') ?><span><?= e(t('mail.add_sender')) ?></span></a><?php endif; ?></div>
<div class="grid-main">
  <div class="stack">
    <div class="card"><div class="table-wrap"><table class="table">
      <thead><tr><th><?= e(t('mail.sender_name')) ?></th><th><?= e(t('mail.smtp_host')) ?></th><th><?= e(t('mail.dns_auth')) ?></th><th><?= e(t('mail.usage')) ?></th><th><?= e(t('mail.active')) ?></th><th></th></tr></thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="6" class="empty"><?= e(t('mail.no_senders')) ?></td></tr><?php endif; ?>
        <?php foreach ($rows as $r): $d = is_array($r['dns_check']) ? $r['dns_check'] : []; ?>
          <tr>
            <td><strong><?= e($r['name']) ?></strong><br><small class="muted" dir="ltr"><?= e($r['email']) ?></small></td>
            <td dir="ltr" class="small"><?= e($r['smtp_host']) ?>:<?= e($r['smtp_port']) ?></td>
            <td><span class="pill <?= $r['last_test_ok'] ? 'pill--ok' : ($r['last_test_at'] ? 'pill--high' : 'pill--muted') ?>">SMTP</span> <span class="pill <?= !empty($d['spf']) ? 'pill--ok' : 'pill--warn' ?>">SPF</span> <span class="pill <?= (!empty($d['dkim']) || !empty($r['dkim_selector'])) ? 'pill--ok' : 'pill--warn' ?>">DKIM</span> <span class="pill <?= !empty($d['dmarc']) ? 'pill--ok' : 'pill--warn' ?>">DMARC</span></td>
            <td class="small"><?= (int)$r['usage']['hour'] ?> / <?= (int)$r['usage']['day'] ?></td>
            <td><?= $r['is_active'] ? icon('check-circle', 'text-ok') : '—' ?></td>
            <td class="td-actions">
              <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/senders/' . $r['id'] . '/edit')) ?>"><?= icon('pencil-simple') ?><span><?= e(t('common.edit')) ?></span></a>
              <form method="post" action="<?= e(admin_url('mail/senders/' . $r['id'] . '/test')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit" title="<?= e(t('mail.test_connection')) ?>"><?= icon('test-tube') ?><span><?= e(t('mail.test_connection')) ?></span></button></form>
              <form method="post" action="<?= e(admin_url('mail/senders/' . $r['id'] . '/send-test')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit" title="<?= e(t('mail.send_test_email')) ?>"><?= icon('paper-plane-tilt') ?><span><?= e(t('mail.send_test_email')) ?></span></button></form>
              <?= view('admin/partials/delete-button', ['action' => admin_url('mail/senders/' . $r['id'] . '/delete')]) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody></table></div></div>

    <?php if ($editing): ?>
    <div class="card"><div class="card__head"><h2><?= icon('shield-check') ?><?= e(t('mail.dns_auth')) ?> — <span dir="ltr"><?= e($domain) ?></span></h2>
      <form method="post" action="<?= e(admin_url('mail/senders/' . $editing['id'] . '/test')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit"><?= icon('arrows-clockwise') ?><span><?= e(t('mail.test_connection')) ?></span></button></form></div>
      <div class="card__body stack-sm">
        <?php foreach (['mx' => $dns['mx'] ?? null, 'spf' => $dns['spf'] ?? null, 'dkim' => $dns['dkim'] ?? null, 'dmarc' => $dns['dmarc'] ?? null] as $k => $v): $state = $dns === null ? 'muted' : ($v ? 'ok' : 'warn'); ?>
          <div class="check-item check-item--<?= $state === 'ok' ? 'ok' : 'warn' ?>"><?= icon($state === 'ok' ? 'check-circle' : ($state === 'muted' ? 'info' : 'warning')) ?><div class="check-item__body"><strong><?= e(t('mail.' . $k)) ?></strong> <span class="muted small"><?= $dns === null ? e(t('mail.not_checked')) : ($v ? e(t('mail.found')) . ($k === 'dkim' && !empty($dns['dkim_selector']) ? ' (' . e($dns['dkim_selector']) . ')' : '') . ($k === 'dmarc' && !empty($dns['dmarc_policy']) ? ' · p=' . e($dns['dmarc_policy']) : '') : e(t('mail.missing'))) ?></span><?php if (is_string($v) && $v !== '' && $k !== 'dkim'): ?><div class="hint" dir="ltr" style="word-break:break-all"><?= e($v) ?></div><?php endif; ?></div></div>
        <?php endforeach; ?>
        <strong class="small" style="margin-top:.6rem"><?= e(t('mail.dns_records')) ?></strong>
        <table class="table table--compact"><tbody>
          <tr><td><code>TXT</code></td><td dir="ltr"><code><?= e($domain) ?></code></td><td dir="ltr" class="small"><code>v=spf1 include:&lt;your SMTP provider&gt; ~all</code></td></tr>
          <tr><td><code>TXT</code></td><td dir="ltr"><code>_dmarc.<?= e($domain) ?></code></td><td dir="ltr" class="small"><code>v=DMARC1; p=none; rua=mailto:<?= e($s['email']) ?></code></td></tr>
          <?php if ($dkimTxt !== ''): ?><tr><td><code>TXT</code></td><td dir="ltr"><code><?= e($s['dkim_selector']) ?>._domainkey.<?= e($domain) ?></code></td><td dir="ltr" class="small" style="word-break:break-all"><code style="user-select:all"><?= e($dkimTxt) ?></code></td></tr><?php endif; ?>
        </tbody></table>
        <form method="post" action="<?= e(admin_url('mail/senders/' . $editing['id'] . '/dkim')) ?>" class="row"><?= csrf_field() ?>
          <input class="input input--sm" type="text" name="selector" value="<?= e($s['dkim_selector'] ?: 'athar') ?>" placeholder="<?= e(t('mail.dkim_selector')) ?>" dir="ltr" style="max-width:160px">
          <button class="btn btn--ghost btn--sm" type="submit"><?= icon('key') ?><span><?= e(t('mail.generate_dkim')) ?></span></button>
          <span class="hint"><?= e(t('mail.dkim_record_hint')) ?></span>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="card"><div class="card__head"><h2><?= icon('identification-badge') ?><?= e($editing ? t('mail.edit_sender') : t('mail.add_sender')) ?></h2></div><div class="card__body">
    <form method="post" action="<?= e($action) ?>" class="stack"><?= csrf_field() ?>
      <div class="grid-2">
        <?= view('admin/partials/field', ['name' => 'name', 'label' => t('mail.sender_name'), 'value' => $s['name'], 'required' => true, 'placeholder' => 'Athar Web Design']) ?>
        <?= view('admin/partials/field', ['name' => 'email', 'label' => t('mail.from_email'), 'type' => 'email', 'value' => $s['email'], 'required' => true]) ?>
      </div>
      <?= view('admin/partials/field', ['name' => 'reply_to', 'label' => t('mail.reply_to'), 'type' => 'email', 'value' => $s['reply_to']]) ?>
      <div class="grid-2">
        <?= view('admin/partials/field', ['name' => 'smtp_host', 'label' => t('mail.smtp_host'), 'value' => $s['smtp_host'], 'required' => true, 'attrs' => 'dir="ltr"', 'placeholder' => 'smtp.example.com']) ?>
        <?= view('admin/partials/field', ['name' => 'smtp_port', 'label' => t('mail.smtp_port'), 'type' => 'number', 'value' => $s['smtp_port']]) ?>
        <?= view('admin/partials/field', ['name' => 'smtp_user', 'label' => t('mail.smtp_user'), 'value' => $s['smtp_user'], 'attrs' => 'dir="ltr" autocomplete="off"']) ?>
        <?= view('admin/partials/field', ['name' => 'smtp_pass', 'label' => t('mail.smtp_pass'), 'type' => 'password', 'value' => '', 'hint' => $editing ? t('mail.smtp_pass_hint') : '', 'attrs' => 'autocomplete="new-password"']) ?>
        <?= view('admin/partials/field', ['name' => 'smtp_secure', 'label' => t('mail.smtp_secure'), 'type' => 'select', 'value' => $s['smtp_secure'], 'options' => ['tls' => 'STARTTLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None (25)']]) ?>
        <?= view('admin/partials/field', ['name' => 'is_active', 'label' => t('mail.active'), 'type' => 'toggle', 'value' => $s['is_active']]) ?>
        <?= view('admin/partials/field', ['name' => 'hourly_limit', 'label' => t('mail.hourly_limit'), 'type' => 'number', 'value' => $s['hourly_limit']]) ?>
        <?= view('admin/partials/field', ['name' => 'daily_limit', 'label' => t('mail.daily_limit'), 'type' => 'number', 'value' => $s['daily_limit']]) ?>
      </div>
      <p class="hint"><?= e(t('mail.limits_hint')) ?></p>
      <?php if ($editing): ?><?= view('admin/partials/field', ['name' => 'dkim_selector', 'label' => t('mail.dkim_selector'), 'value' => $s['dkim_selector'], 'attrs' => 'dir="ltr"', 'hint' => t('mail.dkim_record_hint')]) ?><?php endif; ?>
      <div class="row row--end"><button class="btn btn--primary" type="submit"><?= icon('floppy-disk') ?><span><?= e(t('common.save')) ?></span></button></div>
    </form>
  </div></div>
</div>
