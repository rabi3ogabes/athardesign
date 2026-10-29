<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\Activity;
use App\Models\Leads;
use App\Models\SeoTasks;
use App\Models\Visitors;

final class DashboardController extends AdminController
{
    /** @return array{0:string,1:string,2:string} from, to (ISO, UTC) and range key */
    public static function range(Request $r): array
    {
        $tz = new \DateTimeZone(env('APP_TIMEZONE', 'Asia/Qatar'));
        $key = $r->str('range', '30');
        $to = (new \DateTimeImmutable('tomorrow', $tz))->setTime(0, 0);
        $from = match ($key) {
            'today' => (new \DateTimeImmutable('today', $tz)),
            '7' => $to->modify('-7 days'),
            '90' => $to->modify('-90 days'),
            'month' => (new \DateTimeImmutable('first day of this month', $tz))->setTime(0, 0),
            'custom' => (\DateTimeImmutable::createFromFormat('Y-m-d', $r->str('from'), $tz) ?: $to->modify('-30 days'))->setTime(0, 0),
            default => $to->modify('-30 days'),
        };
        if ($key === 'custom') {
            $t = \DateTimeImmutable::createFromFormat('Y-m-d', $r->str('to'), $tz);
            if ($t) $to = $t->setTime(0, 0)->modify('+1 day');
        }
        if ($key !== '7' && $key !== '90' && $key !== 'today' && $key !== 'month' && $key !== 'custom') $key = '30';
        return [$from->setTimezone(new \DateTimeZone('UTC'))->format('c'), $to->setTimezone(new \DateTimeZone('UTC'))->format('c'), $key];
    }

    public function index(Request $r): string
    {
        [$from, $to, $key] = self::range($r);
        $bots = $r->bool('bots');
        $overview = Visitors::overview($from, $to, $bots);
        return $this->render('admin/dashboard', [
            'title' => t('dashboard.title'),
            'useCharts' => true,
            'o' => $overview,
            'range' => $key,
            'bots' => $bots,
            'from' => substr($from, 0, 10),
            'to' => substr($to, 0, 10),
            'live' => Visitors::liveCount(5),
            'recentClicks' => Visitors::recentClicks(8),
            'recentLeads' => Leads::recent(5),
            'recentActivity' => Activity::recent(6),
            'seoHealth' => SeoTasks::health(),
        ]);
    }

    /** JSON used by the dashboard chart when the range changes without reload */
    public function data(Request $r): void
    {
        [$from, $to] = self::range($r);
        Response::json(Visitors::overview($from, $to, $r->bool('bots')));
    }
}
