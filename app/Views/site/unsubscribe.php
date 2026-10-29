<?php /** @var bool $valid @var bool $done @var string $email @var string $token */
use App\Core\I18n;
$lang = I18n::lang(); $dir = I18n::dir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>" data-theme="<?= e((string)setting('theme', 'ink')) ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title><?= e(t('mail.unsub_title')) ?> — <?= e(setting_tr('site_name')) ?></title>
<link rel="stylesheet" href="<?= e(base_url()) ?>/assets/css/fonts.css">
<link rel="stylesheet" href="<?= e(base_url()) ?>/assets/css/site.css">
</head>
<body class="site page-unsubscribe">
<main class="site-main">
<section class="section section--404"><div class="container container--narrow">
  <p class="brand__mark brand__mark--big" aria-hidden="true">أثر</p>
  <h1 class="display display--lg"><?= e(t('mail.unsub_title')) ?></h1>
  <?php if (!$valid): ?>
    <p class="lede"><?= e(t('mail.unsub_invalid')) ?></p>
  <?php elseif ($done): ?>
    <p class="lede"><?= e(t('mail.unsub_done')) ?></p>
  <?php else: ?>
    <p class="lede"><?= e(t('mail.unsub_text', ['email' => $email])) ?></p>
    <form method="post" action="<?= e(base_url()) ?>/m/u/<?= e($token) ?>" class="btn-row">
      <button class="btn btn--pill btn--brass" type="submit"><span><?= e(t('mail.unsub_confirm')) ?></span></button>
    </form>
  <?php endif; ?>
  <div class="btn-row"><a class="btn btn--pill btn--ghost" href="<?= e(base_url()) ?>/"><span><?= e(t('site.back_home')) ?></span></a></div>
</div></section>
</main>
</body>
</html>
