<?php /** @var array $t */
$isNew = $t['id'] === '';
$action = $isNew ? admin_url('testimonials') : admin_url('testimonials/' . $t['id']);
?>
<form method="post" action="<?= e($action) ?>" data-dirty-guard>
  <?= csrf_field() ?>
  <div class="page-head page-head--sticky">
    <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('testimonials')) ?>"><?= icon('arrow-left', 'rtl-flip') ?><span><?= e(t('testimonials.title')) ?></span></a>
    <div class="row">
      <select class="input input--sm" name="status"><?php foreach (status_options() as $k => $v): ?><option value="<?= $k ?>" <?= $t['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
      <button class="btn btn--primary" type="submit"><?= icon('floppy-disk') ?><span><?= e(t('common.save')) ?></span></button>
    </div>
  </div>
  <div class="grid-editor">
    <div class="card"><div class="card__body stack">
      <div class="grid-2">
        <?= view('admin/partials/field', ['name' => 'author_name', 'label' => t('testimonials.author'), 'value' => $t['author_name'], 'required' => true]) ?>
        <?= view('admin/partials/field', ['name' => 'company', 'label' => t('testimonials.company'), 'value' => $t['company']]) ?>
      </div>
      <?= view('admin/partials/field-i18n', ['name' => 'author_role', 'label' => t('testimonials.role'), 'value' => $t['author_role']]) ?>
      <?= view('admin/partials/field-i18n', ['name' => 'quote', 'label' => t('testimonials.quote'), 'value' => $t['quote'], 'type' => 'textarea', 'rows' => 5, 'required' => true]) ?>
    </div></div>
    <div class="card"><div class="card__body stack">
      <?= view('admin/partials/field-image', ['name' => 'avatar', 'label' => t('testimonials.avatar'), 'value' => $t['avatar']]) ?>
      <?= view('admin/partials/field', ['name' => 'rating', 'label' => t('testimonials.rating'), 'type' => 'select', 'value' => $t['rating'], 'options' => [5 => '★★★★★', 4 => '★★★★', 3 => '★★★', 2 => '★★', 1 => '★']]) ?>
      <?= view('admin/partials/field', ['name' => 'sort_order', 'label' => t('common.order'), 'type' => 'number', 'value' => $t['sort_order']]) ?>
    </div></div>
  </div>
</form>
