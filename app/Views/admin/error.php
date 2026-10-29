<?php /** @var int $status @var string $message */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Error <?= (int)$status ?></title>
<link rel="stylesheet" href="<?= e(base_url()) ?>/assets/css/fonts.css">
<link rel="stylesheet" href="<?= e(base_url()) ?>/assets/css/admin.css">
</head>
<body class="admin auth">
<main class="auth__box">
  <div class="auth__brand"><span class="auth__mark">أثر</span></div>
  <h1><?= (int)$status ?></h1>
  <p class="auth__sub"><?= $status === 404 ? 'Page not found.' : ($status === 403 ? 'You do not have permission to do this.' : ($status === 419 ? 'Your session expired. Go back and try again.' : (!empty($db) ? 'The database could not be reached. Check the DB_* values in .env and the log in storage/logs.' : 'Something went wrong.'))) ?></p>
  <?php if (!empty($message)): ?><pre class="debug"><?= e($message) ?></pre><?php endif; ?>
  <a class="btn btn--primary" href="<?= e(admin_url()) ?>">Dashboard</a>
</main>
</body>
</html>
