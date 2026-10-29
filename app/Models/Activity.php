<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Db;

final class Activity
{
    public static function log(string $action, string $entity = '', string $entityId = '', array $details = []): void
    {
        try {
            $u = Auth::user();
            Db::from('activity_log')->insert([
                'user_id' => $u['id'] ?? null,
                'user_name' => $u['name'] ?? 'system',
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId,
                'details' => $details ?: null,
                'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);
        } catch (\Throwable) {
        }
    }

    /** @return array{0:array,1:int} */
    public static function paginate(int $page, int $perPage = 40): array
    {
        return Db::from('activity_log')->order('created_at', false)->limit($perPage)->offset(($page - 1) * $perPage)->getWithCount();
    }

    public static function recent(int $limit = 8): array
    {
        return Db::from('activity_log')->order('created_at', false)->limit($limit)->get();
    }
}
