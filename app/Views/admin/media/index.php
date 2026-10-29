<?php /** @var array $rows @var array $pager @var string $q */ ?>
<div class="page-head">
  <p class="page-head__sub"><?= e(t('media.subtitle')) ?></p>
  <form class="row" method="get"><input class="input input--sm" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('common.search')) ?>"><button class="btn btn--ghost btn--sm" type="submit"><?= e(t('common.search')) ?></button></form>
</div>
<div class="card" style="margin-bottom:1.2rem">
  <div class="card__body">
    <form method="post" action="<?= e(admin_url('media/upload')) ?>" enctype="multipart/form-data" data-upload-form>
      <?= csrf_field() ?>
      <label class="dropzone" data-dropzone><?= icon('upload-simple') ?><span><?= e(t('media.drop')) ?></span><input type="file" name="files[]" accept="image/*" multiple hidden data-upload-input></label>
    </form>
  </div>
</div>
<div class="card">
  <div class="card__body">
    <?php if (!$rows): ?><p class="empty"><?= e(t('media.no_media')) ?></p><?php endif; ?>
    <div class="media-grid">
      <?php foreach ($rows as $m): ?>
        <button type="button" class="media-item" data-media-detail='<?= e(json_encode(['id' => $m['id'], 'url' => $m['url'], 'full' => media_url($m['url']), 'filename' => $m['filename'], 'alt' => $m['alt'], 'width' => $m['width'], 'height' => $m['height'], 'size' => $m['size'], 'created_at' => fmt_date($m['created_at'])], JSON_UNESCAPED_UNICODE)) ?>'>
          <img src="<?= e(media_url($m['thumb'])) ?>" alt="<?= e(tr($m['alt'])) ?>" loading="lazy">
          <span class="media-item__name"><?= e($m['filename']) ?></span>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
  <?= view('admin/partials/pager', ['pager' => $pager]) ?>
</div>

<div class="modal" id="media-detail-modal" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close></div>
  <div class="modal__panel modal__panel--wide" role="dialog" aria-modal="true">
    <div class="modal__head"><h3><?= e(t('media.details')) ?></h3><button type="button" class="modal__close" data-modal-close><?= icon('x') ?></button></div>
    <div class="modal__body media-detail">
      <img data-md-img src="" alt="">
      <form method="post" class="stack" data-md-form>
        <?= csrf_field() ?>
        <dl class="dl">
          <div><dt><?= e(t('media.url')) ?></dt><dd><input class="input input--sm" type="text" readonly data-md-url dir="ltr"> <button type="button" class="btn btn--ghost btn--sm" data-copy-target="[data-md-url]"><?= icon('copy') ?><span><?= e(t('common.copy')) ?></span></button></dd></div>
          <div><dt><?= e(t('media.dimensions')) ?></dt><dd data-md-dim></dd></div>
          <div><dt><?= e(t('media.size')) ?></dt><dd data-md-size></dd></div>
          <div><dt><?= e(t('media.uploaded')) ?></dt><dd data-md-date></dd></div>
        </dl>
        <div class="field"><div class="field__label"><label><?= e(t('media.alt')) ?></label><span class="hint"><?= e(t('media.alt_hint')) ?></span></div>
          <div class="i18n-grid">
            <div class="i18n-col"><span class="i18n-tag">عربي</span><input class="input" type="text" name="alt[ar]" dir="rtl" data-md-alt-ar></div>
            <div class="i18n-col"><span class="i18n-tag">EN</span><input class="input" type="text" name="alt[en]" dir="ltr" data-md-alt-en></div>
          </div>
        </div>
        <div class="row row--between">
          <button type="submit" class="btn btn--primary btn--sm"><?= icon('floppy-disk') ?><span><?= e(t('common.save')) ?></span></button>
          <button type="button" class="btn btn--ghost btn--sm btn--danger-text" data-md-delete><?= icon('trash') ?><span><?= e(t('common.delete')) ?></span></button>
        </div>
      </form>
    </div>
  </div>
</div>
