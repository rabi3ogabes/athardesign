<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\Visitors;

final class VisitorsController extends AdminController
{
    public function index(Request $r): string
    {
        $filters = ['bots' => $r->bool('bots'), 'country' => $r->str('country'), 'device' => $r->str('device'), 'search' => $r->str('q')];
        $per = 30;
        $page = max(1, $r->int('page', 1));
        [$rows, $total] = Visitors::paginate($page, $per, $filters);
        return $this->render('admin/visitors/index', [
            'title' => t('visitors.title'),
            'rows' => $rows,
            'pager' => $this->pager($r, $total, $per),
            'filters' => $filters,
        ]);
    }

    public function show(Request $r, string $id): string
    {
        $v = Visitors::find($id);
        if (!$v) Response::notFound();
        return $this->render('admin/visitors/show', [
            'title' => t('visitors.details'),
            'v' => $v,
            'timeline' => Visitors::timeline($id),
        ]);
    }

    public function clicks(Request $r): string
    {
        [$from, $to, $key] = DashboardController::range($r);
        $filters = ['bots' => $r->bool('bots'), 'event' => $r->str('event'), 'from' => $from, 'to' => $to];
        $per = 40;
        [$rows, $total] = Visitors::clicks(max(1, $r->int('page', 1)), $per, $filters);
        return $this->render('admin/visitors/clicks', [
            'title' => t('clicks.title'),
            'rows' => $rows,
            'pager' => $this->pager($r, $total, $per),
            'filters' => $filters,
            'range' => $key,
        ]);
    }

    public function visits(Request $r): string
    {
        [$from, $to, $key] = DashboardController::range($r);
        $filters = ['bots' => $r->bool('bots'), 'from' => $from, 'to' => $to];
        $per = 50;
        [$rows, $total] = Visitors::visits(max(1, $r->int('page', 1)), $per, $filters);
        return $this->render('admin/visitors/visits', [
            'title' => t('clicks.pageviews_title'),
            'rows' => $rows,
            'pager' => $this->pager($r, $total, $per),
            'filters' => $filters,
            'range' => $key,
        ]);
    }

    public function export(Request $r, string $type): void
    {
        [$from, $to] = DashboardController::range($r);
        $type = in_array($type, ['visits', 'clicks', 'visitors', 'leads'], true) ? $type : 'visits';
        $rows = match ($type) {
            'clicks' => Visitors::clicks(1, 5000, ['bots' => $r->bool('bots'), 'from' => $from, 'to' => $to])[0],
            'visitors' => Visitors::paginate(1, 5000, ['bots' => $r->bool('bots')])[0],
            'leads' => \App\Models\Leads::paginate(1, 5000)[0],
            default => Visitors::visits(1, 5000, ['bots' => $r->bool('bots'), 'from' => $from, 'to' => $to])[0],
        };
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $type . '-' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF"; // BOM so Excel reads Arabic correctly
        $out = fopen('php://output', 'w');
        if ($rows) {
            $cols = array_keys($rows[0]);
            fputcsv($out, $cols, ',', '"', '');
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string)$v, array_values($row)), ',', '"', '');
            }
        }
        fclose($out);
        exit;
    }
}
