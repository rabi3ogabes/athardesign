<?php /** @var array $package @var array $siblings @var string $catLabel @var string $tierLabel @var string $listingPath */
use App\Models\Packages;
$p = $package;
$tiers = Packages::tierLabels();
$price = Packages::price($p);
$wa = Packages::whatsappLink($p);
$msg = Packages::whatsappMessage($p);
$features = is_array($p['features']) ? $p['features'] : [];
$included = array_filter($features, fn($f) => filter_var($f['included'] ?? true, FILTER_VALIDATE_BOOL) && tr($f) !== '');
$excluded = array_filter($features, fn($f) => !filter_var($f['included'] ?? true, FILTER_VALIDATE_BOOL) && tr($f) !== '');
$cta = tr($p['cta_text']) ?: t('site.package_order');
?>
<section class="section section--hero-sub pk-hero">
  <div class="container">
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="<?= e(url('/')) ?>"><?= e(t('site.home')) ?></a><span>/</span><a href="<?= e(url($listingPath)) ?>"><?= e(t('site.packages')) ?></a><span>/</span><span><?= e($catLabel) ?></span>
    </nav>
    <div class="pk-hero__grid">
      <div class="pk-hero__text">
        <p class="eyebrow" data-reveal><?= e($catLabel) ?></p>
        <div class="pk__tier pk__tier--lg" data-reveal><span class="pk__medal pk__medal--<?= e($p['tier']) ?>" aria-hidden="true"><?= icon($p['tier'] === 'premium' ? 'crown-simple' : ($p['tier'] === 'elite' ? 'medal' : 'sparkle')) ?></span><span><?= e($tierLabel) ?></span><?php if (!empty($p['is_highlighted'])): ?><em class="pk__badge pk__badge--inline"><?= icon('star') ?><?= e(tr($p['badge']) ?: t('site.package_most_popular')) ?></em><?php endif; ?></div>
        <h1 class="display display--xl" data-reveal><?= e(tr($p['name'])) ?></h1>
        <?php if (tr($p['tagline']) !== ''): ?><p class="lede" data-reveal><?= e(tr($p['tagline'])) ?></p><?php endif; ?>
        <div class="btn-row" data-reveal>
          <?php if ($wa !== ''): ?>
            <a class="btn btn--pill btn--wa btn--lg" href="<?= e($wa) ?>" target="_blank" rel="noopener" data-track="whatsapp" data-label="package-<?= e($p['slug']) ?>" data-magnetic><span><?= e($cta) ?></span><i class="btn__ico"><?= icon('brand-whatsapp') ?></i></a>
          <?php endif; ?>
          <a class="btn btn--pill btn--ghost btn--lg" href="<?= e(url('/contact')) ?>?package=<?= e(rawurlencode($p['slug'])) ?>" data-track="cta" data-label="package-<?= e($p['slug']) ?>"><span><?= e(t('site.contact')) ?></span><i class="btn__ico"><?= icon('arrow-up-right') ?></i></a>
        </div>
        <?php if ($wa !== ''): ?><p class="pk-hero__hint" data-reveal><?= icon('check-circle') ?><?= e(t('site.package_cta_hint')) ?></p><?php endif; ?>
      </div>
      <aside class="pk-hero__card" data-reveal data-tilt-layer>
        <div class="pk pk--<?= e($p['tier']) ?> pk--static <?= !empty($p['is_highlighted']) ? 'pk--hl' : '' ?>"><div class="pk__inner">
          <div class="pk__price pk__price--lg">
            <?php if ($price !== ''): ?>
              <?php if (tr($p['price_prefix']) !== ''): ?><span class="pk__prefix"><?= e(tr($p['price_prefix'])) ?></span><?php endif; ?>
              <span class="pk__amount" dir="ltr"><?= e($price) ?></span>
              <?php if (tr($p['period']) !== ''): ?><span class="pk__period"><?= e(tr($p['period'])) ?></span><?php endif; ?>
            <?php else: ?>
              <span class="pk__amount pk__amount--custom"><?= e(t('packages.custom_quote')) ?></span>
            <?php endif; ?>
          </div>
          <?php if (tr($p['delivery']) !== ''): ?><p class="pk__meta"><?= icon('timer') ?><span><?= e(t('site.package_delivery')) ?>: <?= e(tr($p['delivery'])) ?></span></p><?php endif; ?>
          <?php if (tr($p['price_note']) !== ''): ?><p class="pk__note"><?= e(tr($p['price_note'])) ?></p><?php endif; ?>
          <?php if ($wa !== ''): ?>
            <div class="pk__msg" dir="auto">
              <span class="pk__msg-label"><?= icon('brand-whatsapp') ?><?= e(t('site.package_ready')) ?></span>
              <p><?= nl2br(e($msg)) ?></p>
            </div>
          <?php endif; ?>
        </div></div>
      </aside>
    </div>
  </div>
</section>

<section class="section section--ivory">
  <div class="container">
    <div class="split">
      <div class="split__main">
        <?php if (trim(tr($p['body'])) !== ''): ?><div class="prose" data-reveal><?= tr($p['body']) ?></div><?php endif; ?>
        <?php if (!empty($p['image'])): ?><figure class="frame pk-figure" data-reveal><img src="<?= e(media_url($p['image'])) ?>" alt="<?= e(tr($p['name'])) ?>" loading="lazy" data-parallax="0.08"></figure><?php endif; ?>
      </div>
      <aside class="split__aside">
        <?php if ($included): ?>
          <div class="card card--list" data-reveal>
            <h2 class="card__h"><?= e(t('site.package_includes')) ?></h2>
            <ul class="checklist">
              <?php foreach ($included as $f): ?><li><?= icon('check-fat') ?><span><?= e(tr($f)) ?></span></li><?php endforeach; ?>
            </ul>
            <?php if ($excluded): ?>
              <h3 class="card__h card__h--sm"><?= e(t('site.package_not_included')) ?></h3>
              <ul class="checklist checklist--off">
                <?php foreach ($excluded as $f): ?><li><?= icon('minus') ?><span><?= e(tr($f)) ?></span></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</section>

<?php if ($siblings): ?>
<section class="section packages packages--compare">
  <div class="container">
    <div class="section__head section__head--row">
      <div><p class="eyebrow" data-reveal><?= e($catLabel) ?></p><h2 class="display display--lg" data-reveal><?= e(t('site.package_compare')) ?></h2></div>
      <a class="link-arrow" href="<?= e(url($listingPath)) ?>" data-reveal><?= e(t('site.all_packages')) ?><?= icon('arrow-up-right') ?></a>
    </div>
    <div class="pk-grid is-active" style="--n:<?= count($siblings) ?>">
      <?php foreach ($siblings as $i => $s): ?>
        <?= view('site/partials/package-card', ['p' => $s, 'tiers' => $tiers, 'compact' => true, 'i' => $i]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?= view('site/partials/cta-band', []) ?>
