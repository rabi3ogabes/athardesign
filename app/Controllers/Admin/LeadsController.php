<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\Leads;

final class LeadsController extends AdminController
{
    public function index(Request $r): string
    {
        $per = 25;
        $status = in_array($r->str('status'), ['new', 'contacted', 'won', 'lost'], true) ? $r->str('status') : '';
        [$rows, $total] = Leads::paginate(max(1, $r->int('page', 1)), $per, $status, $r->str('q'));
        return $this->render('admin/leads/index', ['title' => t('leads.title'), 'rows' => $rows, 'pager' => $this->pager($r, $total, $per), 'status' => $status, 'q' => $r->str('q')]);
    }

    public function show(Request $r, string $id): string
    {
        $lead = Leads::find($id);
        if (!$lead) Response::notFound();
        return $this->render('admin/leads/show', ['title' => t('leads.details') . ' — ' . $lead['name'], 'lead' => $lead]);
    }

    public function update(Request $r, string $id): void
    {
        if (!Leads::find($id)) Response::notFound();
        $status = in_array($r->str('status'), ['new', 'contacted', 'won', 'lost'], true) ? $r->str('status') : 'new';
        Leads::update($id, ['status' => $status, 'notes' => mb_substr($r->str('notes'), 0, 5000)]);
        $this->log('update', 'leads', $id, ['status' => $status]);
        if ($r->wantsJson()) Response::json(['ok' => true]);
        $this->ok(t('common.saved'), admin_url('leads/' . $id));
    }

    public function destroy(Request $r, string $id): void
    {
        if (!Leads::find($id)) Response::notFound();
        Leads::delete($id);
        $this->log('delete', 'leads', $id);
        $this->ok(t('common.deleted'), admin_url('leads'));
    }
}
