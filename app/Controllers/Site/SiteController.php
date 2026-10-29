<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Analytics;
use App\Core\I18n;
use App\Core\Request;
use App\Core\Seo;
use App\Core\View;
use App\Models\Pages;
use App\Models\Settings;

abstract class SiteController
{
    /**
     * Render a public page in the site layout.
     * $seo: options for Seo::head(); $data: template variables.
     */
    protected function page(Request $request, string $template, array $data, array $seo, ?string $trackTitle = null, int $status = 200): string
    {
        $head = Seo::head($seo);
        $title = $seo['title'] ?? setting_tr('site_name');
        Analytics::pageview($request, $trackTitle ?? $title, I18n::lang());
        http_response_code($status);
        $base = [
            'head'      => $head,
            'pageTitle' => $title,
            'lang'      => I18n::lang(),
            'dir'       => I18n::dir(),
            'path'      => $seo['path'] ?? '/',
            'nav'       => Pages::nav(),
            'footerPages' => Pages::footer(),
            'socials'   => Settings::socials(),
            'bodyClass' => $data['bodyClass'] ?? '',
        ];
        return View::render($template, array_merge($base, $data), 'site/layout');
    }

    protected function breadcrumbs(array $items): array
    {
        $home = [[setting_tr('site_name'), I18n::url('/')]];
        return array_merge($home, $items);
    }
}
