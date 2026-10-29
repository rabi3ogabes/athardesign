<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Cache;
use App\Core\Db;

final class Testimonials
{
    public static function published(int $limit = 12): array
    {
        $all = Cache::remember('testimonials.published', fn() => Db::from('testimonials')->eq('status', 'published')->order('sort_order')->order('created_at', false)->get());
        return array_slice($all, 0, $limit);
    }

    public static function all(): array
    {
        return Db::from('testimonials')->order('sort_order')->order('created_at', false)->get();
    }

    public static function find(string $id): ?array
    {
        if (!preg_match('/^[0-9a-f\-]{36}$/', $id)) return null;
        return Db::from('testimonials')->eq('id', $id)->first();
    }

    public static function create(array $data): array
    {
        $row = Db::from('testimonials')->insert($data)[0] ?? [];
        Cache::flush();
        return $row;
    }

    public static function update(string $id, array $data): void
    {
        Db::from('testimonials')->eq('id', $id)->update($data);
        Cache::flush();
    }

    public static function delete(string $id): void
    {
        Db::from('testimonials')->eq('id', $id)->delete();
        Cache::flush();
    }
}
