<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Cache;
use App\Core\Db;

final class Pages
{
    public const SYSTEM = ['home', 'services', 'work', 'contact', '404'];

    /** All published pages (cached) */
    public static function published(): array
    {
        return Cache::remember('pages.published', fn() => Db::from('pages')->eq('status', 'published')->order('nav_order')->order('created_at')->get());
    }

    public static function nav(): array
    {
        return array_values(array_filter(self::published(), fn($p) => !empty($p['show_in_nav']) && $p['slug'] !== '404' && $p['slug'] !== 'home'));
    }

    public static function footer(): array
    {
        return array_values(array_filter(self::published(), fn($p) => !empty($p['show_in_footer']) && $p['slug'] !== '404'));
    }

    public static function bySlug(string $slug, bool $publishedOnly = true): ?array
    {
        if ($publishedOnly) {
            foreach (self::published() as $p) {
                if ($p['slug'] === $slug) return $p;
            }
            return null;
        }
        return Db::from('pages')->eq('slug', $slug)->first();
    }

    public static function all(): array
    {
        return Db::from('pages')->select('id,slug,template,title,status,show_in_nav,show_in_footer,nav_order,is_system,noindex,meta_description,updated_at')->order('nav_order')->order('created_at')->get();
    }

    public static function find(string $id): ?array
    {
        if (!preg_match('/^[0-9a-f\-]{36}$/', $id)) return null;
        return Db::from('pages')->eq('id', $id)->first();
    }

    public static function create(array $data): array
    {
        $row = Db::from('pages')->insert($data)[0] ?? [];
        Cache::flush();
        return $row;
    }

    public static function update(string $id, array $data): void
    {
        Db::from('pages')->eq('id', $id)->update($data);
        Cache::flush();
    }

    public static function delete(string $id): void
    {
        Db::from('pages')->eq('id', $id)->eq('is_system', false)->delete();
        Cache::flush();
    }

    public static function slugExists(string $slug, ?string $exceptId = null): bool
    {
        $q = Db::from('pages')->select('id')->eq('slug', $slug);
        if ($exceptId) $q->neq('id', $exceptId);
        return $q->first() !== null;
    }
}
