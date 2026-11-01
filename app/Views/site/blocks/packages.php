<?php /** @var array $b @var string $anchor @var string $theme */
use App\Models\Packages;
$only = (string)($b['category'] ?? 'all');
$groups = Packages::grouped($only === 'all' ? null : $only);
if (!$groups) return;
$cats = Packages::categoryLabels();
$tiers = Packages::tierLabels();
$keys = array_keys($groups);
$first = $keys[0];
$uid = 'pk-' . substr(md5($anchor . $only), 0, 6);
$stacked = ($b['layout'] ?? 'tabs') === 'stacked';
$icoFor = fn(string $k) => $k === 'social' ? 'instagram-logo' : ($k === 'seo' ? 'magnifying-glass' : ($k === 'photography' ? 'camera' : ($k === 'bundle' ? 'sparkle' : 'layout')));
?>
<section class="section section--<?= e($theme) ?> packages" id="<?= e($anchor) ?>">
  <div class="container">
    <div class="section__head section__head--center">
      <?php if (tr($b['eyebrow'] ?? '') !== ''): ?><p class="eyebrow eyebrow--center" data-reveal><?= e(tr($b['eyebrow'])) ?></p><?php endif; ?>
      <?php if (tr($b['heading'] ?? '') !== ''): ?><h2 class="display display--lg" data-reveal><?= nl2br(e(tr($b['heading']))) ?></h2><?php endif; ?>
      <?php if (tr($b['intro'] ?? '') !== ''): ?><p class="lede" data-reveal><?= e(tr($b['intro'])) ?></p><?php endif; ?>
    </div>

    <?php if (count($keys) > 1 && !$stacked): ?>
      <div class="pk-tabs" role="tablist" data-pk-tabs data-reveal>
        <span class="pk-tabs__glider" aria-hidden="true"></span>
        <?php foreach ($keys as $k): ?>
          <button type="button" role="tab" id="<?= e($uid . '-tab-' . $k) ?>" aria-controls="<?= e($uid . '-panel-' . $k) ?>" aria-selected="<?= $k === $first ? 'true' : 'false' ?>" class="pk-tabs__btn <?= $k === $first ? 'is-active' : '' ?>" data-pk-tab="<?= e($k) ?>">
            <?= icon($icoFor($k)) ?><span><?= e($cats[$k][lang()] ?? $k) ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php foreach ($groups as $k => $list): $show = $stacked || $k === $first; ?>
      <?php if ($stacked && count($keys) > 1): ?><h3 class="pk-line" data-reveal><span class="pk-line__ico"><?= icon($icoFor($k)) ?></span><?= e($cats[$k][lang()] ?? $k) ?></h3><?php endif; ?>
      <div class="pk-grid <?= $show ? 'is-active' : '' ?> <?= count($list) >= 4 ? 'pk-grid--wide' : '' ?>" <?= $stacked ? '' : 'role="tabpanel" id="' . e($uid . '-panel-' . $k) . '" aria-labelledby="' . e($uid . '-tab-' . $k) . '"' ?> data-pk-panel="<?= e($k) ?>" <?= $show ? '' : 'hidden' ?> style="--n:<?= count($list) ?>">
        <?php foreach ($list as $i => $p): ?>
          <?= view('site/partials/package-card', ['p' => $p, 'tiers' => $tiers, 'compact' => true, 'i' => $i]) ?>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <?php if (!empty($b['show_custom'])): ?>
      <div class="pk-foot" data-reveal>
        <div class="pk-foot__custom">
          <div><strong><?= e(t('site.package_custom')) ?></strong><span><?= e(t('site.package_custom_text')) ?></span></div>
          <?php $wa = whatsapp_link(); ?>
          <a class="btn btn--pill btn--ghost" href="<?= e($wa !== '' ? $wa : url('/contact')) ?>" <?= $wa !== '' ? 'target="_blank" rel="noopener"' : '' ?> data-track="<?= $wa !== '' ? 'whatsapp' : 'cta' ?>" data-label="package-custom"><span><?= e(t('site.lets_talk')) ?></span><i class="btn__ico"><?= icon($wa !== '' ? 'brand-whatsapp' : 'arrow-up-right') ?></i></a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
