<?php
use App\Core\I18n;
[$lang] = I18n::detect(current_path());
I18n::setLang($lang);
$dir = I18n::dir();
$wa = whatsapp_link();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?= e(t('site.maintenance_title')) ?> — <?= e(setting_tr('site_name')) ?></title>
<link rel="stylesheet" href="<?= e(base_url()) ?>/assets/css/fonts.css">
<link rel="stylesheet" href="<?= e(base_url()) ?>/assets/css/site.css">
</head>
<body class="site page-maintenance">
<main class="site-main">
<section class="section section--404"><div class="container">
  <p class="brand__mark brand__mark--big" aria-hidden="true">أثر</p>
  <h1 class="display display--xl"><?= e(t('site.maintenance_title')) ?></h1>
  <p class="lede"><?= e(t('site.maintenance_text')) ?></p>
  <?php if ($wa !== ''): ?><div class="btn-row"><a class="btn btn--pill btn--brass" href="<?= e($wa) ?>"><span><?= e(t('site.chat_whatsapp')) ?></span><i class="btn__ico"><?= icon('brand-whatsapp') ?></i></a></div><?php endif; ?>
</div></section>
</main>
</body>
</html>
