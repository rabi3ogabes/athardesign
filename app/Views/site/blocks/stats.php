<?php /** @var array $b @var string $anchor @var string $theme */
$items = is_array($b['items'] ?? null) ? array_filter($b['items'], fn($i) => ($i['value'] ?? '') !== '') : [];
if (!$items) return;
?>
<section class="section section--<?= e($theme) ?> section--tight stats" id="<?= e($anchor) ?>">
  <div class="container">
    <?php if (tr($b['heading'] ?? '') !== ''): ?><h2 class="display display--md stats__h" data-reveal><?= e(tr($b['heading'])) ?></h2><?php endif; ?>
    <ul class="stats__list">
      <?php foreach ($items as $s): ?>
        <li data-reveal><strong data-count><?= e($s['value']) ?></strong><span><?= e(tr($s['label'] ?? '')) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
