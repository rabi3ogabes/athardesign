<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Users;
use PDO;

/**
 * First-run installation: requirements, database schema, starter content,
 * .env generation and the lock file that hides /install afterwards.
 * Shared by the web installer (/install) and the CLI tools.
 */
final class Installer
{
    public const SCHEMA = BASE_PATH . '/database/mysql/schema.sql';
    public const LOCK = STORAGE_PATH . '/installed.lock';

    /** True once the site is set up (lock file, or a configured database that already has an admin). */
    public static function installed(): bool
    {
        if (is_file(self::LOCK)) return true;
        if (!Db::configured()) return false;
        try {
            if (Db::hasTable('admin_users') && Users::count() > 0) {
                self::lock();
                return true;
            }
        } catch (\Throwable) {
        }
        return false;
    }

    /** The admin account was created in this session: /install may show its final screen once. */
    public static function justCompleted(): bool
    {
        Session::start();
        return (bool)Session::get('install_complete');
    }

    public static function lock(): void
    {
        @file_put_contents(self::LOCK, gmdate('c') . PHP_EOL);
    }

    /**
     * Environment checks. Each item: [label key, passed, required, note]
     * @return array<int,array{key:string,ok:bool,required:bool,note:string}>
     */
    public static function requirements(): array
    {
        $envDir = BASE_PATH;
        $envWritable = is_file(BASE_PATH . '/.env') ? is_writable(BASE_PATH . '/.env') : is_writable($envDir);
        $items = [
            ['php', PHP_VERSION_ID >= 80100, true, PHP_VERSION],
            ['pdo_mysql', extension_loaded('pdo_mysql'), true, ''],
            ['mbstring', extension_loaded('mbstring'), true, ''],
            ['json', extension_loaded('json'), true, ''],
            ['curl', extension_loaded('curl'), true, ''],
            ['fileinfo', extension_loaded('fileinfo'), true, ''],
            ['gd', extension_loaded('gd'), true, function_exists('imagewebp') ? 'WebP' : 'no WebP'],
            ['dom', extension_loaded('dom'), false, ''],
            ['openssl', extension_loaded('openssl'), false, ''],
            ['storage', is_writable(STORAGE_PATH) && is_writable(STORAGE_PATH . '/cache') && is_writable(STORAGE_PATH . '/logs'), true, 'storage/'],
            ['uploads', is_dir(UPLOADS_PATH) && is_writable(UPLOADS_PATH), true, 'public/uploads/'],
            ['env', $envWritable, true, '.env'],
            ['https', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') || PHP_SAPI === 'cli', false, ''],
        ];
        return array_map(fn($i) => ['key' => $i[0], 'ok' => (bool)$i[1], 'required' => $i[2], 'note' => (string)$i[3]], $items);
    }

    public static function requirementsMet(): bool
    {
        foreach (self::requirements() as $r) {
            if ($r['required'] && !$r['ok']) return false;
        }
        return true;
    }

    /** Create every table (idempotent). Returns the number of statements executed. */
    public static function applySchema(PDO $pdo): int
    {
        $sql = (string)file_get_contents(self::SCHEMA);
        if ($sql === '') {
            throw new DbException('database/mysql/schema.sql is missing.');
        }
        $n = 0;
        foreach (self::splitStatements($sql) as $statement) {
            $pdo->exec($statement);
            $n++;
        }
        return $n;
    }

    /** @return string[] */
    public static function splitStatements(string $sql): array
    {
        $lines = [];
        // explicit newline alternatives: \R without the u flag also matches the 0x85 byte inside Arabic UTF-8
        foreach (preg_split('/\r\n|\r|\n/', $sql) as $line) {
            if (preg_match('/^\s*--/', $line)) continue;
            $lines[] = $line;
        }
        $out = [];
        foreach (explode(";\n", implode("\n", $lines) . "\n") as $chunk) {
            $chunk = trim($chunk);
            if ($chunk !== '') $out[] = $chunk;
        }
        return $out;
    }

    /** Seed the starter content (pages, services, packages…). Returns the seeder output. */
    public static function seed(): string
    {
        ob_start();
        try {
            require BASE_PATH . '/database/seed.php';
        } finally {
            $out = (string)ob_get_clean();
        }
        Cache::flush();
        return $out;
    }

    /** Write .env from the installer's answers; existing unrelated keys are preserved. */
    public static function writeEnv(array $values): void
    {
        $file = BASE_PATH . '/.env';
        $current = [];
        $order = [];
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
                if (preg_match('/^\s*([A-Z0-9_]+)\s*=(.*)$/', $line, $m)) {
                    $current[$m[1]] = trim($m[2]);
                    $order[] = $m[1];
                }
            }
        }
        $defaults = [
            'APP_ENV'      => 'production',
            'APP_DEBUG'    => 'false',
            'APP_URL'      => '',
            'APP_TIMEZONE' => 'Asia/Qatar',
            'DB_HOST'      => '127.0.0.1',
            'DB_PORT'      => '3306',
            'DB_NAME'      => '',
            'DB_USER'      => '',
            'DB_PASS'      => '',
            'APP_SECRET'   => '',
            'CACHE_TTL'    => '600',
            'ADMIN_PATH'   => '/admin',
        ];
        $merged = $defaults;
        foreach ($current as $k => $v) $merged[$k] = self::unquote($v);
        foreach ($values as $k => $v) $merged[$k] = (string)$v;
        // legacy keys from the Supabase era are dropped
        foreach (['SUPABASE_URL', 'SUPABASE_KEY', 'SUPABASE_REST_PATH', 'SUPABASE_SECRET_KEY', 'SUPABASE_SERVICE_ROLE_KEY', 'SUPABASE_PUBLISHABLE_KEY'] as $k) unset($merged[$k]);
        if ($merged['APP_SECRET'] === '' || strlen($merged['APP_SECRET']) < 32) {
            $merged['APP_SECRET'] = bin2hex(random_bytes(32));
        }

        $lines = [
            '# ---------------------------------------------------------------',
            '#  أثر لتصميم الويب — environment configuration',
            '#  Generated by the installer on ' . gmdate('Y-m-d H:i') . ' UTC',
            '# ---------------------------------------------------------------',
            '',
            '# production | local',
            'APP_ENV=' . self::quote($merged['APP_ENV']),
            'APP_DEBUG=' . self::quote($merged['APP_DEBUG']),
            '# Public URL of the website (no trailing slash). Leave empty to auto-detect.',
            'APP_URL=' . self::quote($merged['APP_URL']),
            '# Timezone used for dates in the dashboard',
            'APP_TIMEZONE=' . self::quote($merged['APP_TIMEZONE']),
            '',
            '# MySQL / MariaDB',
            'DB_HOST=' . self::quote($merged['DB_HOST']),
            'DB_PORT=' . self::quote($merged['DB_PORT']),
            'DB_NAME=' . self::quote($merged['DB_NAME']),
            'DB_USER=' . self::quote($merged['DB_USER']),
            'DB_PASS=' . self::quote($merged['DB_PASS']),
            '',
            '# Random secret used to sign cookies, cron URLs and tokens. Never share it.',
            'APP_SECRET=' . self::quote($merged['APP_SECRET']),
            '',
            '# Cache lifetime (seconds) for public content. The admin panel clears it on every save.',
            'CACHE_TTL=' . self::quote($merged['CACHE_TTL']),
            '# Path of the admin panel',
            'ADMIN_PATH=' . self::quote($merged['ADMIN_PATH']),
        ];
        $known = array_keys($defaults);
        foreach ($merged as $k => $v) {
            if (!in_array($k, $known, true)) $lines[] = $k . '=' . self::quote($v);
        }
        $tmp = $file . '.tmp';
        if (@file_put_contents($tmp, implode("\n", $lines) . "\n", LOCK_EX) === false || !@rename($tmp, $file)) {
            @unlink($tmp);
            throw new \RuntimeException('Could not write the .env file. Make the project folder writable or create .env by hand.');
        }
        // make the new values visible to the rest of this request
        Env::load($file);
    }

    private static function quote(string $v): string
    {
        if ($v === '' || preg_match('/^[A-Za-z0-9_\-.\/:+@]+$/', $v)) return $v;
        if (!str_contains($v, '"')) return '"' . $v . '"';
        if (!str_contains($v, "'")) return "'" . $v . "'";
        return $v;
    }

    private static function unquote(string $v): string
    {
        if ($v !== '' && ($v[0] === '"' || $v[0] === "'") && str_ends_with($v, $v[0]) && strlen($v) >= 2) {
            return substr($v, 1, -1);
        }
        return $v;
    }
}
