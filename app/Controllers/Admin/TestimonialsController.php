<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Testimonials;

final class TestimonialsController extends AdminController
{
    public function index(Request $r): string
    {
        return $this->render('admin/testimonials/index', ['title' => t('testimonials.title'), 'rows' => Testimonials::all()]);
    }

    public function create(Request $r): string
    {
        $t = ['id' => '', 'author_name' => '', 'author_role' => ['ar' => '', 'en' => ''], 'company' => '', 'quote' => ['ar' => '', 'en' => ''], 'avatar' => '', 'rating' => 5, 'sort_order' => 100, 'status' => 'published'];
        return $this->render('admin/testimonials/edit', ['title' => t('testimonials.new'), 't' => $t]);
    }

    public function store(Request $r): void
    {
        $data = $this->collect($r);
        if (is_string($data)) $this->fail($data, admin_url('testimonials/create'), $r->all());
        $row = Testimonials::create($data);
        $this->log('create', 'testimonials', $row['id'] ?? '');
        $this->ok(t('common.created'), admin_url('testimonials'));
    }

    public function edit(Request $r, string $id): string
    {
        $t = Testimonials::find($id);
        if (!$t) Response::notFound();
        return $this->render('admin/testimonials/edit', ['title' => t('testimonials.edit'), 't' => $t]);
    }

    public function update(Request $r, string $id): void
    {
        if (!Testimonials::find($id)) Response::notFound();
        $data = $this->collect($r);
        if (is_string($data)) $this->fail($data, admin_url('testimonials/' . $id . '/edit'));
        Testimonials::update($id, $data);
        $this->log('update', 'testimonials', $id);
        $this->ok(t('common.saved'), admin_url('testimonials'));
    }

    public function destroy(Request $r, string $id): void
    {
        if (!Testimonials::find($id)) Response::notFound();
        Testimonials::delete($id);
        $this->log('delete', 'testimonials', $id);
        $this->ok(t('common.deleted'), admin_url('testimonials'));
    }

    private function collect(Request $r): array|string
    {
        $data = ['author_name' => $r->str('author_name'), 'quote' => $r->i18n('quote')];
        $v = Validator::make($data, ['author_name' => 'required|max:100', 'quote' => 'required|max:1200']);
        if ($v->fails()) return $v->first();
        return [
            'author_name' => mb_substr(strip_tags($data['author_name']), 0, 100),
            'author_role' => array_map(fn($s) => mb_substr(strip_tags($s), 0, 120), $r->i18n('author_role')),
            'company' => mb_substr(strip_tags($r->str('company')), 0, 120),
            'quote' => array_map(fn($s) => mb_substr(strip_tags($s), 0, 1200), $data['quote']),
            'avatar' => $this->imagePath($r->input('avatar')),
            'rating' => min(5, max(1, $r->int('rating', 5))),
            'sort_order' => $r->int('sort_order', 100),
            'status' => $r->str('status') === 'draft' ? 'draft' : 'published',
        ];
    }
}
