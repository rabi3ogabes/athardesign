<?php /** @var array|null $page @var string $blocksHtml */ ?>
<?php if (!empty($is404)): ?>
  <section class="section section--404">
    <div class="container">
      <p class="eyebrow" data-reveal>404</p>
      <h1 class="display display--xl" data-reveal><?= e($page ? tr($page['title']) : t('site.not_found_title')) ?></h1>
      <p class="lede" data-reveal><?= e(t('site.not_found_text')) ?></p>
      <?php if (!$page || trim($blocksHtml) === ''): ?>
        <div class="btn-row" data-reveal>
          <a class="btn btn--pill btn--brass" href="<?= e(url('/')) ?>"><span><?= e(t('site.back_home')) ?></span><i class="btn__ico"><?= icon('arrow-up-right') ?></i></a>
          <a class="btn btn--pill btn--ghost" href="<?= e(url('/contact')) ?>"><span><?= e(t('site.contact')) ?></span></a>
        </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>
<?= $blocksHtml ?>
<?php if (empty($is404) && trim($blocksHtml) === ''): ?>
  <section class="section">
    <div class="container container--narrow">
      <h1 class="display" data-reveal><?= e(tr($page['title'])) ?></h1>
    </div>
  </section>
<?php endif; ?>
