<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\I18n;
use App\Core\Request;
use App\Core\Response;
use App\Core\Seo;
use App\Models\Projects;
use App\Models\Services;

final class ServicesController extends SiteController
{
    public function show(Request $request, string $slug): string
    {
        $service = Services::bySlug(mb_substr($slug, 0, 120));
        if (!$service) Response::notFound();
        $all = Services::published();
        $others = array_values(array_filter($all, fn($s) => $s['id'] !== $service['id']));
        $related = array_values(array_filter(Projects::published(), fn($p) => in_array($service['slug'], (array)($p['services'] ?? []), true)));
        $path = '/services/' . $service['slug'];
        return $this->page($request, 'site/service', [
            'service' => $service,
            'others' => $others,
            'related' => array_slice($related, 0, 3),
            'bodyClass' => 'page-service',
        ], [
            'title' => tr($service['meta_title']) ?: tr($service['title']),
            'description' => tr($service['meta_description']) ?: str_limit(tr($service['excerpt']), 160),
            'keywords' => tr($service['meta_keywords']) ?: null,
            'image' => $service['image'] ?: null,
            'path' => $path,
            'breadcrumbs' => $this->breadcrumbs([[t('site.services'), I18n::url('/services')], [tr($service['title']), I18n::url($path)]]),
            'jsonld' => [Seo::service($service)],
        ], tr($service['title']));
    }
}
