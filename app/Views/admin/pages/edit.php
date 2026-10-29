<?php /** @var array $page @var array $schema @var array $icons */
$isNew = $page['id'] === '';
$action = $isNew ? admin_url('pages') : admin_url('pages/' . $page['id']);
$blocks = is_array($page['blocks']) ? $page['blocks'] : (json_decode((string)$page['blocks'], true) ?: []);
$publicUrl = $page['slug'] === 'home' ? url('/') : url('/' . $page['slug']);
?>
<form method="post" action="<?= e($action) ?>" class="editor-form" data-page-form data-dirty-guard>
  <?= csrf_field() ?>
  <input type="hidden" name="blocks" value="" data-blocks-input>
  <div class="page-head page-head--sticky">
    <div class="row">
      <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('pages')) ?>"><?= icon('arrow-left', 'rtl-flip') ?><span><?= e(t('pages.title')) ?></span></a>
      <?php if (!$isNew && $page['slug'] !== '404'): ?><a class="btn btn--ghost btn--sm" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener"><?= icon('arrow-square-out') ?><span><?= e(t('pages.view')) ?></span></a><?php endif; ?>
    </div>
    <div class="row">
      <select class="input input--sm" name="status"><?php foreach (status_options() as $k => $v): ?><option value="<?= $k ?>" <?= $page['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
      <button class="btn btn--primary" type="submit"><?= icon('floppy-disk') ?><span><?= e(t('common.save')) ?></span></button>
    </div>
  </div>

  <div class="grid-editor">
    <div class="stack">
      <div class="card">
        <div class="card__head"><h2><?= icon('file-text') ?><?= e(t('pages.settings')) ?></h2></div>
        <div class="card__body stack">
          <?= view('admin/partials/field-i18n', ['name' => 'title', 'label' => t('pages.page_title'), 'value' => $page['title'], 'required' => true]) ?>
          <div class="grid-2">
            <?php if ($page['is_system']): ?>
              <div class="field"><div class="field__label"><label><?= e(t('pages.slug')) ?></label><span class="hint"><?= e(t('pages.system_page')) ?></span></div><input class="input" type="text" value="/<?= e($page['slug'] === 'home' ? '' : $page['slug']) ?>" disabled dir="ltr"></div>
            <?php else: ?>
              <?= view('admin/partials/field', ['name' => 'slug', 'label' => t('pages.slug'), 'value' => $page['slug'], 'hint' => t('pages.slug_hint'), 'attrs' => 'data-slug-from="title_en" dir="ltr"']) ?>
            <?php endif; ?>
            <?= view('admin/partials/field', ['name' => 'nav_order', 'label' => t('pages.nav_order'), 'type' => 'number', 'value' => $page['nav_order']]) ?>
          </div>
          <?= view('admin/partials/field-i18n', ['name' => 'nav_label', 'label' => t('pages.nav_label'), 'value' => $page['nav_label'], 'hint' => t('pages.nav_label_hint')]) ?>
          <div class="grid-2">
            <?= view('admin/partials/field', ['name' => 'show_in_nav', 'label' => t('pages.show_in_nav'), 'type' => 'toggle', 'value' => $page['show_in_nav']]) ?>
            <?= view('admin/partials/field', ['name' => 'show_in_footer', 'label' => t('pages.show_in_footer'), 'type' => 'toggle', 'value' => $page['show_in_footer']]) ?>
          </div>
        </div>
      </div>

      <div class="card card--blocks">
        <div class="card__head"><h2><?= icon('layout') ?><?= e(t('pages.blocks')) ?></h2><button type="button" class="btn btn--primary btn--sm" data-add-block><?= icon('plus') ?><span><?= e(t('pages.add_block')) ?></span></button></div>
        <div class="card__body">
          <div class="blocks" id="blocks-editor" data-blocks-editor data-schema='<?= e(json_encode($schema, JSON_UNESCAPED_UNICODE)) ?>' data-blocks='<?= e(json_encode($blocks, JSON_UNESCAPED_UNICODE)) ?>' data-icons='<?= e(json_encode($icons)) ?>' data-base="<?= e(base_url()) ?>"></div>
          <div class="blocks__empty" data-blocks-empty hidden><?= e(t('pages.no_blocks')) ?></div>
          <div class="row row--center"><button type="button" class="btn btn--ghost" data-add-block><?= icon('plus') ?><span><?= e(t('pages.add_block')) ?></span></button></div>
        </div>
      </div>
    </div>
    <div class="stack">
      <?= view('admin/partials/seo-fields', ['row' => $page, 'withCanonical' => true]) ?>
    </div>
  </div>
</form>

<!-- block picker -->
<div class="modal" id="block-modal" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close></div>
  <div class="modal__panel" role="dialog" aria-modal="true">
    <div class="modal__head"><h3><?= e(t('editor.choose_block')) ?></h3><button type="button" class="modal__close" data-modal-close><?= icon('x') ?></button></div>
    <div class="modal__body block-picker">
      <?php foreach ($schema as $type => $def): ?>
        <button type="button" class="block-picker__item" data-block-type="<?= e($type) ?>"><?= icon($def['icon'] ?? 'layout') ?><span><?= e($def['label'][lang()] ?? $def['label']['en']) ?></span></button>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<template id="icon-sprite" hidden>
  <?php foreach (['dots-six-vertical', 'caret-down', 'caret-up', 'caret-left', 'caret-right', 'trash', 'eye', 'eye-slash', 'plus', 'x', 'image', 'gear', 'copy'] as $ic): ?><span data-icon="<?= $ic ?>"><?= icon($ic) ?></span><?php endforeach; ?>
  <?php foreach ($schema as $type => $def): ?><span data-icon="block-<?= e($type) ?>"><?= icon($def['icon'] ?? 'layout') ?></span><?php endforeach; ?>
</template>
