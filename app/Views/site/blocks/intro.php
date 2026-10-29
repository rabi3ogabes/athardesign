<?php /** @var array $b @var string $anchor @var string $theme */
$img = (string)($b['image'] ?? '');
$bt = tr($b['button_text'] ?? ''); $bl = (string)($b['button_link'] ?? '');
?>
<section class="section section--<?= e($theme) ?> intro" id="<?= e($anchor) ?>">
  <div class="container">
    <div class="intro__grid <?= $img === '' ? 'intro__grid--solo' : '' ?>">
      <div class="intro__text">
        <?php if (tr($b['eyebrow'] ?? '') !== ''): ?><p class="eyebrow" data-reveal><?= e(tr($b['eyebrow'])) ?></p><?php endif; ?>
        <?php if (tr($b['heading'] ?? '') !== ''): ?><h2 class="display display--lg" data-reveal><?= nl2br(e(tr($b['heading']))) ?></h2><?php endif; ?>
        <div class="prose prose--lede" data-reveal><?= tr($b['body'] ?? '') ?></div>
        <?php if ($bt !== ''): ?><div class="btn-row" data-reveal><a class="btn btn--pill btn--outline" href="<?= e(preg_match('#^https?:#', $bl) ? $bl : url($bl ?: '/')) ?>"><span><?= e($bt) ?></span><i class="btn__ico"><?= icon('arrow-up-right') ?></i></a></div><?php endif; ?>
      </div>
      <?php if ($img !== ''): ?>
        <figure class="intro__media frame" data-reveal>
          <img src="<?= e(media_url($img)) ?>" alt="<?= e(tr($b['image_alt'] ?? '')) ?>" loading="lazy" data-parallax="0.08" width="720" height="900">
        </figure>
      <?php endif; ?>
    </div>
  </div>
</section>
