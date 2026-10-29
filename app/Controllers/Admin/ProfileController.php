<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\I18n;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Users;

final class ProfileController extends AdminController
{
    public function edit(Request $r): string
    {
        return $this->render('admin/profile', ['title' => t('profile.title')]);
    }

    public function update(Request $r): void
    {
        $u = Auth::user();
        $data = ['name' => $r->str('name'), 'email' => mb_strtolower($r->str('email'))];
        $v = Validator::make($data, ['name' => 'required|max:80', 'email' => 'required|email|max:120']);
        if ($v->fails()) $this->fail($v->first(), admin_url('profile'));
        $other = Users::findByEmail($data['email']);
        if ($other && $other['id'] !== $u['id']) $this->fail(t('users.email_taken'), admin_url('profile'));
        $lang = in_array($r->str('lang'), I18n::LANGS, true) ? $r->str('lang') : $u['lang'];
        $upd = ['name' => mb_substr(strip_tags($data['name']), 0, 80), 'email' => $data['email'], 'lang' => $lang];
        $new = (string)$r->input('password', '');
        if ($new !== '') {
            if (!password_verify((string)$r->input('current_password', ''), $u['password_hash'])) $this->fail(t('profile.wrong_password'), admin_url('profile'));
            $pv = Validator::make(['password' => $new, 'password_confirmation' => (string)$r->input('password_confirmation', '')], ['password' => 'min:10|confirmed']);
            if ($pv->fails()) $this->fail($pv->first(), admin_url('profile'));
            $upd['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
        }
        Users::update($u['id'], $upd);
        Session::set('admin_lang', $lang);
        if (isset($upd['password_hash'])) {
            Session::set('admin_stamp', substr(sha1($upd['password_hash']), 0, 16));
        }
        $this->log('update', 'admin_users', $u['id'], ['profile' => true]);
        $this->ok(t('profile.updated'), admin_url('profile'));
    }
}
