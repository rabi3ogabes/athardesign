<?php /** @var array $rows @var array $pager */ ?>
<div class="page-head"><p class="page-head__sub"><?= e(t('activity.subtitle')) ?></p></div>
<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th><?= e(t('activity.when')) ?></th><th><?= e(t('activity.user')) ?></th><th><?= e(t('activity.action')) ?></th><th><?= e(t('activity.entity')) ?></th><th>IP</th></tr></thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="5" class="empty"><?= e(t('common.empty')) ?></td></tr><?php endif; ?>
        <?php foreach ($rows as $a): ?>
          <tr>
            <td><time title="<?= e(fmt_date($a['created_at'])) ?>"><?= e(fmt_date($a['created_at'])) ?></time></td>
            <td><?= e($a['user_name'] ?? '') ?></td>
            <td><span class="pill pill--muted"><?= e($a['action']) ?></span></td>
            <td><?= e($a['entity'] ?? '') ?> <small class="muted" dir="ltr"><?= e(is_array($a['details']) ? json_encode($a['details'], JSON_UNESCAPED_UNICODE) : ($a['entity_id'] ?? '')) ?></small></td>
            <td dir="ltr" class="small muted"><?= e($a['ip'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view('admin/partials/pager', ['pager' => $pager]) ?>
</div>
