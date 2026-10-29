<?php /** @var array $p @var array $cats @var array $tiers */
use App\Models\Packages;
$isNew = $p['id'] === '';
$action = $isNew ? admin_url('packages') : admin_url('packages/' . $p['id']);
$features = is_array($p['features']) ? $p['features'] : [];
$L = lang();
?>
<form method="post" action="<?= e($action) ?>" data-dirty-guard data-package-form data-preview-url="<?= e(admin_url('packages/preview')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e($p['id']) ?>">
  <div class="page-head page-head--sticky">
    <div class="row">
      <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('packages')) ?>"><?= icon('arrow-left', 'rtl-flip') ?><span><?= e(t('packages.title')) ?></span></a>
      <?php if (!$isNew): ?><a class="btn btn--ghost btn--sm" href="<?= e(url('/packages/' . $p['slug'])) ?>" target="_blank" rel="noopener"><?= icon('arrow-square-out') ?><span><?= e(t('common.view')) ?></span></a><?php endif; ?>
    </div>
    <div class="row">
      <select class="input input--sm" name="status"><?php foreach (status_options() as $k => $v): ?><option value="<?= $k ?>" <?= $p['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
      <button class="btn btn--primary" type="submit"><?= icon('floppy-disk') ?><span><?= e(t('common.save')) ?></span></button>
    </div>
  </div>
  <div class="grid-editor">
    <div class="stack">
      <div class="card"><div class="card__body stack">
        <div class="grid-2">
          <div class="field">
            <div class="field__label"><label for="category"><?= e(t('packages.category')) ?></label></div>
            <select class="input" id="category" name="category"><?php foreach ($cats as $k => $lbl): ?><option value="<?= e($k) ?>" <?= $p['category'] === $k ? 'selected' : '' ?>><?= e($lbl[$L] ?? $lbl['en']) ?></option><?php endforeach; ?></select>
          </div>
          <div class="field">
            <div class="field__label"><label><?= e(t('packages.tier')) ?></label></div>
            <div class="tier-pick">
              <?php foreach ($tiers as $k => $lbl): ?>
                <label class="tier-pick__opt"><input type="radio" name="tier" value="<?= e($k) ?>" <?= $p['tier'] === $k ? 'checked' : '' ?>><span class="tier tier--<?= e($k) ?>"><?= e($lbl[$L] ?? $lbl['en']) ?></span></label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?= view('admin/partials/field-i18n', ['name' => 'name', 'label' => t('packages.name'), 'value' => $p['name'], 'required' => true]) ?>
        <?= view('admin/partials/field-i18n', ['name' => 'tagline', 'label' => t('packages.tagline'), 'value' => $p['tagline'], 'hint' => t('packages.tagline_hint')]) ?>
        <div class="grid-2">
          <?= view('admin/partials/field', ['name' => 'slug', 'label' => t('pages.slug'), 'value' => $p['slug'], 'hint' => t('pages.slug_hint'), 'attrs' => 'data-slug-from="name_en" dir="ltr"']) ?>
          <?= view('admin/partials/field-i18n', ['name' => 'badge', 'label' => t('packages.badge'), 'value' => $p['badge'], 'hint' => t('packages.badge_hint')]) ?>
        </div>
        <?= view('admin/partials/field-i18n', ['name' => 'body', 'label' => t('packages.body'), 'value' => $p['body'], 'type' => 'richtext']) ?>
      </div></div>

      <div class="card">
        <div class="card__head"><h2><?= icon('list-checks') ?><?= e(t('packages.features')) ?></h2><button type="button" class="btn btn--ghost btn--sm" data-repeater-add="features"><?= icon('plus') ?><span><?= e(t('packages.add_feature')) ?></span></button></div>
        <div class="card__body">
          <div class="repeater" data-repeater="features">
            <?php foreach ($features as $i => $f): $f = is_array($f) ? $f : ['ar' => (string)$f, 'en' => '']; $inc = filter_var($f['included'] ?? true, FILTER_VALIDATE_BOOL); ?>
              <div class="repeater__item" data-repeater-item>
                <div class="repeater__head"><span class="block__drag" data-handle><?= icon('dots-six-vertical') ?></span>
                  <label class="toggle toggle--sm"><input type="hidden" name="features[<?= $i ?>][included]" value="0"><input type="checkbox" name="features[<?= $i ?>][included]" value="1" <?= $inc ? 'checked' : '' ?>><span class="toggle__track"></span><span class="toggle__label"><?= e(t('packages.included')) ?></span></label>
                  <button type="button" class="btn btn--ghost btn--sm btn--danger-text" data-repeater-remove><?= icon('x') ?></button></div>
                <div class="repeater__body"><div class="i18n-grid">
                  <div class="i18n-col"><span class="i18n-tag">عربي</span><input class="input" type="text" name="features[<?= $i ?>][ar]" value="<?= e($f['ar'] ?? '') ?>" dir="rtl"></div>
                  <div class="i18n-col"><span class="i18n-tag">EN</span><input class="input" type="text" name="features[<?= $i ?>][en]" value="<?= e($f['en'] ?? '') ?>" dir="ltr"></div>
                </div></div>
              </div>
            <?php endforeach; ?>
          </div>
          <template data-repeater-template="features">
            <div class="repeater__item" data-repeater-item>
              <div class="repeater__head"><span class="block__drag" data-handle><?= icon('dots-six-vertical') ?></span>
                <label class="toggle toggle--sm"><input type="hidden" name="features[__i__][included]" value="0"><input type="checkbox" name="features[__i__][included]" value="1" checked><span class="toggle__track"></span><span class="toggle__label"><?= e(t('packages.included')) ?></span></label>
                <button type="button" class="btn btn--ghost btn--sm btn--danger-text" data-repeater-remove><?= icon('x') ?></button></div>
              <div class="repeater__body"><div class="i18n-grid">
                <div class="i18n-col"><span class="i18n-tag">عربي</span><input class="input" type="text" name="features[__i__][ar]" dir="rtl"></div>
                <div class="i18n-col"><span class="i18n-tag">EN</span><input class="input" type="text" name="features[__i__][en]" dir="ltr"></div>
              </div></div>
            </div>
          </template>
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2><?= icon('brand-whatsapp') ?><?= e(t('packages.whatsapp')) ?></h2></div>
        <div class="card__body stack">
          <?= view('admin/partials/field-i18n', ['name' => 'whatsapp_message', 'label' => t('packages.whatsapp'), 'value' => $p['whatsapp_message'], 'type' => 'textarea', 'rows' => 3, 'hint' => t('packages.whatsapp_hint')]) ?>
          <?= view('admin/partials/field-i18n', ['name' => 'cta_text', 'label' => t('packages.cta_text'), 'value' => $p['cta_text'], 'hint' => t('packages.cta_text_hint')]) ?>
          <div class="field">
            <div class="field__label"><label><?= e(t('packages.whatsapp_preview')) ?></label></div>
            <div class="wa-preview">
              <div class="wa-preview__bubble" dir="rtl" data-wa-preview="ar"><?= nl2br(e(Packages::whatsappMessage($p, 'ar'))) ?></div>
              <div class="wa-preview__bubble" dir="ltr" data-wa-preview="en"><?= nl2br(e(Packages::whatsappMessage($p, 'en'))) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="stack">
      <div class="card">
        <div class="card__head"><h2><?= icon('tag') ?><?= e(t('packages.pricing')) ?></h2></div>
        <div class="card__body stack">
          <div class="grid-2">
            <?= view('admin/partials/field', ['name' => 'price', 'label' => t('packages.price'), 'type' => 'text', 'value' => $p['price'] === null ? '' : rtrim(rtrim(number_format((float)$p['price'], 2, '.', ''), '0'), '.'), 'hint' => t('packages.price_hint'), 'attrs' => 'inputmode="decimal" dir="ltr"', 'placeholder' => '2500']) ?>
            <?= view('admin/partials/field', ['name' => 'currency', 'label' => t('packages.currency'), 'type' => 'select', 'value' => $p['currency'] ?: 'QAR', 'options' => ['QAR' => 'QAR — ر.ق', 'USD' => 'USD', 'EUR' => 'EUR', 'SAR' => 'SAR', 'AED' => 'AED']]) ?>
          </div>
          <?= view('admin/partials/field-i18n', ['name' => 'price_prefix', 'label' => t('packages.price_prefix'), 'value' => $p['price_prefix'], 'hint' => t('packages.price_prefix_hint')]) ?>
          <?= view('admin/partials/field-i18n', ['name' => 'period', 'label' => t('packages.period'), 'value' => $p['period'], 'hint' => t('packages.period_hint')]) ?>
          <?= view('admin/partials/field-i18n', ['name' => 'delivery', 'label' => t('packages.delivery'), 'value' => $p['delivery'], 'hint' => t('packages.delivery_hint')]) ?>
          <?= view('admin/partials/field-i18n', ['name' => 'price_note', 'label' => t('packages.price_note'), 'value' => $p['price_note'], 'type' => 'textarea', 'rows' => 2]) ?>
        </div>
      </div>
      <div class="card"><div class="card__body stack">
        <?= view('admin/partials/field', ['name' => 'is_highlighted', 'label' => t('packages.is_highlighted'), 'type' => 'toggle', 'value' => $p['is_highlighted']]) ?>
        <?= view('admin/partials/field-image', ['name' => 'image', 'label' => t('packages.image'), 'value' => $p['image']]) ?>
        <?= view('admin/partials/field', ['name' => 'sort_order', 'label' => t('common.order'), 'type' => 'number', 'value' => $p['sort_order']]) ?>
      </div></div>
      <?= view('admin/partials/seo-fields', ['row' => $p]) ?>
    </div>
  </div>
</form>
