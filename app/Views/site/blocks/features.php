<?php /** @var array $b @var string $anchor @var string $theme */
$items = is_array($b['items'] ?? null) ? array_filter($b['items'], fn($i) => tr($i['title'] ?? '') !== '') : [];
if (!$items) return;
?>
<section class="section section--<?= e($theme) ?> features" id="<?= e($anchor) ?>">
  <div class="container">
    <div class="section__head">
      <?php if (tr($b['eyebrow'] ?? '') !== ''): ?><p class="eyebrow" data-reveal><?= e(tr($b['eyebrow'])) ?></p><?php endif; ?>
      <?php if (tr($b['heading'] ?? '') !== ''): ?><h2 class="display display--lg" data-reveal><?= nl2br(e(tr($b['heading']))) ?></h2><?php endif; ?>
      <?php if (tr($b['intro'] ?? '') !== ''): ?><p class="lede" data-reveal><?= e(tr($b['intro'])) ?></p><?php endif; ?>
    </div>
    <div class="feature-grid">
      <?php foreach ($items as $it): ?>
        <div class="feature frame" data-reveal>
          <div class="feature__inner">
            <?php if (!empty($it['icon'])): ?><span class="feature__ico"><?= icon($it['icon']) ?></span><?php endif; ?>
            <h3 class="feature__title"><?= e(tr($it['title'])) ?></h3>
            <p class="feature__text"><?= nl2br(e(tr($it['text'] ?? ''))) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
