<?php /** @var array $b @var string $anchor @var string $theme */
use App\Models\Services;
$limit = max(1, (int)($b['limit'] ?? 6));
$items = ($b['source'] ?? 'all') === 'featured' ? Services::featured($limit) : array_slice(Services::published(), 0, $limit);
if (!$items) return;
?>
<section class="section section--<?= e($theme) ?> services" id="<?= e($anchor) ?>">
  <div class="container">
    <div class="section__head section__head--row">
      <div>
        <?php if (tr($b['eyebrow'] ?? '') !== ''): ?><p class="eyebrow" data-reveal><?= e(tr($b['eyebrow'])) ?></p><?php endif; ?>
        <?php if (tr($b['heading'] ?? '') !== ''): ?><h2 class="display display--lg" data-reveal><?= nl2br(e(tr($b['heading']))) ?></h2><?php endif; ?>
        <?php if (tr($b['intro'] ?? '') !== ''): ?><p class="lede" data-reveal><?= e(tr($b['intro'])) ?></p><?php endif; ?>
      </div>
      <?php if (!empty($b['show_link'])): ?><a class="link-arrow" href="<?= e(url('/services')) ?>" data-reveal><?= e(t('site.all_services')) ?><?= icon('arrow-up-right') ?></a><?php endif; ?>
    </div>
    <ul class="service-list" data-service-list>
      <?php foreach ($items as $s): ?>
        <li data-reveal>
          <a href="<?= e(url('/services/' . $s['slug'])) ?>" data-track="service" data-label="<?= e($s['slug']) ?>" data-preview="<?= e($s['image'] ? media_url($s['image']) : '') ?>">
            <span class="service-list__ico"><?= icon($s['icon'] ?: 'sparkle') ?></span>
            <span class="service-list__main">
              <span class="service-list__title"><?= e(tr($s['title'])) ?></span>
              <span class="service-list__desc"><?= e(str_limit(tr($s['excerpt']), 140)) ?></span>
            </span>
            <?= icon('arrow-up-right', 'service-list__arrow') ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="service-preview" aria-hidden="true" data-service-preview><img alt="" loading="lazy"></div>
  </div>
</section>
