<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\DbException;
use App\Core\I18n;
use App\Core\Installer;
use App\Core\Log;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Activity;
use App\Models\Users;

/**
 * Web installer shown at /install until the site is set up.
 * Steps: requirements → database → admin account → done.
 */
final class InstallController
{
    public function handle(Request $r): never
    {
        Session::start();
        $path = rtrim($r->fullPath, '/') ?: '/';
        // relative redirects on purpose: APP_URL may still point at another host name
        // (a hosting panel often pre-fills it), which would otherwise loop with the server's own www redirect
        if ($path !== '/install') {
            Response::redirect('/install');
        }
        // language toggle (?lang=ar|en), remembered in the session
        $lang = $r->query('lang');
        if (is_string($lang) && in_array($lang, I18n::LANGS, true)) {
            Session::set('install_lang', $lang);
            Response::redirect('/install');
        }
        I18n::setLang((string)Session::get('install_lang', 'en'));

        if ($r->isPost()) {
            Csrf::verify($r);
            $this->post($r);
        }
        $this->show($this->step());
    }

    /** Which step the installation is at right now (derived from the real state, never trusted from the client). */
    private function step(): int
    {
        if (!Installer::requirementsMet()) return 1;
        if (!Db::configured()) return 2;
        try {
            if (!Db::hasTable('admin_users')) return 2;
            if (Users::count() === 0) return 3;
        } catch (\Throwable) {
            return 2;
        }
        return 4;
    }

    private function post(Request $r): void
    {
        $step = $this->step();
        $action = $r->str('step');
        if ($action === 'database' && $step === 2) {
            $this->database($r);
        } elseif ($action === 'admin' && $step === 3) {
            $this->admin($r);
        }
    }

    private function database(Request $r): void
    {
        $socket = '';
        $in = [
            'db_host' => $r->str('db_host', '127.0.0.1') ?: '127.0.0.1',
            'db_port' => (string)max(1, $r->int('db_port', 3306)),
            'db_name' => $r->str('db_name'),
            'db_user' => $r->str('db_user'),
            'db_pass' => (string)$r->input('db_pass', ''),
            'app_url' => rtrim($r->str('app_url'), '/'),
            'app_timezone' => $r->str('app_timezone', 'Asia/Qatar') ?: 'Asia/Qatar',
        ];
        // "Use this database": the hosting panel already wrote the connection into .env
        if ($r->str('source') === 'env' && Db::configured()) {
            $env = Db::settings();
            $socket = $env['socket'];
            $in = ['db_host' => $env['host'], 'db_port' => (string)$env['port'], 'db_name' => $env['name'], 'db_user' => $env['user'], 'db_pass' => $env['pass']] + $in;
        }
        $v = Validator::make($in, ['db_name' => 'required|max:64', 'db_user' => 'required|max:64', 'db_host' => 'required|max:191']);
        if ($v->fails()) $this->fail($v->first(), $in);
        if ($in['app_url'] !== '' && !preg_match('#^https?://[^\s/]+$#i', $in['app_url'])) $this->fail(t('install.err_url'), $in);
        if (!in_array($in['app_timezone'], timezone_identifiers_list(), true)) $in['app_timezone'] = 'Asia/Qatar';

        try {
            $pdo = Db::connect($in['db_host'], (int)$in['db_port'], $in['db_name'], $in['db_user'], $in['db_pass'], $socket);
        } catch (DbException $e) {
            $this->fail(t('install.err_connect') . ' ' . $e->getMessage(), $in);
        }
        try {
            Installer::applySchema($pdo);
        } catch (\Throwable $e) {
            Log::error('Installer schema error: ' . $e->getMessage());
            $this->fail(t('install.err_schema') . ' ' . $e->getMessage(), $in);
        }
        try {
            Installer::writeEnv([
                'APP_ENV' => 'production', 'APP_DEBUG' => 'false',
                'APP_URL' => $in['app_url'], 'APP_TIMEZONE' => $in['app_timezone'],
                'DB_HOST' => $in['db_host'], 'DB_PORT' => $in['db_port'], 'DB_NAME' => $in['db_name'], 'DB_USER' => $in['db_user'], 'DB_PASS' => $in['db_pass'],
            ] + ($socket !== '' ? ['DB_SOCKET' => $socket] : []));
        } catch (\Throwable $e) {
            $this->fail($e->getMessage(), $in);
        }
        Db::use($pdo);
        try {
            Installer::seed();
        } catch (\Throwable $e) {
            Log::error('Installer seed error: ' . $e->getMessage());
            $this->fail(t('install.err_seed') . ' ' . $e->getMessage(), $in);
        }
        Session::flash('success', t('install.db_done'));
        Response::redirect('/install');
    }

    private function admin(Request $r): void
    {
        $data = ['name' => $r->str('name'), 'email' => mb_strtolower($r->str('email')), 'password' => (string)$r->input('password', ''), 'password_confirmation' => (string)$r->input('password_confirmation', '')];
        $v = Validator::make($data, ['name' => 'required|max:80', 'email' => 'required|email|max:120', 'password' => 'required|min:10|confirmed']);
        if ($v->fails()) $this->fail($v->first(), $data);
        $lang = in_array($r->str('lang'), I18n::LANGS, true) ? $r->str('lang') : I18n::lang();
        $user = Users::create([
            'name' => $data['name'], 'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => 'owner', 'lang' => $lang, 'is_active' => true,
        ]);
        Installer::lock();
        Auth::login($user);
        Activity::log('install', 'admin_users', (string)($user['id'] ?? ''));
        Session::set('install_complete', true);
        Response::redirect('/install');
    }

    private function fail(string $message, array $input): never
    {
        unset($input['db_pass'], $input['password'], $input['password_confirmation']);
        Session::flash('error', $message);
        Session::keepOld($input);
        Response::redirect('/install');
    }

    private function show(int $step): never
    {
        if ($step === 4) {
            if (!Session::get('install_complete')) {
                // installed but someone opened /install again: nothing to do here
                Response::redirect(admin_url('login'));
            }
            Session::forget('install_complete'); // the final screen is shown once
        }
        // a connection the hosting panel already wrote into .env (shown without its password)
        $envDb = null;
        if ($step === 2 && Db::configured()) {
            $s = Db::settings();
            $envDb = ['host' => $s['socket'] !== '' ? $s['socket'] : $s['host'] . ':' . $s['port'], 'name' => $s['name'], 'user' => $s['user']];
        }
        $flashes = Session::flashes();
        echo View::make('install', [
            'step' => $step,
            'lang' => I18n::lang(),
            'dir' => I18n::dir(),
            'flashes' => $flashes,
            'requirements' => Installer::requirements(),
            'envDb' => $envDb,
            'detectedUrl' => detected_url(),
            'timezones' => ['Asia/Qatar', 'Asia/Riyadh', 'Asia/Dubai', 'Asia/Kuwait', 'Asia/Bahrain', 'Asia/Muscat', 'Africa/Cairo', 'Europe/London', 'UTC'],
        ]);
        Response::finish();
    }
}
