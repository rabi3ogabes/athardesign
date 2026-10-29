<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Blocks;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Pages;

final class PagesController extends AdminController
{
    private const RESERVED = ['admin', 'services', 'work', 'track', 'sitemap.xml', 'robots.txt', 'assets', 'uploads', 'en', 'ar', 'contact'];

    public function index(Request $r): string
    {
        return $this->render('admin/pages/index', ['title' => t('pages.title'), 'rows' => Pages::all()]);
    }

    public function create(Request $r): string
    {
        $page = ['id' => '', 'slug' => '', 'template' => 'custom', 'title' => ['ar' => '', 'en' => ''], 'nav_label' => ['ar' => '', 'en' => ''], 'meta_title' => ['ar' => '', 'en' => ''], 'meta_description' => ['ar' => '', 'en' => ''], 'meta_keywords' => ['ar' => '', 'en' => ''], 'og_image' => '', 'canonical_url' => '', 'noindex' => false, 'blocks' => [], 'status' => 'published', 'show_in_nav' => true, 'show_in_footer' => false, 'nav_order' => 50, 'is_system' => false];
        return $this->render('admin/pages/edit', ['title' => t('pages.new'), 'page' => $page, 'schema' => Blocks::schema(), 'icons' => Blocks::icons()]);
    }

    public function store(Request $r): void
    {
        $data = $this->collect($r, null);
        if (is_string($data)) $this->fail($data, admin_url('pages/create'), $r->all());
        $page = Pages::create($data);
        $this->log('create', 'pages', $page['id'] ?? '', ['slug' => $data['slug']]);
        $this->ok(t('common.created'), admin_url('pages/' . ($page['id'] ?? '') . '/edit'));
    }

    public function edit(Request $r, string $id): string
    {
        $page = Pages::find($id);
        if (!$page) Response::notFound();
        return $this->render('admin/pages/edit', ['title' => t('pages.edit') . ' — ' . tr($page['title']), 'page' => $page, 'schema' => Blocks::schema(), 'icons' => Blocks::icons()]);
    }

    public function update(Request $r, string $id): void
    {
        $page = Pages::find($id);
        if (!$page) Response::notFound();
        $data = $this->collect($r, $page);
        if (is_string($data)) {
            if ($r->wantsJson()) Response::json(['ok' => false, 'error' => $data], 422);
            $this->fail($data, admin_url('pages/' . $id . '/edit'));
        }
        Pages::update($id, $data);
        $this->log('update', 'pages', $id, ['slug' => $data['slug']]);
        if ($r->wantsJson()) Response::json(['ok' => true, 'message' => t('common.saved')]);
        $this->ok(t('common.saved'), admin_url('pages/' . $id . '/edit'));
    }

    public function destroy(Request $r, string $id): void
    {
        $page = Pages::find($id);
        if (!$page) Response::notFound();
        if ($page['is_system']) $this->fail(t('pages.system_page'), admin_url('pages'));
        Pages::delete($id);
        $this->log('delete', 'pages', $id, ['slug' => $page['slug']]);
        $this->ok(t('common.deleted'), admin_url('pages'));
    }

    public function duplicate(Request $r, string $id): void
    {
        $page = Pages::find($id);
        if (!$page) Response::notFound();
        $slug = $page['slug'] . '-' . t('pages.copy_suffix');
        $n = 1;
        while (Pages::slugExists($slug)) { $slug = $page['slug'] . '-' . t('pages.copy_suffix') . '-' . (++$n); }
        unset($page['id'], $page['created_at'], $page['updated_at']);
        $page['slug'] = $slug;
        $page['is_system'] = false;
        $page['template'] = 'custom';
        $page['status'] = 'draft';
        $page['show_in_nav'] = false;
        $page['title'] = ['ar' => tr($page['title'], 'ar') . ' (' . t('pages.copy_suffix') . ')', 'en' => tr($page['title'], 'en') . ' (copy)'];
        $new = Pages::create($page);
        $this->log('duplicate', 'pages', $new['id'] ?? '', ['from' => $id]);
        $this->ok(t('common.created'), admin_url('pages/' . ($new['id'] ?? '') . '/edit'));
    }

    public function reorder(Request $r): void
    {
        $ids = $this->jsonField($r, 'order');
        foreach (array_values($ids) as $i => $id) {
            if ($this->isUuid((string)$id)) Pages::update((string)$id, ['nav_order' => ($i + 1) * 10]);
        }
        Response::json(['ok' => true]);
    }

    /** @return array|string validated data, or an error message */
    private function collect(Request $r, ?array $existing): array|string
    {
        $title = $r->i18n('title');
        $slug = $existing && $existing['is_system'] ? $existing['slug'] : slugify($r->str('slug') ?: ($title['en'] ?: $title['ar']));
        $v = Validator::make(['title' => $title, 'slug' => $slug], ['title' => 'required|max:160', 'slug' => 'required|slug|max:120']);
        if ($v->fails()) return $v->first();
        if (!$existing || !$existing['is_system']) {
            if (in_array($slug, self::RESERVED, true)) return t('pages.slug_reserved');
            if (Pages::slugExists($slug, $existing['id'] ?? null)) return t('pages.slug_taken');
        }
        return [
            'slug' => $slug,
            'template' => $existing['template'] ?? 'custom',
            'title' => $title,
            'nav_label' => $r->i18n('nav_label'),
            'meta_title' => array_map(fn($s) => mb_substr($s, 0, 120), $r->i18n('meta_title')),
            'meta_description' => array_map(fn($s) => mb_substr($s, 0, 320), $r->i18n('meta_description')),
            'meta_keywords' => array_map(fn($s) => mb_substr($s, 0, 500), $r->i18n('meta_keywords')),
            'og_image' => $this->imagePath($r->input('og_image')),
            'canonical_url' => preg_match('#^https?://#', $r->str('canonical_url')) ? mb_substr($r->str('canonical_url'), 0, 500) : null,
            'noindex' => $r->bool('noindex'),
            'blocks' => Blocks::sanitize($r->input('blocks', '[]')),
            'status' => $r->str('status') === 'draft' ? 'draft' : 'published',
            'show_in_nav' => $r->bool('show_in_nav'),
            'show_in_footer' => $r->bool('show_in_footer'),
            'nav_order' => $r->int('nav_order', 50),
        ];
    }
}
