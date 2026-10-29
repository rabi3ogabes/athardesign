<?php /** @var array $t */ $isNew = $t['id'] === ''; $action = $isNew ? admin_url('mail/templates') : admin_url('mail/templates/' . $t['id']);
$merge = ['{{first_name}}', '{{name}}', '{{company}}', '{{email}}', '{{site_name}}', '{{site_url}}', '{{address}}', '{{unsubscribe_url}}']; ?>
<form method="post" action="<?= e($action) ?>" data-dirty-guard data-template-form data-preview-url="<?= e(admin_url('mail/preview')) ?>">
  <?= csrf_field() ?>
  <div class="page-head page-head--sticky">
    <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/templates')) ?>"><?= icon('arrow-left', 'rtl-flip') ?><span><?= e(t('mail.templates')) ?></span></a>
    <div class="row"><?php if (!$isNew): ?><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/campaigns/create?template=' . $t['id'])) ?>"><?= icon('paper-plane-tilt') ?><span><?= e(t('mail.new_campaign')) ?></span></a><?php endif; ?><button class="btn btn--primary" type="submit"><?= icon('floppy-disk') ?><span><?= e(t('common.save')) ?></span></button></div>
  </div>
  <div class="card"><div class="card__body stack">
    <div class="grid-2">
      <?= view('admin/partials/field', ['name' => 'name', 'label' => t('mail.template_name'), 'value' => $t['name'], 'required' => true]) ?>
      <?= view('admin/partials/field', ['name' => 'subject', 'label' => t('mail.subject'), 'value' => $t['subject'], 'hint' => t('mail.subject_hint')]) ?>
    </div>
    <div class="merge-tags"><span class="hint"><?= e(t('mail.merge_tags')) ?>:</span><?php foreach ($merge as $m): ?><button type="button" class="pill pill--muted" data-merge="<?= e($m) ?>"><?= e($m) ?></button><?php endforeach; ?></div>
    <div class="editor-split">
      <div class="field"><div class="field__label"><label><?= e(t('mail.html')) ?></label></div><textarea class="input input--code" name="html" rows="30" data-html-editor dir="ltr" spellcheck="false"><?= e($t['html']) ?></textarea></div>
      <div class="field"><div class="field__label"><label><?= e(t('mail.preview')) ?></label><span class="hint"><?= e(t('mail.preview_hint')) ?></span></div><div class="preview-frame preview-frame--tall"><iframe title="preview" data-preview-frame sandbox="allow-same-origin"></iframe></div></div>
    </div>
  </div></div>
</form>
