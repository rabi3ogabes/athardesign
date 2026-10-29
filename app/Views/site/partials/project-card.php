<?php /** @var array $p */ $cat = $p['category'] ?? null; $cover = $p['cover_image'] ?: ''; ?>
<a class="work-card frame" href="<?= e(url('/work/' . $p['slug'])) ?>" data-reveal data-track="project" data-label="<?= e($p['slug']) ?>" data-category="<?= e($cat['slug'] ?? '') ?>">
  <span class="work-card__media">
    <?php if ($cover !== ''): ?>
      <img src="<?= e(media_url($cover)) ?>" alt="<?= e(tr($p['title'])) ?>" loading="lazy" width="800" height="1000">
    <?php else: ?>
      <span class="work-card__placeholder" aria-hidden="true"><?= e(mb_substr(tr($p['title']), 0, 1)) ?></span>
    <?php endif; ?>
  </span>
  <span class="work-card__body">
    <span class="work-card__title"><?= e(tr($p['title'])) ?></span>
    <span class="work-card__meta">
      <?php if ($cat): ?><span><?= e(tr($cat['name'])) ?></span><?php endif; ?>
      <?php if (!empty($p['year'])): ?><span><?= e($p['year']) ?></span><?php endif; ?>
    </span>
  </span>
  <span class="work-card__arrow"><?= icon('arrow-up-right') ?></span>
</a>
