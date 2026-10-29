<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Cache;
use App\Core\Db;

final class Services
{
    public static function published(): array
    {
        return Cache::remember('services.published', fn() => Db::from('services')->eq('status', 'published')->order('sort_order')->order('created_at')->get());
    }

    public static function featured(int $limit = 6): array
    {
        return array_slice(array_values(array_filter(self::published(), fn($s) => !empty($s['is_featured']))), 0, $limit);
    }

    public static function bySlug(string $slug): ?array
    {
        foreach (self::published() as $s) {
            if ($s['slug'] === $slug) return $s;
        }
        return null;
    }

    public static function all(): array
    {
        return Db::from('services')->order('sort_order')->order('created_at')->get();
    }

    public static function find(string $id): ?array
    {
        if (!preg_match('/^[0-9a-f\-]{36}$/', $id)) return null;
        return Db::from('services')->eq('id', $id)->first();
    }

    public static function create(array $data): array
    {
        $row = Db::from('services')->insert($data)[0] ?? [];
        Cache::flush();
        return $row;
    }

    public static function update(string $id, array $data): void
    {
        Db::from('services')->eq('id', $id)->update($data);
        Cache::flush();
    }

    public static function delete(string $id): void
    {
        Db::from('services')->eq('id', $id)->delete();
        Cache::flush();
    }

    public static function slugExists(string $slug, ?string $exceptId = null): bool
    {
        $q = Db::from('services')->select('id')->eq('slug', $slug);
        if ($exceptId) $q->neq('id', $exceptId);
        return $q->first() !== null;
    }

    public static function reorder(array $ids): void
    {
        foreach (array_values($ids) as $i => $id) {
            if (preg_match('/^[0-9a-f\-]{36}$/', (string)$id)) {
                Db::from('services')->eq('id', $id)->update(['sort_order' => ($i + 1) * 10]);
            }
        }
        Cache::flush();
    }
}
