<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Cache;
use App\Core\Db;

final class Projects
{
    private const SELECT = '*,category:project_categories(id,slug,name)';

    public static function published(): array
    {
        return Cache::remember('projects.published', fn() => Db::from('projects')->select(self::SELECT)->eq('status', 'published')->order('sort_order')->order('created_at', false)->get());
    }

    public static function featured(int $limit = 6): array
    {
        return array_slice(array_values(array_filter(self::published(), fn($p) => !empty($p['is_featured']))), 0, $limit);
    }

    public static function byCategory(string $slug): array
    {
        return array_values(array_filter(self::published(), fn($p) => ($p['category']['slug'] ?? '') === $slug));
    }

    public static function bySlug(string $slug): ?array
    {
        foreach (self::published() as $p) {
            if ($p['slug'] === $slug) return $p;
        }
        return null;
    }

    public static function related(array $project, int $limit = 3): array
    {
        $cat = $project['category']['slug'] ?? '';
        $same = array_filter(self::published(), fn($p) => $p['id'] !== $project['id'] && ($cat === '' || ($p['category']['slug'] ?? '') === $cat));
        if (count($same) < $limit) {
            $same = array_filter(self::published(), fn($p) => $p['id'] !== $project['id']);
        }
        return array_slice(array_values($same), 0, $limit);
    }

    public static function all(): array
    {
        return Db::from('projects')->select(self::SELECT)->order('sort_order')->order('created_at', false)->get();
    }

    public static function find(string $id): ?array
    {
        if (!preg_match('/^[0-9a-f\-]{36}$/', $id)) return null;
        return Db::from('projects')->select(self::SELECT)->eq('id', $id)->first();
    }

    public static function create(array $data): array
    {
        $row = Db::from('projects')->insert($data)[0] ?? [];
        Cache::flush();
        return $row;
    }

    public static function update(string $id, array $data): void
    {
        Db::from('projects')->eq('id', $id)->update($data);
        Cache::flush();
    }

    public static function delete(string $id): void
    {
        Db::from('projects')->eq('id', $id)->delete();
        Cache::flush();
    }

    public static function slugExists(string $slug, ?string $exceptId = null): bool
    {
        $q = Db::from('projects')->select('id')->eq('slug', $slug);
        if ($exceptId) $q->neq('id', $exceptId);
        return $q->first() !== null;
    }

    public static function reorder(array $ids): void
    {
        foreach (array_values($ids) as $i => $id) {
            if (preg_match('/^[0-9a-f\-]{36}$/', (string)$id)) {
                Db::from('projects')->eq('id', $id)->update(['sort_order' => ($i + 1) * 10]);
            }
        }
        Cache::flush();
    }
}
