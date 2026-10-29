<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\I18n;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Users;

final class AuthController extends AdminController
{
    public function loginForm(Request $r): string
    {
        if (Auth::check()) Response::redirect(admin_url());
        if (Users::count() === 0) Response::redirect(admin_url('setup'));
        return View::make('admin/login', ['flashes' => Session::flashes(), 'lang' => I18n::lang(), 'dir' => I18n::dir()]);
    }

    public function login(Request $r): void
    {
        if (Auth::throttled($r->ip())) {
            $this->fail(t('login.throttled'), admin_url('login'));
        }
        $user = Auth::attempt($r->str('email'), (string)$r->input('password', ''), $r->ip());
        if (!$user) {
            $this->fail(t('login.failed'), admin_url('login'), ['email' => $r->str('email')]);
        }
        Auth::login($user);
        $this->log('login');
        $to = (string)Session::get('_intended', '');
        Session::forget('_intended');
        Response::redirect(($to !== '' && str_starts_with($to, admin_url())) ? $to : admin_url());
    }

    public function logout(Request $r): void
    {
        $this->log('logout');
        Auth::logout();
        Session::destroy();
        Session::flash('success', t('login.logged_out'));
        Response::redirect(admin_url('login'));
    }

    public function setupForm(Request $r): string
    {
        if (Users::count() > 0) Response::redirect(admin_url('login'));
        return View::make('admin/setup', ['flashes' => Session::flashes(), 'lang' => I18n::lang(), 'dir' => I18n::dir()]);
    }

    public function setup(Request $r): void
    {
        if (Users::count() > 0) $this->fail(t('setup.exists'), admin_url('login'));
        $data = ['name' => $r->str('name'), 'email' => mb_strtolower($r->str('email')), 'password' => (string)$r->input('password', ''), 'password_confirmation' => (string)$r->input('password_confirmation', '')];
        $v = Validator::make($data, ['name' => 'required|max:80', 'email' => 'required|email|max:120', 'password' => 'required|min:10|confirmed']);
        if ($v->fails()) $this->fail($v->first(), admin_url('setup'), $data);
        $user = Users::create([
            'name' => $data['name'], 'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => 'owner', 'lang' => in_array($r->str('lang'), I18n::LANGS, true) ? $r->str('lang') : 'en', 'is_active' => true,
        ]);
        Auth::login($user);
        $this->log('setup', 'admin_users', $user['id']);
        $this->ok(t('setup.done'), admin_url());
    }

    public function switchLang(Request $r): void
    {
        $lang = in_array($r->str('lang'), I18n::LANGS, true) ? $r->str('lang') : 'en';
        Session::set('admin_lang', $lang);
        if (Auth::check()) Users::update(Auth::id(), ['lang' => $lang]);
        Response::back(admin_url());
    }
}
