<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Image;
use App\Core\Request;
use App\Core\Response;
use App\Models\Media;

final class MediaController extends AdminController
{
    public function index(Request $r): string
    {
        $per = 40;
        [$rows, $total] = Media::paginate(max(1, $r->int('page', 1)), $per, $r->str('q'));
        foreach ($rows as &$m) $m['thumb'] = Image::thumbOf($m['url']);
        unset($m);
        return $this->render('admin/media/index', ['title' => t('media.title'), 'rows' => $rows, 'pager' => $this->pager($r, $total, $per), 'q' => $r->str('q')]);
    }

    /** JSON list for the media picker modal */
    public function list(Request $r): void
    {
        [$rows, $total] = Media::paginate(max(1, $r->int('page', 1)), 40, $r->str('q'));
        $items = array_map(fn($m) => ['id' => $m['id'], 'url' => $m['url'], 'thumb' => Image::thumbOf($m['url']), 'filename' => $m['filename'], 'alt' => $m['alt'], 'width' => $m['width'], 'height' => $m['height']], $rows);
        Response::json(['ok' => true, 'items' => $items, 'total' => $total]);
    }

    public function upload(Request $r): void
    {
        $files = $_FILES['files'] ?? null;
        $uploaded = [];
        $errors = [];
        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $i => $name) {
                $f = ['name' => $name, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
                if ($f['error'] === UPLOAD_ERR_NO_FILE) continue;
                try {
                    $m = Media::store($f, ['ar' => '', 'en' => '']);
                    $uploaded[] = ['id' => $m['id'], 'url' => $m['url'], 'thumb' => $m['thumb'], 'filename' => $m['filename']];
                } catch (\Throwable $e) {
                    $errors[] = $name . ': ' . $e->getMessage();
                }
            }
        } elseif ($single = $r->file('file')) {
            try {
                $m = Media::store($single, ['ar' => '', 'en' => '']);
                $uploaded[] = ['id' => $m['id'], 'url' => $m['url'], 'thumb' => $m['thumb'], 'filename' => $m['filename']];
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
        if ($uploaded) $this->log('upload', 'media', '', ['count' => count($uploaded)]);
        if ($r->wantsJson()) {
            Response::json(['ok' => $uploaded !== [], 'items' => $uploaded, 'errors' => $errors, 'message' => $uploaded ? t('media.uploaded_ok', ['n' => count($uploaded)]) : implode(' ', $errors)]);
        }
        if ($errors) $this->fail(implode(' ', $errors), admin_url('media'));
        $this->ok(t('media.uploaded_ok', ['n' => count($uploaded)]), admin_url('media'));
    }

    public function update(Request $r, string $id): void
    {
        if (!Media::find($id)) Response::notFound();
        Media::update($id, ['alt' => array_map(fn($s) => mb_substr(strip_tags($s), 0, 200), $r->i18n('alt'))]);
        if ($r->wantsJson()) Response::json(['ok' => true]);
        $this->ok(t('common.saved'), admin_url('media'));
    }

    public function destroy(Request $r, string $id): void
    {
        $m = Media::find($id);
        if (!$m) Response::notFound();
        Media::delete($id);
        $this->log('delete', 'media', $id, ['file' => $m['filename']]);
        if ($r->wantsJson()) Response::json(['ok' => true]);
        $this->ok(t('common.deleted'), admin_url('media'));
    }
}
