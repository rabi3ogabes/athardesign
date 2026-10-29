<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

final class Leads
{
    public static function create(array $data): array
    {
        return Db::from('leads')->insert($data)[0] ?? [];
    }

    /** @return array{0:array,1:int} */
    public static function paginate(int $page, int $perPage = 25, string $status = '', string $search = ''): array
    {
        $q = Db::from('leads')->order('created_at', false)->limit($perPage)->offset(($page - 1) * $perPage);
        if ($status !== '') $q->eq('status', $status);
        if ($search !== '') {
            $s = str_replace(['%', '*', ',', '(', ')', '"'], '', $search);
            $q->or('name.ilike.*' . $s . '*,phone.ilike.*' . $s . '*,email.ilike.*' . $s . '*,company.ilike.*' . $s . '*');
        }
        return $q->getWithCount();
    }

    public static function recent(int $limit = 5): array
    {
        return Db::from('leads')->order('created_at', false)->limit($limit)->get();
    }

    public static function countNew(): int
    {
        return Db::from('leads')->eq('status', 'new')->count();
    }

    public static function find(string $id): ?array
    {
        if (!preg_match('/^[0-9a-f\-]{36}$/', $id)) return null;
        return Db::from('leads')->eq('id', $id)->first();
    }

    public static function update(string $id, array $data): void
    {
        Db::from('leads')->eq('id', $id)->update($data);
    }

    public static function delete(string $id): void
    {
        Db::from('leads')->eq('id', $id)->delete();
    }

    /** Simple per-IP rate limit for the public form: max N per hour */
    public static function recentFromIp(string $ip, int $minutes = 60): int
    {
        return Db::from('leads')->eq('ip', $ip)->gte('created_at', gmdate('c', time() - $minutes * 60))->count();
    }
}
