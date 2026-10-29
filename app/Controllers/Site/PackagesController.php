<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\I18n;
use App\Core\Request;
use App\Core\Response;
use App\Core\Seo;
use App\Models\Packages;
use App\Models\Pages;

final class PackagesController extends SiteController
{
    public function show(Request $request, string $slug): string
    {
        $package = Packages::bySlug(mb_substr($slug, 0, 120));
        if (!$package) Response::notFound();
        $siblings = array_values(array_filter(Packages::published(), fn($p) => $p['category'] === $package['category'] && $p['id'] !== $package['id']));
        $path = '/packages/' . $package['slug'];
        $catLabel = tr(Packages::categoryLabels()[$package['category']] ?? ['ar' => '', 'en' => '']);
        $tierLabel = tr(Packages::tierLabels()[$package['tier']] ?? ['ar' => '', 'en' => '']);
        $listingPath = Pages::bySlug('packages') ? '/packages' : '/services';
        $title = tr($package['meta_title']) ?: (tr($package['name']) . ' — ' . $tierLabel . ' | ' . $catLabel);
        return $this->page($request, 'site/package', [
            'package' => $package,
            'siblings' => $siblings,
            'catLabel' => $catLabel,
            'tierLabel' => $tierLabel,
            'listingPath' => $listingPath,
            'bodyClass' => 'page-package',
        ], [
            'title' => $title,
            'description' => tr($package['meta_description']) ?: str_limit(tr($package['tagline']) ?: strip_tags(tr($package['body'])), 160),
            'keywords' => tr($package['meta_keywords']) ?: null,
            'image' => $package['image'] ?: null,
            'path' => $path,
            'breadcrumbs' => $this->breadcrumbs([[t('site.packages'), I18n::url($listingPath)], [tr($package['name']), I18n::url($path)]]),
            'jsonld' => [Seo::package($package)],
        ], tr($package['name']));
    }
}
