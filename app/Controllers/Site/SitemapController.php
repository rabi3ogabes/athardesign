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
            $legal = in_array($p['slug'], ['privacy', 'terms'], true);
            $add($path, (string)$p['updated_at'], $p['slug'] === 'home' ? '1.0' : ($legal ? '0.3' : '0.8'), $p['slug'] === 'home' ? 'weekly' : ($legal ? 'yearly' : 'monthly'));
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

    /** llms.txt: a plain-text map of the site for AI assistants and agents (https://llmstxt.org) */
    public function llms(Request $request): void
    {
        $langs = I18n::englishEnabled() ? I18n::LANGS : [I18n::defaultLang()];
        $line = fn(string $path, string $label) => '- [' . $label . '](' . I18n::url($path, 'ar') . ')' . (count($langs) > 1 ? ' · [EN](' . I18n::url($path, 'en') . ')' : '');
        $out = ['# ' . tr(Settings::get('site_name'), 'en') . ' — ' . tr(Settings::get('site_name'), 'ar'), ''];
        $out[] = '> ' . tr(Settings::get('default_meta_description'), 'en');
        $out[] = '';
        $out[] = tr(Settings::get('default_meta_description'), 'ar');
        $out[] = '';
        $out[] = 'Arabic is the default language (URLs without a prefix); English pages live under /en.';
        $out[] = '';
        $out[] = '## Pages';
        foreach (Pages::published() as $p) {
            if ($p['slug'] === '404' || !empty($p['noindex'])) continue;
            $out[] = $line($p['slug'] === 'home' ? '/' : '/' . $p['slug'], tr($p['title'], 'en') . ' / ' . tr($p['title'], 'ar'));
        }
        $out[] = '';
        $out[] = '## Services';
        foreach (Services::published() as $s) {
            $out[] = $line('/services/' . $s['slug'], tr($s['title'], 'en') . ' / ' . tr($s['title'], 'ar')) . ': ' . str_limit(tr($s['excerpt'], 'en'), 160);
        }
        $out[] = '';
        $out[] = '## Packages';
        foreach (\App\Models\Packages::published() as $p) {
            $price = \App\Models\Packages::price($p, 'en');
            $period = trim(tr($p['period'] ?? '', 'en'));
            $out[] = $line('/packages/' . $p['slug'], tr($p['name'], 'en') . ' / ' . tr($p['name'], 'ar')) . ': ' . ($price !== '' ? $price . ($period !== '' ? ' ' . $period : '') : 'custom quote') . '. ' . str_limit(tr($p['tagline'], 'en'), 160);
        }
        $projects = Projects::published();
        if ($projects) {
            $out[] = '';
            $out[] = '## Work';
            foreach ($projects as $p) $out[] = $line('/work/' . $p['slug'], tr($p['title'], 'en') . ' / ' . tr($p['title'], 'ar'));
        }
        $out[] = '';
        $out[] = '## Contact';
        $email = trim((string)Settings::get('email', ''));
        if ($email !== '') $out[] = '- Email: ' . $email;
        $wa = preg_replace('/\D+/', '', (string)Settings::get('whatsapp_number', ''));
        if (strlen($wa) >= 8) $out[] = '- WhatsApp: https://wa.me/' . $wa;
        $out[] = '- Location: ' . tr(Settings::get('address'), 'en') . ' (serves all of Qatar)';
        $out[] = '- Sitemap: ' . base_url() . '/sitemap.xml';
        header('Cache-Control: public, max-age=3600');
        Response::text(implode("\n", $out) . "\n", 'text/plain; charset=UTF-8');
    }

    public function robots(Request $request): void
    {
        $txt = (string)Settings::get('robots_txt', "User-agent: *\nAllow: /\n");
        $txt = rtrim($txt) . "\n\nSitemap: " . base_url() . "/sitemap.xml\n";
        header('Cache-Control: public, max-age=3600');
        Response::text($txt);
    }
}
