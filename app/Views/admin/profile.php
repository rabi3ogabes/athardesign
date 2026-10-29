<?php /** @var array $user */ ?>
<div class="grid-editor">
  <form method="post" action="<?= e(admin_url('profile')) ?>" class="stack"><?= csrf_field() ?>
    <div class="card"><div class="card__head"><h2><?= icon('user') ?><?= e(t('profile.title')) ?></h2></div><div class="card__body stack">
      <div class="grid-2">
        <?= view('admin/partials/field', ['name' => 'name', 'label' => t('users.name'), 'value' => $user['name'], 'required' => true]) ?>
        <?= view('admin/partials/field', ['name' => 'email', 'label' => t('users.email'), 'type' => 'email', 'value' => $user['email'], 'required' => true]) ?>
      </div>
      <?= view('admin/partials/field', ['name' => 'lang', 'label' => t('profile.lang'), 'type' => 'select', 'value' => $user['lang'], 'options' => ['en' => 'English', 'ar' => 'العربية']]) ?>
    </div></div>
    <div class="card"><div class="card__head"><h2><?= icon('key') ?><?= e(t('profile.change_password')) ?></h2></div><div class="card__body stack">
      <?= view('admin/partials/field', ['name' => 'current_password', 'label' => t('fields.current_password'), 'type' => 'password', 'attrs' => 'autocomplete="current-password"']) ?>
      <div class="grid-2">
        <?= view('admin/partials/field', ['name' => 'password', 'label' => t('fields.password'), 'type' => 'password', 'hint' => t('users.password_hint'), 'attrs' => 'autocomplete="new-password"']) ?>
        <?= view('admin/partials/field', ['name' => 'password_confirmation', 'label' => t('fields.password_confirmation'), 'type' => 'password', 'attrs' => 'autocomplete="new-password"']) ?>
      </div>
    </div></div>
    <div class="row row--end"><button class="btn btn--primary" type="submit"><?= icon('floppy-disk') ?><span><?= e(t('common.save')) ?></span></button></div>
  </form>
</div>
