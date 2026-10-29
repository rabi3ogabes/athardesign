<?php /** @var array $b @var string $anchor @var string $theme */
$logos = is_array($b['logos'] ?? null) ? array_filter($b['logos'], fn($l) => !empty($l['image'])) : [];
if (!$logos) return;
?>
<section class="section section--<?= e($theme) ?> section--tight clients" id="<?= e($anchor) ?>">
  <div class="container">
    <?php if (tr($b['heading'] ?? '') !== ''): ?><p class="eyebrow eyebrow--center" data-reveal><?= e(tr($b['heading'])) ?></p><?php endif; ?>
    <ul class="logos" data-reveal>
      <?php foreach ($logos as $l): $inner = '<img src="' . e(media_url($l['image'])) . '" alt="' . e($l['name'] ?? '') . '" loading="lazy" height="40">'; ?>
        <li><?php if (!empty($l['link'])): ?><a href="<?= e($l['link']) ?>" target="_blank" rel="noopener" data-track="link" data-label="client-<?= e($l['name'] ?? '') ?>"><?= $inner ?></a><?php else: echo $inner; endif; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
