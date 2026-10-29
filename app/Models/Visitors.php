<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

final class Visitors
{
    public static function overview(string $from, string $to, bool $bots = false): array
    {
        $r = Db::rpc('dashboard_overview', ['p_from' => $from, 'p_to' => $to, 'p_bots' => $bots]);
        return is_array($r) ? $r : [];
    }

    /** @return array{0:array,1:int} */
    public static function paginate(int $page, int $perPage = 30, array $filters = []): array
    {
        $q = Db::from('visitors')->order('last_seen', false)->limit($perPage)->offset(($page - 1) * $perPage);
        if (empty($filters['bots'])) $q->eq('is_bot', false);
        if (!empty($filters['country'])) $q->eq('country_code', strtoupper(substr((string)$filters['country'], 0, 2)));
        if (!empty($filters['device'])) $q->eq('device', (string)$filters['device']);
        if (!empty($filters['search'])) {
            $s = str_replace(['%', '*', ',', '(', ')', '"'], '', (string)$filters['search']);
            $q->or('ip.ilike.*' . $s . '*,city.ilike.*' . $s . '*,country.ilike.*' . $s . '*,referrer.ilike.*' . $s . '*,landing_page.ilike.*' . $s . '*');
        }
        return $q->getWithCount();
    }

    public static function find(string $id): ?array
    {
        if (!preg_match('/^[0-9a-f\-]{36}$/', $id)) return null;
        return Db::from('visitors')->eq('id', $id)->first();
    }

    public static function timeline(string $id): array
    {
        $r = Db::rpc('visitor_timeline', ['p_visitor' => $id, 'p_limit' => 300]);
        return is_array($r) ? $r : [];
    }

    /** @return array{0:array,1:int} */
    public static function clicks(int $page, int $perPage = 40, array $filters = []): array
    {
        $q = Db::from('clicks')->order('created_at', false)->limit($perPage)->offset(($page - 1) * $perPage);
        if (empty($filters['bots'])) $q->eq('is_bot', false);
        if (!empty($filters['event'])) $q->eq('event', (string)$filters['event']);
        if (!empty($filters['from'])) $q->gte('created_at', (string)$filters['from']);
        if (!empty($filters['to'])) $q->lt('created_at', (string)$filters['to']);
        return $q->getWithCount();
    }

    /** @return array{0:array,1:int} */
    public static function visits(int $page, int $perPage = 50, array $filters = []): array
    {
        $q = Db::from('visits')->order('created_at', false)->limit($perPage)->offset(($page - 1) * $perPage);
        if (empty($filters['bots'])) $q->eq('is_bot', false);
        if (!empty($filters['from'])) $q->gte('created_at', (string)$filters['from']);
        if (!empty($filters['to'])) $q->lt('created_at', (string)$filters['to']);
        return $q->getWithCount();
    }

    public static function recentClicks(int $limit = 8): array
    {
        return Db::from('clicks')->eq('is_bot', false)->order('created_at', false)->limit($limit)->get();
    }

    public static function prune(int $days): array
    {
        $r = Db::rpc('prune_analytics', ['p_days' => max(7, $days)]);
        return is_array($r) ? $r : [];
    }

    public static function liveCount(int $minutes = 5): int
    {
        try {
            $rows = Db::from('visits')->select('visitor_id')->eq('is_bot', false)->gte('created_at', gmdate('c', time() - $minutes * 60))->limit(500)->get();
            return count(array_unique(array_column($rows, 'visitor_id')));
        } catch (\Throwable) {
            return 0;
        }
    }
}
