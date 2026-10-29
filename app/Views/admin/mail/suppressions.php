<?php /** @var array $rows */ ?>
<div class="page-head"><p class="page-head__sub"><?= e(t('mail.suppressions_hint')) ?></p><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/lists')) ?>"><?= icon('arrow-left', 'rtl-flip') ?><span><?= e(t('mail.lists')) ?></span></a></div>
<div class="card"><div class="table-wrap"><table class="table">
  <thead><tr><th><?= e(t('mail.email')) ?></th><th><?= e(t('mail.reason')) ?></th><th><?= e(t('common.date')) ?></th><th></th></tr></thead>
  <tbody>
    <?php if (!$rows): ?><tr><td colspan="4" class="empty"><?= e(t('common.empty')) ?></td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr><td dir="ltr"><?= e($r['email']) ?></td><td><span class="pill pill--<?= $r['reason'] === 'bounce' ? 'high' : 'warn' ?>"><?= e($r['reason']) ?></span></td><td><time><?= e(fmt_date($r['created_at'])) ?></time></td>
        <td class="td-actions"><form method="post" action="<?= e(admin_url('mail/suppressions/remove')) ?>" class="inline" data-confirm="<?= e(t('mail.remove')) ?>?"><?= csrf_field() ?><input type="hidden" name="email" value="<?= e($r['email']) ?>"><button class="btn btn--ghost btn--sm" type="submit"><?= icon('x') ?><span><?= e(t('mail.remove')) ?></span></button></form></td></tr>
    <?php endforeach; ?>
  </tbody></table></div></div>
