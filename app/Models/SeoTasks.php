<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

final class SeoTasks
{
    public const STATUSES = ['todo', 'in_progress', 'review', 'done'];
    public const PRIORITIES = ['low', 'medium', 'high'];
    public const CATEGORIES = ['technical', 'content', 'onpage', 'offpage', 'local', 'social', 'performance'];

    public static function all(): array
    {
        return Db::from('seo_tasks')->order('sort_order')->order('created_at', false)->get();
    }

    public static function find(string $id): ?array
    {
        if (!preg_match('/^[0-9a-f\-]{36}$/', $id)) return null;
        return Db::from('seo_tasks')->eq('id', $id)->first();
    }

    public static function create(array $data): array
    {
        return Db::from('seo_tasks')->insert($data)[0] ?? [];
    }

    public static function update(string $id, array $data): void
    {
        Db::from('seo_tasks')->eq('id', $id)->update($data);
    }

    public static function delete(string $id): void
    {
        Db::from('seo_tasks')->eq('id', $id)->delete();
    }

    public static function health(): array
    {
        $h = Db::rpc('seo_health');
        return is_array($h) ? $h : [];
    }

    public static function openCount(): int
    {
        return Db::from('seo_tasks')->neq('status', 'done')->count();
    }
}
