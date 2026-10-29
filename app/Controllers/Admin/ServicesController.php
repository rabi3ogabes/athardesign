<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Blocks;
use App\Core\Html;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Services;

final class ServicesController extends AdminController
{
    public function index(Request $r): string
    {
        return $this->render('admin/services/index', ['title' => t('services.title'), 'rows' => Services::all()]);
    }

    public function create(Request $r): string
    {
        $s = ['id' => '', 'slug' => '', 'title' => ['ar' => '', 'en' => ''], 'excerpt' => ['ar' => '', 'en' => ''], 'body' => ['ar' => '', 'en' => ''], 'icon' => 'sparkle', 'image' => '', 'features' => [], 'meta_title' => ['ar' => '', 'en' => ''], 'meta_description' => ['ar' => '', 'en' => ''], 'meta_keywords' => ['ar' => '', 'en' => ''], 'sort_order' => 100, 'is_featured' => true, 'status' => 'published'];
        return $this->render('admin/services/edit', ['title' => t('services.new'), 's' => $s, 'icons' => Blocks::icons()]);
    }

    public function store(Request $r): void
    {
        $data = $this->collect($r, null);
        if (is_string($data)) $this->fail($data, admin_url('services/create'), $r->all());
        $row = Services::create($data);
        $this->log('create', 'services', $row['id'] ?? '', ['slug' => $data['slug']]);
        $this->ok(t('common.created'), admin_url('services/' . ($row['id'] ?? '') . '/edit'));
    }

    public function edit(Request $r, string $id): string
    {
        $s = Services::find($id);
        if (!$s) Response::notFound();
        return $this->render('admin/services/edit', ['title' => t('services.edit') . ' — ' . tr($s['title']), 's' => $s, 'icons' => Blocks::icons()]);
    }

    public function update(Request $r, string $id): void
    {
        $s = Services::find($id);
        if (!$s) Response::notFound();
        $data = $this->collect($r, $s);
        if (is_string($data)) $this->fail($data, admin_url('services/' . $id . '/edit'));
        Services::update($id, $data);
        $this->log('update', 'services', $id, ['slug' => $data['slug']]);
        $this->ok(t('common.saved'), admin_url('services/' . $id . '/edit'));
    }

    public function destroy(Request $r, string $id): void
    {
        $s = Services::find($id);
        if (!$s) Response::notFound();
        Services::delete($id);
        $this->log('delete', 'services', $id, ['slug' => $s['slug']]);
        $this->ok(t('common.deleted'), admin_url('services'));
    }

    public function reorder(Request $r): void
    {
        Services::reorder($this->jsonField($r, 'order'));
        Response::json(['ok' => true]);
    }

    private function collect(Request $r, ?array $existing): array|string
    {
        $title = $r->i18n('title');
        $slug = slugify($r->str('slug') ?: ($title['en'] ?: $title['ar']));
        $v = Validator::make(['title' => $title, 'slug' => $slug], ['title' => 'required|max:160', 'slug' => 'required|slug|max:120']);
        if ($v->fails()) return $v->first();
        if (Services::slugExists($slug, $existing['id'] ?? null)) return t('services.slug_taken');
        $features = [];
        foreach ($this->jsonField($r, 'features') as $f) {
            $f = is_array($f) ? $f : ['ar' => (string)$f, 'en' => ''];
            $row = ['ar' => mb_substr(trim(strip_tags((string)($f['ar'] ?? ''))), 0, 200), 'en' => mb_substr(trim(strip_tags((string)($f['en'] ?? ''))), 0, 200)];
            if ($row['ar'] !== '' || $row['en'] !== '') $features[] = $row;
        }
        $body = $r->i18n('body');
        return [
            'slug' => $slug,
            'title' => $title,
            'excerpt' => array_map(fn($s) => mb_substr(strip_tags($s), 0, 400), $r->i18n('excerpt')),
            'body' => ['ar' => Html::clean($body['ar']), 'en' => Html::clean($body['en'])],
            'icon' => in_array($r->str('icon'), Blocks::icons(), true) ? $r->str('icon') : 'sparkle',
            'image' => $this->imagePath($r->input('image')),
            'features' => $features,
            'meta_title' => array_map(fn($s) => mb_substr($s, 0, 120), $r->i18n('meta_title')),
            'meta_description' => array_map(fn($s) => mb_substr($s, 0, 320), $r->i18n('meta_description')),
            'meta_keywords' => array_map(fn($s) => mb_substr($s, 0, 500), $r->i18n('meta_keywords')),
            'sort_order' => $r->int('sort_order', 100),
            'is_featured' => $r->bool('is_featured'),
            'status' => $r->str('status') === 'draft' ? 'draft' : 'published',
        ];
    }
}
