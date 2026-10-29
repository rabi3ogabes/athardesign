<?php /** @var array $byStatus @var array $tasks @var array $pages */
use App\Models\SeoTasks;
$today = date('Y-m-d');
$pageOpts = ['' => '—'];
foreach ($pages as $p) $pageOpts[$p['slug']] = tr($p['title']) . ' (/' . ($p['slug'] === 'home' ? '' : $p['slug']) . ')';
?>
<div class="page-head"><p class="page-head__sub"><?= e(t('seo.tasks_subtitle')) ?></p><button type="button" class="btn btn--primary" data-task-new><?= icon('plus') ?><span><?= e(t('seo.new_task')) ?></span></button></div>
<?php if (!$tasks): ?><div class="card"><p class="empty"><?= e(t('seo.no_tasks')) ?></p></div><?php endif; ?>
<div class="kanban" data-kanban data-status-url="<?= e(admin_url('seo/tasks')) ?>">
  <?php foreach (SeoTasks::STATUSES as $st): ?>
    <div class="kanban__col" data-status="<?= $st ?>">
      <div class="kanban__head"><span><?= e(t('seo.' . $st)) ?></span><span class="kanban__count"><?= count($byStatus[$st]) ?></span></div>
      <div class="kanban__list" data-kanban-list="<?= $st ?>">
        <?php foreach ($byStatus[$st] as $t): $cl = is_array($t['checklist']) ? $t['checklist'] : []; $done = count(array_filter($cl, fn($c) => !empty($c['done']))); $overdue = $t['due_date'] && $t['due_date'] < $today && $st !== 'done'; ?>
          <div class="task <?= $st === 'done' ? 'is-done' : '' ?>" data-id="<?= e($t['id']) ?>" data-task='<?= e(json_encode($t, JSON_UNESCAPED_UNICODE)) ?>'>
            <div class="task__title"><?= e($t['title']) ?></div>
            <div class="task__meta">
              <span class="pill pill--<?= e($t['priority']) ?>"><?= e(t('seo.' . $t['priority'])) ?></span>
              <span class="pill pill--muted"><?= e(t('seo.' . $t['category'])) ?></span>
              <?php if ($t['due_date']): ?><span class="task__due <?= $overdue ? 'is-overdue' : '' ?>"><?= icon('calendar') ?> <?= e($t['due_date']) ?></span><?php endif; ?>
            </div>
            <?php if ($cl): ?><div class="task__progress" title="<?= $done ?>/<?= count($cl) ?>"><i style="width:<?= round($done / count($cl) * 100) ?>%"></i></div><?php endif; ?>
            <div class="task__actions">
              <button type="button" data-task-edit title="<?= e(t('common.edit')) ?>"><?= icon('pencil-simple') ?></button>
              <?php if ($st !== 'done'): ?><button type="button" data-task-done title="<?= e(t('seo.done')) ?>"><?= icon('check-circle') ?></button><?php endif; ?>
              <button type="button" data-task-delete title="<?= e(t('common.delete')) ?>"><?= icon('trash') ?></button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="modal" id="task-modal" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close></div>
  <div class="modal__panel" role="dialog" aria-modal="true">
    <form method="post" action="<?= e(admin_url('seo/tasks')) ?>" data-task-form>
      <?= csrf_field() ?>
      <div class="modal__head"><h3 data-task-modal-title><?= e(t('seo.new_task')) ?></h3><button type="button" class="modal__close" data-modal-close><?= icon('x') ?></button></div>
      <div class="modal__body stack">
        <?= view('admin/partials/field', ['name' => 'title', 'label' => t('seo.task_title'), 'required' => true]) ?>
        <?= view('admin/partials/field', ['name' => 'description', 'label' => t('seo.description'), 'type' => 'textarea', 'rows' => 3]) ?>
        <div class="grid-2">
          <?= view('admin/partials/field', ['name' => 'category', 'label' => t('seo.category'), 'type' => 'select', 'options' => array_combine(SeoTasks::CATEGORIES, array_map(fn($c) => t('seo.' . $c), SeoTasks::CATEGORIES))]) ?>
          <?= view('admin/partials/field', ['name' => 'priority', 'label' => t('seo.priority'), 'type' => 'select', 'value' => 'medium', 'options' => array_combine(SeoTasks::PRIORITIES, array_map(fn($c) => t('seo.' . $c), SeoTasks::PRIORITIES))]) ?>
          <?= view('admin/partials/field', ['name' => 'status', 'label' => t('seo.status'), 'type' => 'select', 'options' => array_combine(SeoTasks::STATUSES, array_map(fn($c) => t('seo.' . $c), SeoTasks::STATUSES))]) ?>
          <?= view('admin/partials/field', ['name' => 'due_date', 'label' => t('seo.due'), 'type' => 'date']) ?>
          <?= view('admin/partials/field', ['name' => 'page_slug', 'label' => t('seo.page'), 'type' => 'select', 'options' => $pageOpts]) ?>
          <?= view('admin/partials/field', ['name' => 'url', 'label' => t('seo.url'), 'type' => 'url', 'placeholder' => 'https://']) ?>
        </div>
        <div class="field">
          <div class="field__label"><label><?= e(t('seo.checklist')) ?></label></div>
          <div class="checklist-edit" data-checklist></div>
          <button type="button" class="btn btn--ghost btn--sm" data-checklist-add><?= icon('plus') ?><span><?= e(t('seo.add_check')) ?></span></button>
          <input type="hidden" name="checklist" data-checklist-input>
        </div>
        <?= view('admin/partials/field', ['name' => 'notes', 'label' => t('seo.notes'), 'type' => 'textarea', 'rows' => 3]) ?>
      </div>
      <div class="modal__foot"><button type="button" class="btn btn--ghost" data-modal-close><?= e(t('common.cancel')) ?></button><button type="submit" class="btn btn--primary"><?= e(t('common.save')) ?></button></div>
    </form>
  </div>
</div>
