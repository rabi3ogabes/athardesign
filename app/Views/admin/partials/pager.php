<?php /** @var array $pager */ if (($pager['pages'] ?? 1) <= 1 && ($pager['total'] ?? 0) === 0) return;
$qs = $_GET; unset($qs['page']);
$link = fn(int $p) => '?' . http_build_query(array_merge($qs, ['page' => $p]));
?>
<div class="pager">
  <span class="pager__info"><?= e(t('common.showing', ['from' => $pager['from'], 'to' => $pager['to'], 'total' => $pager['total']])) ?></span>
  <?php if ($pager['pages'] > 1): ?>
    <div class="pager__nav">
      <a class="btn btn--ghost btn--sm <?= $pager['page'] <= 1 ? 'is-disabled' : '' ?>" href="<?= e($link(max(1, $pager['page'] - 1))) ?>"><?= e(t('common.prev')) ?></a>
      <span><?= e($pager['page']) ?> / <?= e($pager['pages']) ?></span>
      <a class="btn btn--ghost btn--sm <?= $pager['page'] >= $pager['pages'] ? 'is-disabled' : '' ?>" href="<?= e($link(min($pager['pages'], $pager['page'] + 1))) ?>"><?= e(t('common.next')) ?></a>
    </div>
  <?php endif; ?>
</div>
