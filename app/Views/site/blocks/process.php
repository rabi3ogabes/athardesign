<?php /** @var array $b @var string $anchor @var string $theme */
$steps = is_array($b['steps'] ?? null) ? array_values(array_filter($b['steps'], fn($i) => tr($i['title'] ?? '') !== '')) : [];
if (!$steps) return;
?>
<section class="section section--<?= e($theme) ?> process" id="<?= e($anchor) ?>">
  <div class="container">
    <div class="process__grid">
      <div class="process__head">
        <?php if (tr($b['eyebrow'] ?? '') !== ''): ?><p class="eyebrow" data-reveal><?= e(tr($b['eyebrow'])) ?></p><?php endif; ?>
        <?php if (tr($b['heading'] ?? '') !== ''): ?><h2 class="display display--lg" data-reveal><?= nl2br(e(tr($b['heading']))) ?></h2><?php endif; ?>
        <?php if (tr($b['intro'] ?? '') !== ''): ?><p class="lede" data-reveal><?= e(tr($b['intro'])) ?></p><?php endif; ?>
      </div>
      <ol class="steps">
        <?php foreach ($steps as $i => $s): ?>
          <li class="step" data-reveal>
            <span class="step__num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <div>
              <h3 class="step__title"><?= e(tr($s['title'])) ?></h3>
              <p class="step__text"><?= nl2br(e(tr($s['text'] ?? ''))) ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>
</section>
