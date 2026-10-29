<?php /** @var array $b @var string $anchor @var string $theme */
$items = is_array($b['items'] ?? null) ? array_filter($b['items'], fn($i) => tr($i['question'] ?? '') !== '') : [];
if (!$items) return;
?>
<section class="section section--<?= e($theme) ?> faq" id="<?= e($anchor) ?>">
  <div class="container">
    <div class="faq__grid">
      <div class="faq__head">
        <?php if (tr($b['eyebrow'] ?? '') !== ''): ?><p class="eyebrow" data-reveal><?= e(tr($b['eyebrow'])) ?></p><?php endif; ?>
        <h2 class="display display--lg" data-reveal><?= nl2br(e(tr($b['heading'] ?? '') ?: t('site.faq'))) ?></h2>
        <?php if (tr($b['intro'] ?? '') !== ''): ?><p class="lede" data-reveal><?= e(tr($b['intro'])) ?></p><?php endif; ?>
      </div>
      <div class="accordion">
        <?php foreach ($items as $i => $it): ?>
          <details class="accordion__item" data-reveal <?= $i === 0 ? 'open' : '' ?>>
            <summary><span><?= e(tr($it['question'])) ?></span><i class="accordion__ico"><?= icon('plus') ?></i></summary>
            <div class="accordion__body"><div class="prose"><p><?= nl2br(e(tr($it['answer'] ?? ''))) ?></p></div></div>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
