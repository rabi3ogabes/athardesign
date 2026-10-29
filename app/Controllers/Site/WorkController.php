<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\I18n;
use App\Core\Request;
use App\Core\Response;
use App\Core\Seo;
use App\Models\Projects;
use App\Models\Services;

final class WorkController extends SiteController
{
    public function show(Request $request, string $slug): string
    {
        $project = Projects::bySlug(mb_substr($slug, 0, 120));
        if (!$project) Response::notFound();
        $all = Projects::published();
        $idx = array_search($project['id'], array_column($all, 'id'), true);
        $next = $all[($idx + 1) % max(1, count($all))] ?? null;
        $prev = $all[($idx - 1 + count($all)) % max(1, count($all))] ?? null;
        $services = [];
        foreach ((array)($project['services'] ?? []) as $sslug) {
            $s = Services::bySlug((string)$sslug);
            if ($s) $services[] = $s;
        }
        $path = '/work/' . $project['slug'];
        return $this->page($request, 'site/project', [
            'project' => $project,
            'services' => $services,
            'related' => Projects::related($project, 3),
            'next' => ($next && $next['id'] !== $project['id']) ? $next : null,
            'prev' => ($prev && $prev['id'] !== $project['id']) ? $prev : null,
            'bodyClass' => 'page-project',
        ], [
            'title' => tr($project['meta_title']) ?: tr($project['title']),
            'description' => tr($project['meta_description']) ?: str_limit(tr($project['excerpt']), 160),
            'keywords' => tr($project['meta_keywords']) ?: null,
            'image' => $project['cover_image'] ?: null,
            'type' => 'article',
            'path' => $path,
            'breadcrumbs' => $this->breadcrumbs([[t('site.work'), I18n::url('/work')], [tr($project['title']), I18n::url($path)]]),
            'jsonld' => [Seo::creativeWork($project)],
        ], tr($project['title']));
    }
}
