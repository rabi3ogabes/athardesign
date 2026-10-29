<?php /** @var int $status @var string $message */
$lang = 'ar'; $dir = 'rtl';
try { $lang = lang(); $dir = \App\Core\I18n::dir(); } catch (Throwable) {}
$title = t('site.error_title', [], $lang); $text = t('site.error_text', [], $lang);
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?= e($title) ?></title>
<link rel="stylesheet" href="<?= e(base_url()) ?>/assets/css/fonts.css">
<link rel="stylesheet" href="<?= e(base_url()) ?>/assets/css/site.css">
</head>
<body class="site page-error">
<main class="site-main">
<section class="section section--404"><div class="container">
  <p class="eyebrow"><?= e($status) ?></p>
  <h1 class="display display--xl"><?= e($title) ?></h1>
  <p class="lede"><?= e($text) ?></p>
  <?php if ($message !== ''): ?><pre class="debug"><?= e($message) ?></pre><?php endif; ?>
  <div class="btn-row"><a class="btn btn--pill btn--brass" href="<?= e(base_url()) ?>/"><span><?= e(t('site.back_home', [], $lang)) ?></span></a></div>
</div></section>
</main>
</body>
</html>
