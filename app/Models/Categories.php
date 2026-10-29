<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Cache;
use App\Core\Db;

final class Categories
{
    public static function all(): array
    {
        return Cache::remember('categories.all', fn() => Db::from('project_categories')->order('sort_order')->order('created_at')->get());
    }

    public static function find(string $id): ?array
    {
        if (!preg_match('/^[0-9a-f\-]{36}$/', $id)) return null;
        return Db::from('project_categories')->eq('id', $id)->first();
    }

    public static function create(array $data): array
    {
        $row = Db::from('project_categories')->insert($data)[0] ?? [];
        Cache::flush();
        return $row;
    }

    public static function update(string $id, array $data): void
    {
        Db::from('project_categories')->eq('id', $id)->update($data);
        Cache::flush();
    }

    public static function delete(string $id): void
    {
        Db::from('project_categories')->eq('id', $id)->delete();
        Cache::flush();
    }
}
