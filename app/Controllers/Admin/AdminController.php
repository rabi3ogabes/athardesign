<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\I18n;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Activity;

abstract class AdminController
{
    protected function render(string $template, array $data = []): string
    {
        $data += [
            'user' => Auth::user(),
            'lang' => I18n::lang(),
            'dir' => I18n::dir(),
            'title' => $data['title'] ?? t('nav.dashboard'),
            'flashes' => Session::flashes(),
        ];
        return View::render($template, $data, 'admin/layout');
    }

    protected function ok(string $message, string $to): never
    {
        Session::flash('success', $message);
        Response::redirect($to);
    }

    protected function fail(string $message, string $to, array $input = []): never
    {
        Session::flash('error', $message);
        if ($input) Session::keepOld($input);
        Response::redirect($to);
    }

    protected function log(string $action, string $entity = '', string $id = '', array $details = []): void
    {
        Activity::log($action, $entity, $id, $details);
    }

    /** Pagination metadata */
    protected function pager(Request $r, int $total, int $perPage): array
    {
        $page = max(1, $r->int('page', 1));
        $pages = max(1, (int)ceil($total / $perPage));
        if ($page > $pages) $page = $pages;
        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'per' => $perPage, 'from' => $total ? ($page - 1) * $perPage + 1 : 0, 'to' => min($total, $page * $perPage)];
    }

    protected function isUuid(string $id): bool
    {
        return (bool)preg_match('/^[0-9a-f\-]{36}$/', $id);
    }

    /** Normalise a posted image path/URL */
    protected function imagePath(mixed $v): string
    {
        $v = is_string($v) ? trim($v) : '';
        if ($v === '') return '';
        if (preg_match('#^https?://#i', $v)) return mb_substr($v, 0, 500);
        $v = '/' . ltrim($v, '/');
        return (str_starts_with($v, '/uploads/') || str_starts_with($v, '/assets/')) ? mb_substr($v, 0, 500) : '';
    }

    /** Decode a JSON field posted by the admin JS (repeaters) */
    protected function jsonField(Request $r, string $key): array
    {
        $raw = $r->input($key);
        if (is_array($raw)) return $raw;
        $d = json_decode((string)$raw, true);
        return is_array($d) ? $d : [];
    }
}
