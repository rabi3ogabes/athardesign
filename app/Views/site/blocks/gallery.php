<?php /** @var array $b @var string $anchor @var string $theme */
$imgs = is_array($b['images'] ?? null) ? array_filter($b['images'], fn($i) => !empty($i['image'])) : [];
if (!$imgs) return;
?>
<section class="section section--<?= e($theme) ?> gallery-block" id="<?= e($anchor) ?>">
  <div class="container container--wide">
    <?php if (tr($b['heading'] ?? '') !== '' || tr($b['eyebrow'] ?? '') !== ''): ?>
      <div class="section__head">
        <?php if (tr($b['eyebrow'] ?? '') !== ''): ?><p class="eyebrow" data-reveal><?= e(tr($b['eyebrow'])) ?></p><?php endif; ?>
        <?php if (tr($b['heading'] ?? '') !== ''): ?><h2 class="display display--lg" data-reveal><?= nl2br(e(tr($b['heading']))) ?></h2><?php endif; ?>
        <?php if (tr($b['intro'] ?? '') !== ''): ?><p class="lede" data-reveal><?= e(tr($b['intro'])) ?></p><?php endif; ?>
      </div>
    <?php endif; ?>
    <div class="gallery">
      <?php foreach ($imgs as $g): ?>
        <figure class="gallery__item frame" data-reveal>
          <img src="<?= e(media_url($g['image'])) ?>" alt="<?= e(tr($g['alt'] ?? '')) ?>" loading="lazy">
          <?php if (tr($g['caption'] ?? '') !== ''): ?><figcaption><?= e(tr($g['caption'])) ?></figcaption><?php endif; ?>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
