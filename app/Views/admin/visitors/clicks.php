<?php /** @var array $rows @var array $pager @var array $filters @var string $range */
$ranges = ['today' => t('common.today'), '7' => t('common.last_7'), '30' => t('common.last_30'), '90' => t('common.last_90')];
$events = ['whatsapp', 'phone', 'email', 'instagram', 'facebook', 'tiktok', 'snapchat', 'x', 'linkedin', 'youtube', 'threads', 'map', 'cta', 'form', 'service', 'project', 'link'];
?>
<div class="page-head">
  <p class="page-head__sub"><?= e(t('clicks.subtitle')) ?></p>
  <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('export/clicks')) ?>?range=<?= e($range) ?>&bots=<?= $filters['bots'] ? 1 : 0 ?>"><?= icon('download-simple') ?><span><?= e(t('common.export')) ?></span></a>
</div>
<form class="filters-bar" method="get">
  <div class="seg seg--sm"><?php foreach ($ranges as $k => $lbl): ?><button type="submit" name="range" value="<?= e($k) ?>" class="<?= $range === $k ? 'is-active' : '' ?>"><?= e($lbl) ?></button><?php endforeach; ?></div>
  <select class="input input--sm" name="event"><option value=""><?= e(t('clicks.event')) ?>: <?= e(t('common.all')) ?></option><?php foreach ($events as $ev): ?><option value="<?= $ev ?>" <?= $filters['event'] === $ev ? 'selected' : '' ?>><?= e($ev) ?></option><?php endforeach; ?></select>
  <label class="check"><input type="checkbox" name="bots" value="1" <?= $filters['bots'] ? 'checked' : '' ?>><span><?= e(t('dashboard.bots')) ?></span></label>
  <button class="btn btn--primary btn--sm" type="submit"><?= e(t('common.filter')) ?></button>
</form>
<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th><?= e(t('clicks.event')) ?></th><th><?= e(t('clicks.label')) ?></th><th><?= e(t('clicks.target')) ?></th><th><?= e(t('clicks.page')) ?></th><th><?= e(t('visitors.visitor')) ?></th><th><?= e(t('clicks.when')) ?></th></tr></thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="6" class="empty"><?= e(t('clicks.no_clicks')) ?></td></tr><?php endif; ?>
        <?php foreach ($rows as $c): ?>
          <tr>
            <td><span class="pill pill--<?= e($c['event']) ?>"><?= e($c['event']) ?></span></td>
            <td><?= e($c['label'] ?? '') ?></td>
            <td class="td-trunc" dir="ltr"><?= e(str_limit((string)$c['target'], 40)) ?></td>
            <td dir="ltr"><?= e($c['path'] ?? '') ?></td>
            <td><?php if ($c['visitor_id']): ?><a class="link" href="<?= e(admin_url('visitors/' . $c['visitor_id'])) ?>"><?= e(trim(flag_emoji($c['country_code']) . ' ' . ($c['country'] ?? substr($c['visitor_id'], 0, 8)))) ?> · <?= e($c['device'] ?? '') ?></a><?php endif; ?></td>
            <td><time title="<?= e(fmt_date($c['created_at'])) ?>"><?= e(time_ago($c['created_at'])) ?></time></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view('admin/partials/pager', ['pager' => $pager]) ?>
</div>
