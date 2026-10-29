<?php /** @var array $b @var string $anchor @var string $theme */
$bt = tr($b['button_text'] ?? ''); $bl = (string)($b['button_link'] ?? '');
$wa = !empty($b['show_whatsapp']) ? whatsapp_link() : '';
?>
<section class="section section--<?= e($theme) ?> section--cta" id="<?= e($anchor) ?>">
  <div class="container">
    <div class="cta-band" data-reveal>
      <?php if (tr($b['heading'] ?? '') !== ''): ?><h2 class="display display--lg"><?= nl2br(e(tr($b['heading']))) ?></h2><?php endif; ?>
      <?php if (tr($b['text'] ?? '') !== ''): ?><p class="lede"><?= nl2br(e(tr($b['text']))) ?></p><?php endif; ?>
      <div class="btn-row">
        <?php if ($bt !== ''): ?><a class="btn btn--pill btn--brass btn--lg" href="<?= e(preg_match('#^(https?:|mailto:|tel:)#i', $bl) ? $bl : url($bl ?: '/contact')) ?>" data-track="cta" data-label="<?= e($anchor) ?>" data-magnetic><span><?= e($bt) ?></span><i class="btn__ico"><?= icon('arrow-up-right') ?></i></a><?php endif; ?>
        <?php if ($wa !== ''): ?><a class="btn btn--pill btn--ghost btn--lg" href="<?= e($wa) ?>" target="_blank" rel="noopener" data-track="whatsapp" data-label="<?= e($anchor) ?>" data-magnetic><span><?= e(t('site.whatsapp')) ?></span><i class="btn__ico"><?= icon('brand-whatsapp') ?></i></a><?php endif; ?>
      </div>
    </div>
  </div>
</section>
