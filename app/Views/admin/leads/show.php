<?php /** @var array $lead */
$phone = preg_replace('/\D+/', '', (string)$lead['phone']);
if ($phone !== '' && !str_starts_with($phone, '974') && strlen($phone) === 8) $phone = '974' . $phone;
?>
<div class="page-head"><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('leads')) ?>"><?= icon('arrow-left', 'rtl-flip') ?><span><?= e(t('common.back')) ?></span></a>
  <div class="row">
    <?php if ($phone !== ''): ?><a class="btn btn--primary btn--sm" href="https://wa.me/<?= e($phone) ?>" target="_blank" rel="noopener"><?= icon('brand-whatsapp') ?><span><?= e(t('leads.reply_whatsapp')) ?></span></a><?php endif; ?>
    <?php if ($lead['email']): ?><a class="btn btn--ghost btn--sm" href="mailto:<?= e($lead['email']) ?>"><?= icon('envelope-simple') ?><span><?= e(t('site.email_us')) ?></span></a><?php endif; ?>
    <?= view('admin/partials/delete-button', ['action' => admin_url('leads/' . $lead['id'] . '/delete')]) ?>
  </div>
</div>
<div class="grid-side">
  <div class="card"><div class="card__head"><h2><?= e(t('leads.details')) ?></h2></div><div class="card__body">
    <dl class="dl">
      <div><dt><?= e(t('fields.name')) ?></dt><dd><?= e($lead['name']) ?></dd></div>
      <div><dt><?= e(t('fields.phone')) ?></dt><dd dir="ltr"><?= e($lead['phone'] ?? '') ?></dd></div>
      <div><dt><?= e(t('fields.email')) ?></dt><dd dir="ltr"><?= e($lead['email'] ?? '') ?></dd></div>
      <div><dt><?= e(t('fields.company')) ?></dt><dd><?= e($lead['company'] ?? '') ?></dd></div>
      <div><dt><?= e(t('fields.service')) ?></dt><dd><?= e($lead['service'] ?? '') ?></dd></div>
      <div><dt><?= e(t('leads.from_page')) ?></dt><dd dir="ltr" class="small"><?= e($lead['page'] ?? '') ?></dd></div>
      <div><dt><?= e(t('visitors.language')) ?></dt><dd><?= e($lead['lang'] ?? '') ?></dd></div>
      <div><dt><?= e(t('leads.received')) ?></dt><dd><?= e(fmt_date($lead['created_at'])) ?></dd></div>
    </dl>
    <h3 style="margin:1.2rem 0 .4rem;font-size:.9rem"><?= e(t('fields.message')) ?></h3>
    <p style="white-space:pre-wrap"><?= e($lead['message'] ?? '') ?></p>
  </div></div>
  <div class="card"><div class="card__head"><h2><?= e(t('leads.status')) ?></h2></div><div class="card__body">
    <form method="post" action="<?= e(admin_url('leads/' . $lead['id'])) ?>" class="stack"><?= csrf_field() ?>
      <?= view('admin/partials/field', ['name' => 'status', 'label' => t('leads.status'), 'type' => 'select', 'value' => $lead['status'], 'options' => ['new' => t('leads.new'), 'contacted' => t('leads.contacted'), 'won' => t('leads.won'), 'lost' => t('leads.lost')]]) ?>
      <?= view('admin/partials/field', ['name' => 'notes', 'label' => t('leads.notes'), 'type' => 'textarea', 'rows' => 8, 'value' => $lead['notes']]) ?>
      <div class="row row--end"><button class="btn btn--primary" type="submit"><?= icon('floppy-disk') ?><span><?= e(t('common.save')) ?></span></button></div>
    </form>
  </div></div>
</div>
