<?php /** @var array $s @var array $icons */
$isNew = $s['id'] === '';
$action = $isNew ? admin_url('services') : admin_url('services/' . $s['id']);
$features = is_array($s['features']) ? $s['features'] : [];
?>
<form method="post" action="<?= e($action) ?>" data-dirty-guard>
  <?= csrf_field() ?>
  <div class="page-head page-head--sticky">
    <div class="row">
      <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('services')) ?>"><?= icon('arrow-left', 'rtl-flip') ?><span><?= e(t('services.title')) ?></span></a>
      <?php if (!$isNew): ?><a class="btn btn--ghost btn--sm" href="<?= e(url('/services/' . $s['slug'])) ?>" target="_blank" rel="noopener"><?= icon('arrow-square-out') ?><span><?= e(t('common.view')) ?></span></a><?php endif; ?>
    </div>
    <div class="row">
      <select class="input input--sm" name="status"><?php foreach (status_options() as $k => $v): ?><option value="<?= $k ?>" <?= $s['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
      <button class="btn btn--primary" type="submit"><?= icon('floppy-disk') ?><span><?= e(t('common.save')) ?></span></button>
    </div>
  </div>
  <div class="grid-editor">
    <div class="stack">
      <div class="card"><div class="card__body stack">
        <?= view('admin/partials/field-i18n', ['name' => 'title', 'label' => t('services.name'), 'value' => $s['title'], 'required' => true]) ?>
        <div class="grid-2">
          <?= view('admin/partials/field', ['name' => 'slug', 'label' => t('pages.slug'), 'value' => $s['slug'], 'hint' => t('pages.slug_hint'), 'attrs' => 'data-slug-from="title_en" dir="ltr"']) ?>
          <div class="field">
            <div class="field__label"><label><?= e(t('services.icon')) ?></label></div>
            <div class="icon-picker"><span class="icon-picker__preview" data-icon-preview><?= icon($s['icon'] ?: 'sparkle') ?></span>
              <select class="input" name="icon" data-icon-select><?php foreach ($icons as $ic): ?><option value="<?= e($ic) ?>" <?= ($s['icon'] ?: 'sparkle') === $ic ? 'selected' : '' ?>><?= e($ic) ?></option><?php endforeach; ?></select>
            </div>
          </div>
        </div>
        <?= view('admin/partials/field-i18n', ['name' => 'excerpt', 'label' => t('services.excerpt'), 'value' => $s['excerpt'], 'type' => 'textarea', 'rows' => 2, 'hint' => t('services.excerpt_hint')]) ?>
        <?= view('admin/partials/field-i18n', ['name' => 'body', 'label' => t('services.body'), 'value' => $s['body'], 'type' => 'richtext']) ?>
      </div></div>
      <div class="card">
        <div class="card__head"><h2><?= icon('list-checks') ?><?= e(t('services.features')) ?></h2><button type="button" class="btn btn--ghost btn--sm" data-repeater-add="features"><?= icon('plus') ?><span><?= e(t('services.add_feature')) ?></span></button></div>
        <div class="card__body">
          <div class="repeater" data-repeater="features">
            <?php foreach ($features as $i => $f): $f = is_array($f) ? $f : ['ar' => (string)$f, 'en' => '']; ?>
              <div class="repeater__item" data-repeater-item>
                <div class="repeater__head"><span class="block__drag" data-handle><?= icon('dots-six-vertical') ?></span><button type="button" class="btn btn--ghost btn--sm btn--danger-text" data-repeater-remove><?= icon('x') ?></button></div>
                <div class="repeater__body"><div class="i18n-grid">
                  <div class="i18n-col"><span class="i18n-tag">عربي</span><input class="input" type="text" name="features[<?= $i ?>][ar]" value="<?= e($f['ar'] ?? '') ?>" dir="rtl"></div>
                  <div class="i18n-col"><span class="i18n-tag">EN</span><input class="input" type="text" name="features[<?= $i ?>][en]" value="<?= e($f['en'] ?? '') ?>" dir="ltr"></div>
                </div></div>
              </div>
            <?php endforeach; ?>
          </div>
          <template data-repeater-template="features">
            <div class="repeater__item" data-repeater-item>
              <div class="repeater__head"><span class="block__drag" data-handle><?= icon('dots-six-vertical') ?></span><button type="button" class="btn btn--ghost btn--sm btn--danger-text" data-repeater-remove><?= icon('x') ?></button></div>
              <div class="repeater__body"><div class="i18n-grid">
                <div class="i18n-col"><span class="i18n-tag">عربي</span><input class="input" type="text" name="features[__i__][ar]" dir="rtl"></div>
                <div class="i18n-col"><span class="i18n-tag">EN</span><input class="input" type="text" name="features[__i__][en]" dir="ltr"></div>
              </div></div>
            </div>
          </template>
        </div>
      </div>
    </div>
    <div class="stack">
      <div class="card"><div class="card__body stack">
        <?= view('admin/partials/field-image', ['name' => 'image', 'label' => t('services.image'), 'value' => $s['image']]) ?>
        <?= view('admin/partials/field', ['name' => 'is_featured', 'label' => t('services.is_featured'), 'type' => 'toggle', 'value' => $s['is_featured']]) ?>
        <?= view('admin/partials/field', ['name' => 'sort_order', 'label' => t('common.order'), 'type' => 'number', 'value' => $s['sort_order']]) ?>
      </div></div>
      <?= view('admin/partials/seo-fields', ['row' => $s]) ?>
    </div>
  </div>
</form>
