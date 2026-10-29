<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Users;

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (self::$loaded) return self::$user;
        self::$loaded = true;
        $id = Session::get('admin_id');
        $stamp = Session::get('admin_stamp');
        if (!is_string($id) || $id === '') return null;
        $user = Users::find($id);
        if (!$user || !$user['is_active']) {
            self::logout();
            return null;
        }
        // password change invalidates other sessions
        if ($stamp !== substr(sha1($user['password_hash']), 0, 16)) {
            self::logout();
            return null;
        }
        return self::$user = $user;
    }

    public static function check(): bool { return self::user() !== null; }

    public static function id(): ?string { return self::user()['id'] ?? null; }

    public static function role(): string { return self::user()['role'] ?? 'editor'; }

    public static function can(string $ability): bool
    {
        $role = self::role();
        return match ($ability) {
            'manage_users', 'manage_settings', 'danger' => in_array($role, ['owner', 'admin'], true),
            default => true,
        };
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('admin_id', $user['id']);
        Session::set('admin_stamp', substr(sha1($user['password_hash']), 0, 16));
        Session::set('admin_login_at', time());
        self::$user = $user;
        self::$loaded = true;
        Users::touchLogin($user['id']);
    }

    public static function logout(): void
    {
        Session::forget('admin_id');
        Session::forget('admin_stamp');
        self::$user = null;
        self::$loaded = true;
    }

    /** Returns true when the IP exceeded the failed-login limit. */
    public static function throttled(string $ip): bool
    {
        return Users::failedAttempts($ip, 15) >= 8;
    }

    public static function attempt(string $email, string $password, string $ip): ?array
    {
        $email = mb_strtolower(trim($email));
        $user = Users::findByEmail($email);
        $ok = $user && $user['is_active'] && password_verify($password, $user['password_hash']);
        Users::logAttempt($email, $ip, (bool)$ok);
        if (!$ok) return null;
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            Users::update($user['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
            $user = Users::find($user['id']) ?? $user;
        }
        return $user;
    }
}
