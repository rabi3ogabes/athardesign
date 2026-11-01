<?php
declare(strict_types=1);

/** @var App\Core\Router $router */
/** @var App\Core\Request $request */
/** @var bool $isAdmin */

use App\Controllers\Admin as A;
use App\Controllers\Site as S;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Users;

if ($isAdmin) {
    $adminPath = rtrim(env('ADMIN_PATH', '/admin'), '/') ?: '/admin';

    $requireAuth = static function (Request $r): void {
        if (!Auth::check()) {
            if ($r->wantsJson()) Response::json(['ok' => false, 'error' => 'unauthenticated'], 401);
            Session::set('_intended', $r->url());
            Response::redirect(admin_url('login'));
        }
        if ($r->isPost()) Csrf::verify($r);
    };
    $requireManage = static function (Request $r): void {
        if (!Auth::can('manage_settings')) Response::abort(403, t('common.forbidden'));
    };
    $guestOnly = static function (Request $r): void {
        if ($r->isPost()) Csrf::verify($r);
    };

    // ---- auth ----
    $router->group($adminPath, [$guestOnly], function ($r) {
        $r->get('/login', [A\AuthController::class, 'loginForm']);
        $r->post('/login', [A\AuthController::class, 'login']);
        $r->get('/setup', [A\AuthController::class, 'setupForm']);
        $r->post('/setup', [A\AuthController::class, 'setup']);
        $r->post('/logout', [A\AuthController::class, 'logout']);
        $r->post('/lang', [A\AuthController::class, 'switchLang']);
    });

    // ---- panel ----
    $router->group($adminPath, [$requireAuth], function ($r) use ($requireManage) {
        $r->get('', [A\DashboardController::class, 'index']);
        $r->get('/', [A\DashboardController::class, 'index']);
        $r->get('/dashboard/data', [A\DashboardController::class, 'data']);

        $r->get('/visitors', [A\VisitorsController::class, 'index']);
        $r->get('/visitors/{id}', [A\VisitorsController::class, 'show']);
        $r->get('/clicks', [A\VisitorsController::class, 'clicks']);
        $r->get('/pageviews', [A\VisitorsController::class, 'visits']);
        $r->get('/export/{type}', [A\VisitorsController::class, 'export']);

        $r->get('/pages', [A\PagesController::class, 'index']);
        $r->get('/pages/create', [A\PagesController::class, 'create']);
        $r->post('/pages', [A\PagesController::class, 'store']);
        $r->get('/pages/{id}/edit', [A\PagesController::class, 'edit']);
        $r->post('/pages/{id}', [A\PagesController::class, 'update']);
        $r->post('/pages/{id}/delete', [A\PagesController::class, 'destroy']);
        $r->post('/pages/{id}/duplicate', [A\PagesController::class, 'duplicate']);
        $r->post('/pages/reorder', [A\PagesController::class, 'reorder']);

        $r->get('/services', [A\ServicesController::class, 'index']);
        $r->get('/services/create', [A\ServicesController::class, 'create']);
        $r->post('/services', [A\ServicesController::class, 'store']);
        $r->get('/services/{id}/edit', [A\ServicesController::class, 'edit']);
        $r->post('/services/{id}', [A\ServicesController::class, 'update']);
        $r->post('/services/{id}/delete', [A\ServicesController::class, 'destroy']);
        $r->post('/services/reorder', [A\ServicesController::class, 'reorder']);
        // packages (service tiers)
        $r->get('/packages', [A\PackagesController::class, 'index']);
        $r->get('/packages/create', [A\PackagesController::class, 'create']);
        $r->post('/packages', [A\PackagesController::class, 'store']);
        $r->post('/packages/reorder', [A\PackagesController::class, 'reorder']);
        $r->post('/packages/preview', [A\PackagesController::class, 'preview']);
        $r->get('/packages/{id}/edit', [A\PackagesController::class, 'edit']);
        $r->post('/packages/{id}', [A\PackagesController::class, 'update']);
        $r->post('/packages/{id}/delete', [A\PackagesController::class, 'destroy']);
        $r->post('/packages/{id}/duplicate', [A\PackagesController::class, 'duplicate']);

        $r->get('/projects', [A\ProjectsController::class, 'index']);
        $r->get('/projects/create', [A\ProjectsController::class, 'create']);
        $r->post('/projects', [A\ProjectsController::class, 'store']);
        $r->get('/projects/{id}/edit', [A\ProjectsController::class, 'edit']);
        $r->post('/projects/{id}', [A\ProjectsController::class, 'update']);
        $r->post('/projects/{id}/delete', [A\ProjectsController::class, 'destroy']);
        $r->post('/projects/reorder', [A\ProjectsController::class, 'reorder']);
        $r->post('/categories', [A\ProjectsController::class, 'storeCategory']);
        $r->post('/categories/{id}', [A\ProjectsController::class, 'updateCategory']);
        $r->post('/categories/{id}/delete', [A\ProjectsController::class, 'deleteCategory']);

        $r->get('/testimonials', [A\TestimonialsController::class, 'index']);
        $r->get('/testimonials/create', [A\TestimonialsController::class, 'create']);
        $r->post('/testimonials', [A\TestimonialsController::class, 'store']);
        $r->get('/testimonials/{id}/edit', [A\TestimonialsController::class, 'edit']);
        $r->post('/testimonials/{id}', [A\TestimonialsController::class, 'update']);
        $r->post('/testimonials/{id}/delete', [A\TestimonialsController::class, 'destroy']);

        $r->get('/media', [A\MediaController::class, 'index']);
        $r->get('/media/list', [A\MediaController::class, 'list']);
        $r->post('/media/upload', [A\MediaController::class, 'upload']);
        $r->post('/media/{id}', [A\MediaController::class, 'update']);
        $r->post('/media/{id}/delete', [A\MediaController::class, 'destroy']);

        $r->get('/leads', [A\LeadsController::class, 'index']);
        $r->get('/leads/{id}', [A\LeadsController::class, 'show']);
        $r->post('/leads/{id}', [A\LeadsController::class, 'update']);
        $r->post('/leads/{id}/delete', [A\LeadsController::class, 'destroy']);

        $r->get('/seo', [A\SeoController::class, 'index']);
        $r->get('/seo/tasks', [A\SeoController::class, 'tasks']);
        $r->post('/seo/tasks', [A\SeoController::class, 'storeTask']);
        $r->post('/seo/tasks/{id}', [A\SeoController::class, 'updateTask']);
        $r->post('/seo/tasks/{id}/status', [A\SeoController::class, 'taskStatus']);
        $r->post('/seo/tasks/{id}/delete', [A\SeoController::class, 'deleteTask']);
        $r->get('/seo/redirects', [A\SeoController::class, 'redirects']);
        $r->post('/seo/redirects', [A\SeoController::class, 'storeRedirect']);
        $r->post('/seo/redirects/{id}/delete', [A\SeoController::class, 'deleteRedirect']);


        // ---- email marketing ----
        $r->get('/mail', [A\MailCampaignsController::class, 'index']);
        $r->get('/mail/campaigns', [A\MailCampaignsController::class, 'index']);
        $r->get('/mail/campaigns/create', [A\MailCampaignsController::class, 'create']);
        $r->post('/mail/campaigns', [A\MailCampaignsController::class, 'store']);
        $r->get('/mail/campaigns/{id}', [A\MailCampaignsController::class, 'show']);
        $r->get('/mail/campaigns/{id}/edit', [A\MailCampaignsController::class, 'edit']);
        $r->post('/mail/campaigns/{id}', [A\MailCampaignsController::class, 'update']);
        $r->post('/mail/campaigns/{id}/score', [A\MailCampaignsController::class, 'score']);
        $r->post('/mail/campaigns/{id}/test', [A\MailCampaignsController::class, 'test']);
        $r->post('/mail/campaigns/{id}/launch', [A\MailCampaignsController::class, 'launch']);
        $r->post('/mail/campaigns/{id}/process', [A\MailCampaignsController::class, 'process']);
        $r->post('/mail/campaigns/{id}/pause', [A\MailCampaignsController::class, 'pause']);
        $r->post('/mail/campaigns/{id}/cancel', [A\MailCampaignsController::class, 'cancel']);
        $r->post('/mail/campaigns/{id}/duplicate', [A\MailCampaignsController::class, 'duplicate']);
        $r->post('/mail/campaigns/{id}/resend', [A\MailCampaignsController::class, 'resend']);
        $r->post('/mail/campaigns/{id}/delete', [A\MailCampaignsController::class, 'destroy']);
        $r->get('/mail/senders', [A\MailSendersController::class, 'index']);
        $r->post('/mail/senders', [A\MailSendersController::class, 'store']);
        $r->get('/mail/senders/{id}/edit', [A\MailSendersController::class, 'edit']);
        $r->post('/mail/senders/{id}', [A\MailSendersController::class, 'update']);
        $r->post('/mail/senders/{id}/test', [A\MailSendersController::class, 'test']);
        $r->post('/mail/senders/{id}/send-test', [A\MailSendersController::class, 'sendTest']);
        $r->post('/mail/senders/{id}/dkim', [A\MailSendersController::class, 'dkim']);
        $r->post('/mail/senders/{id}/delete', [A\MailSendersController::class, 'destroy']);
        $r->get('/mail/lists', [A\MailListsController::class, 'index']);
        $r->post('/mail/lists', [A\MailListsController::class, 'store']);
        $r->get('/mail/lists/{id}', [A\MailListsController::class, 'show']);
        $r->post('/mail/lists/{id}', [A\MailListsController::class, 'update']);
        $r->post('/mail/lists/{id}/import', [A\MailListsController::class, 'import']);
        $r->post('/mail/lists/{id}/verify', [A\MailListsController::class, 'verify']);
        $r->post('/mail/lists/{id}/clean', [A\MailListsController::class, 'clean']);
        $r->get('/mail/lists/{id}/export', [A\MailListsController::class, 'export']);
        $r->post('/mail/lists/{id}/contacts/{cid}', [A\MailListsController::class, 'contactUpdate']);
        $r->post('/mail/lists/{id}/delete', [A\MailListsController::class, 'destroy']);
        $r->get('/mail/suppressions', [A\MailListsController::class, 'suppressions']);
        $r->post('/mail/suppressions/remove', [A\MailListsController::class, 'unsuppress']);
        $r->get('/mail/templates', [A\MailTemplatesController::class, 'index']);
        $r->get('/mail/templates/create', [A\MailTemplatesController::class, 'create']);
        $r->post('/mail/templates', [A\MailTemplatesController::class, 'store']);
        $r->get('/mail/templates/{id}/edit', [A\MailTemplatesController::class, 'edit']);
        $r->post('/mail/templates/{id}', [A\MailTemplatesController::class, 'update']);
        $r->post('/mail/templates/{id}/delete', [A\MailTemplatesController::class, 'destroy']);
        $r->any('/mail/preview', [A\MailTemplatesController::class, 'preview']);

        $r->get('/activity', [A\ActivityController::class, 'index']);

        $r->get('/profile', [A\ProfileController::class, 'edit']);
        $r->post('/profile', [A\ProfileController::class, 'update']);

        $r->group('', [$requireManage], function ($r) {
            $r->get('/settings', [A\SettingsController::class, 'edit']);
            $r->post('/settings/font', [A\SettingsController::class, 'uploadFont']);
            $r->post('/settings/font/remove', [A\SettingsController::class, 'removeFont']);
            $r->get('/settings/{group}', [A\SettingsController::class, 'edit']);
            $r->post('/settings/{group}', [A\SettingsController::class, 'update']);
            $r->get('/users', [A\UsersController::class, 'index']);
            $r->post('/users', [A\UsersController::class, 'store']);
            $r->post('/users/{id}', [A\UsersController::class, 'update']);
            $r->post('/users/{id}/delete', [A\UsersController::class, 'destroy']);
            $r->post('/tools/clear-cache', [A\ToolsController::class, 'clearCache']);
            $r->post('/tools/prune', [A\ToolsController::class, 'prune']);
            $r->post('/tools/test-mail', [A\ToolsController::class, 'testMail']);
        });
    });
    return;
}

// ---- public site ----
$router->get('/', [S\PageController::class, 'home']);
$router->get('/services/{slug}', [S\ServicesController::class, 'show']);
$router->get('/packages/{slug}', [S\PackagesController::class, 'show']);
$router->get('/work/{slug}', [S\WorkController::class, 'show']);
$router->post('/contact', [S\ContactController::class, 'submit']);
$router->post('/track', [S\TrackController::class, 'click']);
$router->get('/sitemap.xml', [S\SitemapController::class, 'sitemap']);
$router->get('/robots.txt', [S\SitemapController::class, 'robots']);
$router->get('/llms.txt', [S\SitemapController::class, 'llms']);
$router->get('/m/o/{token:[a-f0-9]+}.gif', [S\MailTrackController::class, 'open']);
$router->get('/m/c/{token:[a-f0-9]+}', [S\MailTrackController::class, 'click']);
$router->any('/m/u/{token:[a-f0-9]+}', [S\MailTrackController::class, 'unsubscribe']);
$router->get('/cron/mail/{key}', [S\MailTrackController::class, 'cron']);
$router->get('/{slug}', [S\PageController::class, 'show']);
