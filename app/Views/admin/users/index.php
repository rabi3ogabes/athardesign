<?php /** @var array $rows @var array $user */ ?>
<div class="page-head"><p class="page-head__sub"><?= e(t('users.subtitle')) ?></p><button type="button" class="btn btn--primary" data-modal-open="#user-modal"><?= icon('plus') ?><span><?= e(t('users.new')) ?></span></button></div>
<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th><?= e(t('users.name')) ?></th><th><?= e(t('users.email')) ?></th><th><?= e(t('users.role')) ?></th><th><?= e(t('users.active')) ?></th><th><?= e(t('users.last_login')) ?></th><th><?= e(t('common.actions')) ?></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $u): ?>
          <tr>
            <td><strong><?= e($u['name']) ?></strong><?= $u['id'] === $user['id'] ? ' <span class="pill pill--muted">you</span>' : '' ?></td>
            <td dir="ltr"><?= e($u['email']) ?></td>
            <td><span class="pill <?= $u['role'] === 'owner' ? 'pill--ok' : 'pill--muted' ?>"><?= e(t('users.' . $u['role'])) ?></span></td>
            <td><?= $u['is_active'] ? icon('check-circle', 'text-ok') : icon('x-circle', 'text-danger') ?></td>
            <td><?= $u['last_login_at'] ? e(fmt_date($u['last_login_at'])) : e(t('users.never')) ?></td>
            <td class="td-actions">
              <button type="button" class="btn btn--ghost btn--sm" data-modal-open="#edit-<?= e($u['id']) ?>"><?= icon('pencil-simple') ?><span><?= e(t('common.edit')) ?></span></button>
              <?php if ($u['id'] !== $user['id'] && $u['role'] !== 'owner'): ?><?= view('admin/partials/delete-button', ['action' => admin_url('users/' . $u['id'] . '/delete')]) ?><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php foreach ($rows as $u): ?>
<div class="modal" id="edit-<?= e($u['id']) ?>" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close></div>
  <div class="modal__panel" role="dialog" aria-modal="true">
    <form method="post" action="<?= e(admin_url('users/' . $u['id'])) ?>"><?= csrf_field() ?>
      <div class="modal__head"><h3><?= e($u['name']) ?></h3><button type="button" class="modal__close" data-modal-close><?= icon('x') ?></button></div>
      <div class="modal__body stack">
        <div class="grid-2">
          <?= view('admin/partials/field', ['name' => 'name', 'label' => t('users.name'), 'value' => $u['name'], 'required' => true]) ?>
          <?= view('admin/partials/field', ['name' => 'email', 'label' => t('users.email'), 'type' => 'email', 'value' => $u['email'], 'required' => true]) ?>
        </div>
        <?php if ($u['role'] !== 'owner'): ?>
          <div class="grid-2">
            <?= view('admin/partials/field', ['name' => 'role', 'label' => t('users.role'), 'type' => 'select', 'value' => $u['role'], 'options' => ['admin' => t('users.admin'), 'editor' => t('users.editor')], 'hint' => t('users.role_hint')]) ?>
            <?= view('admin/partials/field', ['name' => 'is_active', 'label' => t('users.active'), 'type' => 'toggle', 'value' => $u['is_active']]) ?>
          </div>
        <?php endif; ?>
        <?= view('admin/partials/field', ['name' => 'password', 'label' => t('users.password'), 'type' => 'password', 'hint' => t('users.password_hint'), 'attrs' => 'autocomplete="new-password"']) ?>
      </div>
      <div class="modal__foot"><button type="button" class="btn btn--ghost" data-modal-close><?= e(t('common.cancel')) ?></button><button type="submit" class="btn btn--primary"><?= e(t('common.save')) ?></button></div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<div class="modal" id="user-modal" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close></div>
  <div class="modal__panel" role="dialog" aria-modal="true">
    <form method="post" action="<?= e(admin_url('users')) ?>"><?= csrf_field() ?>
      <div class="modal__head"><h3><?= e(t('users.new')) ?></h3><button type="button" class="modal__close" data-modal-close><?= icon('x') ?></button></div>
      <div class="modal__body stack">
        <div class="grid-2">
          <?= view('admin/partials/field', ['name' => 'name', 'label' => t('users.name'), 'value' => old('name'), 'required' => true]) ?>
          <?= view('admin/partials/field', ['name' => 'email', 'label' => t('users.email'), 'type' => 'email', 'value' => old('email'), 'required' => true]) ?>
        </div>
        <div class="grid-2">
          <?= view('admin/partials/field', ['name' => 'role', 'label' => t('users.role'), 'type' => 'select', 'value' => 'editor', 'options' => ['admin' => t('users.admin'), 'editor' => t('users.editor')], 'hint' => t('users.role_hint')]) ?>
          <?= view('admin/partials/field', ['name' => 'lang', 'label' => t('profile.lang'), 'type' => 'select', 'value' => 'en', 'options' => ['en' => 'English', 'ar' => 'العربية']]) ?>
        </div>
        <?= view('admin/partials/field', ['name' => 'password', 'label' => t('users.password'), 'type' => 'password', 'required' => true, 'hint' => t('users.password_hint'), 'attrs' => 'autocomplete="new-password" minlength="10"']) ?>
      </div>
      <div class="modal__foot"><button type="button" class="btn btn--ghost" data-modal-close><?= e(t('common.cancel')) ?></button><button type="submit" class="btn btn--primary"><?= e(t('common.create')) ?></button></div>
    </form>
  </div>
</div>
