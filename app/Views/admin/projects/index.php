<?php /** @var array $rows @var array $categories */ ?>
<div class="page-head">
  <p class="page-head__sub"><?= e(t('projects.subtitle')) ?></p>
  <div class="row">
    <button type="button" class="btn btn--ghost" data-modal-open="#categories-modal"><?= icon('tag') ?><span><?= e(t('projects.manage_categories')) ?></span></button>
    <a class="btn btn--primary" href="<?= e(admin_url('projects/create')) ?>"><?= icon('plus') ?><span><?= e(t('projects.new')) ?></span></a>
  </div>
</div>
<div class="card">
  <div class="table-wrap">
    <table class="table" data-sortable-rows data-reorder-url="<?= e(admin_url('projects/reorder')) ?>">
      <thead><tr><th class="th-drag"></th><th></th><th><?= e(t('projects.name')) ?></th><th><?= e(t('projects.category')) ?></th><th><?= e(t('projects.client')) ?></th><th><?= e(t('common.status')) ?></th><th><?= e(t('common.featured')) ?></th><th><?= e(t('common.actions')) ?></th></tr></thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="8" class="empty"><?= e(t('common.empty')) ?></td></tr><?php endif; ?>
        <?php foreach ($rows as $p): ?>
          <tr data-id="<?= e($p['id']) ?>">
            <td class="td-drag"><?= icon('dots-six-vertical') ?></td>
            <td class="td-thumb"><?php if ($p['cover_image']): ?><img src="<?= e(media_url(App\Core\Image::thumbOf($p['cover_image']))) ?>" alt=""><?php endif; ?></td>
            <td><a class="link strong" href="<?= e(admin_url('projects/' . $p['id'] . '/edit')) ?>"><?= e(tr($p['title'])) ?></a><br><small class="muted" dir="ltr">/work/<?= e($p['slug']) ?></small></td>
            <td><?= e(tr($p['category']['name'] ?? '') ?: '—') ?></td>
            <td><?= e($p['client'] ?? '') ?></td>
            <td><span class="pill pill--status-<?= e($p['status']) ?>"><?= e(t('common.' . $p['status'])) ?></span></td>
            <td><?= $p['is_featured'] ? icon('check-circle', 'text-ok') : '—' ?></td>
            <td class="td-actions">
              <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('projects/' . $p['id'] . '/edit')) ?>"><?= icon('pencil-simple') ?><span><?= e(t('common.edit')) ?></span></a>
              <a class="btn btn--ghost btn--sm" href="<?= e(url('/work/' . $p['slug'])) ?>" target="_blank" rel="noopener"><?= icon('arrow-square-out') ?><span><?= e(t('common.view')) ?></span></a>
              <?= view('admin/partials/delete-button', ['action' => admin_url('projects/' . $p['id'] . '/delete')]) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal" id="categories-modal" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close></div>
  <div class="modal__panel" role="dialog" aria-modal="true">
    <div class="modal__head"><h3><?= e(t('projects.categories')) ?></h3><button type="button" class="modal__close" data-modal-close><?= icon('x') ?></button></div>
    <div class="modal__body stack">
      <?php foreach ($categories as $c): ?>
        <form method="post" action="<?= e(admin_url('categories/' . $c['id'])) ?>" class="row" style="flex-wrap:nowrap"><?= csrf_field() ?>
          <input class="input input--sm" type="text" name="name[ar]" value="<?= e($c['name']['ar'] ?? '') ?>" dir="rtl" placeholder="عربي">
          <input class="input input--sm" type="text" name="name[en]" value="<?= e($c['name']['en'] ?? '') ?>" dir="ltr" placeholder="English">
          <input class="input input--sm input--xs" type="text" name="slug" value="<?= e($c['slug']) ?>" dir="ltr" style="width:110px">
          <input type="hidden" name="sort_order" value="<?= (int)$c['sort_order'] ?>">
          <button class="btn btn--ghost btn--sm" type="submit"><?= icon('floppy-disk') ?></button>
        </form>
        <?= view('admin/partials/delete-button', ['action' => admin_url('categories/' . $c['id'] . '/delete'), 'confirm' => t('projects.category_in_use')]) ?>
      <?php endforeach; ?>
      <form method="post" action="<?= e(admin_url('categories')) ?>" class="stack" style="padding-top:1rem;border-top:1px solid var(--line)"><?= csrf_field() ?>
        <strong><?= e(t('projects.new_category')) ?></strong>
        <div class="i18n-grid">
          <div class="i18n-col"><span class="i18n-tag">عربي</span><input class="input" type="text" name="name[ar]" dir="rtl" required></div>
          <div class="i18n-col"><span class="i18n-tag">EN</span><input class="input" type="text" name="name[en]" dir="ltr"></div>
        </div>
        <div class="row"><input class="input input--sm" type="text" name="slug" placeholder="<?= e(t('projects.category_slug')) ?>" dir="ltr" style="width:180px"><button class="btn btn--primary btn--sm" type="submit"><?= e(t('common.add')) ?></button></div>
      </form>
    </div>
  </div>
</div>
