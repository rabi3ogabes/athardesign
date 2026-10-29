<?php /** @var array $rows */ ?>
<div class="page-head">
  <p class="page-head__sub"><?= e(t('testimonials.subtitle')) ?></p>
  <a class="btn btn--primary" href="<?= e(admin_url('testimonials/create')) ?>"><?= icon('plus') ?><span><?= e(t('testimonials.new')) ?></span></a>
</div>
<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th><?= e(t('testimonials.author')) ?></th><th><?= e(t('testimonials.quote')) ?></th><th><?= e(t('testimonials.rating')) ?></th><th><?= e(t('common.status')) ?></th><th><?= e(t('common.actions')) ?></th></tr></thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="5" class="empty"><?= e(t('common.empty')) ?></td></tr><?php endif; ?>
        <?php foreach ($rows as $t): ?>
          <tr>
            <td><strong><?= e($t['author_name']) ?></strong><br><small class="muted"><?= e(trim(tr($t['author_role']) . ' ' . ($t['company'] ?? ''))) ?></small></td>
            <td class="td-trunc" style="max-width:420px"><?= e(str_limit(tr($t['quote']), 90)) ?></td>
            <td><?= str_repeat('★', (int)$t['rating']) ?></td>
            <td><span class="pill pill--status-<?= e($t['status']) ?>"><?= e(t('common.' . $t['status'])) ?></span></td>
            <td class="td-actions">
              <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('testimonials/' . $t['id'] . '/edit')) ?>"><?= icon('pencil-simple') ?><span><?= e(t('common.edit')) ?></span></a>
              <?= view('admin/partials/delete-button', ['action' => admin_url('testimonials/' . $t['id'] . '/delete')]) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
