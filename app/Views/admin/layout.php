<?php
/** @var string $content @var array|null $user @var string $lang @var string $dir @var string $title @var array $flashes */
use App\Core\Auth;
use App\Core\I18n;
$siteName = setting_tr('site_name');
$menu = [
    ['', 'house', t('nav.dashboard')],
    ['section', '', t('nav.analytics')],
    ['visitors', 'users', t('nav.visitors')],
    ['clicks', 'cursor-click', t('nav.clicks')],
    ['pageviews', 'eye', t('nav.pageviews')],
    ['leads', 'tray', t('nav.leads')],
    ['section', '', t('nav.content')],
    ['pages', 'file-text', t('nav.pages')],
    ['services', 'briefcase', t('nav.services')],
    ['packages', 'crown-simple', t('nav.packages')],
    ['projects', 'images', t('nav.projects')],
    ['testimonials', 'quotes', t('nav.testimonials')],
    ['media', 'image', t('nav.media')],
    ['section', '', t('nav.email')],
    ['mail/campaigns', 'paper-plane-tilt', t('mail.campaigns')],
    ['mail/lists', 'address-book', t('mail.lists')],
    ['mail/templates', 'envelope-open', t('mail.templates')],
    ['mail/senders', 'identification-badge', t('mail.senders')],
    ['section', '', t('nav.seo')],
    ['seo', 'gauge', t('nav.seo_overview')],
    ['seo/tasks', 'list-checks', t('nav.seo_tasks')],
    ['seo/redirects', 'arrow-square-out', t('nav.redirects')],
];
if (Auth::can('manage_settings')) {
    $menu[] = ['section', '', t('nav.system')];
    $menu[] = ['settings', 'gear', t('nav.settings')];
    $menu[] = ['users', 'identification-card', t('nav.users')];
    $menu[] = ['activity', 'clock-counter-clockwise', t('nav.activity')];
}
$isActive = function (string $p): string {
    $cur = current_path();
    $base = rtrim(env('ADMIN_PATH', '/admin'), '/');
    if ($p === '') return ($cur === $base || $cur === $base . '/') ? 'is-active' : '';
    if ($p === 'seo') return ($cur === $base . '/seo') ? 'is-active' : '';
    if ($p === 'mail/campaigns') return (str_starts_with($cur, $base . '/mail/campaigns') || $cur === $base . '/mail') ? 'is-active' : '';
    return ($cur === $base . '/' . $p || str_starts_with($cur, $base . '/' . $p . '/')) ? 'is-active' : '';
};
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>" data-admin="<?= e(admin_url()) ?>" data-base="<?= e(base_url()) ?>" data-csrf="<?= e(csrf_token()) ?>" data-lang="<?= e($lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · <?= e($siteName) ?></title>
<link rel="icon" href="<?= e(base_url()) ?>/assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('assets/css/fonts.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/2.0.2/quill.snow.min.css">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
<script>window.ATHAR_I18N = <?= json_encode([
    'confirm_delete' => t('common.confirm_delete'), 'yes_delete' => t('common.yes_delete'), 'cancel' => t('common.cancel'),
    'saved' => t('common.saved'), 'error' => t('common.error'), 'copied' => t('common.copied'), 'loading' => t('common.loading'),
    'add_item' => t('editor.add_item'), 'remove_item' => t('editor.remove_item'), 'choose_block' => t('editor.choose_block'),
    'pick_image' => t('editor.pick_image'), 'upload_image' => t('editor.upload_image'), 'or_url' => t('editor.or_url'),
    'leave_warning' => t('editor.leave_warning'), 'no_icon' => t('editor.no_icon'), 'confirm_remove_block' => t('pages.confirm_remove_block'),
    'block_settings' => t('pages.block_settings'), 'anchor' => t('pages.anchor'), 'anchor_hint' => t('pages.anchor_hint'), 'theme' => t('pages.theme'),
    'theme_ink' => t('pages.theme_ink'), 'theme_ivory' => t('pages.theme_ivory'), 'hidden' => t('pages.hidden'), 'move_up' => t('pages.move_up'),
    'move_down' => t('pages.move_down'), 'collapse' => t('pages.collapse'), 'remove_block' => t('pages.remove_block'), 'no_blocks' => t('pages.no_blocks'),
    'change_image' => t('common.change_image'), 'remove_image' => t('common.remove_image'), 'choose_image' => t('common.choose_image'),
    'no_media' => t('media.no_media'), 'select' => t('media.select'), 'char_count' => t('pages.char_count'), 'unsaved' => t('pages.unsaved'),
    'ar' => t('editor.ar'), 'en' => t('editor.en'), 'optional' => t('common.optional'),
    'verifying' => t('mail.verifying'), 'verified_all' => t('mail.verified_all'), 'minutes' => t('mail.minutes'), 'hours' => t('mail.hours'),
], JSON_UNESCAPED_UNICODE) ?>;
window.ATHAR_SCORE_LABELS = <?= json_encode((array)((require APP_PATH . '/lang/mail-' . (in_array($lang, ['ar', 'en'], true) ? $lang : 'en') . '.php')['score_checks'] ?? []), JSON_UNESCAPED_UNICODE) ?>;</script>
</head>
<body class="admin">
<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
      <a href="<?= e(admin_url()) ?>" class="sidebar__logo"><img class="sidebar__mark" src="<?= e(base_url()) ?>/assets/img/logo-mark-transparent.svg" alt="" width="30" height="30"><span><?= e($siteName) ?></span></a>
      <button class="sidebar__close" type="button" data-sidebar-close aria-label="<?= e(t('common.close')) ?>"><?= icon('x') ?></button>
    </div>
    <nav class="sidebar__nav">
      <?php foreach ($menu as [$p, $ico, $label]): ?>
        <?php if ($p === 'section'): ?><div class="sidebar__section"><?= e($label) ?></div>
        <?php else: ?><a class="sidebar__link <?= $isActive($p) ?>" href="<?= e(admin_url($p)) ?>"><?= icon($ico) ?><span><?= e($label) ?></span></a><?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar__foot">
      <a class="sidebar__link" href="<?= e(base_url()) ?>/" target="_blank" rel="noopener"><?= icon('arrow-square-out') ?><span><?= e(t('nav.view_site')) ?></span></a>
      <a class="sidebar__link <?= $isActive('profile') ?>" href="<?= e(admin_url('profile')) ?>"><?= icon('user') ?><span><?= e($user['name'] ?? '') ?></span></a>
      <form method="post" action="<?= e(admin_url('logout')) ?>"><?= csrf_field() ?><button class="sidebar__link" type="submit"><?= icon('sign-out') ?><span><?= e(t('nav.logout')) ?></span></button></form>
    </div>
  </aside>
  <div class="sidebar__backdrop" data-sidebar-close></div>

  <div class="main">
    <header class="topbar">
      <button class="topbar__menu" type="button" data-sidebar-open aria-label="<?= e(t('site.menu')) ?>"><?= icon('list') ?></button>
      <h1 class="topbar__title"><?= e($title) ?></h1>
      <div class="topbar__actions">
        <form method="post" action="<?= e(admin_url('lang')) ?>" class="topbar__lang"><?= csrf_field() ?>
          <input type="hidden" name="lang" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>">
          <button type="submit" class="btn btn--ghost btn--sm"><?= icon('translate') ?><span><?= $lang === 'ar' ? 'English' : 'العربية' ?></span></button>
        </form>
        <a class="btn btn--ghost btn--sm" href="<?= e(base_url()) ?>/" target="_blank" rel="noopener"><?= icon('arrow-square-out') ?><span><?= e(t('nav.view_site')) ?></span></a>
      </div>
    </header>

    <div class="toasts" id="toasts" aria-live="polite">
      <?php foreach ($flashes as $f): ?><div class="toast toast--<?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?>
    </div>

    <main class="content">
      <?= $content ?>
    </main>
  </div>
</div>

<!-- media picker -->
<div class="modal" id="media-modal" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close></div>
  <div class="modal__panel modal__panel--wide" role="dialog" aria-modal="true" aria-label="<?= e(t('media.library')) ?>">
    <div class="modal__head">
      <div class="tabs" data-media-tabs>
        <button type="button" class="is-active" data-tab="library"><?= e(t('media.library')) ?></button>
        <button type="button" data-tab="upload"><?= e(t('editor.upload_image')) ?></button>
      </div>
      <input type="search" class="input input--sm" placeholder="<?= e(t('common.search')) ?>" data-media-search>
      <button type="button" class="modal__close" data-modal-close aria-label="<?= e(t('common.close')) ?>"><?= icon('x') ?></button>
    </div>
    <div class="modal__body">
      <div data-media-pane="library"><div class="media-grid" data-media-grid></div><div class="media-more"><button type="button" class="btn btn--ghost btn--sm" data-media-more>…</button></div></div>
      <div data-media-pane="upload" hidden>
        <label class="dropzone" data-media-dropzone><?= icon('upload-simple') ?><span><?= e(t('media.drop')) ?></span><input type="file" accept="image/*" multiple hidden></label>
        <p class="hint"><?= e(t('editor.or_url')) ?></p>
        <div class="row"><input type="url" class="input" placeholder="https://" data-media-url><button type="button" class="btn btn--primary" data-media-url-use><?= e(t('common.select')) ?></button></div>
      </div>
    </div>
  </div>
</div>

<!-- confirm dialog -->
<div class="modal" id="confirm-modal" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close></div>
  <div class="modal__panel modal__panel--sm" role="alertdialog" aria-modal="true">
    <p class="confirm__text" data-confirm-text></p>
    <div class="row row--end"><button type="button" class="btn btn--ghost" data-modal-close><?= e(t('common.cancel')) ?></button><button type="button" class="btn btn--danger" data-confirm-ok><?= e(t('common.yes_delete')) ?></button></div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/2.0.2/quill.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.3/Sortable.min.js" defer></script>
<?php if (!empty($useCharts)): ?><script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" defer></script><?php endif; ?>
<script src="<?= e(asset('assets/js/admin.js')) ?>" defer></script>
</body>
</html>
