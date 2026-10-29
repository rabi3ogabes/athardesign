<?php /** @var array $b @var string $anchor @var string $theme */ ?>
<section class="section section--<?= e($theme) ?> html-block" id="<?= e($anchor) ?>">
  <div class="container"><?= tr($b['code'] ?? '') ?></div>
</section>
