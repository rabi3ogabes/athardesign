<?php /** @var array $rows @var array $pager @var array $filters */ ?>
<div class="page-head">
  <p class="page-head__sub"><?= e(t('visitors.subtitle')) ?></p>
  <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('export/visitors')) ?>?bots=<?= $filters['bots'] ? 1 : 0 ?>"><?= icon('download-simple') ?><span><?= e(t('common.export')) ?></span></a>
</div>
<form class="filters-bar" method="get">
  <input class="input input--sm" type="search" name="q" value="<?= e($filters['search']) ?>" placeholder="<?= e(t('common.search')) ?>">
  <input class="input input--sm input--xs" type="text" name="country" value="<?= e($filters['country']) ?>" placeholder="<?= e(t('visitors.filter_country')) ?>" maxlength="2">
  <select class="input input--sm" name="device"><option value=""><?= e(t('visitors.filter_device')) ?></option><?php foreach (['desktop', 'mobile', 'tablet'] as $d): ?><option value="<?= $d ?>" <?= $filters['device'] === $d ? 'selected' : '' ?>><?= e(t('visitors.' . $d)) ?></option><?php endforeach; ?></select>
  <label class="check"><input type="checkbox" name="bots" value="1" <?= $filters['bots'] ? 'checked' : '' ?>><span><?= e(t('dashboard.bots')) ?></span></label>
  <button class="btn btn--primary btn--sm" type="submit"><?= e(t('common.filter')) ?></button>
  <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('visitors')) ?>"><?= e(t('common.reset')) ?></a>
</form>
<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th><?= e(t('visitors.visitor')) ?></th><th><?= e(t('visitors.location')) ?></th><th><?= e(t('visitors.device')) ?></th><th><?= e(t('visitors.referrer')) ?></th><th><?= e(t('visitors.landing')) ?></th><th><?= e(t('visitors.visits')) ?></th><th><?= e(t('visitors.last_seen')) ?></th></tr></thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="7" class="empty"><?= e(t('visitors.no_visitors')) ?></td></tr><?php endif; ?>
        <?php foreach ($rows as $v): ?>
          <tr>
            <td><a class="link" href="<?= e(admin_url('visitors/' . $v['id'])) ?>"><span dir="ltr"><?= e($v['ip'] ?: substr($v['id'], 0, 8)) ?></span></a><?= $v['is_bot'] ? ' <span class="pill pill--muted">' . e(t('visitors.bot')) . '</span>' : '' ?></td>
            <td><?= e(trim(flag_emoji($v['country_code']) . ' ' . ($v['city'] ? $v['city'] . ', ' : '') . ($v['country'] ?? '—'))) ?></td>
            <td><?= e(($v['device'] ?? '')) ?> · <?= e($v['browser'] ?? '') ?> · <?= e($v['os'] ?? '') ?></td>
            <td class="td-trunc" dir="ltr"><?= e($v['referrer'] ? str_limit(preg_replace('#^https?://(www\.)?#', '', $v['referrer']), 36) : t('common.direct')) ?></td>
            <td class="td-trunc" dir="ltr"><?= e($v['landing_page'] ?? '') ?></td>
            <td><?= e($v['visits_count']) ?></td>
            <td><time title="<?= e(fmt_date($v['last_seen'])) ?>"><?= e(time_ago($v['last_seen'])) ?></time></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view('admin/partials/pager', ['pager' => $pager]) ?>
</div>
