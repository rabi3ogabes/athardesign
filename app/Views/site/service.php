<?php /** @var array $service @var array $others @var array $related */
$wa = whatsapp_link(null, (lang() === 'ar' ? 'مرحبًا أثر، أود الاستفسار عن خدمة: ' : 'Hello Athar, I am interested in: ') . tr($service['title']));
$features = is_array($service['features']) ? $service['features'] : [];
?>
<section class="section section--hero-sub">
  <div class="container">
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="<?= e(url('/')) ?>"><?= e(t('site.home')) ?></a><span>/</span><a href="<?= e(url('/services')) ?>"><?= e(t('site.services')) ?></a>
    </nav>
    <div class="sub-hero">
      <div class="sub-hero__text">
        <?php if (!empty($service['icon'])): ?><div class="sub-hero__icon" data-reveal><?= icon($service['icon']) ?></div><?php endif; ?>
        <h1 class="display display--lg" data-reveal><?= e(tr($service['title'])) ?></h1>
        <p class="lede" data-reveal><?= e(tr($service['excerpt'])) ?></p>
        <div class="btn-row" data-reveal>
          <a class="btn btn--pill btn--brass" href="<?= e(url('/contact')) ?>?service=<?= e(rawurlencode($service['slug'])) ?>" data-track="cta" data-label="service-<?= e($service['slug']) ?>"><span><?= e(t('site.request_service')) ?></span><i class="btn__ico"><?= icon('arrow-up-right') ?></i></a>
          <?php if ($wa !== ''): ?><a class="btn btn--pill btn--ghost" href="<?= e($wa) ?>" target="_blank" rel="noopener" data-track="whatsapp" data-label="service-<?= e($service['slug']) ?>"><span><?= e(t('site.whatsapp')) ?></span><i class="btn__ico"><?= icon('brand-whatsapp') ?></i></a><?php endif; ?>
        </div>
      </div>
      <?php if (!empty($service['image'])): ?>
        <figure class="sub-hero__media frame" data-reveal>
          <img src="<?= e(media_url($service['image'])) ?>" alt="<?= e(tr($service['title'])) ?>" loading="eager" fetchpriority="high" data-parallax="0.12">
        </figure>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section section--ivory">
  <div class="container">
    <div class="split">
      <div class="split__main prose" data-reveal>
        <?= tr($service['body']) ?>
      </div>
      <?php if ($features): ?>
        <aside class="split__aside">
          <div class="card card--list" data-reveal>
            <h2 class="card__h"><?= e(t('site.what_included')) ?></h2>
            <ul class="checklist">
              <?php foreach ($features as $f): $txt = is_array($f) ? tr($f) : (string)$f; if ($txt === '') continue; ?>
                <li><?= icon('check') ?><span><?= e($txt) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </aside>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($related): ?>
<section class="section">
  <div class="container">
    <div class="section__head">
      <h2 class="display display--md" data-reveal><?= e(t('site.related_work')) ?></h2>
    </div>
    <div class="work-grid work-grid--3">
      <?php foreach ($related as $p): ?>
        <?= view('site/partials/project-card', ['p' => $p]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($others): ?>
<section class="section section--tight">
  <div class="container">
    <div class="section__head section__head--row">
      <h2 class="display display--sm" data-reveal><?= e(t('site.other_services')) ?></h2>
      <a class="link-arrow" href="<?= e(url('/services')) ?>"><?= e(t('site.all_services')) ?><?= icon('arrow-up-right') ?></a>
    </div>
    <ul class="service-list service-list--compact">
      <?php foreach ($others as $s): ?>
        <li data-reveal><a href="<?= e(url('/services/' . $s['slug'])) ?>" data-track="service" data-label="<?= e($s['slug']) ?>"><span class="service-list__ico"><?= icon($s['icon'] ?: 'sparkle') ?></span><span class="service-list__title"><?= e(tr($s['title'])) ?></span><?= icon('arrow-up-right', 'service-list__arrow') ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?= view('site/partials/cta-band', []) ?>
