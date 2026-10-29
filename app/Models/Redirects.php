<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Cache;
use App\Core\Db;

final class Redirects
{
    public static function active(): array
    {
        return Cache::remember('redirects.active', fn() => Db::from('redirects')->eq('is_active', true)->get());
    }

    public static function match(string $path): ?array
    {
        $path = rtrim($path, '/') ?: '/';
        foreach (self::active() as $r) {
            if ((rtrim($r['from_path'], '/') ?: '/') === $path) return $r;
        }
        return null;
    }

    public static function all(): array
    {
        return Db::from('redirects')->order('created_at', false)->get();
    }

    public static function create(array $data): void
    {
        Db::from('redirects')->insert($data);
        Cache::flush();
    }

    public static function update(string $id, array $data): void
    {
        Db::from('redirects')->eq('id', $id)->update($data);
        Cache::flush();
    }

    public static function delete(string $id): void
    {
        Db::from('redirects')->eq('id', $id)->delete();
        Cache::flush();
    }

    public static function hit(string $id): void
    {
        try {
            Db::rpc('increment_redirect_hits', ['p_id' => $id]);
        } catch (\Throwable) {
        }
    }
}
