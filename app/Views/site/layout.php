<?php
/** @var string $head @var string $content @var string $lang @var string $dir @var string $path @var array $nav @var array $footerPages @var array $socials @var string $bodyClass */
use App\Core\I18n;
use App\Core\Session;

$siteName = setting_tr('site_name');
$logo = (string)setting('logo', '') ?: ('/assets/img/logo' . (lang() === 'en' ? '-en' : '') . (in_array((string)setting('theme', 'ink'), ['ivory', 'sand'], true) ? '-light' : '') . '.svg');
$logoDefault = (string)setting('logo', '') === '';
$favicon = (string)setting('favicon', '');
$wa = whatsapp_link();
$otherLang = I18n::other();
$switchUrl = I18n::url($path, $otherLang);
$ctaText = setting_tr('header_cta_text');
$ctaLink = (string)setting('header_cta_link', '/contact');
$ctaHref = preg_match('#^https?://#', $ctaLink) ? $ctaLink : url($ctaLink);
$ga = trim((string)setting('ga4_id', ''));
$gtm = trim((string)setting('gtm_id', ''));
$phone = trim((string)setting('phone', ''));
$email = trim((string)setting('email', ''));
$isHome = ($path === '/');
$year = date('Y');
$themes = App\Models\Settings::themes();
$theme = (string)setting('theme', 'ink'); if (!isset($themes[$theme])) $theme = 'ink';
$accent = (string)setting('accent_color', '');
$accent = preg_match('/^#[0-9A-Fa-f]{6}$/', $accent) ? $accent : '';
$fontKeys = array_keys(App\Models\Settings::fonts());
$fontIsFile = fn(string $p) => $p !== '' && (bool)preg_match('#^/uploads/fonts/font-[a-f0-9]{12}\.(woff2|woff|ttf|otf)$#', $p);
$fontFiles = []; // uploaded custom typeface: css weight => public path
foreach (App\Models\Settings::fontSlots() as [$slotKey, $weight]) {
    $p = (string)setting($slotKey, '');
    if ($fontIsFile($p)) $fontFiles[$weight] = $p;
}
$customOk = isset($fontFiles['400']);
$headingFont = (string)setting('heading_font', 'lusail');
if (!in_array($headingFont, $fontKeys, true) || ($headingFont === 'custom' && !$customOk)) $headingFont = 'lusail';
$bodyFont = (string)setting('body_font', 'lusail');
if (!in_array($bodyFont, $fontKeys, true) || ($bodyFont === 'custom' && !$customOk)) $bodyFont = 'lusail';
$fontFormat = fn(string $p) => ['woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype', 'otf' => 'opentype'][pathinfo($p, PATHINFO_EXTENSION)] ?? 'truetype';
$preloadFont = ($headingFont === 'lusail' || $bodyFont === 'lusail') ? '/assets/fonts/lusail/Lusail-Regular.woff2' : '/assets/fonts/f1.woff2';
$radius = setting('radius', 'soft') === 'sharp' ? 'sharp' : 'soft';
$themeBg = $themes[$theme]['bg'];
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>" data-base="<?= e(base_url()) ?>" data-lang="<?= e($lang) ?>" data-theme="<?= e($theme) ?>" data-fonts="<?= e($headingFont) ?>" data-body="<?= e($bodyFont) ?>" data-radius="<?= e($radius) ?>" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?= $head ?>

<meta name="theme-color" content="<?= e($themeBg) ?>">
<?php if ($favicon !== ''): ?>
<link rel="icon" href="<?= e(media_url($favicon)) ?>">
<link rel="apple-touch-icon" href="<?= e(media_url($favicon)) ?>">
<?php else: ?>
<link rel="icon" href="<?= e(base_url()) ?>/favicon.ico" sizes="32x32">
<link rel="icon" href="<?= e(base_url()) ?>/assets/img/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= e(base_url()) ?>/assets/img/apple-touch-icon.png">
<link rel="manifest" href="<?= e(base_url()) ?>/site.webmanifest">
<?php endif; ?>
<link rel="preload" href="<?= e(base_url() . $preloadFont) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('assets/css/fonts.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/site.css')) ?>">
<?php if ($headingFont === 'custom' || $bodyFont === 'custom'): ?>
<link rel="preload" href="<?= e(base_url() . $fontFiles['400']) ?>" as="font" type="font/<?= e(pathinfo($fontFiles['400'], PATHINFO_EXTENSION)) ?>" crossorigin>
<style><?php foreach ($fontFiles as $weight => $p): ?>@font-face{font-family:'AtharCustom';font-weight:<?= e($weight) ?>;font-display:swap;src:url('<?= e(base_url() . $p) ?>') format('<?= e($fontFormat($p)) ?>')}<?php endforeach; ?>html[data-fonts="custom"]{--font-display:'AtharCustom','Lusail','Amiri',sans-serif}html[data-body="custom"]{--font-body:'AtharCustom','Lusail','IBM Plex Sans Arabic',system-ui,sans-serif}</style>
<?php endif; ?>
<?php if ($accent !== ''): ?><style>:root{--brass:<?= e($accent) ?>;--brass-2:color-mix(in srgb,<?= e($accent) ?> 72%,white)}</style><?php endif; ?>
<script>document.documentElement.classList.replace('no-js','js');</script>
<?php if ($gtm !== ''): ?>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e($gtm) ?>');</script>
<?php endif; ?>
<?php if ($ga !== ''): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($ga) ?>');</script>
<?php endif; ?>
<?= (string)setting('head_scripts', '') ?>
</head>
<body class="<?= e(trim('site ' . $bodyClass)) ?>">
<?php if ($gtm !== ''): ?><noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($gtm) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript><?php endif; ?>
<a class="skip-link" href="#main"><?= e(t('site.skip')) ?></a>
<div class="grain" aria-hidden="true"></div>
<div class="cursor" aria-hidden="true"><span class="cursor__dot"></span><span class="cursor__ring"></span></div>

<header class="site-header" id="top">
  <div class="nav-pill" data-nav>
    <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e($siteName) ?>">
      <?php if ($logo !== ''): ?>
        <img src="<?= e(media_url($logo)) ?>" alt="<?= e($siteName) ?>"<?= $logoDefault ? ' width="110"' : '' ?> height="40" class="brand__img">
      <?php else: ?>
        <span class="brand__mark" aria-hidden="true">أثر</span>
        <span class="brand__name"><?= e($siteName) ?></span>
      <?php endif; ?>
    </a>
    <nav class="nav-links" aria-label="<?= e(t('site.menu')) ?>">
      <?php foreach ($nav as $p): $href = url('/' . $p['slug']); $active = (rtrim($path, '/') === '/' . $p['slug']); ?>
        <a href="<?= e($href) ?>" <?= $active ? 'aria-current="page"' : '' ?>><?= e(tr($p['nav_label']) ?: tr($p['title'])) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="nav-actions">
      <?php if (I18n::englishEnabled()): ?>
        <a class="lang-switch" href="<?= e($switchUrl) ?>" hreflang="<?= e($otherLang) ?>" lang="<?= e($otherLang) ?>" aria-label="<?= e(t('site.lang_switch')) ?>"><?= e(t('site.lang_switch_code')) ?></a>
      <?php endif; ?>
      <?php if ($ctaText !== ''): ?>
        <a class="btn btn--pill btn--brass nav-cta" href="<?= e($ctaHref) ?>" data-track="cta" data-label="header-cta"><span><?= e($ctaText) ?></span><i class="btn__ico"><?= icon('arrow-up-right') ?></i></a>
      <?php endif; ?>
      <button class="burger" type="button" aria-label="<?= e(t('site.menu')) ?>" aria-expanded="false" aria-controls="menu" data-menu-toggle><span></span><span></span></button>
    </div>
  </div>
</header>

<div class="menu" id="menu" aria-hidden="true">
  <div class="menu__inner">
    <nav class="menu__links" aria-label="<?= e(t('site.menu')) ?>">
      <a href="<?= e(url('/')) ?>" style="--i:0"><?= e(t('site.home')) ?></a>
      <?php foreach ($nav as $i => $p): ?>
        <a href="<?= e(url('/' . $p['slug'])) ?>" style="--i:<?= $i + 1 ?>"><?= e(tr($p['nav_label']) ?: tr($p['title'])) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="menu__aside">
      <?php if ($wa !== ''): ?>
        <a class="btn btn--pill btn--ivory" href="<?= e($wa) ?>" target="_blank" rel="noopener" data-track="whatsapp" data-label="menu"><span><?= e(t('site.chat_whatsapp')) ?></span><i class="btn__ico"><?= icon('brand-whatsapp') ?></i></a>
      <?php endif; ?>
      <?php if ($socials): ?>
        <div class="socials">
          <?php foreach ($socials as $k => $u): ?>
            <a href="<?= e($u) ?>" target="_blank" rel="noopener" data-track="<?= e($k) ?>" data-label="menu" aria-label="<?= e(ucfirst($k)) ?>"><?= icon('brand-' . $k) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if (I18n::englishEnabled()): ?>
        <a class="menu__lang" href="<?= e($switchUrl) ?>" lang="<?= e($otherLang) ?>"><?= e(t('site.lang_switch')) ?></a>
      <?php endif; ?>
    </div>
  </div>
</div>

<main id="main" class="site-main">
<?php foreach (Session::flashes() as $f): ?>
  <div class="flash flash--<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
<?php endforeach; ?>
<?= $content ?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer__top">
      <div class="footer__brand">
        <a class="brand brand--lg" href="<?= e(url('/')) ?>">
          <?php if ($logo !== ''): ?>
            <img src="<?= e(media_url($logo)) ?>" alt="<?= e($siteName) ?>"<?= $logoDefault ? ' width="154"' : '' ?> height="56" loading="lazy" decoding="async" class="brand__img brand__img--lg">
          <?php else: ?>
            <span class="brand__mark" aria-hidden="true">أثر</span>
            <span class="brand__name"><?= e($siteName) ?></span>
          <?php endif; ?>
        </a>
        <p class="footer__text"><?= e(setting_tr('footer_text')) ?></p>
        <?php if ($socials): ?>
          <div class="socials socials--footer">
            <?php foreach ($socials as $k => $u): ?>
              <a href="<?= e($u) ?>" target="_blank" rel="noopener" data-track="<?= e($k) ?>" data-label="footer" aria-label="<?= e(ucfirst($k)) ?>"><?= icon('brand-' . $k) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="footer__col">
        <h2 class="footer__h"><?= e(t('site.quick_links')) ?></h2>
        <ul class="footer__list">
          <li><a href="<?= e(url('/')) ?>"><?= e(t('site.home')) ?></a></li>
          <?php foreach ($nav as $p): ?>
            <li><a href="<?= e(url('/' . $p['slug'])) ?>"><?= e(tr($p['nav_label']) ?: tr($p['title'])) ?></a></li>
          <?php endforeach; ?>
          <?php foreach ($footerPages as $p): if (!empty($p['show_in_nav'])) continue; ?>
            <li><a href="<?= e(url('/' . $p['slug'])) ?>"><?= e(tr($p['nav_label']) ?: tr($p['title'])) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="footer__col">
        <h2 class="footer__h"><?= e(t('site.our_services')) ?></h2>
        <ul class="footer__list">
          <?php foreach (array_slice(App\Models\Services::published(), 0, 7) as $s): ?>
            <li><a href="<?= e(url('/services/' . $s['slug'])) ?>"><?= e(tr($s['title'])) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="footer__col footer__col--contact">
        <h2 class="footer__h"><?= e(t('site.get_in_touch')) ?></h2>
        <ul class="footer__list footer__list--contact">
          <?php if ($wa !== ''): ?><li><a href="<?= e($wa) ?>" target="_blank" rel="noopener" data-track="whatsapp" data-label="footer"><?= icon('brand-whatsapp') ?><span><?= e(t('site.whatsapp')) ?></span></a></li><?php endif; ?>
          <?php if ($phone !== ''): ?><li><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>" data-track="phone" data-label="footer"><?= icon('phone') ?><span dir="ltr"><?= e($phone) ?></span></a></li><?php endif; ?>
          <?php if ($email !== ''): ?><li><a href="mailto:<?= e($email) ?>" data-track="email" data-label="footer"><?= icon('envelope-simple') ?><span><?= e($email) ?></span></a></li><?php endif; ?>
          <?php if (setting_tr('address') !== ''): ?><li><?php $map = trim((string)setting('map_link', '')); ?><?php if ($map !== ''): ?><a href="<?= e($map) ?>" target="_blank" rel="noopener" data-track="map" data-label="footer"><?= icon('map-pin') ?><span><?= e(setting_tr('address')) ?></span></a><?php else: ?><span class="footer__plain"><?= icon('map-pin') ?><span><?= e(setting_tr('address')) ?></span></span><?php endif; ?></li><?php endif; ?>
          <?php if (setting_tr('working_hours') !== ''): ?><li><span class="footer__plain"><?= icon('clock') ?><span><?= e(setting_tr('working_hours')) ?></span></span></li><?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="footer__bottom">
      <p>© <?= e($year) ?> <?= e($siteName) ?>. <?= e(t('site.rights')) ?></p>
      <p class="footer__loc"><?= e(t('site.doha_qatar')) ?></p>
    </div>
  </div>
</footer>

<?php if ($wa !== '' && setting('show_whatsapp_button', true)): ?>
  <a class="wa-float" href="<?= e($wa) ?>" target="_blank" rel="noopener" data-track="whatsapp" data-label="floating" aria-label="<?= e(t('site.chat_whatsapp')) ?>">
    <?= icon('brand-whatsapp') ?><span class="wa-float__txt"><?= e(t('site.chat_whatsapp')) ?></span>
  </a>
<?php endif; ?>

<script src="<?= e(asset('assets/js/vendor/gsap.min.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/vendor/ScrollTrigger.min.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/site.js')) ?>" defer></script>
</body>
</html>
