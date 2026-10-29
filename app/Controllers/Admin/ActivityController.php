<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\Activity;

final class ActivityController extends AdminController
{
    public function index(Request $r): string
    {
        $per = 40;
        [$rows, $total] = Activity::paginate(max(1, $r->int('page', 1)), $per);
        return $this->render('admin/activity', ['title' => t('activity.title'), 'rows' => $rows, 'pager' => $this->pager($r, $total, $per)]);
    }
}
