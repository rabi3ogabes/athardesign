<?php /** @var array $project @var array $services @var array $related @var array|null $next @var array|null $prev */
$gallery = is_array($project['gallery']) ? $project['gallery'] : [];
$results = is_array($project['results']) ? $project['results'] : [];
$cat = $project['category'] ?? null;
?>
<section class="section section--hero-sub">
  <div class="container">
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="<?= e(url('/')) ?>"><?= e(t('site.home')) ?></a><span>/</span><a href="<?= e(url('/work')) ?>"><?= e(t('site.work')) ?></a>
    </nav>
    <div class="project-head">
      <div>
        <?php if ($cat): ?><p class="eyebrow" data-reveal><?= e(tr($cat['name'])) ?></p><?php endif; ?>
        <h1 class="display display--lg" data-reveal><?= e(tr($project['title'])) ?></h1>
        <p class="lede" data-reveal><?= e(tr($project['excerpt'])) ?></p>
      </div>
      <dl class="meta-list" data-reveal>
        <?php if (!empty($project['client'])): ?><div><dt><?= e(t('site.client')) ?></dt><dd><?= e($project['client']) ?></dd></div><?php endif; ?>
        <?php if (!empty($project['year'])): ?><div><dt><?= e(t('site.year')) ?></dt><dd><?= e($project['year']) ?></dd></div><?php endif; ?>
        <?php if ($services): ?><div><dt><?= e(t('site.services_provided')) ?></dt><dd><?php foreach ($services as $i => $s): ?><a href="<?= e(url('/services/' . $s['slug'])) ?>"><?= e(tr($s['title'])) ?></a><?= $i < count($services) - 1 ? '، ' : '' ?><?php endforeach; ?></dd></div><?php endif; ?>
        <?php if (!empty($project['link'])): ?><div><dt><?= e(t('site.visit_site')) ?></dt><dd><a href="<?= e($project['link']) ?>" target="_blank" rel="noopener" data-track="link" data-label="project-<?= e($project['slug']) ?>" dir="ltr"><?= e(preg_replace('#^https?://(www\.)?#', '', $project['link'])) ?></a></dd></div><?php endif; ?>
      </dl>
    </div>
  </div>
  <?php if (!empty($project['cover_image'])): ?>
    <div class="container container--wide">
      <figure class="project-cover frame" data-reveal>
        <img src="<?= e(media_url($project['cover_image'])) ?>" alt="<?= e(tr($project['title'])) ?>" loading="eager" fetchpriority="high" data-parallax="0.1">
      </figure>
    </div>
  <?php endif; ?>
</section>

<?php if (trim(tr($project['body'])) !== '' || $results): ?>
<section class="section section--ivory">
  <div class="container">
    <div class="split">
      <div class="split__main prose" data-reveal><?= tr($project['body']) ?></div>
      <?php if ($results): ?>
        <aside class="split__aside">
          <div class="card card--list" data-reveal>
            <h2 class="card__h"><?= e(t('site.results')) ?></h2>
            <ul class="results">
              <?php foreach ($results as $r): ?>
                <li><strong><?= e($r['value'] ?? '') ?></strong><span><?= e(tr($r['label'] ?? '')) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </aside>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($gallery): ?>
<section class="section">
  <div class="container container--wide">
    <div class="gallery gallery--project">
      <?php foreach ($gallery as $g): $src = is_array($g) ? ($g['image'] ?? $g['src'] ?? '') : (string)$g; if ($src === '') continue; ?>
        <figure class="gallery__item frame" data-reveal>
          <img src="<?= e(media_url($src)) ?>" alt="<?= e(is_array($g) ? tr($g['alt'] ?? '') : '') ?: e(tr($project['title'])) ?>" loading="lazy">
          <?php if (is_array($g) && tr($g['caption'] ?? '') !== ''): ?><figcaption><?= e(tr($g['caption'])) ?></figcaption><?php endif; ?>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($prev || $next): ?>
<nav class="project-nav container" aria-label="<?= e(t('site.work')) ?>">
  <?php if ($prev): ?><a class="project-nav__link" href="<?= e(url('/work/' . $prev['slug'])) ?>"><small><?= e(t('site.prev_project')) ?></small><span><?= e(tr($prev['title'])) ?></span></a><?php else: ?><span></span><?php endif; ?>
  <?php if ($next): ?><a class="project-nav__link project-nav__link--next" href="<?= e(url('/work/' . $next['slug'])) ?>"><small><?= e(t('site.next_project')) ?></small><span><?= e(tr($next['title'])) ?></span></a><?php endif; ?>
</nav>
<?php endif; ?>

<?php if ($related): ?>
<section class="section section--tight">
  <div class="container">
    <div class="section__head section__head--row">
      <h2 class="display display--md" data-reveal><?= e(t('site.related_work')) ?></h2>
      <a class="link-arrow" href="<?= e(url('/work')) ?>"><?= e(t('site.all_work')) ?><?= icon('arrow-up-right') ?></a>
    </div>
    <div class="work-grid work-grid--3">
      <?php foreach ($related as $p): ?><?= view('site/partials/project-card', ['p' => $p]) ?><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?= view('site/partials/cta-band', []) ?>
