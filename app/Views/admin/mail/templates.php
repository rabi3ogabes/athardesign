<?php /** @var array $rows @var array $starters */ ?>
<div class="page-head"><p class="page-head__sub"><?= e(t('mail.no_templates')) ?></p><a class="btn btn--primary" href="<?= e(admin_url('mail/templates/create')) ?>"><?= icon('plus') ?><span><?= e(t('mail.new_template')) ?></span></a></div>
<div class="card" style="margin-bottom:1.2rem"><div class="card__head"><h2><?= icon('sparkle') ?><?= e(t('mail.starters')) ?></h2></div><div class="card__body">
  <div class="tpl-grid">
    <?php foreach ($starters as $k => $s): ?>
      <a class="tpl-card" href="<?= e(admin_url('mail/templates/create?starter=' . $k)) ?>"><iframe class="tpl-card__frame" src="<?= e(admin_url('mail/preview')) ?>?starter=<?= e($k) ?>" sandbox="allow-same-origin" tabindex="-1" loading="lazy" title="<?= e($s['name']) ?>"></iframe><span class="tpl-card__name"><?= e($s['name']) ?></span></a>
    <?php endforeach; ?>
    <a class="tpl-card tpl-card--blank" href="<?= e(admin_url('mail/templates/create')) ?>"><?= icon('plus') ?><span class="tpl-card__name"><?= e(t('mail.blank')) ?></span></a>
  </div>
</div></div>
<div class="card"><div class="table-wrap"><table class="table">
  <thead><tr><th><?= e(t('mail.template_name')) ?></th><th><?= e(t('mail.subject')) ?></th><th><?= e(t('common.date')) ?></th><th></th></tr></thead>
  <tbody>
    <?php if (!$rows): ?><tr><td colspan="4" class="empty"><?= e(t('common.empty')) ?></td></tr><?php endif; ?>
    <?php foreach ($rows as $t): ?>
      <tr><td><a class="link strong" href="<?= e(admin_url('mail/templates/' . $t['id'] . '/edit')) ?>"><?= e($t['name']) ?></a></td><td><?= e(str_limit((string)$t['subject'], 70)) ?></td><td><time><?= e(fmt_date($t['updated_at'], 'd M Y')) ?></time></td>
        <td class="td-actions"><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/campaigns/create?template=' . $t['id'])) ?>"><?= icon('paper-plane-tilt') ?><span><?= e(t('mail.new_campaign')) ?></span></a><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('mail/templates/' . $t['id'] . '/edit')) ?>"><?= icon('pencil-simple') ?><span><?= e(t('common.edit')) ?></span></a><?= view('admin/partials/delete-button', ['action' => admin_url('mail/templates/' . $t['id'] . '/delete')]) ?></td></tr>
    <?php endforeach; ?>
  </tbody></table></div></div>
