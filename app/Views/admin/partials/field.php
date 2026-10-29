<?php
/**
 * Generic field. $name, $label, $type = text|number|url|email|password|select|toggle|textarea|code, $value, $hint, $options (select), $attrs, $placeholder
 */
$type = $type ?? 'text';
$id = preg_replace('/[^a-z0-9]/i', '_', $name);
$attrs = $attrs ?? '';
$value = $value ?? '';
?>
<?php if ($type === 'toggle'): ?>
  <div class="field field--toggle">
    <label class="toggle">
      <input type="hidden" name="<?= e($name) ?>" value="0">
      <input type="checkbox" name="<?= e($name) ?>" value="1" <?= filter_var($value, FILTER_VALIDATE_BOOL) ? 'checked' : '' ?> <?= $attrs ?>>
      <span class="toggle__track"></span><span class="toggle__label"><?= e($label) ?></span>
    </label>
    <?php if (!empty($hint)): ?><span class="hint"><?= e($hint) ?></span><?php endif; ?>
  </div>
<?php else: ?>
  <div class="field">
    <div class="field__label"><label for="<?= e($id) ?>"><?= e($label) ?><?= !empty($required) ? ' <b>*</b>' : '' ?></label><?php if (!empty($hint)): ?><span class="hint"><?= e($hint) ?></span><?php endif; ?></div>
    <?php if ($type === 'select'): ?>
      <select class="input" id="<?= e($id) ?>" name="<?= e($name) ?>" <?= $attrs ?>>
        <?php foreach (($options ?? []) as $k => $lbl): ?><option value="<?= e($k) ?>" <?= (string)$k === (string)$value ? 'selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?>
      </select>
    <?php elseif ($type === 'textarea' || $type === 'code'): ?>
      <textarea class="input <?= $type === 'code' ? 'input--code' : '' ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" rows="<?= (int)($rows ?? 4) ?>" <?= $attrs ?> <?= $type === 'code' ? 'dir="ltr" spellcheck="false"' : '' ?>><?= e($value) ?></textarea>
    <?php else: ?>
      <input class="input" type="<?= e($type) ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" placeholder="<?= e($placeholder ?? '') ?>" <?= in_array($type, ['url', 'email', 'number', 'password', 'tel'], true) ? 'dir="ltr"' : '' ?> <?= !empty($required) ? 'required' : '' ?> <?= $attrs ?>>
    <?php endif; ?>
  </div>
<?php endif; ?>
