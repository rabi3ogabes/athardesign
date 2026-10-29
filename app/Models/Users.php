<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

final class Users
{
    public static function count(): int
    {
        return Db::from('admin_users')->count();
    }

    public static function all(): array
    {
        return Db::from('admin_users')->select('id,name,email,role,lang,is_active,last_login_at,created_at')->order('created_at')->get();
    }

    public static function find(string $id): ?array
    {
        if (!preg_match('/^[0-9a-f\-]{36}$/', $id)) return null;
        return Db::from('admin_users')->eq('id', $id)->first();
    }

    public static function findByEmail(string $email): ?array
    {
        return Db::from('admin_users')->eq('email', $email)->first();
    }

    public static function create(array $data): array
    {
        return Db::from('admin_users')->insert($data)[0] ?? [];
    }

    public static function update(string $id, array $data): void
    {
        Db::from('admin_users')->eq('id', $id)->update($data);
    }

    public static function delete(string $id): void
    {
        Db::from('admin_users')->eq('id', $id)->delete();
    }

    public static function touchLogin(string $id): void
    {
        try {
            Db::from('admin_users')->eq('id', $id)->update(['last_login_at' => gmdate('c')]);
        } catch (\Throwable) {
        }
    }

    public static function logAttempt(string $email, string $ip, bool $success): void
    {
        try {
            Db::from('login_attempts')->insert(['email' => mb_substr($email, 0, 120), 'ip' => $ip, 'success' => $success]);
        } catch (\Throwable) {
        }
    }

    public static function failedAttempts(string $ip, int $minutes): int
    {
        try {
            return Db::from('login_attempts')->eq('ip', $ip)->eq('success', false)->gte('created_at', gmdate('c', time() - $minutes * 60))->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
