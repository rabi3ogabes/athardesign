<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Settings;

/** Builds the <head> SEO tags and JSON-LD for public pages. */
final class Seo
{
    /**
     * @param array{
     *   title?:string, description?:string, keywords?:string, image?:string,
     *   path?:string, canonical?:string, noindex?:bool, type?:string,
     *   jsonld?:array, breadcrumbs?:array, alternates?:bool
     * } $o
     */
    public static function head(array $o): string
    {
        $lang = I18n::lang();
        $siteName = setting_tr('site_name', 'أثر لتصميم الويب');
        $suffix = setting_tr('meta_title_suffix', '');
        $title = trim((string)($o['title'] ?? ''));
        if ($title === '') {
            $fullTitle = $siteName;
        } elseif (mb_stripos($title, $siteName) !== false || str_contains($title, '|')) {
            $fullTitle = $title; // the page title already carries the brand / its own suffix
        } else {
            $fullTitle = $suffix !== '' ? $title . ' ' . $suffix : $title . ' | ' . $siteName;
        }
        $description = mb_substr(trim((string)($o['description'] ?? setting_tr('default_meta_description'))), 0, 160);
        $keywords = trim((string)($o['keywords'] ?? setting_tr('default_meta_keywords')));
        $image = media_url($o['image'] ?? setting('og_image', ''));
        if ($image === '') $image = base_url() . '/assets/img/og-default.jpg';
        $path = $o['path'] ?? '/';
        $canonical = $o['canonical'] ?? I18n::url($path);
        $noindex = !empty($o['noindex']);

        $h = [];
        $h[] = '<title>' . e($fullTitle) . '</title>';
        $h[] = '<meta name="description" content="' . e($description) . '">';
        if ($keywords !== '') $h[] = '<meta name="keywords" content="' . e($keywords) . '">';
        $h[] = '<meta name="robots" content="' . ($noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large') . '">';
        $h[] = '<link rel="canonical" href="' . e($canonical) . '">';

        if (($o['alternates'] ?? true) && I18n::englishEnabled()) {
            foreach (I18n::LANGS as $l) {
                $h[] = '<link rel="alternate" hreflang="' . $l . '" href="' . e(I18n::url($path, $l)) . '">';
            }
            $h[] = '<link rel="alternate" hreflang="x-default" href="' . e(I18n::url($path, I18n::defaultLang())) . '">';
        }

        $h[] = '<meta property="og:type" content="' . e($o['type'] ?? 'website') . '">';
        $h[] = '<meta property="og:site_name" content="' . e($siteName) . '">';
        $h[] = '<meta property="og:title" content="' . e($fullTitle) . '">';
        $h[] = '<meta property="og:description" content="' . e($description) . '">';
        $h[] = '<meta property="og:url" content="' . e($canonical) . '">';
        $h[] = '<meta property="og:image" content="' . e($image) . '">';
        if (str_ends_with($image, '/assets/img/og-default.jpg')) {
            $h[] = '<meta property="og:image:width" content="1200">';
            $h[] = '<meta property="og:image:height" content="630">';
        }
        $h[] = '<meta property="og:image:alt" content="' . e($fullTitle) . '">';
        $h[] = '<meta property="og:locale" content="' . ($lang === 'ar' ? 'ar_QA' : 'en_QA') . '">';
        if (($o['alternates'] ?? true) && I18n::englishEnabled()) {
            $h[] = '<meta property="og:locale:alternate" content="' . ($lang === 'ar' ? 'en_QA' : 'ar_QA') . '">';
        }
        $h[] = '<meta name="twitter:card" content="summary_large_image">';
        $h[] = '<meta name="twitter:title" content="' . e($fullTitle) . '">';
        $h[] = '<meta name="twitter:description" content="' . e($description) . '">';
        $h[] = '<meta name="twitter:image" content="' . e($image) . '">';

        $gv = trim((string)setting('google_verification', ''));
        if ($gv !== '') $h[] = '<meta name="google-site-verification" content="' . e($gv) . '">';
        $bv = trim((string)setting('bing_verification', ''));
        if ($bv !== '') $h[] = '<meta name="msvalidate.01" content="' . e($bv) . '">';

        // JSON-LD: organization + website + this page (+ breadcrumbs and page-specific nodes)
        $graph = [self::organization(), self::website()];
        $page = [
            '@type' => preg_match('/^[A-Za-z]+$/', (string)($o['page_type'] ?? '')) ? $o['page_type'] : 'WebPage',
            '@id' => $canonical . '#webpage',
            'url' => $canonical,
            'name' => $fullTitle,
            'description' => $description,
            'inLanguage' => $lang === 'ar' ? 'ar-QA' : 'en-QA',
            'isPartOf' => ['@id' => base_url() . '/#website'],
            'about' => ['@id' => base_url() . '/#organization'],
            'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $image],
        ];
        if (!empty($o['breadcrumbs'])) {
            $crumbs = self::breadcrumbs($o['breadcrumbs']);
            $crumbs['@id'] = $canonical . '#breadcrumb';
            $page['breadcrumb'] = ['@id' => $canonical . '#breadcrumb'];
            $graph[] = $page;
            $graph[] = $crumbs;
        } else {
            $graph[] = $page;
        }
        if (!empty($o['jsonld'])) {
            foreach ($o['jsonld'] as $node) $graph[] = $node;
        }
        $h[] = '<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

        return implode("\n", $h);
    }

    public static function organization(): array
    {
        $sameAs = [];
        foreach (['instagram', 'facebook', 'tiktok', 'snapchat', 'x', 'linkedin', 'youtube', 'threads', 'pinterest', 'behance'] as $k) {
            $v = trim((string)setting('social_' . $k, ''));
            if ($v !== '') $sameAs[] = $v;
        }
        $type = (string)setting('schema_type', 'ProfessionalService');
        $node = [
            '@type' => $type ?: 'ProfessionalService',
            '@id'   => base_url() . '/#organization',
            'name'  => setting_tr('site_name', 'أثر لتصميم الويب'),
            'url'   => base_url() . '/',
            'description' => setting_tr('default_meta_description'),
            'areaServed' => ['@type' => 'Country', 'name' => 'Qatar'],
        ];
        $logo = (string)setting('logo', '');
        $node['logo'] = $logo !== '' ? media_url($logo) : base_url() . '/assets/img/logo-mark-1024.png';
        $node['image'] = $node['logo'];
        $slogan = setting_tr('tagline');
        if ($slogan !== '') $node['slogan'] = $slogan;
        $node['knowsLanguage'] = ['ar', 'en'];
        $node['currenciesAccepted'] = 'QAR';
        $prices = [];
        foreach (\App\Models\Packages::published() as $p) {
            if ($p['price'] !== null && $p['price'] !== '' && strtoupper((string)($p['currency'] ?: 'QAR')) === 'QAR') $prices[] = (float)$p['price'];
        }
        if ($prices) $node['priceRange'] = 'QAR ' . number_format(min($prices), 0, '.', ',') . ' - ' . number_format(max($prices), 0, '.', ',');
        $phone = trim((string)setting('phone', ''));
        if ($phone !== '') $node['telephone'] = $phone;
        $wa = preg_replace('/\D+/', '', (string)setting('whatsapp_number', ''));
        if (strlen($wa) >= 8 || $phone !== '') {
            $contact = ['@type' => 'ContactPoint', 'contactType' => 'sales', 'availableLanguage' => ['Arabic', 'English'], 'areaServed' => 'QA'];
            if ($phone !== '') $contact['telephone'] = $phone;
            if (strlen($wa) >= 8) $contact['url'] = 'https://wa.me/' . $wa;
            $node['contactPoint'] = $contact;
        }
        $email = trim((string)setting('email', ''));
        if ($email !== '') $node['email'] = $email;
        $address = setting_tr('address');
        if ($address !== '') {
            $node['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $address, 'addressLocality' => 'Doha', 'addressCountry' => 'QA'];
        }
        $lat = (string)setting('geo_lat', ''); $lng = (string)setting('geo_lng', '');
        if (is_numeric($lat) && is_numeric($lng)) {
            $node['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float)$lat, 'longitude' => (float)$lng];
        }
        if ($sameAs) $node['sameAs'] = $sameAs;
        return $node;
    }

    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => base_url() . '/#website',
            'url' => base_url() . '/',
            'name' => setting_tr('site_name', 'أثر لتصميم الويب'),
            'inLanguage' => I18n::englishEnabled() ? ['ar', 'en'] : ['ar'],
            'publisher' => ['@id' => base_url() . '/#organization'],
        ];
    }

    public static function breadcrumbs(array $items): array
    {
        $list = [];
        foreach (array_values($items) as $i => [$name, $url]) {
            $list[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => $url];
        }
        return ['@type' => 'BreadcrumbList', 'itemListElement' => $list];
    }

    public static function service(array $s): array
    {
        return [
            '@type' => 'Service',
            'name' => tr($s['title']),
            'description' => str_limit(tr($s['excerpt']), 200),
            'provider' => ['@id' => base_url() . '/#organization'],
            'areaServed' => ['@type' => 'Country', 'name' => 'Qatar'],
            'url' => I18n::url('/services/' . $s['slug']),
        ];
    }

    public static function package(array $p): array
    {
        $node = [
            '@type' => 'Product',
            'name' => tr($p['name']),
            'description' => str_limit(tr($p['tagline']) ?: strip_tags(tr($p['body'])), 200),
            'brand' => ['@id' => base_url() . '/#organization'],
            'category' => tr(\App\Models\Packages::categoryLabels()[$p['category']] ?? ['ar' => '', 'en' => '']),
            'url' => I18n::url('/packages/' . $p['slug']),
        ];
        if (!empty($p['image'])) $node['image'] = media_url($p['image']);
        if ($p['price'] !== null && $p['price'] !== '') {
            $node['offers'] = [
                '@type' => 'Offer',
                'price' => (string)round((float)$p['price'], 2),
                'priceCurrency' => strtoupper((string)($p['currency'] ?: 'QAR')),
                'availability' => 'https://schema.org/InStock',
                'url' => I18n::url('/packages/' . $p['slug']),
                'areaServed' => ['@type' => 'Country', 'name' => 'Qatar'],
            ];
        }
        return $node;
    }

    public static function faq(array $items): array
    {
        $main = [];
        foreach ($items as $it) {
            $q = tr($it['question'] ?? ''); $a = tr($it['answer'] ?? '');
            if ($q === '' || $a === '') continue;
            $main[] = ['@type' => 'Question', 'name' => $q, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($a)]];
        }
        return ['@type' => 'FAQPage', 'mainEntity' => $main];
    }

    public static function creativeWork(array $p): array
    {
        $node = [
            '@type' => 'CreativeWork',
            'name' => tr($p['title']),
            'description' => str_limit(tr($p['excerpt']), 200),
            'url' => I18n::url('/work/' . $p['slug']),
            'creator' => ['@id' => base_url() . '/#organization'],
        ];
        if (!empty($p['cover_image'])) $node['image'] = media_url($p['cover_image']);
        if (!empty($p['year'])) $node['dateCreated'] = (string)$p['year'];
        return $node;
    }
}
