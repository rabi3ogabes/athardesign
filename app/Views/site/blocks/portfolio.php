<?php /** @var array $b @var string $anchor @var string $theme */
use App\Models\Categories;
use App\Models\Projects;
$limit = max(1, (int)($b['limit'] ?? 6));
$cat = trim((string)($b['category'] ?? ''));
$items = $cat !== '' ? Projects::byCategory($cat) : (($b['source'] ?? 'featured') === 'featured' ? Projects::featured(100) : Projects::published());
if (($b['source'] ?? '') === 'featured' && !$items) $items = Projects::published();
$items = array_slice($items, 0, $limit);
if (!$items && ($b['source'] ?? '') !== 'all') return;
$empty = !$items;
$layout = ($b['layout'] ?? 'bento') === 'grid' ? 'grid' : 'bento';
$cats = !empty($b['show_filters']) ? Categories::all() : [];
?>
<section class="section section--<?= e($theme) ?> portfolio" id="<?= e($anchor) ?>">
  <div class="container">
    <div class="section__head section__head--row">
      <div>
        <?php if (tr($b['eyebrow'] ?? '') !== ''): ?><p class="eyebrow" data-reveal><?= e(tr($b['eyebrow'])) ?></p><?php endif; ?>
        <?php if (tr($b['heading'] ?? '') !== ''): ?><h2 class="display display--lg" data-reveal><?= nl2br(e(tr($b['heading']))) ?></h2><?php endif; ?>
        <?php if (tr($b['intro'] ?? '') !== ''): ?><p class="lede" data-reveal><?= e(tr($b['intro'])) ?></p><?php endif; ?>
      </div>
      <?php if (!empty($b['show_link'])): ?><a class="link-arrow" href="<?= e(url('/work')) ?>" data-reveal><?= e(t('site.all_work')) ?><?= icon('arrow-up-right') ?></a><?php endif; ?>
    </div>
    <?php if ($cats): ?>
      <div class="filters" data-filters data-reveal>
        <button type="button" class="is-active" data-filter=""><?= e(t('site.all')) ?></button>
        <?php foreach ($cats as $c): ?><button type="button" data-filter="<?= e($c['slug']) ?>"><?= e(tr($c['name'])) ?></button><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($empty): $wa = whatsapp_link(); ?>
      <div class="work-empty" data-reveal>
        <span class="work-empty__ico"><?= icon('sparkle') ?></span>
        <div><strong><?= e(t('site.work_empty')) ?></strong><span><?= e(t('site.work_empty_text')) ?></span></div>
        <a class="btn btn--pill btn--brass" href="<?= e($wa !== '' ? $wa : url('/contact')) ?>" <?= $wa !== '' ? 'target="_blank" rel="noopener"' : '' ?> data-track="<?= $wa !== '' ? 'whatsapp' : 'cta' ?>" data-label="work-empty"><span><?= e(t('site.lets_talk')) ?></span><i class="btn__ico"><?= icon($wa !== '' ? 'brand-whatsapp' : 'arrow-up-right') ?></i></a>
      </div>
    <?php endif; ?>
    <div class="work-grid work-grid--<?= $layout ?>" data-work-grid>
      <?php foreach ($items as $p): ?><?= view('site/partials/project-card', ['p' => $p]) ?><?php endforeach; ?>
    </div>
  </div>
</section>
