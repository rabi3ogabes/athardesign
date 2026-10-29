<?php
declare(strict_types=1);

/**
 * Deployment check: run `php tools/check.php` on the server
 * to verify PHP extensions, writable folders and the MySQL connection.
 */
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Db;
use App\Core\Installer;

$ok = true;
$line = function (string $label, bool $pass, string $note = '', bool $required = true) use (&$ok): void {
    $ok = $ok && ($pass || !$required);
    echo ($pass ? '[ OK ] ' : ($required ? '[FAIL] ' : '[WARN] ')) . $label . ($note !== '' ? ' — ' . $note : '') . PHP_EOL;
};

echo "أثر لتصميم الويب — environment check\n\n";
$labels = [
    'php' => 'PHP >= 8.1', 'pdo_mysql' => 'extension pdo_mysql', 'mbstring' => 'extension mbstring', 'json' => 'extension json',
    'curl' => 'extension curl', 'fileinfo' => 'extension fileinfo', 'gd' => 'extension gd', 'dom' => 'extension dom', 'openssl' => 'extension openssl',
    'storage' => 'storage/ writable', 'uploads' => 'public/uploads writable', 'env' => '.env writable', 'https' => 'HTTPS',
];
foreach (Installer::requirements() as $req) {
    if ($req['key'] === 'https') continue; // meaningless from the CLI
    $line($labels[$req['key']] ?? $req['key'], $req['ok'], $req['note'], $req['required']);
}
$line('.env present', is_file(BASE_PATH . '/.env'));
$line('DB_NAME / DB_USER set', Db::configured(), Db::configured() ? '' : 'open /install or fill in the DB_* values');
$line('APP_SECRET set (32+ chars)', strlen((string)env('APP_SECRET', '')) >= 32);
$line('APP_URL set', (string)env('APP_URL', '') !== '', (string)env('APP_URL', ''), false);
$line('APP_DEBUG off', !filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL), '', false);

try {
    $t0 = microtime(true);
    $version = (string)Db::value('SELECT VERSION()');
    $line('MySQL connection', true, $version . ', ' . round((microtime(true) - $t0) * 1000) . ' ms');
    $line('Tables created', Db::hasTable('pages') && Db::hasTable('mail_queue'), Db::hasTable('pages') ? '' : 'run: php tools/migrate.php');
    if (Db::hasTable('pages')) {
        $pages = Db::from('pages')->count();
        $line('Content seeded (pages table)', $pages > 0, $pages . ' pages' . ($pages === 0 ? ' — run: php database/seed.php' : ''));
        $users = Db::from('admin_users')->count();
        $line('Admin account exists', $users > 0, $users === 0 ? 'open /install or /admin/setup to create it' : $users . ' user(s)');
    }
} catch (Throwable $e) {
    $line('MySQL connection', false, $e->getMessage());
}

echo PHP_EOL . ($ok ? 'All checks passed.' : 'Some checks failed. Fix them before going live.') . PHP_EOL;
exit($ok ? 0 : 1);
