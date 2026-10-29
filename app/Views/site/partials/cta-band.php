<?php $wa = whatsapp_link(); ?>
<section class="section section--cta">
  <div class="container">
    <div class="cta-band" data-reveal>
      <h2 class="display display--lg"><?= e(lang() === 'ar' ? 'جاهز لصناعة أثرك؟' : 'Ready to make your mark?') ?></h2>
      <p class="lede"><?= e(lang() === 'ar' ? 'أخبرنا عن مشروعك وسنعود إليك بخطة واضحة خلال يوم عمل.' : 'Tell us about your project and we will come back with a clear plan within one business day.') ?></p>
      <div class="btn-row">
        <a class="btn btn--pill btn--brass btn--lg" href="<?= e(url('/contact')) ?>" data-track="cta" data-label="cta-band"><span><?= e(t('site.start_project')) ?></span><i class="btn__ico"><?= icon('arrow-up-right') ?></i></a>
        <?php if ($wa !== ''): ?><a class="btn btn--pill btn--ghost btn--lg" href="<?= e($wa) ?>" target="_blank" rel="noopener" data-track="whatsapp" data-label="cta-band"><span><?= e(t('site.whatsapp')) ?></span><i class="btn__ico"><?= icon('brand-whatsapp') ?></i></a><?php endif; ?>
      </div>
    </div>
  </div>
</section>
