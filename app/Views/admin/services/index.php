<?php /** @var array $rows */ ?>
<div class="page-head">
  <p class="page-head__sub"><?= e(t('services.subtitle')) ?> <?= e(t('services.reorder_hint')) ?></p>
  <a class="btn btn--primary" href="<?= e(admin_url('services/create')) ?>"><?= icon('plus') ?><span><?= e(t('services.new')) ?></span></a>
</div>
<div class="card">
  <div class="table-wrap">
    <table class="table" data-sortable-rows data-reorder-url="<?= e(admin_url('services/reorder')) ?>">
      <thead><tr><th class="th-drag"></th><th></th><th><?= e(t('services.name')) ?></th><th><?= e(t('pages.slug')) ?></th><th><?= e(t('common.status')) ?></th><th><?= e(t('common.featured')) ?></th><th><?= e(t('common.actions')) ?></th></tr></thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="7" class="empty"><?= e(t('common.empty')) ?></td></tr><?php endif; ?>
        <?php foreach ($rows as $s): ?>
          <tr data-id="<?= e($s['id']) ?>">
            <td class="td-drag"><?= icon('dots-six-vertical') ?></td>
            <td><span class="icon-picker__preview"><?= icon($s['icon'] ?: 'sparkle') ?></span></td>
            <td><a class="link strong" href="<?= e(admin_url('services/' . $s['id'] . '/edit')) ?>"><?= e(tr($s['title'])) ?></a><br><small class="muted"><?= e(str_limit(tr($s['excerpt']), 70)) ?></small></td>
            <td dir="ltr"><code>/services/<?= e($s['slug']) ?></code></td>
            <td><span class="pill pill--status-<?= e($s['status']) ?>"><?= e(t('common.' . $s['status'])) ?></span></td>
            <td><?= $s['is_featured'] ? icon('check-circle', 'text-ok') : '—' ?></td>
            <td class="td-actions">
              <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('services/' . $s['id'] . '/edit')) ?>"><?= icon('pencil-simple') ?><span><?= e(t('common.edit')) ?></span></a>
              <a class="btn btn--ghost btn--sm" href="<?= e(url('/services/' . $s['slug'])) ?>" target="_blank" rel="noopener"><?= icon('arrow-square-out') ?><span><?= e(t('common.view')) ?></span></a>
              <?= view('admin/partials/delete-button', ['action' => admin_url('services/' . $s['id'] . '/delete')]) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
