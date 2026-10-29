<?php /** @var array $rows @var array $pager @var string $status @var string $q */ ?>
<div class="page-head">
  <p class="page-head__sub"><?= e(t('leads.subtitle')) ?></p>
  <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('export/leads')) ?>"><?= icon('download-simple') ?><span><?= e(t('common.export')) ?></span></a>
</div>
<form class="filters-bar" method="get">
  <div class="seg seg--sm">
    <button type="submit" name="status" value="" class="<?= $status === '' ? 'is-active' : '' ?>"><?= e(t('common.all')) ?></button>
    <?php foreach (['new', 'contacted', 'won', 'lost'] as $s): ?><button type="submit" name="status" value="<?= $s ?>" class="<?= $status === $s ? 'is-active' : '' ?>"><?= e(t('leads.' . $s)) ?></button><?php endforeach; ?>
  </div>
  <input class="input input--sm" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('common.search')) ?>">
  <button class="btn btn--primary btn--sm" type="submit"><?= e(t('common.search')) ?></button>
</form>
<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th><?= e(t('fields.name')) ?></th><th><?= e(t('fields.phone')) ?></th><th><?= e(t('fields.service')) ?></th><th><?= e(t('fields.message')) ?></th><th><?= e(t('common.status')) ?></th><th><?= e(t('leads.received')) ?></th></tr></thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="6" class="empty"><?= e(t('leads.no_leads')) ?></td></tr><?php endif; ?>
        <?php foreach ($rows as $l): ?>
          <tr>
            <td><a class="link strong" href="<?= e(admin_url('leads/' . $l['id'])) ?>"><?= e($l['name']) ?></a><?php if ($l['company']): ?><br><small class="muted"><?= e($l['company']) ?></small><?php endif; ?></td>
            <td dir="ltr"><?= e($l['phone'] ?? '') ?></td>
            <td><?= e($l['service'] ?? '') ?></td>
            <td class="td-trunc" style="max-width:320px"><?= e(str_limit((string)$l['message'], 70)) ?></td>
            <td><span class="pill pill--status-<?= e($l['status']) ?>"><?= e(t('leads.' . $l['status'])) ?></span></td>
            <td><time title="<?= e(fmt_date($l['created_at'])) ?>"><?= e(time_ago($l['created_at'])) ?></time></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view('admin/partials/pager', ['pager' => $pager]) ?>
</div>
