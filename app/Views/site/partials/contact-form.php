<?php
use App\Models\Services;
$services = Services::published();
$preselect = (string)($_GET['service'] ?? '');
$pkg = isset($_GET['package']) ? \App\Models\Packages::bySlug(mb_substr((string)$_GET['package'], 0, 120)) : null;
$prefillMsg = $pkg ? \App\Models\Packages::whatsappMessage($pkg) : '';
$sent = isset($_GET['sent']);
?>
<form class="contact-form" method="post" action="<?= e(url('/contact')) ?>" data-contact-form novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="_ts" value="<?= time() ?>">
  <div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
  <div class="form-grid">
    <label class="field"><span><?= e(t('site.your_name')) ?> *</span><input type="text" name="name" required maxlength="80" autocomplete="name" value="<?= e(old('name')) ?>"></label>
    <label class="field"><span><?= e(t('site.your_phone')) ?> *</span><input type="tel" name="phone" required maxlength="30" autocomplete="tel" dir="ltr" value="<?= e(old('phone')) ?>"></label>
    <label class="field"><span><?= e(t('site.your_email')) ?></span><input type="email" name="email" maxlength="120" autocomplete="email" dir="ltr" value="<?= e(old('email')) ?>"></label>
    <label class="field"><span><?= e(t('site.company')) ?></span><input type="text" name="company" maxlength="120" autocomplete="organization" value="<?= e(old('company')) ?>"></label>
    <label class="field field--full"><span><?= e(t('site.service_interest')) ?></span>
      <select name="service">
        <option value=""><?= e(t('site.select_service')) ?></option>
        <?php foreach ($services as $s): ?>
          <option value="<?= e(tr($s['title'])) ?>" <?= ($preselect === $s['slug'] || old('service') === tr($s['title'])) ? 'selected' : '' ?>><?= e(tr($s['title'])) ?></option>
        <?php endforeach; ?>
        <option value="<?= e(t('site.other')) ?>"><?= e(t('site.other')) ?></option>
      </select>
    </label>
    <label class="field field--full"><span><?= e(t('site.message')) ?></span><textarea name="message" rows="5" maxlength="3000"><?= e(old('message') ?: $prefillMsg) ?></textarea></label>
  </div>
  <div class="form-foot">
    <button class="btn btn--pill btn--brass btn--lg" type="submit"><span data-label-idle="<?= e(t('site.send_message')) ?>" data-label-busy="<?= e(t('site.sending')) ?>"><?= e(t('site.send_message')) ?></span><i class="btn__ico"><?= icon('arrow-up-right') ?></i></button>
    <p class="form-msg" role="status" aria-live="polite" data-form-msg <?= $sent ? 'data-state="ok"' : '' ?>><?= $sent ? e(t('site.sent_text')) : '' ?></p>
  </div>
</form>
