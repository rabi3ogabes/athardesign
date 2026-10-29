<?php /** $name, $label, $value, $hint */ $value = (string)($value ?? ''); $id = preg_replace('/[^a-z0-9]/i', '_', $name); ?>
<div class="field">
  <div class="field__label"><label><?= e($label) ?></label><?php if (!empty($hint)): ?><span class="hint"><?= e($hint) ?></span><?php endif; ?></div>
  <div class="image-field <?= $value !== '' ? 'has-image' : '' ?>" data-image-field>
    <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>" id="<?= e($id) ?>" data-image-input>
    <div class="image-field__preview" data-image-preview><?php if ($value !== ''): ?><img src="<?= e(media_url($value)) ?>" alt=""><?php endif; ?></div>
    <div class="image-field__actions">
      <button type="button" class="btn btn--ghost btn--sm" data-image-pick><?= icon('image') ?><span><?= e(t('common.choose_image')) ?></span></button>
      <button type="button" class="btn btn--ghost btn--sm btn--danger-text" data-image-clear><?= icon('x') ?><span><?= e(t('common.remove_image')) ?></span></button>
    </div>
  </div>
</div>
