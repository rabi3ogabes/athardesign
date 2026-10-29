<?php /** @var int $step @var string $lang @var string $dir @var array $flashes @var array $requirements @var string $detectedUrl @var array $timezones */
$steps = [1 => t('install.step_requirements'), 2 => t('install.step_database'), 3 => t('install.step_admin'), 4 => t('install.step_done')];
$base = base_url();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title><?= e(t('install.title')) ?> · أثر</title>
<link rel="icon" href="<?= e($base) ?>/assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('assets/css/fonts.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin auth install">
<main class="auth__box auth__box--wide">
  <div class="install__head">
    <div class="auth__brand"><img class="install__mark" src="<?= e($base) ?>/assets/img/logo-mark-transparent.svg" alt="" width="34" height="34"><span><?= e(t('install.brand')) ?></span></div>
    <a class="install__lang" href="<?= e($base) ?>/install?lang=<?= $lang === 'ar' ? 'en' : 'ar' ?>"><?= $lang === 'ar' ? 'English' : 'العربية' ?></a>
  </div>

  <ol class="install__steps" aria-label="<?= e(t('install.title')) ?>">
    <?php foreach ($steps as $n => $label): ?>
      <li class="<?= $n < $step ? 'is-done' : ($n === $step ? 'is-current' : '') ?>"><span class="install__num"><?= $n < $step ? icon('check') : $n ?></span><span><?= e($label) ?></span></li>
    <?php endforeach; ?>
  </ol>

  <?php foreach ($flashes as $f): ?><div class="alert alert--<?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?>

  <?php if ($step === 1): ?>
    <h1><?= e(t('install.req_title')) ?></h1>
    <p class="auth__sub"><?= e(t('install.req_sub')) ?></p>
    <ul class="checks">
      <?php foreach ($requirements as $req): ?>
        <li class="check <?= $req['ok'] ? 'is-ok' : ($req['required'] ? 'is-fail' : 'is-warn') ?>">
          <span class="check__ico"><?= icon($req['ok'] ? 'check-circle' : ($req['required'] ? 'x-circle' : 'warning')) ?></span>
          <span class="check__label"><?= e(t('install.req_' . $req['key'])) ?><?php if ($req['note'] !== ''): ?> <code><?= e($req['note']) ?></code><?php endif; ?></span>
          <span class="check__state"><?= e($req['ok'] ? t('install.ok') : ($req['required'] ? t('install.fail') : t('install.optional'))) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <p class="hint install__hint"><?= e(t('install.req_help')) ?></p>
    <a class="btn btn--primary btn--block" href="<?= e($base) ?>/install"><?= icon('arrows-clockwise') ?><span><?= e(t('install.recheck')) ?></span></a>

  <?php elseif ($step === 2): ?>
    <h1><?= e(t('install.db_title')) ?></h1>
    <p class="auth__sub"><?= e(t('install.db_sub')) ?></p>
    <form method="post" action="<?= e($base) ?>/install" class="stack" autocomplete="off">
      <?= csrf_field() ?><input type="hidden" name="step" value="database">
      <div class="grid-2 install__grid">
        <?= view('admin/partials/field', ['name' => 'db_host', 'label' => t('install.db_host'), 'value' => old('db_host', 'localhost'), 'required' => true, 'attrs' => 'dir="ltr" autofocus', 'hint' => t('install.db_host_hint')]) ?>
        <?= view('admin/partials/field', ['name' => 'db_port', 'label' => t('install.db_port'), 'type' => 'number', 'value' => old('db_port', '3306'), 'required' => true, 'attrs' => 'min="1" max="65535"']) ?>
      </div>
      <?= view('admin/partials/field', ['name' => 'db_name', 'label' => t('install.db_name'), 'value' => old('db_name'), 'required' => true, 'attrs' => 'dir="ltr"', 'hint' => t('install.db_name_hint')]) ?>
      <div class="grid-2 install__grid">
        <?= view('admin/partials/field', ['name' => 'db_user', 'label' => t('install.db_user'), 'value' => old('db_user'), 'required' => true, 'attrs' => 'dir="ltr"']) ?>
        <?= view('admin/partials/field', ['name' => 'db_pass', 'label' => t('install.db_pass'), 'type' => 'password', 'attrs' => 'autocomplete="new-password"']) ?>
      </div>
      <details class="install__more">
        <summary><?= e(t('install.site_settings')) ?></summary>
        <div class="stack install__more-body">
          <?= view('admin/partials/field', ['name' => 'app_url', 'label' => t('install.app_url'), 'type' => 'url', 'value' => old('app_url', $detectedUrl), 'hint' => t('install.app_url_hint')]) ?>
          <?= view('admin/partials/field', ['name' => 'app_timezone', 'label' => t('install.timezone'), 'type' => 'select', 'value' => old('app_timezone', 'Asia/Qatar'), 'options' => array_combine($timezones, $timezones)]) ?>
        </div>
      </details>
      <button class="btn btn--primary btn--block" type="submit" data-busy="<?= e(t('install.db_busy')) ?>"><?= icon('lightning') ?><span><?= e(t('install.db_submit')) ?></span></button>
      <p class="hint install__hint"><?= e(t('install.db_note')) ?></p>
    </form>

  <?php elseif ($step === 3): ?>
    <h1><?= e(t('setup.title')) ?></h1>
    <p class="auth__sub"><?= e(t('install.admin_sub')) ?></p>
    <form method="post" action="<?= e($base) ?>/install" class="stack">
      <?= csrf_field() ?><input type="hidden" name="step" value="admin">
      <?= view('admin/partials/field', ['name' => 'name', 'label' => t('setup.name'), 'value' => old('name'), 'required' => true, 'attrs' => 'autofocus']) ?>
      <?= view('admin/partials/field', ['name' => 'email', 'label' => t('login.email'), 'type' => 'email', 'value' => old('email'), 'required' => true]) ?>
      <?= view('admin/partials/field', ['name' => 'password', 'label' => t('login.password'), 'type' => 'password', 'required' => true, 'hint' => t('install.password_hint'), 'attrs' => 'autocomplete="new-password" minlength="10"']) ?>
      <?= view('admin/partials/field', ['name' => 'password_confirmation', 'label' => t('fields.password_confirmation'), 'type' => 'password', 'required' => true, 'attrs' => 'autocomplete="new-password"']) ?>
      <?= view('admin/partials/field', ['name' => 'lang', 'label' => t('profile.lang'), 'type' => 'select', 'value' => $lang, 'options' => ['en' => 'English', 'ar' => 'العربية']]) ?>
      <button class="btn btn--primary btn--block" type="submit"><?= icon('crown-simple') ?><span><?= e(t('setup.submit')) ?></span></button>
    </form>

  <?php else: ?>
    <div class="install__done">
      <span class="install__done-ico"><?= icon('seal-check') ?></span>
      <h1><?= e(t('install.done_title')) ?></h1>
      <p class="auth__sub"><?= e(t('install.done_sub')) ?></p>
    </div>
    <div class="stack-sm">
      <a class="btn btn--primary btn--block" href="<?= e(admin_url()) ?>"><?= icon('squares-four') ?><span><?= e(t('install.open_admin')) ?></span></a>
      <a class="btn btn--ghost btn--block" href="<?= e($base) ?>/" target="_blank" rel="noopener"><?= icon('globe') ?><span><?= e(t('install.open_site')) ?></span></a>
    </div>
    <ul class="install__next">
      <li><?= icon('check') ?><span><?= e(t('install.next_1')) ?></span></li>
      <li><?= icon('check') ?><span><?= e(t('install.next_2')) ?></span></li>
      <li><?= icon('check') ?><span><?= e(t('install.next_3')) ?></span></li>
    </ul>
  <?php endif; ?>
</main>
<script>
document.querySelectorAll('form').forEach(function (f) {
  f.addEventListener('submit', function () {
    var b = f.querySelector('button[type=submit]');
    if (!b) return;
    b.classList.add('is-busy');
    if (b.dataset.busy) b.querySelector('span').textContent = b.dataset.busy;
  });
});
</script>
</body>
</html>
