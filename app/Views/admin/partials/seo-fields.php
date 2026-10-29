<?php /** $row (array with meta_title, meta_description, meta_keywords, og_image, canonical_url?, noindex?), $withCanonical */ ?>
<div class="card">
  <div class="card__head"><h2><?= icon('magnifying-glass') ?><?= e(t('pages.seo')) ?></h2></div>
  <div class="card__body stack">
    <?= view('admin/partials/field-i18n', ['name' => 'meta_title', 'label' => t('pages.meta_title'), 'value' => $row['meta_title'] ?? [], 'hint' => t('pages.meta_title_hint'), 'count' => true]) ?>
    <?= view('admin/partials/field-i18n', ['name' => 'meta_description', 'label' => t('pages.meta_description'), 'value' => $row['meta_description'] ?? [], 'type' => 'textarea', 'rows' => 3, 'hint' => t('pages.meta_description_hint'), 'count' => true]) ?>
    <?= view('admin/partials/field-i18n', ['name' => 'meta_keywords', 'label' => t('pages.meta_keywords'), 'value' => $row['meta_keywords'] ?? [], 'type' => 'textarea', 'rows' => 2, 'hint' => t('pages.meta_keywords_hint')]) ?>
    <?php if (array_key_exists('og_image', $row)): ?><?= view('admin/partials/field-image', ['name' => 'og_image', 'label' => t('pages.og_image'), 'value' => $row['og_image'] ?? '']) ?><?php endif; ?>
    <?php if (!empty($withCanonical)): ?>
      <div class="grid-2">
        <?= view('admin/partials/field', ['name' => 'canonical_url', 'label' => t('pages.canonical'), 'type' => 'url', 'value' => $row['canonical_url'] ?? '', 'hint' => t('pages.canonical_hint')]) ?>
        <?= view('admin/partials/field', ['name' => 'noindex', 'label' => t('pages.noindex'), 'type' => 'toggle', 'value' => $row['noindex'] ?? false]) ?>
      </div>
    <?php endif; ?>
    <div class="serp-preview" data-serp>
      <span class="serp__url"><?= e(base_url()) ?>/…</span>
      <span class="serp__title" data-serp-title><?= e(tr($row['meta_title'] ?? '') ?: tr($row['title'] ?? '')) ?></span>
      <span class="serp__desc" data-serp-desc><?= e(tr($row['meta_description'] ?? '')) ?></span>
    </div>
  </div>
</div>
