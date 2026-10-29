<?php /** @var array $b @var string $anchor */
$items = array_values(array_filter(array_map(fn($i) => tr($i['text'] ?? ''), is_array($b['items'] ?? null) ? $b['items'] : [])));
if (!$items) return;
$list = array_merge($items, $items);
?>
<div class="marquee" id="<?= e($anchor) ?>" aria-hidden="true">
  <div class="marquee__track">
    <?php foreach ($list as $t): ?><span class="marquee__item"><?= e($t) ?><i>✦</i></span><?php endforeach; ?>
  </div>
</div>
<ul class="sr-only"><?php foreach ($items as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ul>
