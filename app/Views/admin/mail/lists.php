<?php /** @var array $rows */ ?>
<div class="page-head"><p class="page-head__sub"><?= e(t('mail.verify_hint')) ?></p><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/suppressions')) ?>"><?= icon('prohibit') ?><span><?= e(t('mail.suppressions')) ?></span></a></div>
<div class="grid-main">
  <div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th><?= e(t('mail.list_name')) ?></th><th><?= e(t('mail.contacts')) ?></th><th><?= e(t('mail.st_valid')) ?></th><th><?= e(t('mail.st_risky')) ?></th><th><?= e(t('mail.st_invalid')) ?></th><th><?= e(t('mail.st_pending')) ?></th><th><?= e(t('mail.avg_score')) ?></th><th></th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="8" class="empty"><?= e(t('mail.no_contacts')) ?></td></tr><?php endif; ?>
      <?php foreach ($rows as $l): $s = $l['summary']; ?>
        <tr>
          <td><a class="link strong" href="<?= e(admin_url('mail/lists/' . $l['id'])) ?>"><?= e($l['name']) ?></a><?= $l['description'] ? '<br><small class="muted">' . e(str_limit($l['description'], 60)) . '</small>' : '' ?></td>
          <td><?= e(fmt_num((int)($s['total'] ?? 0))) ?></td><td class="text-ok"><?= (int)($s['valid'] ?? 0) ?></td><td><?= (int)($s['risky'] ?? 0) ?></td><td class="text-danger"><?= (int)($s['invalid'] ?? 0) ?></td><td><?= (int)($s['pending'] ?? 0) ?></td>
          <td><?php $avg = (int)($s['avg_score'] ?? 0); ?><span class="score-pill score-pill--<?= $avg >= 80 ? 'A' : ($avg >= 60 ? 'B' : 'D') ?>"><?= $avg ?></span></td>
          <td class="td-actions"><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/lists/' . $l['id'])) ?>"><?= icon('eye') ?><span><?= e(t('common.view')) ?></span></a><?= view('admin/partials/delete-button', ['action' => admin_url('mail/lists/' . $l['id'] . '/delete')]) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody></table></div></div>
  <div class="card"><div class="card__head"><h2><?= icon('plus') ?><?= e(t('mail.new_list')) ?></h2></div><div class="card__body">
    <form method="post" action="<?= e(admin_url('mail/lists')) ?>" class="stack"><?= csrf_field() ?>
      <?= view('admin/partials/field', ['name' => 'name', 'label' => t('mail.list_name'), 'required' => true, 'placeholder' => 'Restaurants Doha 2026']) ?>
      <?= view('admin/partials/field', ['name' => 'description', 'label' => t('mail.list_description'), 'type' => 'textarea', 'rows' => 2]) ?>
      <button class="btn btn--primary" type="submit"><?= e(t('common.create')) ?></button>
    </form>
  </div></div>
</div>
