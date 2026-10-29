<?php /** @var array $health @var array $checks @var array $settingsChecks @var int $score @var array $pagesById */
$host = base_url();
?>
<div class="page-head"><p class="page-head__sub"><?= e(t('seo.subtitle')) ?></p><a class="btn btn--primary" href="<?= e(admin_url('seo/tasks')) ?>"><?= icon('list-checks') ?><span><?= e(t('seo.tasks_title')) ?></span></a></div>
<div class="grid-main">
  <div class="stack">
    <div class="card"><div class="card__body score">
      <div class="score__ring" style="--p:<?= (int)$score ?>"><span><?= (int)$score ?></span></div>
      <div>
        <strong style="font-size:1.1rem"><?= e(t('seo.title')) ?></strong>
        <p class="muted small"><?= e(t('seo.health_pages')) ?>: <?= (int)($health['pages_total'] ?? 0) ?> · <?= e(t('seo.media_missing_alt')) ?>: <?= (int)($health['media_missing_alt'] ?? 0) ?> · <?= e(t('dashboard.seo_tasks_open')) ?>: <?= (int)($health['tasks_open'] ?? 0) ?></p>
        <div class="row" style="margin-top:.6rem">
          <a class="btn btn--ghost btn--sm" href="<?= e($host) ?>/sitemap.xml" target="_blank" rel="noopener"><?= icon('arrow-square-out') ?><span><?= e(t('seo.sitemap')) ?></span></a>
          <a class="btn btn--ghost btn--sm" href="<?= e($host) ?>/robots.txt" target="_blank" rel="noopener"><?= icon('arrow-square-out') ?><span><?= e(t('seo.robots')) ?></span></a>
        </div>
      </div>
    </div></div>
    <div class="card"><div class="card__head"><h2><?= icon('seal-check') ?><?= e(t('seo.checklist_intro')) ?></h2></div><div class="card__body check-list">
      <?php foreach ($checks as $c): $n = count($c['items']); ?>
        <div class="check-item <?= $n === 0 ? 'check-item--ok' : 'check-item--warn' ?>">
          <?= icon($n === 0 ? 'check-circle' : 'warning') ?>
          <div class="check-item__body">
            <strong><?= e(t('seo.' . $c['key'])) ?></strong> <span class="muted"><?= $n === 0 ? e(t('seo.health_ok')) : $n ?></span>
            <?php if ($n > 0): ?><div class="check-item__items"><?php foreach ($c['items'] as $slug): ?>
              <?php $href = $c['fix'] === 'pages' && isset($pagesById[$slug]) ? admin_url('pages/' . $pagesById[$slug] . '/edit') : admin_url($c['fix']); ?>
              <a class="pill pill--warn" href="<?= e($href) ?>"><?= e($slug) ?></a>
            <?php endforeach; ?></div><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php foreach ($settingsChecks as $c): ?>
        <div class="check-item <?= $c['ok'] ? 'check-item--ok' : 'check-item--warn' ?>"><?= icon($c['ok'] ? 'check-circle' : 'warning') ?><div class="check-item__body"><strong><?= e($c['label']) ?></strong> <?php if (!$c['ok']): ?><a class="link" href="<?= e($c['url']) ?>"><?= e(t('seo.fix')) ?></a><?php endif; ?></div></div>
      <?php endforeach; ?>
    </div></div>
  </div>
  <div class="stack">
    <div class="card"><div class="card__head"><h2><?= icon('compass') ?><?= e(t('seo.tools')) ?></h2></div><div class="card__body stack-sm">
      <a class="btn btn--ghost" href="https://search.google.com/search-console" target="_blank" rel="noopener"><?= icon('arrow-square-out') ?><span><?= e(t('seo.open_gsc')) ?></span></a>
      <a class="btn btn--ghost" href="https://pagespeed.web.dev/report?url=<?= e(rawurlencode($host . '/')) ?>" target="_blank" rel="noopener"><?= icon('gauge') ?><span><?= e(t('seo.open_psi')) ?></span></a>
      <a class="btn btn--ghost" href="https://search.google.com/test/rich-results?url=<?= e(rawurlencode($host . '/')) ?>" target="_blank" rel="noopener"><?= icon('seal-check') ?><span><?= e(t('seo.open_rich')) ?></span></a>
      <a class="btn btn--ghost" href="https://business.google.com/" target="_blank" rel="noopener"><?= icon('map-pin') ?><span><?= e(t('seo.open_gbp')) ?></span></a>
    </div></div>
    <div class="card"><div class="card__head"><h2><?= icon('gear') ?><?= e(t('settings.seo')) ?></h2></div><div class="card__body stack-sm">
      <div class="stat-row"><span><?= e(t('settings.meta_title_suffix')) ?></span><strong class="small"><?= e(setting_tr('meta_title_suffix')) ?></strong></div>
      <div class="stat-row"><span><?= e(t('settings.schema_type')) ?></span><strong class="small"><?= e((string)setting('schema_type')) ?></strong></div>
      <div class="stat-row"><span><?= e(t('settings.default_lang')) ?></span><strong class="small"><?= e((string)setting('default_lang')) ?></strong></div>
      <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('settings/seo')) ?>"><?= icon('pencil-simple') ?><span><?= e(t('common.edit')) ?></span></a>
    </div></div>
  </div>
</div>
