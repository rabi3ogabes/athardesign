<?php /** @var array $b @var string $anchor @var string $theme */
$wa = whatsapp_link();
$phone = trim((string)setting('phone', ''));
$email = trim((string)setting('email', ''));
$map = trim((string)setting('map_link', ''));
$showForm = $b['show_form'] ?? true; $showInfo = $b['show_info'] ?? true; $showMap = $b['show_map'] ?? true;
?>
<section class="section section--<?= e($theme) ?> contact" id="<?= e($anchor) ?>">
  <div class="container">
    <div class="section__head">
      <?php if (tr($b['eyebrow'] ?? '') !== ''): ?><p class="eyebrow" data-reveal><?= e(tr($b['eyebrow'])) ?></p><?php endif; ?>
      <?php if (tr($b['heading'] ?? '') !== ''): ?><h2 class="display display--lg" data-reveal><?= nl2br(e(tr($b['heading']))) ?></h2><?php endif; ?>
      <?php if (tr($b['intro'] ?? '') !== ''): ?><p class="lede" data-reveal><?= e(tr($b['intro'])) ?></p><?php endif; ?>
    </div>
    <div class="contact__grid <?= !$showInfo ? 'contact__grid--solo' : '' ?>">
      <?php if ($showForm): ?>
        <div class="contact__form frame" data-reveal><div class="frame__inner"><?= view('site/partials/contact-form', []) ?></div></div>
      <?php endif; ?>
      <?php if ($showInfo): ?>
        <aside class="contact__info" data-reveal>
          <?php if ($wa !== ''): ?>
            <a class="contact__card contact__card--wa" href="<?= e($wa) ?>" target="_blank" rel="noopener" data-track="whatsapp" data-label="contact-page">
              <?= icon('brand-whatsapp') ?><span><strong><?= e(t('site.chat_whatsapp')) ?></strong><em dir="ltr">+<?= e(preg_replace('/\D/', '', (string)setting('whatsapp_number'))) ?></em></span><?= icon('arrow-up-right', 'contact__arrow') ?>
            </a>
          <?php endif; ?>
          <?php if ($phone !== ''): ?>
            <a class="contact__card" href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>" data-track="phone" data-label="contact-page"><?= icon('phone') ?><span><strong><?= e(t('site.call_us')) ?></strong><em dir="ltr"><?= e($phone) ?></em></span></a>
          <?php endif; ?>
          <?php if ($email !== ''): ?>
            <a class="contact__card" href="mailto:<?= e($email) ?>" data-track="email" data-label="contact-page"><?= icon('envelope-simple') ?><span><strong><?= e(t('site.email_us')) ?></strong><em dir="ltr"><?= e($email) ?></em></span></a>
          <?php endif; ?>
          <?php if (setting_tr('address') !== ''): ?>
            <div class="contact__card contact__card--static"><?= icon('map-pin') ?><span><strong><?= e(t('site.address')) ?></strong><em><?= e(setting_tr('address')) ?></em><?php if ($showMap && $map !== ''): ?><a class="link-arrow link-arrow--sm" href="<?= e($map) ?>" target="_blank" rel="noopener" data-track="map" data-label="contact-page"><?= e(t('site.open_map')) ?><?= icon('arrow-up-right') ?></a><?php endif; ?></span></div>
          <?php endif; ?>
          <?php if (setting_tr('working_hours') !== ''): ?>
            <div class="contact__card contact__card--static"><?= icon('clock') ?><span><strong><?= e(t('site.working_hours')) ?></strong><em><?= e(setting_tr('working_hours')) ?></em></span></div>
          <?php endif; ?>
          <?php $socials = App\Models\Settings::socials(); if ($socials): ?>
            <div class="contact__social"><strong><?= e(t('site.follow_us')) ?></strong>
              <div class="socials"><?php foreach ($socials as $k => $u): ?><a href="<?= e($u) ?>" target="_blank" rel="noopener" data-track="<?= e($k) ?>" data-label="contact-page" aria-label="<?= e(ucfirst($k)) ?>"><?= icon('brand-' . $k) ?></a><?php endforeach; ?></div>
            </div>
          <?php endif; ?>
        </aside>
      <?php endif; ?>
    </div>
  </div>
</section>
