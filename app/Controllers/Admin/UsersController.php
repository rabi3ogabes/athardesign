<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\I18n;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Users;

final class UsersController extends AdminController
{
    public function index(Request $r): string
    {
        return $this->render('admin/users/index', ['title' => t('users.title'), 'rows' => Users::all()]);
    }

    public function store(Request $r): void
    {
        $data = ['name' => $r->str('name'), 'email' => mb_strtolower($r->str('email')), 'password' => (string)$r->input('password', '')];
        $v = Validator::make($data, ['name' => 'required|max:80', 'email' => 'required|email|max:120', 'password' => 'required|min:10']);
        if ($v->fails()) $this->fail($v->first(), admin_url('users'), $data);
        if (Users::findByEmail($data['email'])) $this->fail(t('users.email_taken'), admin_url('users'), $data);
        $u = Users::create([
            'name' => mb_substr(strip_tags($data['name']), 0, 80), 'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => in_array($r->str('role'), ['admin', 'editor'], true) ? $r->str('role') : 'editor',
            'lang' => in_array($r->str('lang'), I18n::LANGS, true) ? $r->str('lang') : 'en', 'is_active' => true,
        ]);
        $this->log('create', 'admin_users', $u['id'] ?? '', ['email' => $data['email']]);
        $this->ok(t('common.created'), admin_url('users'));
    }

    public function update(Request $r, string $id): void
    {
        $u = Users::find($id);
        if (!$u) Response::notFound();
        $data = ['name' => $r->str('name'), 'email' => mb_strtolower($r->str('email'))];
        $v = Validator::make($data, ['name' => 'required|max:80', 'email' => 'required|email|max:120']);
        if ($v->fails()) $this->fail($v->first(), admin_url('users'));
        $other = Users::findByEmail($data['email']);
        if ($other && $other['id'] !== $id) $this->fail(t('users.email_taken'), admin_url('users'));
        $upd = ['name' => mb_substr(strip_tags($data['name']), 0, 80), 'email' => $data['email']];
        if ($u['role'] !== 'owner') {
            $upd['role'] = in_array($r->str('role'), ['admin', 'editor'], true) ? $r->str('role') : $u['role'];
            $upd['is_active'] = $r->bool('is_active');
        }
        if ($id === Auth::id()) $upd['is_active'] = true;
        $pw = (string)$r->input('password', '');
        if ($pw !== '') {
            if (mb_strlen($pw) < 10) $this->fail(t('validation.min', ['field' => t('fields.password'), 'min' => 10]), admin_url('users'));
            $upd['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
        }
        Users::update($id, $upd);
        $this->log('update', 'admin_users', $id);
        $this->ok(t('common.saved'), admin_url('users'));
    }

    public function destroy(Request $r, string $id): void
    {
        $u = Users::find($id);
        if (!$u) Response::notFound();
        if ($id === Auth::id()) $this->fail(t('users.cannot_delete_self'), admin_url('users'));
        if ($u['role'] === 'owner') $this->fail(t('common.forbidden'), admin_url('users'));
        $admins = array_filter(Users::all(), fn($x) => $x['is_active'] && in_array($x['role'], ['owner', 'admin'], true) && $x['id'] !== $id);
        if (!$admins) $this->fail(t('users.cannot_delete_last'), admin_url('users'));
        Users::delete($id);
        $this->log('delete', 'admin_users', $id, ['email' => $u['email']]);
        $this->ok(t('common.deleted'), admin_url('users'));
    }
}
