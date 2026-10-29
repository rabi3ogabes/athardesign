<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Pages;
use App\Models\Redirects;
use App\Models\SeoTasks;

final class SeoController extends AdminController
{
    public function index(Request $r): string
    {
        $health = SeoTasks::health();
        $checks = [
            ['key' => 'missing_description', 'items' => $health['missing_description'] ?? [], 'fix' => 'pages'],
            ['key' => 'missing_keywords', 'items' => $health['missing_keywords'] ?? [], 'fix' => 'pages'],
            ['key' => 'missing_title', 'items' => $health['missing_title'] ?? [], 'fix' => 'pages'],
            ['key' => 'noindex', 'items' => $health['noindex'] ?? [], 'fix' => 'pages'],
            ['key' => 'services_missing_description', 'items' => $health['services_missing_description'] ?? [], 'fix' => 'services'],
            ['key' => 'projects_missing_cover', 'items' => $health['projects_missing_cover'] ?? [], 'fix' => 'projects'],
        ];
        $score = 100;
        foreach ($checks as $c) { if ($c['key'] !== 'noindex') $score -= min(20, count($c['items']) * 5); }
        $score -= min(10, (int)($health['media_missing_alt'] ?? 0));
        $settingsChecks = [
            ['label' => t('settings.google_verification'), 'ok' => trim((string)setting('google_verification', '')) !== '', 'url' => admin_url('settings/seo')],
            ['label' => t('settings.ga4_id'), 'ok' => trim((string)setting('ga4_id', '')) !== '', 'url' => admin_url('settings/seo')],
            ['label' => t('settings.og_image'), 'ok' => trim((string)setting('og_image', '')) !== '', 'url' => admin_url('settings/seo')],
            ['label' => t('settings.whatsapp_number'), 'ok' => strlen(preg_replace('/\D/', '', (string)setting('whatsapp_number', ''))) >= 8, 'url' => admin_url('settings/contact')],
            ['label' => t('settings.social') . ' (Instagram)', 'ok' => trim((string)setting('social_instagram', '')) !== '', 'url' => admin_url('settings/social')],
            ['label' => t('settings.address'), 'ok' => setting_tr('address') !== '', 'url' => admin_url('settings/contact')],
        ];
        foreach ($settingsChecks as $c) { if (!$c['ok']) $score -= 4; }
        return $this->render('admin/seo/index', [
            'title' => t('seo.title'),
            'health' => $health,
            'checks' => $checks,
            'settingsChecks' => $settingsChecks,
            'score' => max(0, $score),
            'pagesById' => array_column(Pages::all(), 'id', 'slug'),
        ]);
    }

    public function tasks(Request $r): string
    {
        $tasks = SeoTasks::all();
        $byStatus = array_fill_keys(SeoTasks::STATUSES, []);
        foreach ($tasks as $t) $byStatus[$t['status']][] = $t;
        return $this->render('admin/seo/tasks', ['title' => t('seo.tasks_title'), 'byStatus' => $byStatus, 'tasks' => $tasks, 'pages' => Pages::all()]);
    }

    public function storeTask(Request $r): void
    {
        $data = $this->collectTask($r);
        if (is_string($data)) {
            if ($r->wantsJson()) Response::json(['ok' => false, 'error' => $data], 422);
            $this->fail($data, admin_url('seo/tasks'), $r->all());
        }
        $t = SeoTasks::create($data);
        $this->log('create', 'seo_tasks', $t['id'] ?? '', ['title' => $data['title']]);
        if ($r->wantsJson()) Response::json(['ok' => true, 'task' => $t]);
        $this->ok(t('common.created'), admin_url('seo/tasks'));
    }

    public function updateTask(Request $r, string $id): void
    {
        if (!SeoTasks::find($id)) Response::notFound();
        $data = $this->collectTask($r);
        if (is_string($data)) {
            if ($r->wantsJson()) Response::json(['ok' => false, 'error' => $data], 422);
            $this->fail($data, admin_url('seo/tasks'));
        }
        SeoTasks::update($id, $data);
        $this->log('update', 'seo_tasks', $id, ['title' => $data['title']]);
        if ($r->wantsJson()) Response::json(['ok' => true]);
        $this->ok(t('common.saved'), admin_url('seo/tasks'));
    }

    /** Kanban drag/drop + quick status change */
    public function taskStatus(Request $r, string $id): void
    {
        $t = SeoTasks::find($id);
        if (!$t) Response::notFound();
        $status = in_array($r->str('status'), SeoTasks::STATUSES, true) ? $r->str('status') : $t['status'];
        $data = ['status' => $status, 'completed_at' => $status === 'done' ? gmdate('c') : null];
        if ($r->input('sort_order') !== null) $data['sort_order'] = $r->int('sort_order', (int)$t['sort_order']);
        SeoTasks::update($id, $data);
        if ($r->wantsJson()) Response::json(['ok' => true, 'status' => $status]);
        $this->ok($status === 'done' ? t('seo.task_completed') : t('common.saved'), admin_url('seo/tasks'));
    }

    public function deleteTask(Request $r, string $id): void
    {
        if (!SeoTasks::find($id)) Response::notFound();
        SeoTasks::delete($id);
        $this->log('delete', 'seo_tasks', $id);
        if ($r->wantsJson()) Response::json(['ok' => true]);
        $this->ok(t('common.deleted'), admin_url('seo/tasks'));
    }

    private function collectTask(Request $r): array|string
    {
        $v = Validator::make(['title' => $r->str('title'), 'url' => $r->str('url')], ['title' => 'required|max:200', 'url' => 'url|max:500']);
        if ($v->fails()) return $v->first();
        $checklist = [];
        foreach ($this->jsonField($r, 'checklist') as $c) {
            $text = is_array($c) ? (string)($c['text'] ?? '') : (string)$c;
            $text = mb_substr(trim(strip_tags($text)), 0, 200);
            if ($text === '') continue;
            $checklist[] = ['text' => $text, 'done' => is_array($c) && filter_var($c['done'] ?? false, FILTER_VALIDATE_BOOL)];
        }
        $due = $r->str('due_date');
        $status = in_array($r->str('status'), SeoTasks::STATUSES, true) ? $r->str('status') : 'todo';
        return [
            'title' => mb_substr(strip_tags($r->str('title')), 0, 200),
            'description' => mb_substr(strip_tags($r->str('description')), 0, 3000),
            'page_slug' => mb_substr($r->str('page_slug'), 0, 120) ?: null,
            'url' => $r->str('url') !== '' ? mb_substr($r->str('url'), 0, 500) : null,
            'category' => in_array($r->str('category'), SeoTasks::CATEGORIES, true) ? $r->str('category') : 'onpage',
            'priority' => in_array($r->str('priority'), SeoTasks::PRIORITIES, true) ? $r->str('priority') : 'medium',
            'status' => $status,
            'due_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $due) ? $due : null,
            'completed_at' => $status === 'done' ? gmdate('c') : null,
            'checklist' => $checklist,
            'notes' => mb_substr(strip_tags($r->str('notes')), 0, 5000),
            'sort_order' => $r->int('sort_order', 100),
        ];
    }

    // ---------------- redirects ----------------
    public function redirects(Request $r): string
    {
        return $this->render('admin/seo/redirects', ['title' => t('seo.redirects_title'), 'rows' => Redirects::all()]);
    }

    public function storeRedirect(Request $r): void
    {
        $from = '/' . trim($r->str('from_path'), '/ ');
        $to = trim($r->str('to_path'));
        if (!preg_match('#^https?://#', $to)) $to = '/' . trim($to, '/ ');
        $v = Validator::make(['from_path' => $from, 'to_path' => $to], ['from_path' => 'required|max:300', 'to_path' => 'required|max:500']);
        if ($v->fails()) $this->fail($v->first(), admin_url('seo/redirects'), $r->all());
        if ($from === $to || $from === '/') $this->fail(t('seo.redirect_loop'), admin_url('seo/redirects'), $r->all());
        foreach (Redirects::all() as $x) {
            if ($x['from_path'] === $from) $this->fail(t('seo.redirect_exists'), admin_url('seo/redirects'), $r->all());
        }
        Redirects::create(['from_path' => $from, 'to_path' => $to, 'code' => $r->int('code', 301) === 302 ? 302 : 301, 'is_active' => true]);
        $this->log('create', 'redirects', $from, ['to' => $to]);
        $this->ok(t('common.created'), admin_url('seo/redirects'));
    }

    public function deleteRedirect(Request $r, string $id): void
    {
        if (!$this->isUuid($id)) Response::notFound();
        Redirects::delete($id);
        $this->log('delete', 'redirects', $id);
        $this->ok(t('common.deleted'), admin_url('seo/redirects'));
    }
}
