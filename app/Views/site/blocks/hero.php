<?php /** @var array $b @var string $anchor @var string $theme */
$heading = tr($b['heading'] ?? '');
$sub = tr($b['subheading'] ?? '');
$eyebrow = tr($b['eyebrow'] ?? '');
$imgs = array_values(array_filter([$b['image'] ?? '', $b['image_2'] ?? '', $b['image_3'] ?? '']));
$alt = tr($b['image_alt'] ?? '') ?: setting_tr('site_name');
$p1 = tr($b['primary_text'] ?? ''); $l1 = (string)($b['primary_link'] ?? '');
$p2 = tr($b['secondary_text'] ?? ''); $l2 = (string)($b['secondary_link'] ?? '');
$href = fn(string $l) => preg_match('#^(https?:|mailto:|tel:)#i', $l) ? $l : url($l ?: '/');
$stats = is_array($b['stats'] ?? null) ? array_filter($b['stats'], fn($s) => ($s['value'] ?? '') !== '') : [];
$wa = whatsapp_link();
?>
<section class="hero hero--stage <?= $imgs ? '' : 'hero--solo' ?>" id="<?= e($anchor) ?>">
  <canvas class="hero__particles" data-particles aria-hidden="true"></canvas>
  <div class="hero__ghost" aria-hidden="true" data-ghost>أثر</div>
  <div class="container hero__inner">
    <?php if ($eyebrow !== ''): ?><p class="eyebrow hero__eyebrow" data-hero="0"><?= e($eyebrow) ?></p><?php endif; ?>
    <h1 class="display display--hero hero__title" data-split data-hero="1"><?= nl2br(e($heading)) ?></h1>
    <div class="hero__row">
      <div class="hero__text">
        <?php if ($sub !== ''): ?><p class="lede hero__lede" data-hero="2"><?= nl2br(e($sub)) ?></p><?php endif; ?>
        <div class="btn-row hero__actions" data-hero="3">
          <?php if ($p1 !== ''): ?><a class="btn btn--pill btn--brass btn--lg" href="<?= e($href($l1)) ?>" data-track="cta" data-label="hero-primary" data-magnetic><span><?= e($p1) ?></span><i class="btn__ico"><?= icon('arrow-up-right') ?></i></a><?php endif; ?>
          <?php if ($p2 !== ''): ?><a class="btn btn--pill btn--ghost btn--lg" href="<?= e($href($l2)) ?>" data-track="cta" data-label="hero-secondary" data-magnetic><span><?= e($p2) ?></span></a>
          <?php elseif ($wa !== ''): ?><a class="btn btn--pill btn--ghost btn--lg" href="<?= e($wa) ?>" target="_blank" rel="noopener" data-track="whatsapp" data-label="hero" data-magnetic><span><?= e(t('site.whatsapp')) ?></span><i class="btn__ico"><?= icon('brand-whatsapp') ?></i></a><?php endif; ?>
        </div>
      </div>
      <?php if ($stats): ?>
        <ul class="hero__stats" data-hero="4">
          <?php foreach ($stats as $s): ?>
            <li><strong data-count><?= e($s['value']) ?></strong><span><?= e(tr($s['label'] ?? '')) ?></span></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <?php if ($imgs): ?>
      <div class="hero__gallery" data-hero="5" data-tilt>
        <div class="hero__tilt" data-tilt-layer>
          <?php foreach ($imgs as $i => $src): ?>
            <figure class="hero__card frame hero__card--<?= $i + 1 ?>" data-parallax="<?= [0.12, 0.2, 0.08][$i] ?>" data-depth="<?= [14, 24, 10][$i] ?>">
              <img src="<?= e(media_url($src)) ?>" alt="<?= e($alt) ?>" <?= $i === 0 ? 'fetchpriority="high" loading="eager"' : 'loading="lazy"' ?> width="640" height="800">
            </figure>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
  <a class="hero__scroll" href="#<?= e($anchor) ?>-next" aria-label="<?= e(t('site.scroll')) ?>"><span><?= e(t('site.scroll')) ?></span><i></i></a>
  <span id="<?= e($anchor) ?>-next" class="anchor-target"></span>
</section>
