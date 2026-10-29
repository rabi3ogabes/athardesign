<?php /** @var array $rows @var array $pager @var array $filters @var string $range */
$ranges = ['today' => t('common.today'), '7' => t('common.last_7'), '30' => t('common.last_30'), '90' => t('common.last_90')];
?>
<div class="page-head">
  <p class="page-head__sub"><?= e(t('clicks.pageviews_subtitle')) ?></p>
  <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('export/visits')) ?>?range=<?= e($range) ?>&bots=<?= $filters['bots'] ? 1 : 0 ?>"><?= icon('download-simple') ?><span><?= e(t('common.export')) ?></span></a>
</div>
<form class="filters-bar" method="get">
  <div class="seg seg--sm"><?php foreach ($ranges as $k => $lbl): ?><button type="submit" name="range" value="<?= e($k) ?>" class="<?= $range === $k ? 'is-active' : '' ?>"><?= e($lbl) ?></button><?php endforeach; ?></div>
  <label class="check"><input type="checkbox" name="bots" value="1" <?= $filters['bots'] ? 'checked' : '' ?> onchange="this.form.submit()"><span><?= e(t('dashboard.bots')) ?></span></label>
</form>
<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th><?= e(t('clicks.path')) ?></th><th><?= e(t('visitors.visitor')) ?></th><th><?= e(t('visitors.device')) ?></th><th><?= e(t('visitors.referrer')) ?></th><th><?= e(t('visitors.language')) ?></th><th><?= e(t('clicks.when')) ?></th></tr></thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="6" class="empty"><?= e(t('common.empty')) ?></td></tr><?php endif; ?>
        <?php foreach ($rows as $v): ?>
          <tr>
            <td dir="ltr"><?= e($v['path']) ?> <small class="muted"><?= e($v['title'] ?? '') ?></small></td>
            <td><a class="link" href="<?= e(admin_url('visitors/' . $v['visitor_id'])) ?>"><?= e(trim(flag_emoji($v['country_code']) . ' ' . ($v['city'] ?: ($v['country'] ?? '')))) ?: '<span dir="ltr">' . e($v['ip'] ?? '') . '</span>' ?></a></td>
            <td><?= e($v['device'] ?? '') ?> · <?= e($v['browser'] ?? '') ?></td>
            <td class="td-trunc" dir="ltr"><?= e($v['referrer'] ? str_limit(preg_replace('#^https?://(www\.)?#', '', $v['referrer']), 36) : t('common.direct')) ?></td>
            <td><?= e($v['lang'] ?? '') ?></td>
            <td><time title="<?= e(fmt_date($v['created_at'])) ?>"><?= e(time_ago($v['created_at'])) ?></time></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view('admin/partials/pager', ['pager' => $pager]) ?>
</div>
