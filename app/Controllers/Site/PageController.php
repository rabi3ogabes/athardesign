<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Blocks;
use App\Core\I18n;
use App\Core\Request;
use App\Core\Response;
use App\Core\Seo;
use App\Models\Pages;

final class PageController extends SiteController
{
    public function home(Request $request): string
    {
        $page = Pages::bySlug('home');
        if (!$page) Response::notFound();
        return $this->renderPage($request, $page, '/');
    }

    public function show(Request $request, string $slug): string
    {
        $slug = mb_substr($slug, 0, 120);
        if ($slug === 'home' || $slug === '404') {
            Response::redirect(I18n::url('/'), 301);
        }
        $page = Pages::bySlug($slug);
        if (!$page) Response::notFound();
        return $this->renderPage($request, $page, '/' . $slug);
    }

    /** Rendered by index.php when nothing matched */
    public function notFound(Request $request): string
    {
        $page = Pages::bySlug('404');
        $blocks = $page ? (array)$page['blocks'] : [];
        $title = $page ? tr($page['title']) : t('site.not_found_title');
        $ctx = ['page' => $page, 'path' => $request->fullPath, 'promote_h1' => false]; // the 404 view prints its own H1
        return $this->page($request, 'site/page', [
            'page' => $page,
            'blocksHtml' => Blocks::renderAll($blocks, $ctx),
            'is404' => true,
            'bodyClass' => 'page-404',
        ], [
            'title' => $title,
            'description' => $page ? tr($page['meta_description']) : '',
            'path' => $request->path,
            'noindex' => true,
            'alternates' => false,
        ], $title . ' (404)', 404);
    }

    private function renderPage(Request $request, array $page, string $path): string
    {
        $blocks = is_array($page['blocks']) ? $page['blocks'] : (json_decode((string)$page['blocks'], true) ?: []);
        $ctx = ['page' => $page, 'path' => $path];
        $jsonld = [];
        foreach ($blocks as $b) {
            if (($b['type'] ?? '') === 'faq' && !empty($b['data']['items'])) {
                $jsonld[] = Seo::faq($b['data']['items']);
            }
        }
        $title = tr($page['meta_title']) ?: tr($page['title']);
        $isHome = $page['slug'] === 'home';
        return $this->page($request, 'site/page', [
            'page' => $page,
            'blocksHtml' => Blocks::renderAll($blocks, $ctx),
            'bodyClass' => $isHome ? 'page-home' : 'page-' . preg_replace('/[^a-z0-9\-]/', '', $page['slug']),
        ], [
            'title' => $isHome ? (tr($page['meta_title']) ?: '') : $title,
            'description' => tr($page['meta_description']) ?: null,
            'keywords' => tr($page['meta_keywords']) ?: null,
            'image' => $page['og_image'] ?: null,
            'page_type' => ['contact' => 'ContactPage', 'about' => 'AboutPage'][$page['slug']] ?? 'WebPage',
            'path' => $path,
            'canonical' => $page['canonical_url'] ?: null,
            'noindex' => (bool)$page['noindex'],
            'breadcrumbs' => $isHome ? [] : $this->breadcrumbs([[tr($page['title']), I18n::url($path)]]),
            'jsonld' => $jsonld,
        ], tr($page['title']));
    }
}
