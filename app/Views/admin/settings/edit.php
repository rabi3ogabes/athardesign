<?php /** @var string $group @var array $groups @var array $keys @var array $values */
$f = fn(string $k, array $o = []) => view('admin/partials/field', array_merge(['name' => $k, 'label' => t('settings.' . $k), 'value' => $values[$k] ?? ''], $o));
$fi = fn(string $k, array $o = []) => view('admin/partials/field-i18n', array_merge(['name' => $k, 'label' => t('settings.' . $k), 'value' => $values[$k] ?? []], $o));
$img = fn(string $k, array $o = []) => view('admin/partials/field-image', array_merge(['name' => $k, 'label' => t('settings.' . $k), 'value' => $values[$k] ?? ''], $o));
$tg = fn(string $k) => view('admin/partials/field', ['name' => $k, 'label' => t('settings.' . $k), 'type' => 'toggle', 'value' => $values[$k] ?? false]);
$icons = ['general' => 'gear', 'appearance' => 'palette', 'contact' => 'phone', 'social' => 'share-network', 'seo' => 'magnifying-glass', 'analytics' => 'chart-line-up', 'mail' => 'envelope-simple'];
$themes = App\Models\Settings::themes();
?>
<div class="grid-settings">
  <nav class="subnav">
    <?php foreach ($groups as $g): ?><a class="<?= $g === $group ? 'is-active' : '' ?>" href="<?= e(admin_url('settings/' . $g)) ?>"><?= icon($icons[$g] ?? 'gear') ?><?= e(t('settings.' . $g)) ?></a><?php endforeach; ?>
  </nav>
  <div class="stack">
    <form method="post" action="<?= e(admin_url('settings/' . $group)) ?>" class="stack" data-dirty-guard>
      <?= csrf_field() ?>
      <div class="card">
        <div class="card__head"><h2><?= icon($icons[$group] ?? 'gear') ?><?= e(t('settings.' . $group)) ?></h2><button class="btn btn--primary btn--sm" type="submit"><?= icon('floppy-disk') ?><span><?= e(t('common.save')) ?></span></button></div>
        <div class="card__body stack">
          <?php if ($group === 'general'): ?>
            <?= $fi('site_name') ?>
            <?= $fi('tagline') ?>
            <div class="grid-2"><?= $img('logo', ['hint' => t('settings.logo_hint')]) ?><?= $img('favicon', ['hint' => t('settings.favicon_hint')]) ?></div>
            <div class="grid-2">
              <?= $f('default_lang', ['type' => 'select', 'options' => ['ar' => t('common.arabic'), 'en' => t('common.english')]]) ?>
              <?= $tg('enable_english') ?>
            </div>
            <div class="grid-2"><?= $fi('header_cta_text') ?><?= $f('header_cta_link', ['placeholder' => '/contact']) ?></div>
            <?= $fi('footer_text', ['type' => 'textarea', 'rows' => 3]) ?>
            <?= $tg('show_whatsapp_button') ?>
            <?= $tg('maintenance_mode') ?>
          <?php elseif ($group === 'appearance'): ?>
            <div class="field">
              <div class="field__label"><label><?= e(t('settings.theme')) ?></label><span class="hint"><?= e(t('settings.theme_hint')) ?></span></div>
              <div class="themes">
                <?php foreach ($themes as $key => $th): ?>
                  <label class="theme-card">
                    <input type="radio" name="theme" value="<?= e($key) ?>" <?= ($values['theme'] ?? 'ink') === $key ? 'checked' : '' ?>>
                    <span class="theme-card__box">
                      <span class="theme-card__preview" style="background:<?= e($th['bg']) ?>"><i style="background:<?= e($th['fg']) ?>"></i><b style="background:<?= e($th['fg']) ?>"></b><s style="background:<?= e($th['accent']) ?>"></s></span>
                      <span class="theme-card__label"><?= e($th[lang()] ?? $th['en']) ?><small><?= e($th['accent']) ?></small></span>
                    </span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="field">
              <div class="field__label"><label for="accent_color"><?= e(t('settings.accent_color')) ?></label><span class="hint"><?= e(t('settings.accent_color_hint')) ?></span></div>
              <div class="swatch-row">
                <input type="color" value="<?= e(($values['accent_color'] ?? '') ?: $themes[$values['theme'] ?? 'ink']['accent'] ?? '#C9A868') ?>" data-color-pick="accent_color" aria-label="<?= e(t('settings.accent_color')) ?>">
                <input class="input" type="text" id="accent_color" name="accent_color" value="<?= e($values['accent_color'] ?? '') ?>" placeholder="#C9A868" dir="ltr" pattern="^#[0-9A-Fa-f]{6}$|^$" maxlength="7">
                <button type="button" class="btn btn--ghost btn--sm" data-clear-input="accent_color"><?= e(t('common.reset')) ?></button>
              </div>
            </div>
            <div class="grid-2">
              <?= $f('heading_font', ['type' => 'select', 'options' => ['serif' => t('settings.heading_serif'), 'sans' => t('settings.heading_sans'), 'custom' => t('settings.heading_custom')]]) ?>
              <?= $f('radius', ['type' => 'select', 'options' => ['soft' => t('settings.radius_soft'), 'sharp' => t('settings.radius_sharp')]]) ?>
            </div>
            <div class="grid-2">
              <?= $f('custom_font_name', ['hint' => t('settings.custom_font_hint'), 'placeholder' => 'Lusail']) ?>
              <?= $tg('custom_font_body') ?>
            </div>
            <p class="hint"><a class="link" href="<?= e(base_url()) ?>/" target="_blank" rel="noopener"><?= e(t('settings.preview')) ?> ↗</a></p>
          <?php elseif ($group === 'contact'): ?>
            <div class="grid-2"><?= $f('whatsapp_number', ['hint' => t('settings.whatsapp_number_hint'), 'placeholder' => '974XXXXXXXX', 'attrs' => 'dir="ltr"']) ?><?= $f('phone', ['type' => 'tel', 'placeholder' => '+974 ...']) ?></div>
            <?= $fi('whatsapp_message', ['type' => 'textarea', 'rows' => 2]) ?>
            <div class="grid-2"><?= $f('email', ['type' => 'email']) ?><?= $f('notify_email', ['type' => 'email', 'hint' => t('settings.notify_email_hint')]) ?></div>
            <?= $fi('address') ?>
            <?= $f('map_link', ['type' => 'url', 'placeholder' => 'https://maps.app.goo.gl/...']) ?>
            <?= $fi('working_hours') ?>
          <?php elseif ($group === 'social'): ?>
            <p class="hint"><?= e(t('settings.social_hint')) ?></p>
            <div class="socials-grid">
              <?php foreach ($keys as $k): $n = substr($k, 7); ?>
                <div class="field"><div class="field__label"><label><?= e(ucfirst($n)) ?></label></div><div class="social-input"><?= icon('brand-' . $n) ?><input class="input" type="url" name="<?= e($k) ?>" value="<?= e($values[$k] ?? '') ?>" placeholder="https://<?= e($n === 'x' ? 'x.com' : $n . '.com') ?>/..." dir="ltr"></div></div>
              <?php endforeach; ?>
            </div>
          <?php elseif ($group === 'seo'): ?>
            <?= $fi('meta_title_suffix', ['hint' => t('settings.meta_title_suffix_hint')]) ?>
            <?= $fi('default_meta_description', ['type' => 'textarea', 'rows' => 3, 'count' => true]) ?>
            <?= $fi('default_meta_keywords', ['type' => 'textarea', 'rows' => 2]) ?>
            <div class="grid-2">
              <?= $img('og_image', ['hint' => t('settings.og_image_hint')]) ?>
              <?= $f('schema_type', ['type' => 'select', 'options' => ['ProfessionalService' => 'ProfessionalService', 'LocalBusiness' => 'LocalBusiness', 'Organization' => 'Organization', 'MarketingAgency' => 'MarketingAgency' /* not in schema.org core; kept as Organization subtype label */]]) ?>
            </div>
            <div class="grid-2"><?= $f('google_verification', ['attrs' => 'dir="ltr"']) ?><?= $f('bing_verification', ['attrs' => 'dir="ltr"']) ?></div>
            <div class="grid-2"><?= $f('ga4_id', ['placeholder' => 'G-XXXXXXXXXX', 'attrs' => 'dir="ltr"']) ?><?= $f('gtm_id', ['placeholder' => 'GTM-XXXXXXX', 'attrs' => 'dir="ltr"']) ?></div>
            <div class="grid-2"><?= $f('geo_lat', ['placeholder' => '25.2854', 'attrs' => 'dir="ltr"']) ?><?= $f('geo_lng', ['placeholder' => '51.5310', 'attrs' => 'dir="ltr"']) ?></div>
            <?= $f('robots_txt', ['type' => 'code', 'rows' => 6, 'hint' => t('settings.robots_hint')]) ?>
            <?= $f('head_scripts', ['type' => 'code', 'rows' => 5, 'hint' => t('settings.head_scripts_hint')]) ?>
          <?php elseif ($group === 'analytics'): ?>
            <?= $tg('analytics_enabled') ?>
            <?= $tg('analytics_geo') ?>
            <?= $tg('analytics_track_bots') ?>
            <?= $tg('analytics_track_admins') ?>
            <?= $f('analytics_retention_days', ['type' => 'number', 'attrs' => 'min="7" max="3650"']) ?>
          <?php elseif ($group === 'mail'): ?>
            <p class="hint"><?= e(t('settings.smtp_hint')) ?></p>
            <?= $f('mail_from', ['type' => 'email']) ?>
            <div class="grid-2"><?= $f('smtp_host', ['attrs' => 'dir="ltr"']) ?><?= $f('smtp_port', ['type' => 'number']) ?></div>
            <div class="grid-2"><?= $f('smtp_user', ['attrs' => 'dir="ltr" autocomplete="off"']) ?><?= view('admin/partials/field', ['name' => 'smtp_pass', 'label' => t('settings.smtp_pass'), 'type' => 'password', 'value' => '', 'placeholder' => ($values['smtp_pass'] ?? '') !== '' ? '••••••••' : '', 'attrs' => 'autocomplete="new-password"']) ?></div>
            <?= $f('smtp_secure', ['type' => 'select', 'options' => ['tls' => 'STARTTLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None']]) ?>
            <?= $tg('mail_smtp_probe') ?>
          <?php endif; ?>
        </div>
      </div>
    </form>
    <?php if ($group === 'appearance'): ?>
      <div class="card">
        <div class="card__head"><h2><?= icon('text-aa') ?><?= e(t('settings.custom_font')) ?></h2></div>
        <div class="card__body grid-2">
          <?php foreach (['regular' => 'custom_font_regular', 'bold' => 'custom_font_bold'] as $slot => $key): $cur = (string)($values[$key] ?? ''); ?>
            <div class="field">
              <div class="field__label"><label><?= e(t('settings.' . $key)) ?></label><?php if ($cur !== ''): ?><span class="hint"><?= e(t('settings.font_current')) ?>: <code dir="ltr"><?= e(basename($cur)) ?></code></span><?php endif; ?></div>
              <?php if ($cur !== ''): ?><p class="font-sample" style="font-family:'AtharCustomPreview<?= $slot === 'bold' ? 'B' : '' ?>'"><style>@font-face{font-family:'AtharCustomPreview<?= $slot === 'bold' ? 'B' : '' ?>';src:url('<?= e(base_url() . $cur) ?>')}</style>أثر لتصميم الويب — Athar Web Design 0123</p><?php endif; ?>
              <div class="row">
                <form method="post" action="<?= e(admin_url('settings/font')) ?>" enctype="multipart/form-data" class="row"><?= csrf_field() ?><input type="hidden" name="slot" value="<?= e($slot) ?>">
                  <label class="btn btn--ghost btn--sm file-btn"><?= icon('upload-simple') ?><span><?= e(t('settings.font_upload')) ?></span><input type="file" name="font" accept=".woff2,.woff,.ttf,.otf,font/woff2,font/woff,font/ttf,font/otf" hidden onchange="this.form.submit()"></label>
                </form>
                <?php if ($cur !== ''): ?><form method="post" action="<?= e(admin_url('settings/font/remove')) ?>"><?= csrf_field() ?><input type="hidden" name="slot" value="<?= e($slot) ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= icon('trash') ?><span><?= e(t('common.delete')) ?></span></button></form><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($group === 'general'): ?>
      <div class="card danger-zone"><div class="card__head"><h2><?= icon('broom') ?><?= e(t('settings.danger')) ?></h2></div><div class="card__body row">
        <form method="post" action="<?= e(admin_url('tools/clear-cache')) ?>"><?= csrf_field() ?><button class="btn btn--ghost" type="submit"><?= icon('broom') ?><span><?= e(t('settings.clear_cache')) ?></span></button></form>
        <span class="hint"><?= e(t('settings.env_hint')) ?></span>
      </div></div>
    <?php elseif ($group === 'analytics'): ?>
      <div class="card danger-zone"><div class="card__head"><h2><?= icon('trash') ?><?= e(t('settings.danger')) ?></h2></div><div class="card__body row">
        <form method="post" action="<?= e(admin_url('tools/prune')) ?>" data-confirm="<?= e(t('settings.prune')) ?>?"><?= csrf_field() ?><button class="btn btn--ghost btn--danger-text" type="submit"><?= icon('trash') ?><span><?= e(t('settings.prune')) ?></span></button></form>
      </div></div>
    <?php elseif ($group === 'mail'): ?>
      <div class="card"><div class="card__body row">
        <form method="post" action="<?= e(admin_url('tools/test-mail')) ?>"><?= csrf_field() ?><button class="btn btn--ghost" type="submit"><?= icon('envelope-simple') ?><span><?= e(t('settings.test_mail')) ?></span></button></form>
      </div></div>
    <?php endif; ?>
  </div>
</div>
