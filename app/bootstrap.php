<?php
declare(strict_types=1);

/**
 * أثر لتصميم الويب — application bootstrap.
 * Loads environment, registers the autoloader, configures PHP and sessions.
 */

define('APP_START', microtime(true));
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);
define('PUBLIC_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'public');
define('STORAGE_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'storage');
define('UPLOADS_PATH', PUBLIC_PATH . DIRECTORY_SEPARATOR . 'uploads');

// ---- autoloader (App\Core\Foo -> app/Core/Foo.php) ----
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = APP_PATH . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require APP_PATH . '/helpers.php';

// ---- environment ----
App\Core\Env::load(BASE_PATH . '/.env');

$debug = filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Qatar'));
mb_internal_encoding('UTF-8');

if (!is_dir(STORAGE_PATH . '/cache')) { @mkdir(STORAGE_PATH . '/cache', 0775, true); }
if (!is_dir(STORAGE_PATH . '/logs'))  { @mkdir(STORAGE_PATH . '/logs', 0775, true); }

// ---- secure session cookie ----
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name('athar_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
