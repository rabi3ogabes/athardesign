<?php /** @var array $b @var string $anchor @var string $theme */
use App\Models\Testimonials;
$items = Testimonials::published(max(1, (int)($b['limit'] ?? 6)));
if (!$items) return;
?>
<section class="section section--<?= e($theme) ?> testimonials" id="<?= e($anchor) ?>">
  <div class="container">
    <div class="section__head section__head--row">
      <div>
        <?php if (tr($b['eyebrow'] ?? '') !== ''): ?><p class="eyebrow" data-reveal><?= e(tr($b['eyebrow'])) ?></p><?php endif; ?>
        <h2 class="display display--lg" data-reveal><?= nl2br(e(tr($b['heading'] ?? '') ?: t('site.testimonials'))) ?></h2>
        <?php if (tr($b['intro'] ?? '') !== ''): ?><p class="lede" data-reveal><?= e(tr($b['intro'])) ?></p><?php endif; ?>
      </div>
      <?php if (count($items) > 2): ?>
        <div class="scroller__nav" data-reveal>
          <button type="button" class="scroller__btn" data-scroll="prev" aria-label="previous"><?= icon('arrow-left') ?></button>
          <button type="button" class="scroller__btn" data-scroll="next" aria-label="next"><?= icon('arrow-right') ?></button>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="scroller" data-scroller>
    <div class="container scroller__track">
      <?php foreach ($items as $tst): ?>
        <blockquote class="quote frame" data-reveal>
          <div class="quote__inner">
            <span class="quote__mark" aria-hidden="true">“</span>
            <p class="quote__text"><?= e(tr($tst['quote'])) ?></p>
            <footer class="quote__by">
              <?php if (!empty($tst['avatar'])): ?><img src="<?= e(media_url($tst['avatar'])) ?>" alt="" width="48" height="48" loading="lazy"><?php endif; ?>
              <div>
                <cite><?= e($tst['author_name']) ?></cite>
                <span><?= e(trim(tr($tst['author_role']) . ((tr($tst['author_role']) && $tst['company']) ? ' · ' : '') . ($tst['company'] ?? ''))) ?></span>
              </div>
              <?php if ((int)$tst['rating'] > 0): ?><span class="quote__stars" aria-label="<?= (int)$tst['rating'] ?>/5"><?php for ($i = 0; $i < (int)$tst['rating']; $i++) echo icon('star'); ?></span><?php endif; ?>
            </footer>
          </div>
        </blockquote>
      <?php endforeach; ?>
    </div>
  </div>
</section>
