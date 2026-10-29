<?php
declare(strict_types=1);

/**
 * Create or update the database tables from database/mysql/schema.sql
 * using the DB_* values in .env. Safe to re-run (every statement is IF NOT EXISTS).
 *
 *   php tools/migrate.php            # tables only
 *   php tools/migrate.php --seed     # tables + starter content
 */
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Db;
use App\Core\Installer;

if (!Db::configured()) {
    fwrite(STDERR, "DB_NAME / DB_USER are not set in .env\n");
    exit(1);
}
$n = Installer::applySchema(Db::pdo());
$s = Db::settings();
echo "Schema applied ({$n} statements) on {$s['name']}@" . ($s['socket'] !== '' ? $s['socket'] : $s['host']) . PHP_EOL;
if (in_array('--seed', $argv ?? [], true)) {
    echo Installer::seed();
}
echo "Done. Next: php tools/check.php\n";
