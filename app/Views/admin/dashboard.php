<?php
/** @var array $o @var string $range @var bool $bots @var int $live @var array $recentClicks @var array $recentLeads @var array $recentActivity @var array $seoHealth @var array $user */
$useCharts = true;
$pct = function (int $cur, int $prev): string {
    if ($prev <= 0) return $cur > 0 ? '+100%' : '—';
    $d = ($cur - $prev) / $prev * 100;
    return ($d >= 0 ? '+' : '') . number_format($d, 0) . '%';
};
$kpis = [
    ['pageviews', (int)($o['pageviews'] ?? 0), $pct((int)($o['pageviews'] ?? 0), (int)($o['prev_pageviews'] ?? 0)), 'eye'],
    ['visitors', (int)($o['visitors'] ?? 0), $pct((int)($o['visitors'] ?? 0), (int)($o['prev_visitors'] ?? 0)), 'users'],
    ['clicks', (int)($o['clicks'] ?? 0), null, 'cursor-click'],
    ['whatsapp', (int)($o['whatsapp_clicks'] ?? 0), null, 'brand-whatsapp'],
    ['leads', (int)($o['leads'] ?? 0), null, 'tray'],
];
$visitorsN = max(1, (int)($o['visitors'] ?? 0));
$clickVisitors = 0;
foreach (($o['clicks_by_event'] ?? []) as $c) $clickVisitors = max($clickVisitors, (int)$c['visitors']);
$rate = (int)($o['visitors'] ?? 0) > 0 ? min(100, round($clickVisitors / $visitorsN * 100)) : 0;
$ranges = ['today' => t('common.today'), '7' => t('common.last_7'), '30' => t('common.last_30'), '90' => t('common.last_90'), 'month' => t('common.this_month')];
$bar = function (array $rows, string $key, string $val, ?callable $fmt = null) {
    $max = 0; foreach ($rows as $r) $max = max($max, (int)$r[$val]);
    $html = '';
    foreach ($rows as $r) {
        $w = $max > 0 ? round((int)$r[$val] / $max * 100) : 0;
        $label = $fmt ? $fmt($r) : e((string)$r[$key]);
        $html .= '<li class="bar"><span class="bar__label">' . $label . '</span><span class="bar__track"><i style="width:' . $w . '%"></i></span><span class="bar__val">' . fmt_num((int)$r[$val]) . '</span></li>';
    }
    return $html !== '' ? '<ul class="bars">' . $html . '</ul>' : '<p class="empty">' . e(t('dashboard.no_data')) . '</p>';
};
?>
<div class="page-head">
  <div>
    <p class="page-head__sub"><?= e(t('dashboard.welcome', ['name' => $user['name'] ?? ''])) ?></p>
    <div class="live"><i></i><?= e(fmt_num($live)) ?> <?= e(t('dashboard.live')) ?></div>
  </div>
  <form class="range" method="get" data-range-form>
    <div class="seg">
      <?php foreach ($ranges as $k => $lbl): ?><button type="submit" name="range" value="<?= e($k) ?>" class="<?= $range === $k ? 'is-active' : '' ?>"><?= e($lbl) ?></button><?php endforeach; ?>
    </div>
    <label class="check"><input type="checkbox" name="bots" value="1" <?= $bots ? 'checked' : '' ?> onchange="this.form.submit()"><span><?= e(t('dashboard.bots')) ?></span></label>
  </form>
</div>

<div class="kpis">
  <?php foreach ($kpis as [$k, $n, $delta, $ico]): ?>
    <div class="kpi">
      <span class="kpi__ico"><?= icon($ico) ?></span>
      <span class="kpi__label"><?= e(t('dashboard.' . $k)) ?></span>
      <strong class="kpi__val"><?= e(fmt_num($n)) ?></strong>
      <?php if ($delta !== null): ?><span class="kpi__delta <?= str_starts_with($delta, '+') ? 'is-up' : (str_starts_with($delta, '-') ? 'is-down' : '') ?>"><?= e($delta) ?> <small><?= e(t('dashboard.vs_prev')) ?></small></span><?php endif; ?>
    </div>
  <?php endforeach; ?>
  <div class="kpi kpi--accent">
    <span class="kpi__ico"><?= icon('target') ?></span>
    <span class="kpi__label"><?= e(t('dashboard.conversion')) ?></span>
    <strong class="kpi__val"><?= e($rate) ?>%</strong>
    <span class="kpi__delta"><small><?= e(t('dashboard.conversion_hint')) ?></small></span>
  </div>
</div>

<div class="grid-main">
  <div class="card card--chart">
    <div class="card__head"><h2><?= icon('chart-line-up') ?><?= e(t('dashboard.traffic')) ?></h2></div>
    <div class="card__body"><canvas id="traffic-chart" height="110" data-chart='<?= e(json_encode($o['daily'] ?? [], JSON_UNESCAPED_UNICODE)) ?>' data-labels='<?= e(json_encode(['pageviews' => t('dashboard.pageviews'), 'visitors' => t('dashboard.visitors'), 'clicks' => t('dashboard.clicks')], JSON_UNESCAPED_UNICODE)) ?>'></canvas></div>
  </div>
  <div class="card">
    <div class="card__head"><h2><?= icon('cursor-click') ?><?= e(t('dashboard.clicks_by_event')) ?></h2><a href="<?= e(admin_url('clicks')) ?>" class="link"><?= e(t('common.view')) ?></a></div>
    <div class="card__body"><?= $bar($o['clicks_by_event'] ?? [], 'event', 'clicks', fn($r) => icon(in_array($r['event'], ['whatsapp', 'instagram', 'facebook', 'tiktok', 'snapchat', 'x', 'linkedin', 'youtube', 'threads', 'pinterest', 'behance', 'telegram'], true) ? 'brand-' . $r['event'] : ($r['event'] === 'phone' ? 'phone' : ($r['event'] === 'email' ? 'envelope-simple' : 'link')), 'bar__ico') . e(ucfirst((string)$r['event']))) ?></div>
  </div>
</div>

<div class="grid-3">
  <div class="card"><div class="card__head"><h2><?= e(t('dashboard.top_pages')) ?></h2></div><div class="card__body"><?= $bar($o['top_pages'] ?? [], 'path', 'views', fn($r) => '<span dir="ltr">' . e((string)$r['path']) . '</span>') ?></div></div>
  <div class="card"><div class="card__head"><h2><?= e(t('dashboard.referrers')) ?></h2></div><div class="card__body"><?= $bar($o['top_referrers'] ?? [], 'referrer', 'views', fn($r) => '<span dir="ltr">' . e(str_limit(preg_replace('#^https?://(www\.)?#', '', (string)$r['referrer']) ?: '', 38)) . '</span>') ?></div></div>
  <div class="card"><div class="card__head"><h2><?= e(t('dashboard.countries')) ?></h2></div><div class="card__body"><?= $bar($o['countries'] ?? [], 'country', 'visitors', fn($r) => e(flag_emoji($r['code'] ?? '') . ' ' . (string)$r['country'])) ?></div></div>
  <div class="card"><div class="card__head"><h2><?= e(t('dashboard.devices')) ?></h2></div><div class="card__body"><?= $bar($o['devices'] ?? [], 'device', 'views', fn($r) => e(t('visitors.' . $r['device']) !== 'visitors.' . $r['device'] ? t('visitors.' . $r['device']) : ucfirst((string)$r['device']))) ?></div></div>
  <div class="card"><div class="card__head"><h2><?= e(t('dashboard.browsers')) ?></h2></div><div class="card__body"><?= $bar($o['browsers'] ?? [], 'browser', 'views') ?></div></div>
  <div class="card"><div class="card__head"><h2><?= e(t('dashboard.clicks_by_page')) ?></h2></div><div class="card__body"><?= $bar($o['clicks_by_page'] ?? [], 'path', 'clicks', fn($r) => '<span dir="ltr">' . e((string)$r['path']) . '</span>') ?></div></div>
</div>

<div class="grid-3">
  <div class="card">
    <div class="card__head"><h2><?= e(t('dashboard.recent_clicks')) ?></h2><a href="<?= e(admin_url('clicks')) ?>" class="link"><?= e(t('common.view')) ?></a></div>
    <div class="card__body list">
      <?php if (!$recentClicks): ?><p class="empty"><?= e(t('clicks.no_clicks')) ?></p><?php endif; ?>
      <?php foreach ($recentClicks as $c): ?>
        <a class="list__row" href="<?= e(admin_url('visitors/' . $c['visitor_id'])) ?>">
          <span class="pill pill--<?= e($c['event']) ?>"><?= e($c['event']) ?></span>
          <span class="list__main"><span dir="ltr"><?= e($c['path'] ?? '/') ?></span><small><?= e(trim(flag_emoji($c['country_code'] ?? '') . ' ' . ($c['country'] ?? ''))) ?> · <?= e($c['device'] ?? '') ?></small></span>
          <time><?= e(time_ago($c['created_at'])) ?></time>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="card__head"><h2><?= e(t('dashboard.recent_leads')) ?></h2><a href="<?= e(admin_url('leads')) ?>" class="link"><?= e(t('common.view')) ?></a></div>
    <div class="card__body list">
      <?php if (!$recentLeads): ?><p class="empty"><?= e(t('leads.no_leads')) ?></p><?php endif; ?>
      <?php foreach ($recentLeads as $l): ?>
        <a class="list__row" href="<?= e(admin_url('leads/' . $l['id'])) ?>">
          <span class="pill pill--status-<?= e($l['status']) ?>"><?= e(t('leads.' . $l['status'])) ?></span>
          <span class="list__main"><?= e($l['name']) ?><small><?= e($l['service'] ?: ($l['phone'] ?? '')) ?></small></span>
          <time><?= e(time_ago($l['created_at'])) ?></time>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="card__head"><h2><?= e(t('nav.seo')) ?></h2><a href="<?= e(admin_url('seo')) ?>" class="link"><?= e(t('common.view')) ?></a></div>
    <div class="card__body stack-sm">
      <div class="stat-row"><span><?= e(t('dashboard.seo_tasks_open')) ?></span><strong><?= e((int)($seoHealth['tasks_open'] ?? 0)) ?></strong></div>
      <div class="stat-row"><span><?= e(t('seo.overdue')) ?></span><strong class="<?= (int)($seoHealth['tasks_overdue'] ?? 0) > 0 ? 'text-danger' : '' ?>"><?= e((int)($seoHealth['tasks_overdue'] ?? 0)) ?></strong></div>
      <div class="stat-row"><span><?= e(t('seo.missing_description')) ?></span><strong><?= e(count($seoHealth['missing_description'] ?? [])) ?></strong></div>
      <div class="stat-row"><span><?= e(t('seo.media_missing_alt')) ?></span><strong><?= e((int)($seoHealth['media_missing_alt'] ?? 0)) ?></strong></div>
      <?php if (App\Core\Auth::can('manage_settings')): $themes = App\Models\Settings::themes(); $cur = (string)setting('theme', 'ink'); ?>
      <div class="card__head" style="margin-top:.5rem"><h2><?= icon('palette') ?><?= e(t('settings.quick_theme')) ?></h2><a href="<?= e(admin_url('settings/appearance')) ?>" class="link"><?= e(t('common.edit')) ?></a></div>
      <form method="post" action="<?= e(admin_url('settings/appearance')) ?>" class="theme-dots" data-theme-dots><?= csrf_field() ?>
        <?php foreach ($themes as $key => $th): ?>
          <button type="submit" name="theme" value="<?= e($key) ?>" class="theme-dot <?= $cur === $key ? 'is-active' : '' ?>" style="background:<?= e($th['bg']) ?>;--dot-accent:<?= e($th['accent']) ?>" title="<?= e($th[lang()] ?? $th['en']) ?>" aria-label="<?= e($th[lang()] ?? $th['en']) ?>"></button>
        <?php endforeach; ?>
      </form>
      <?php endif; ?>
      <div class="card__head" style="margin-top:.5rem"><h2><?= e(t('dashboard.quick_actions')) ?></h2></div>
      <div class="quick">
        <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('pages/create')) ?>"><?= icon('plus') ?><span><?= e(t('dashboard.new_page')) ?></span></a>
        <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('projects/create')) ?>"><?= icon('plus') ?><span><?= e(t('dashboard.new_project')) ?></span></a>
        <?php $home = App\Models\Pages::bySlug('home', false); if ($home): ?><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('pages/' . $home['id'] . '/edit')) ?>"><?= icon('pencil-simple') ?><span><?= e(t('dashboard.edit_home')) ?></span></a><?php endif; ?>
        <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('settings')) ?>"><?= icon('gear') ?><span><?= e(t('dashboard.settings')) ?></span></a>
      </div>
    </div>
  </div>
</div>
