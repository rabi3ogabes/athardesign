<?php /** @var array $p @var array $categories @var array $services */
$isNew = $p['id'] === '';
$action = $isNew ? admin_url('projects') : admin_url('projects/' . $p['id']);
$gallery = is_array($p['gallery']) ? $p['gallery'] : [];
$results = is_array($p['results']) ? $p['results'] : [];
$sel = (array)($p['services'] ?? []);
$catOpts = ['' => t('projects.no_category')];
foreach ($categories as $c) $catOpts[$c['id']] = tr($c['name']);
?>
<form method="post" action="<?= e($action) ?>" data-dirty-guard>
  <?= csrf_field() ?>
  <div class="page-head page-head--sticky">
    <div class="row">
      <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('projects')) ?>"><?= icon('arrow-left', 'rtl-flip') ?><span><?= e(t('projects.title')) ?></span></a>
      <?php if (!$isNew): ?><a class="btn btn--ghost btn--sm" href="<?= e(url('/work/' . $p['slug'])) ?>" target="_blank" rel="noopener"><?= icon('arrow-square-out') ?><span><?= e(t('common.view')) ?></span></a><?php endif; ?>
    </div>
    <div class="row">
      <select class="input input--sm" name="status"><?php foreach (status_options() as $k => $v): ?><option value="<?= $k ?>" <?= $p['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
      <button class="btn btn--primary" type="submit"><?= icon('floppy-disk') ?><span><?= e(t('common.save')) ?></span></button>
    </div>
  </div>
  <div class="grid-editor">
    <div class="stack">
      <div class="card"><div class="card__body stack">
        <?= view('admin/partials/field-i18n', ['name' => 'title', 'label' => t('projects.name'), 'value' => $p['title'], 'required' => true]) ?>
        <div class="grid-2">
          <?= view('admin/partials/field', ['name' => 'slug', 'label' => t('pages.slug'), 'value' => $p['slug'], 'hint' => t('pages.slug_hint'), 'attrs' => 'data-slug-from="title_en" dir="ltr"']) ?>
          <?= view('admin/partials/field', ['name' => 'client', 'label' => t('projects.client'), 'value' => $p['client']]) ?>
          <?= view('admin/partials/field', ['name' => 'category_id', 'label' => t('projects.category'), 'type' => 'select', 'value' => $p['category_id'] ?? '', 'options' => $catOpts]) ?>
          <?= view('admin/partials/field', ['name' => 'year', 'label' => t('projects.year'), 'type' => 'number', 'value' => $p['year']]) ?>
        </div>
        <?= view('admin/partials/field-i18n', ['name' => 'excerpt', 'label' => t('projects.excerpt'), 'value' => $p['excerpt'], 'type' => 'textarea', 'rows' => 2]) ?>
        <?= view('admin/partials/field-i18n', ['name' => 'body', 'label' => t('projects.body'), 'value' => $p['body'], 'type' => 'richtext']) ?>
        <div class="field">
          <div class="field__label"><label><?= e(t('projects.services')) ?></label></div>
          <div class="row">
            <?php foreach ($services as $s): ?><label class="check"><input type="checkbox" name="services[]" value="<?= e($s['slug']) ?>" <?= in_array($s['slug'], $sel, true) ? 'checked' : '' ?>><span><?= e(tr($s['title'])) ?></span></label><?php endforeach; ?>
          </div>
        </div>
        <?= view('admin/partials/field', ['name' => 'link', 'label' => t('projects.link'), 'type' => 'url', 'value' => $p['link'], 'placeholder' => 'https://']) ?>
      </div></div>

      <div class="card">
        <div class="card__head"><h2><?= icon('images') ?><?= e(t('projects.gallery')) ?></h2><button type="button" class="btn btn--ghost btn--sm" data-repeater-add="gallery"><?= icon('plus') ?><span><?= e(t('projects.add_image')) ?></span></button></div>
        <div class="card__body">
          <div class="repeater" data-repeater="gallery">
            <?php foreach ($gallery as $i => $g): ?>
              <div class="repeater__item" data-repeater-item>
                <div class="repeater__head"><span class="block__drag" data-handle><?= icon('dots-six-vertical') ?></span><button type="button" class="btn btn--ghost btn--sm btn--danger-text" data-repeater-remove><?= icon('x') ?></button></div>
                <div class="repeater__body">
                  <?= view('admin/partials/field-image', ['name' => 'gallery[' . $i . '][image]', 'label' => t('common.image'), 'value' => $g['image'] ?? '']) ?>
                  <?= view('admin/partials/field-i18n', ['name' => 'gallery[' . $i . '][alt]', 'label' => t('media.alt'), 'value' => $g['alt'] ?? []]) ?>
                  <?= view('admin/partials/field-i18n', ['name' => 'gallery[' . $i . '][caption]', 'label' => t('media.alt') === '' ? '' : 'Caption', 'value' => $g['caption'] ?? []]) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <template data-repeater-template="gallery">
            <div class="repeater__item" data-repeater-item>
              <div class="repeater__head"><span class="block__drag" data-handle><?= icon('dots-six-vertical') ?></span><button type="button" class="btn btn--ghost btn--sm btn--danger-text" data-repeater-remove><?= icon('x') ?></button></div>
              <div class="repeater__body">
                <?= view('admin/partials/field-image', ['name' => 'gallery[__i__][image]', 'label' => t('common.image'), 'value' => '']) ?>
                <?= view('admin/partials/field-i18n', ['name' => 'gallery[__i__][alt]', 'label' => t('media.alt'), 'value' => []]) ?>
                <?= view('admin/partials/field-i18n', ['name' => 'gallery[__i__][caption]', 'label' => 'Caption', 'value' => []]) ?>
              </div>
            </div>
          </template>
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2><?= icon('trend-up') ?><?= e(t('projects.results')) ?></h2><button type="button" class="btn btn--ghost btn--sm" data-repeater-add="results"><?= icon('plus') ?><span><?= e(t('projects.add_result')) ?></span></button></div>
        <div class="card__body">
          <div class="repeater" data-repeater="results">
            <?php foreach ($results as $i => $x): ?>
              <div class="repeater__item" data-repeater-item>
                <div class="repeater__head"><span class="block__drag" data-handle><?= icon('dots-six-vertical') ?></span><button type="button" class="btn btn--ghost btn--sm btn--danger-text" data-repeater-remove><?= icon('x') ?></button></div>
                <div class="repeater__body grid-2">
                  <?= view('admin/partials/field', ['name' => 'results[' . $i . '][value]', 'label' => t('projects.result_value'), 'value' => $x['value'] ?? '']) ?>
                  <?= view('admin/partials/field-i18n', ['name' => 'results[' . $i . '][label]', 'label' => t('projects.result_label'), 'value' => $x['label'] ?? []]) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <template data-repeater-template="results">
            <div class="repeater__item" data-repeater-item>
              <div class="repeater__head"><span class="block__drag" data-handle><?= icon('dots-six-vertical') ?></span><button type="button" class="btn btn--ghost btn--sm btn--danger-text" data-repeater-remove><?= icon('x') ?></button></div>
              <div class="repeater__body grid-2">
                <?= view('admin/partials/field', ['name' => 'results[__i__][value]', 'label' => t('projects.result_value'), 'value' => '']) ?>
                <?= view('admin/partials/field-i18n', ['name' => 'results[__i__][label]', 'label' => t('projects.result_label'), 'value' => []]) ?>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
    <div class="stack">
      <div class="card"><div class="card__body stack">
        <?= view('admin/partials/field-image', ['name' => 'cover_image', 'label' => t('projects.cover'), 'value' => $p['cover_image']]) ?>
        <?= view('admin/partials/field', ['name' => 'is_featured', 'label' => t('projects.is_featured'), 'type' => 'toggle', 'value' => $p['is_featured']]) ?>
        <?= view('admin/partials/field', ['name' => 'sort_order', 'label' => t('common.order'), 'type' => 'number', 'value' => $p['sort_order']]) ?>
      </div></div>
      <?= view('admin/partials/seo-fields', ['row' => $p]) ?>
    </div>
  </div>
</form>
