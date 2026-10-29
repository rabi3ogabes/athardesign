<?php
declare(strict_types=1);

namespace App\Core;

/** Simple file cache for public content. Flushed on every admin write. */
final class Cache
{
    private static array $memory = [];

    private static function file(string $key): string
    {
        return STORAGE_PATH . '/cache/' . sha1($key) . '.cache';
    }

    public static function ttl(): int
    {
        return max(0, (int)env('CACHE_TTL', 600));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$memory)) {
            return self::$memory[$key];
        }
        $file = self::file($key);
        if (!is_file($file)) {
            return $default;
        }
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return $default;
        }
        $data = @unserialize($raw, ['allowed_classes' => false]);
        if (!is_array($data) || !isset($data['exp'], $data['val'])) {
            return $default;
        }
        if ($data['exp'] !== 0 && $data['exp'] < time()) {
            @unlink($file);
            return $default;
        }
        return self::$memory[$key] = $data['val'];
    }

    public static function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $ttl ??= self::ttl();
        self::$memory[$key] = $value;
        $payload = serialize(['exp' => $ttl > 0 ? time() + $ttl : 0, 'val' => $value]);
        $file = self::file($key);
        @file_put_contents($file . '.tmp', $payload, LOCK_EX);
        @rename($file . '.tmp', $file);
    }

    public static function remember(string $key, callable $resolver, ?int $ttl = null): mixed
    {
        $sentinel = new \stdClass();
        $hit = self::get($key, $sentinel);
        if ($hit !== $sentinel) {
            return $hit;
        }
        $value = $resolver();
        self::set($key, $value, $ttl);
        return $value;
    }

    public static function forget(string $key): void
    {
        unset(self::$memory[$key]);
        @unlink(self::file($key));
    }

    public static function flush(): void
    {
        self::$memory = [];
        foreach (glob(STORAGE_PATH . '/cache/*.cache') ?: [] as $f) {
            @unlink($f);
        }
    }
}
