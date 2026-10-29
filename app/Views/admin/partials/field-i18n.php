<?php
/**
 * Bilingual field. $name, $label, $value (array ar/en), $type = text|textarea|richtext, $hint, $rows, $required, $count (char counter)
 */
$value = is_array($value ?? null) ? $value : ['ar' => (string)($value ?? ''), 'en' => ''];
$type = $type ?? 'text';
$rows = $rows ?? 3;
$id = preg_replace('/[^a-z0-9]/i', '_', $name);
?>
<div class="field field--i18n">
  <div class="field__label"><label for="<?= e($id) ?>_ar"><?= e($label) ?><?= !empty($required) ? ' <b>*</b>' : '' ?></label><?php if (!empty($hint)): ?><span class="hint"><?= e($hint) ?></span><?php endif; ?></div>
  <div class="i18n-grid">
    <?php foreach (['ar', 'en'] as $l): $v = (string)($value[$l] ?? ''); ?>
      <div class="i18n-col" data-lang-col="<?= $l ?>">
        <span class="i18n-tag"><?= $l === 'ar' ? 'عربي' : 'EN' ?></span>
        <?php if ($type === 'textarea'): ?>
          <textarea class="input" id="<?= e($id) ?>_<?= $l ?>" name="<?= e($name) ?>[<?= $l ?>]" rows="<?= (int)$rows ?>" dir="<?= $l === 'ar' ? 'rtl' : 'ltr' ?>" <?= !empty($count) ? 'data-count' : '' ?>><?= e($v) ?></textarea>
        <?php elseif ($type === 'richtext'): ?>
          <div class="richtext" data-richtext dir="<?= $l === 'ar' ? 'rtl' : 'ltr' ?>"><div class="richtext__editor"></div><textarea hidden id="<?= e($id) ?>_<?= $l ?>" name="<?= e($name) ?>[<?= $l ?>]"><?= e($v) ?></textarea></div>
        <?php else: ?>
          <input class="input" type="text" id="<?= e($id) ?>_<?= $l ?>" name="<?= e($name) ?>[<?= $l ?>]" value="<?= e($v) ?>" dir="<?= $l === 'ar' ? 'rtl' : 'ltr' ?>" <?= !empty($count) ? 'data-count' : '' ?> <?= !empty($required) && $l === 'ar' ? 'required' : '' ?>>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
