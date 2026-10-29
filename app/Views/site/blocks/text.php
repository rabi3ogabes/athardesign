<?php /** @var array $b @var string $anchor @var string $theme */ ?>
<section class="section section--<?= e($theme) ?> text-block" id="<?= e($anchor) ?>">
  <div class="container <?= ($b['width'] ?? 'narrow') === 'narrow' ? 'container--narrow' : '' ?>">
    <?php if (tr($b['heading'] ?? '') !== ''): ?><h2 class="display display--md" data-reveal><?= nl2br(e(tr($b['heading']))) ?></h2><?php endif; ?>
    <div class="prose" data-reveal><?= tr($b['body'] ?? '') ?></div>
  </div>
</section>
