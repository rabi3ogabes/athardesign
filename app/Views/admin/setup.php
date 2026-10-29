<?php /** @var array $flashes @var string $lang @var string $dir */ ?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title><?= e(t('setup.title')) ?></title>
<link rel="icon" href="<?= e(base_url()) ?>/assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('assets/css/fonts.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin auth">
<main class="auth__box">
  <div class="auth__brand"><span class="auth__mark">أثر</span><span><?= e(setting_tr('site_name')) ?></span></div>
  <h1><?= e(t('setup.title')) ?></h1>
  <p class="auth__sub"><?= e(t('setup.subtitle')) ?></p>
  <?php foreach ($flashes as $f): ?><div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?>
  <form method="post" action="<?= e(admin_url('setup')) ?>" class="stack">
    <?= csrf_field() ?>
    <?= view('admin/partials/field', ['name' => 'name', 'label' => t('setup.name'), 'value' => old('name'), 'required' => true, 'attrs' => 'autofocus']) ?>
    <?= view('admin/partials/field', ['name' => 'email', 'label' => t('login.email'), 'type' => 'email', 'value' => old('email'), 'required' => true]) ?>
    <?= view('admin/partials/field', ['name' => 'password', 'label' => t('login.password'), 'type' => 'password', 'required' => true, 'hint' => t('users.password_hint'), 'attrs' => 'autocomplete="new-password" minlength="10"']) ?>
    <?= view('admin/partials/field', ['name' => 'password_confirmation', 'label' => t('fields.password_confirmation'), 'type' => 'password', 'required' => true, 'attrs' => 'autocomplete="new-password"']) ?>
    <?= view('admin/partials/field', ['name' => 'lang', 'label' => t('profile.lang'), 'type' => 'select', 'value' => $lang, 'options' => ['en' => 'English', 'ar' => 'العربية']]) ?>
    <button class="btn btn--primary btn--block" type="submit"><?= e(t('setup.submit')) ?></button>
  </form>
  <form method="post" action="<?= e(admin_url('lang')) ?>" class="auth__lang"><?= csrf_field() ?><input type="hidden" name="lang" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>"><button type="submit"><?= $lang === 'ar' ? 'English' : 'العربية' ?></button></form>
</main>
</body>
</html>
