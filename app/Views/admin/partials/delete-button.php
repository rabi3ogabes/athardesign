<?php /** $action (url), $label?, $confirm?, $class? */ ?>
<form method="post" action="<?= e($action) ?>" class="inline" data-confirm="<?= e($confirm ?? t('common.confirm_delete')) ?>"><?= csrf_field() ?><button type="submit" class="<?= e($class ?? 'btn btn--ghost btn--sm btn--danger-text') ?>"><?= icon('trash') ?><span><?= e($label ?? t('common.delete')) ?></span></button></form>
