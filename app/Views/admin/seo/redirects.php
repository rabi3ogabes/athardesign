<?php /** @var array $rows */ ?>
<div class="page-head"><p class="page-head__sub"><?= e(t('seo.redirects_subtitle')) ?></p></div>
<div class="grid-main">
  <div class="card">
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th><?= e(t('seo.from')) ?></th><th><?= e(t('seo.to')) ?></th><th><?= e(t('seo.code')) ?></th><th><?= e(t('seo.hits')) ?></th><th></th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="5" class="empty"><?= e(t('common.empty')) ?></td></tr><?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td dir="ltr"><code><?= e($r['from_path']) ?></code></td>
              <td dir="ltr" class="td-trunc"><code><?= e($r['to_path']) ?></code></td>
              <td><?= (int)$r['code'] ?></td>
              <td><?= (int)$r['hits'] ?></td>
              <td class="td-actions"><?= view('admin/partials/delete-button', ['action' => admin_url('seo/redirects/' . $r['id'] . '/delete')]) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card"><div class="card__head"><h2><?= icon('plus') ?><?= e(t('seo.add_redirect')) ?></h2></div><div class="card__body">
    <form method="post" action="<?= e(admin_url('seo/redirects')) ?>" class="stack"><?= csrf_field() ?>
      <?= view('admin/partials/field', ['name' => 'from_path', 'label' => t('seo.from'), 'value' => old('from_path'), 'placeholder' => '/old-page', 'required' => true, 'attrs' => 'dir="ltr"']) ?>
      <?= view('admin/partials/field', ['name' => 'to_path', 'label' => t('seo.to'), 'value' => old('to_path'), 'placeholder' => '/services/web-design', 'required' => true, 'attrs' => 'dir="ltr"']) ?>
      <?= view('admin/partials/field', ['name' => 'code', 'label' => t('seo.code'), 'type' => 'select', 'value' => 301, 'options' => [301 => '301 — permanent', 302 => '302 — temporary']]) ?>
      <button class="btn btn--primary" type="submit"><?= e(t('seo.add_redirect')) ?></button>
    </form>
  </div></div>
</div>
