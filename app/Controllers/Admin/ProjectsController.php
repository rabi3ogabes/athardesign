<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Html;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Categories;
use App\Models\Projects;
use App\Models\Services;

final class ProjectsController extends AdminController
{
    public function index(Request $r): string
    {
        return $this->render('admin/projects/index', ['title' => t('projects.title'), 'rows' => Projects::all(), 'categories' => Categories::all()]);
    }

    public function create(Request $r): string
    {
        $p = ['id' => '', 'slug' => '', 'title' => ['ar' => '', 'en' => ''], 'client' => '', 'category_id' => null, 'excerpt' => ['ar' => '', 'en' => ''], 'body' => ['ar' => '', 'en' => ''], 'cover_image' => '', 'gallery' => [], 'services' => [], 'results' => [], 'link' => '', 'year' => (int)date('Y'), 'is_featured' => false, 'sort_order' => 100, 'status' => 'published', 'meta_title' => ['ar' => '', 'en' => ''], 'meta_description' => ['ar' => '', 'en' => ''], 'meta_keywords' => ['ar' => '', 'en' => '']];
        return $this->render('admin/projects/edit', ['title' => t('projects.new'), 'p' => $p, 'categories' => Categories::all(), 'services' => Services::all()]);
    }

    public function store(Request $r): void
    {
        $data = $this->collect($r, null);
        if (is_string($data)) $this->fail($data, admin_url('projects/create'), $r->all());
        $row = Projects::create($data);
        $this->log('create', 'projects', $row['id'] ?? '', ['slug' => $data['slug']]);
        $this->ok(t('common.created'), admin_url('projects/' . ($row['id'] ?? '') . '/edit'));
    }

    public function edit(Request $r, string $id): string
    {
        $p = Projects::find($id);
        if (!$p) Response::notFound();
        return $this->render('admin/projects/edit', ['title' => t('projects.edit') . ' — ' . tr($p['title']), 'p' => $p, 'categories' => Categories::all(), 'services' => Services::all()]);
    }

    public function update(Request $r, string $id): void
    {
        $p = Projects::find($id);
        if (!$p) Response::notFound();
        $data = $this->collect($r, $p);
        if (is_string($data)) $this->fail($data, admin_url('projects/' . $id . '/edit'));
        Projects::update($id, $data);
        $this->log('update', 'projects', $id, ['slug' => $data['slug']]);
        $this->ok(t('common.saved'), admin_url('projects/' . $id . '/edit'));
    }

    public function destroy(Request $r, string $id): void
    {
        $p = Projects::find($id);
        if (!$p) Response::notFound();
        Projects::delete($id);
        $this->log('delete', 'projects', $id, ['slug' => $p['slug']]);
        $this->ok(t('common.deleted'), admin_url('projects'));
    }

    public function reorder(Request $r): void
    {
        Projects::reorder($this->jsonField($r, 'order'));
        Response::json(['ok' => true]);
    }

    public function storeCategory(Request $r): void
    {
        $name = $r->i18n('name');
        $slug = slugify($r->str('slug') ?: ($name['en'] ?: $name['ar']));
        if ($name['ar'] === '' && $name['en'] === '') $this->fail(t('validation.required', ['field' => t('projects.category_name')]), admin_url('projects'));
        Categories::create(['slug' => $slug, 'name' => $name, 'sort_order' => $r->int('sort_order', 100)]);
        $this->log('create', 'project_categories', $slug);
        $this->ok(t('common.created'), admin_url('projects'));
    }

    public function updateCategory(Request $r, string $id): void
    {
        $c = Categories::find($id);
        if (!$c) Response::notFound();
        $name = $r->i18n('name');
        Categories::update($id, ['name' => $name, 'slug' => slugify($r->str('slug') ?: $c['slug']), 'sort_order' => $r->int('sort_order', (int)$c['sort_order'])]);
        $this->log('update', 'project_categories', $id);
        $this->ok(t('common.saved'), admin_url('projects'));
    }

    public function deleteCategory(Request $r, string $id): void
    {
        $c = Categories::find($id);
        if (!$c) Response::notFound();
        Categories::delete($id);
        $this->log('delete', 'project_categories', $id, ['slug' => $c['slug']]);
        $this->ok(t('common.deleted'), admin_url('projects'));
    }

    private function collect(Request $r, ?array $existing): array|string
    {
        $title = $r->i18n('title');
        $slug = slugify($r->str('slug') ?: ($title['en'] ?: $title['ar']));
        $v = Validator::make(['title' => $title, 'slug' => $slug, 'link' => $r->str('link')], ['title' => 'required|max:160', 'slug' => 'required|slug|max:120', 'link' => 'url|max:300']);
        if ($v->fails()) return $v->first();
        if (Projects::slugExists($slug, $existing['id'] ?? null)) return t('projects.slug_taken');

        $gallery = [];
        foreach ($this->jsonField($r, 'gallery') as $g) {
            if (!is_array($g)) continue;
            $img = $this->imagePath($g['image'] ?? '');
            if ($img === '') continue;
            $gallery[] = ['image' => $img, 'alt' => ['ar' => mb_substr(strip_tags((string)($g['alt']['ar'] ?? '')), 0, 200), 'en' => mb_substr(strip_tags((string)($g['alt']['en'] ?? '')), 0, 200)], 'caption' => ['ar' => mb_substr(strip_tags((string)($g['caption']['ar'] ?? '')), 0, 200), 'en' => mb_substr(strip_tags((string)($g['caption']['en'] ?? '')), 0, 200)]];
        }
        $results = [];
        foreach ($this->jsonField($r, 'results') as $x) {
            if (!is_array($x) || trim((string)($x['value'] ?? '')) === '') continue;
            $results[] = ['value' => mb_substr(strip_tags((string)$x['value']), 0, 30), 'label' => ['ar' => mb_substr(strip_tags((string)($x['label']['ar'] ?? '')), 0, 120), 'en' => mb_substr(strip_tags((string)($x['label']['en'] ?? '')), 0, 120)]];
        }
        $validServices = array_column(Services::all(), 'slug');
        $services = array_values(array_filter((array)$r->input('services', []), fn($s) => is_string($s) && in_array($s, $validServices, true)));
        $catId = $r->str('category_id');
        $body = $r->i18n('body');
        return [
            'slug' => $slug,
            'title' => $title,
            'client' => mb_substr(strip_tags($r->str('client')), 0, 120),
            'category_id' => $this->isUuid($catId) ? $catId : null,
            'excerpt' => array_map(fn($s) => mb_substr(strip_tags($s), 0, 400), $r->i18n('excerpt')),
            'body' => ['ar' => Html::clean($body['ar']), 'en' => Html::clean($body['en'])],
            'cover_image' => $this->imagePath($r->input('cover_image')),
            'gallery' => $gallery,
            'services' => $services,
            'results' => $results,
            'link' => $r->str('link') !== '' ? mb_substr($r->str('link'), 0, 300) : null,
            'year' => $r->int('year') > 1990 ? $r->int('year') : null,
            'is_featured' => $r->bool('is_featured'),
            'sort_order' => $r->int('sort_order', 100),
            'status' => $r->str('status') === 'draft' ? 'draft' : 'published',
            'meta_title' => array_map(fn($s) => mb_substr($s, 0, 120), $r->i18n('meta_title')),
            'meta_description' => array_map(fn($s) => mb_substr($s, 0, 320), $r->i18n('meta_description')),
            'meta_keywords' => array_map(fn($s) => mb_substr($s, 0, 500), $r->i18n('meta_keywords')),
        ];
    }
}
