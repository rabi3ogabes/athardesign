<?php /** @var array $rows */ ?>
<div class="page-head">
  <p class="page-head__sub"><?= e(t('pages.subtitle')) ?></p>
  <a class="btn btn--primary" href="<?= e(admin_url('pages/create')) ?>"><?= icon('plus') ?><span><?= e(t('pages.new')) ?></span></a>
</div>
<div class="card">
  <div class="table-wrap">
    <table class="table table--sortable" data-sortable-rows data-reorder-url="<?= e(admin_url('pages/reorder')) ?>">
      <thead><tr><th class="th-drag"></th><th><?= e(t('pages.page_title')) ?></th><th><?= e(t('pages.slug')) ?></th><th><?= e(t('common.status')) ?></th><th><?= e(t('pages.show_in_nav')) ?></th><th><?= e(t('pages.seo')) ?></th><th><?= e(t('common.actions')) ?></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $p): $url = $p['slug'] === 'home' ? url('/') : url('/' . $p['slug']); ?>
          <tr data-id="<?= e($p['id']) ?>">
            <td class="td-drag"><?= icon('dots-six-vertical') ?></td>
            <td><a class="link strong" href="<?= e(admin_url('pages/' . $p['id'] . '/edit')) ?>"><?= e(tr($p['title']) ?: t('common.untitled')) ?></a><?= $p['is_system'] ? ' <span class="pill pill--muted">system</span>' : '' ?></td>
            <td dir="ltr"><code>/<?= e($p['slug'] === 'home' ? '' : $p['slug']) ?></code></td>
            <td><span class="pill pill--status-<?= e($p['status']) ?>"><?= e(t('common.' . $p['status'])) ?></span></td>
            <td><?= $p['show_in_nav'] ? icon('check-circle', 'text-ok') : '—' ?></td>
            <td><?php $ok = tr($p['meta_description'] ?? '') !== ''; ?><span class="pill <?= $ok ? 'pill--ok' : 'pill--warn' ?>"><?= $ok ? 'OK' : e(t('seo.missing_description')) ?></span><?= $p['noindex'] ? ' <span class="pill pill--muted">noindex</span>' : '' ?></td>
            <td class="td-actions">
              <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('pages/' . $p['id'] . '/edit')) ?>"><?= icon('pencil-simple') ?><span><?= e(t('common.edit')) ?></span></a>
              <?php if ($p['slug'] !== '404'): ?><a class="btn btn--ghost btn--sm" href="<?= e($url) ?>" target="_blank" rel="noopener"><?= icon('arrow-square-out') ?><span><?= e(t('pages.view')) ?></span></a><?php endif; ?>
              <form method="post" action="<?= e(admin_url('pages/' . $p['id'] . '/duplicate')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit"><?= icon('copy') ?><span><?= e(t('pages.duplicate')) ?></span></button></form>
              <?php if (!$p['is_system']): ?><?= view('admin/partials/delete-button', ['action' => admin_url('pages/' . $p['id'] . '/delete')]) ?><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
