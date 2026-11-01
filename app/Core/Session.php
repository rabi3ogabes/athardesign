<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }
        session_cache_limiter(''); // Response::html sets Cache-Control itself (PHP's default no-store blocks back/forward cache)
        session_start();
        self::$started = true;
        // move one-shot flash data for this request
        $_SESSION['_flash_now'] = $_SESSION['_flash'] ?? [];
        $_SESSION['_flash'] = [];
        $_SESSION['_old_now'] = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        if ($key === '_old') {
            return $_SESSION['_old_now'] ?? $default;
        }
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $type, string $message): void
    {
        self::start();
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /** @return array<int,array{type:string,message:string}> */
    public static function flashes(): array
    {
        self::start();
        $f = $_SESSION['_flash_now'] ?? [];
        $_SESSION['_flash_now'] = [];
        return $f;
    }

    public static function keepOld(array $input): void
    {
        self::start();
        unset($input['_token'], $input['password'], $input['password_confirmation']);
        $_SESSION['_old'] = $input;
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$started = false;
    }
}
