<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Html;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Packages;

final class PackagesController extends AdminController
{
    public function index(Request $r): string
    {
        return $this->render('admin/packages/index', ['title' => t('packages.title'), 'rows' => Packages::all()] + $this->lists());
    }

    public function create(Request $r): string
    {
        $cat = in_array($r->str('category'), Packages::CATEGORIES, true) ? $r->str('category') : 'website';
        $tier = in_array($r->str('tier'), Packages::TIERS, true) ? $r->str('tier') : 'silver';
        $p = [
            'id' => '', 'slug' => '', 'category' => $cat, 'tier' => $tier,
            'name' => ['ar' => '', 'en' => ''], 'tagline' => ['ar' => '', 'en' => ''], 'body' => ['ar' => '', 'en' => ''],
            'price' => null, 'currency' => 'QAR', 'price_prefix' => ['ar' => 'يبدأ من', 'en' => 'From'], 'period' => ['ar' => '', 'en' => ''], 'price_note' => ['ar' => '', 'en' => ''],
            'delivery' => ['ar' => '', 'en' => ''], 'features' => [], 'whatsapp_message' => ['ar' => '', 'en' => ''], 'cta_text' => ['ar' => '', 'en' => ''], 'badge' => ['ar' => '', 'en' => ''],
            'is_highlighted' => $tier === 'elite', 'image' => '',
            'meta_title' => ['ar' => '', 'en' => ''], 'meta_description' => ['ar' => '', 'en' => ''], 'meta_keywords' => ['ar' => '', 'en' => ''],
            'sort_order' => 100, 'status' => 'published',
        ];
        return $this->render('admin/packages/edit', ['title' => t('packages.new'), 'p' => $p] + $this->lists());
    }

    public function store(Request $r): void
    {
        $data = $this->collect($r, null);
        if (is_string($data)) $this->fail($data, admin_url('packages/create'), $r->all());
        $row = Packages::create($data);
        $this->log('create', 'packages', $row['id'] ?? '', ['slug' => $data['slug']]);
        $this->ok(t('common.created'), admin_url('packages/' . ($row['id'] ?? '') . '/edit'));
    }

    public function edit(Request $r, string $id): string
    {
        $p = Packages::find($id);
        if (!$p) Response::notFound();
        return $this->render('admin/packages/edit', ['title' => t('packages.edit') . ' — ' . tr($p['name']), 'p' => $p] + $this->lists());
    }

    public function update(Request $r, string $id): void
    {
        $p = Packages::find($id);
        if (!$p) Response::notFound();
        $data = $this->collect($r, $p);
        if (is_string($data)) $this->fail($data, admin_url('packages/' . $id . '/edit'));
        Packages::update($id, $data);
        $this->log('update', 'packages', $id, ['slug' => $data['slug']]);
        $this->ok(t('common.saved'), admin_url('packages/' . $id . '/edit'));
    }

    public function destroy(Request $r, string $id): void
    {
        $p = Packages::find($id);
        if (!$p) Response::notFound();
        Packages::delete($id);
        $this->log('delete', 'packages', $id, ['slug' => $p['slug']]);
        $this->ok(t('common.deleted'), admin_url('packages'));
    }

    public function duplicate(Request $r, string $id): void
    {
        $p = Packages::find($id);
        if (!$p) Response::notFound();
        unset($p['id'], $p['created_at'], $p['updated_at']);
        $base = $p['slug'] . '-copy';
        $slug = $base;
        $i = 2;
        while (Packages::slugExists($slug)) $slug = $base . '-' . $i++;
        $p['slug'] = $slug;
        $p['status'] = 'draft';
        $row = Packages::create($p);
        $this->log('duplicate', 'packages', $row['id'] ?? '', ['from' => $id]);
        $this->ok(t('common.created'), admin_url('packages/' . ($row['id'] ?? '') . '/edit'));
    }

    public function reorder(Request $r): void
    {
        Packages::reorder($this->jsonField($r, 'order'));
        Response::json(['ok' => true]);
    }

    /** JSON preview of the WhatsApp text the visitor will send (used live by the editor) */
    public function preview(Request $r): void
    {
        $p = $this->collect($r, ['id' => $r->str('id')], false);
        if (is_string($p)) Response::json(['ok' => false, 'error' => $p]);
        Response::json(['ok' => true, 'ar' => Packages::whatsappMessage($p, 'ar'), 'en' => Packages::whatsappMessage($p, 'en'), 'price_ar' => Packages::price($p, 'ar'), 'price_en' => Packages::price($p, 'en')]);
    }

    private function lists(): array
    {
        return ['cats' => Packages::categoryLabels(), 'tiers' => Packages::tierLabels()];
    }

    private function collect(Request $r, ?array $existing, bool $strict = true): array|string
    {
        $name = $r->i18n('name');
        $slug = slugify($r->str('slug') ?: trim(($name['en'] ?: $name['ar']) . ' ' . $r->str('tier')));
        if ($strict) {
            $v = Validator::make(['name' => $name, 'slug' => $slug], ['name' => 'required|max:120', 'slug' => 'required|slug|max:120']);
            if ($v->fails()) return $v->first();
            if (Packages::slugExists($slug, $existing['id'] ?? null)) return t('packages.slug_taken');
        }
        $features = [];
        foreach ($this->jsonField($r, 'features') as $f) {
            $f = is_array($f) ? $f : ['ar' => (string)$f, 'en' => ''];
            $row = ['ar' => mb_substr(trim(strip_tags((string)($f['ar'] ?? ''))), 0, 200), 'en' => mb_substr(trim(strip_tags((string)($f['en'] ?? ''))), 0, 200), 'included' => filter_var($f['included'] ?? true, FILTER_VALIDATE_BOOL)];
            if ($row['ar'] !== '' || $row['en'] !== '') $features[] = $row;
        }
        $price = str_replace([',', ' '], '', $r->str('price'));
        $body = $r->i18n('body');
        $short = fn(string $k, int $n = 200) => array_map(fn($s) => mb_substr(strip_tags($s), 0, $n), $r->i18n($k));
        return [
            'slug' => $slug,
            'category' => in_array($r->str('category'), Packages::CATEGORIES, true) ? $r->str('category') : 'website',
            'tier' => in_array($r->str('tier'), Packages::TIERS, true) ? $r->str('tier') : 'silver',
            'name' => array_map(fn($s) => mb_substr(strip_tags($s), 0, 120), $name),
            'tagline' => $short('tagline', 240),
            'body' => ['ar' => Html::clean($body['ar']), 'en' => Html::clean($body['en'])],
            'price' => is_numeric($price) && (float)$price >= 0 ? round((float)$price, 2) : null,
            'currency' => preg_match('/^[A-Za-z]{3}$/', $r->str('currency')) ? strtoupper($r->str('currency')) : 'QAR',
            'price_prefix' => $short('price_prefix', 40),
            'period' => $short('period', 60),
            'price_note' => $short('price_note', 200),
            'delivery' => $short('delivery', 120),
            'features' => $features,
            'whatsapp_message' => array_map(fn($s) => mb_substr(strip_tags($s), 0, 800), $r->i18n('whatsapp_message')),
            'cta_text' => $short('cta_text', 60),
            'badge' => $short('badge', 40),
            'is_highlighted' => $r->bool('is_highlighted'),
            'image' => $this->imagePath($r->input('image')),
            'meta_title' => array_map(fn($s) => mb_substr($s, 0, 120), $r->i18n('meta_title')),
            'meta_description' => array_map(fn($s) => mb_substr($s, 0, 320), $r->i18n('meta_description')),
            'meta_keywords' => array_map(fn($s) => mb_substr($s, 0, 500), $r->i18n('meta_keywords')),
            'sort_order' => $r->int('sort_order', 100),
            'status' => $r->str('status') === 'draft' ? 'draft' : 'published',
        ];
    }
}
