<?php /** @var array $rows @var array $cats @var array $tiers */
$byCat = [];
foreach ($rows as $p) $byCat[$p['category']][] = $p;
$wa = trim((string)setting('whatsapp_number', ''));
?>
<div class="page-head">
  <p class="page-head__sub"><?= e(t('packages.subtitle')) ?> <?= e(t('packages.reorder_hint')) ?></p>
  <a class="btn btn--primary" href="<?= e(admin_url('packages/create')) ?>"><?= icon('plus') ?><span><?= e(t('packages.new')) ?></span></a>
</div>
<?php if (preg_replace('/\D/', '', $wa) === '' || preg_replace('/\D/', '', $wa) === '974'): ?>
  <div class="notice notice--warn"><?= icon('warning') ?><span><?= e(t('packages.no_whatsapp')) ?> <a class="link" href="<?= e(admin_url('settings/contact')) ?>"><?= e(t('settings.contact')) ?> →</a></span></div>
<?php endif; ?>
<?php if (!$rows): ?>
  <div class="card"><div class="card__body empty"><?= e(t('common.empty')) ?></div></div>
<?php endif; ?>
<?php foreach ($cats as $catKey => $catLabel): $list = $byCat[$catKey] ?? []; if (!$list && !in_array($catKey, ['bundle', 'website', 'social'], true)) continue; ?>
  <div class="card pk-group">
    <div class="card__head">
      <h2><?= icon($catKey === 'social' ? 'instagram-logo' : ($catKey === 'seo' ? 'magnifying-glass' : ($catKey === 'photography' ? 'camera' : ($catKey === 'bundle' ? 'sparkle' : 'layout')))) ?><?= e($catLabel[lang()] ?? $catLabel['en']) ?> <small class="muted">(<?= count($list) ?>)</small></h2>
      <div class="row">
        <?php foreach ($tiers as $tk => $tl): $has = array_filter($list, fn($p) => $p['tier'] === $tk); if ($has) continue; ?>
          <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('packages/create?category=' . $catKey . '&tier=' . $tk)) ?>"><?= icon('plus') ?><span><?= e($tl[lang()] ?? $tl['en']) ?></span></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="table-wrap">
      <table class="table" data-sortable-rows data-reorder-url="<?= e(admin_url('packages/reorder')) ?>">
        <thead><tr><th class="th-drag"></th><th><?= e(t('packages.tier')) ?></th><th><?= e(t('packages.name')) ?></th><th><?= e(t('packages.price')) ?></th><th><?= e(t('packages.features')) ?></th><th><?= e(t('common.status')) ?></th><th><?= e(t('common.actions')) ?></th></tr></thead>
        <tbody>
          <?php if (!$list): ?><tr><td colspan="7" class="empty"><?= e(t('common.empty')) ?></td></tr><?php endif; ?>
          <?php foreach ($list as $p): ?>
            <tr data-id="<?= e($p['id']) ?>">
              <td class="td-drag"><?= icon('dots-six-vertical') ?></td>
              <td><span class="tier tier--<?= e($p['tier']) ?>"><?= e($tiers[$p['tier']][lang()] ?? $p['tier']) ?></span><?php if (!empty($p['is_highlighted'])): ?> <?= icon('star', 'text-brass', t('packages.is_highlighted')) ?><?php endif; ?></td>
              <td><a class="link strong" href="<?= e(admin_url('packages/' . $p['id'] . '/edit')) ?>"><?= e(tr($p['name'])) ?></a><br><small class="muted"><?= e(str_limit(tr($p['tagline']), 70)) ?></small></td>
              <td dir="ltr"><strong><?= e(\App\Models\Packages::price($p) ?: t('packages.custom_quote')) ?></strong><?php if (tr($p['period']) !== ''): ?> <small class="muted"><?= e(tr($p['period'])) ?></small><?php endif; ?></td>
              <td><?= count(is_array($p['features']) ? $p['features'] : []) ?></td>
              <td><span class="pill pill--status-<?= e($p['status']) ?>"><?= e(t('common.' . $p['status'])) ?></span></td>
              <td class="td-actions">
                <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('packages/' . $p['id'] . '/edit')) ?>"><?= icon('pencil-simple') ?><span><?= e(t('common.edit')) ?></span></a>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('/packages/' . $p['slug'])) ?>" target="_blank" rel="noopener"><?= icon('arrow-square-out') ?><span><?= e(t('common.view')) ?></span></a>
                <form method="post" action="<?= e(admin_url('packages/' . $p['id'] . '/duplicate')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit" title="<?= e(t('packages.duplicate')) ?>"><?= icon('copy') ?></button></form>
                <?= view('admin/partials/delete-button', ['action' => admin_url('packages/' . $p['id'] . '/delete')]) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endforeach; ?>
