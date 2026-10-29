<?php
declare(strict_types=1);

/**
 * أثر لتصميم الويب — front controller.
 * Every request (site + admin) enters here. See app/routes.php.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Analytics;
use App\Core\Auth;
use App\Core\DbException;
use App\Core\HttpException;
use App\Core\I18n;
use App\Core\Installer;
use App\Core\Log;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Models\Redirects;
use App\Models\Settings;

ob_start();
$request = new Request();
$debug = filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL);

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

try {
    // first run: no database yet -> the installer takes over every request
    // (and shows its "done" screen once more right after the last step)
    if (!Installer::installed() || ($request->fullPath === '/install' && Installer::justCompleted())) {
        (new App\Controllers\InstallController())->handle($request);
    }

    $router = new Router();
    $adminPath = rtrim(env('ADMIN_PATH', '/admin'), '/') ?: '/admin';
    $isAdmin = is_admin_request();

    if ($isAdmin) {
        Session::start();
        $user = Auth::user();
        I18n::setLang($user['lang'] ?? (string)Session::get('admin_lang', 'en'));
        require APP_PATH . '/routes.php';
        if (!$router->dispatch($request)) {
            Response::abort(404);
        }
        exit;
    }

    // ---- public site ----
    [$lang, $path, $disabledLangPrefix] = I18n::detect($request->fullPath);
    if ($disabledLangPrefix) {
        Response::redirect(I18n::url($path), 301);
    }
    // default language must not carry a prefix (/ar/x -> /x); canonical URLs only
    if ($path !== $request->fullPath && $lang === I18n::defaultLang()) {
        Response::redirect(I18n::url($path, $lang), 301);
    }
    // normalise trailing slashes for SEO
    if (strlen($request->fullPath) > 1 && str_ends_with((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/')) {
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        Response::redirect(base_url() . rtrim($request->fullPath, '/') . ($qs ? '?' . $qs : ''), 301);
    }
    I18n::setLang($lang);
    $request->path = $path;

    // maintenance mode (admins still see the site)
    if (Settings::get('maintenance_mode', false) && !$request->isPost()) {
        Session::start();
        if (!Auth::check() && !str_starts_with($path, '/track')) {
            http_response_code(503);
            header('Retry-After: 3600');
            echo View::make('site/maintenance', []);
            exit;
        }
    }

    // manual redirects (SEO)
    if ($request->method === 'GET') {
        $redir = Redirects::match($request->fullPath);
        if ($redir) {
            Redirects::hit($redir['id']);
            $to = $redir['to_path'];
            if (!preg_match('#^https?://#i', $to)) $to = base_url() . '/' . ltrim($to, '/');
            Response::redirect($to, (int)$redir['code']);
        }
    }

    require APP_PATH . '/routes.php';
    if (!$router->dispatch($request)) {
        Response::abort(404);
    }
} catch (HttpException $e) {
    $status = $e->status;
    if ($isAdmin ?? false) {
        http_response_code($status);
        echo View::make('admin/error', ['status' => $status, 'message' => $e->getMessage()]);
    } else {
        $controller = new App\Controllers\Site\PageController();
        if ($status === 404) {
            echo $controller->notFound($request);
        } else {
            http_response_code($status);
            echo View::make('site/error', ['status' => $status, 'message' => $e->getMessage()]);
        }
    }
    Response::finish();
} catch (Throwable $e) {
    Log::error(get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    while (ob_get_level() > 0) ob_end_clean();
    $isDb = $e instanceof DbException;
    // a database outage is temporary: answer 503 + Retry-After so search engines keep the pages indexed
    $status = $isDb ? 503 : 500;
    http_response_code($status);
    if ($isDb) header('Retry-After: 1800');
    $message = $debug ? get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString() : '';
    try {
        echo View::make(($isAdmin ?? false) ? 'admin/error' : 'site/error', ['status' => $status, 'message' => $message, 'db' => $isDb]);
    } catch (Throwable) {
        echo '<!doctype html><meta charset="utf-8"><title>Error</title><p style="font:16px/1.5 sans-serif;padding:40px">Something went wrong. Please try again later.</p>' . ($debug ? '<pre>' . e($message) . '</pre>' : '');
    }
    exit;
}
