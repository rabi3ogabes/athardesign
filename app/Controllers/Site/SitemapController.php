<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\I18n;
use App\Core\Request;
use App\Core\Response;
use App\Models\Pages;
use App\Models\Projects;
use App\Models\Services;
use App\Models\Settings;

final class SitemapController
{
    public function sitemap(Request $request): void
    {
        $langs = I18n::englishEnabled() ? I18n::LANGS : [I18n::defaultLang()];
        $entries = [];
        $add = function (string $path, string $lastmod, string $priority, string $freq) use (&$entries, $langs) {
            foreach ($langs as $l) {
                $alts = '';
                foreach ($langs as $al) {
                    $alts .= '<xhtml:link rel="alternate" hreflang="' . $al . '" href="' . e(I18n::url($path, $al)) . '"/>';
                }
                $entries[] = '<url><loc>' . e(I18n::url($path, $l)) . '</loc>' . $alts
                    . ($lastmod ? '<lastmod>' . e(substr($lastmod, 0, 10)) . '</lastmod>' : '')
                    . '<changefreq>' . $freq . '</changefreq><priority>' . $priority . '</priority></url>';
            }
        };
        foreach (Pages::published() as $p) {
            if ($p['slug'] === '404' || !empty($p['noindex'])) continue;
            $path = $p['slug'] === 'home' ? '/' : '/' . $p['slug'];
            $add($path, (string)$p['updated_at'], $p['slug'] === 'home' ? '1.0' : '0.8', $p['slug'] === 'home' ? 'weekly' : 'monthly');
        }
        foreach (Services::published() as $s) {
            $add('/services/' . $s['slug'], (string)$s['updated_at'], '0.7', 'monthly');
        }
        foreach (\App\Models\Packages::published() as $p) {
            $add('/packages/' . $p['slug'], (string)$p['updated_at'], '0.7', 'monthly');
        }
        foreach (Projects::published() as $p) {
            $add('/work/' . $p['slug'], (string)$p['updated_at'], '0.6', 'monthly');
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n"
            . implode("\n", $entries) . "\n</urlset>";
        header('Cache-Control: public, max-age=3600');
        Response::text($xml, 'application/xml; charset=UTF-8');
    }

    public function robots(Request $request): void
    {
        $txt = (string)Settings::get('robots_txt', "User-agent: *\nAllow: /\n");
        $txt = rtrim($txt) . "\n\nSitemap: " . base_url() . "/sitemap.xml\n";
        header('Cache-Control: public, max-age=3600');
        Response::text($txt);
    }
}
