<?php
/**
 * One package card. $p (package row), $tiers (labels), optional $compact (bool), $i (index for reveal delay)
 */
use App\Models\Packages;
$tiers = $tiers ?? Packages::tierLabels();
$compact = !empty($compact);
$price = Packages::price($p);
$wa = Packages::whatsappLink($p);
$features = is_array($p['features']) ? $p['features'] : [];
$hl = !empty($p['is_highlighted']);
$badge = tr($p['badge']);
if ($badge === '' && $hl) $badge = t('site.package_most_popular');
$cta = tr($p['cta_text']) ?: t('site.package_order');
$detail = url('/packages/' . $p['slug']);
?>
<article class="pk <?= $hl ? 'pk--hl' : '' ?> pk--<?= e($p['tier']) ?>" data-reveal data-tier="<?= e($p['tier']) ?>" style="--i:<?= (int)($i ?? 0) ?>">
  <div class="pk__inner">
    <?php if ($badge !== ''): ?><span class="pk__badge"><?= icon('star') ?><?= e($badge) ?></span><?php endif; ?>
    <div class="pk__tier"><span class="pk__medal pk__medal--<?= e($p['tier']) ?>" aria-hidden="true"><?= icon($p['tier'] === 'premium' ? 'crown-simple' : ($p['tier'] === 'elite' ? 'medal' : 'sparkle')) ?></span><span><?= e($tiers[$p['tier']][lang()] ?? $p['tier']) ?></span></div>
    <h3 class="pk__name"><a href="<?= e($detail) ?>" data-track="package" data-label="card-<?= e($p['slug']) ?>"><?= e(tr($p['name'])) ?></a></h3>
    <?php if (tr($p['tagline']) !== ''): ?><p class="pk__tag"><?= e(tr($p['tagline'])) ?></p><?php endif; ?>
    <div class="pk__price">
      <?php if ($price !== ''): ?>
        <?php if (tr($p['price_prefix']) !== ''): ?><span class="pk__prefix"><?= e(tr($p['price_prefix'])) ?></span><?php endif; ?>
        <span class="pk__amount" dir="ltr"><?= e($price) ?></span>
        <?php if (tr($p['period']) !== ''): ?><span class="pk__period"><?= e(tr($p['period'])) ?></span><?php endif; ?>
      <?php else: ?>
        <span class="pk__amount pk__amount--custom"><?= e(t('packages.custom_quote')) ?></span>
      <?php endif; ?>
    </div>
    <?php if (tr($p['delivery']) !== ''): ?><p class="pk__meta"><?= icon('timer') ?><span><?= e(t('site.package_delivery')) ?>: <?= e(tr($p['delivery'])) ?></span></p><?php endif; ?>
    <?php if ($features): ?>
      <ul class="pk__list">
        <?php foreach (array_slice($features, 0, $compact ? 6 : 14) as $f): $txt = tr($f); if ($txt === '') continue; $inc = filter_var($f['included'] ?? true, FILTER_VALIDATE_BOOL); ?>
          <li class="<?= $inc ? '' : 'is-off' ?>"><?= icon($inc ? 'check' : 'minus') ?><span><?= e($txt) ?></span></li>
        <?php endforeach; ?>
        <?php if ($compact && count($features) > 6): ?><li class="pk__more"><a href="<?= e($detail) ?>">+<?= count($features) - 6 ?> <?= e(t('site.read_more')) ?></a></li><?php endif; ?>
      </ul>
    <?php endif; ?>
    <div class="pk__actions">
      <?php if ($wa !== ''): ?>
        <a class="btn btn--pill btn--wa" href="<?= e($wa) ?>" target="_blank" rel="noopener" data-track="whatsapp" data-label="package-<?= e($p['slug']) ?>" data-magnetic><span><?= e($cta) ?></span><i class="btn__ico"><?= icon('brand-whatsapp') ?></i></a>
      <?php else: ?>
        <a class="btn btn--pill btn--brass" href="<?= e(url('/contact')) ?>?package=<?= e(rawurlencode($p['slug'])) ?>" data-track="cta" data-label="package-<?= e($p['slug']) ?>"><span><?= e($cta) ?></span><i class="btn__ico"><?= icon('arrow-up-right') ?></i></a>
      <?php endif; ?>
      <a class="link-arrow link-arrow--sm" href="<?= e($detail) ?>" data-track="package" data-label="more-<?= e($p['slug']) ?>"><?= e(t('site.read_more')) ?><?= icon('arrow-up-right') ?></a>
    </div>
  </div>
</article>
