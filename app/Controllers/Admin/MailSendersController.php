<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Deliverability;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Mail;

final class MailSendersController extends AdminController
{
    public function index(Request $r): string
    {
        $senders = Mail::senders();
        foreach ($senders as &$s) $s['usage'] = Mail::senderUsage($s['id']);
        unset($s);
        return $this->render('admin/mail/senders', ['title' => t('mail.senders'), 'rows' => $senders, 'editing' => null, 'useMail' => true]);
    }

    public function edit(Request $r, string $id): string
    {
        $s = Mail::sender($id);
        if (!$s) Response::notFound();
        $senders = Mail::senders();
        foreach ($senders as &$x) $x['usage'] = Mail::senderUsage($x['id']);
        unset($x);
        return $this->render('admin/mail/senders', ['title' => t('mail.edit_sender'), 'rows' => $senders, 'editing' => $s, 'useMail' => true]);
    }

    public function store(Request $r): void
    {
        $d = $this->collect($r, null);
        if (is_string($d)) $this->fail($d, admin_url('mail/senders'), $r->all());
        $s = Mail::createSender($d);
        $this->log('create', 'mail_senders', $s['id'] ?? '', ['email' => $d['email']]);
        $this->runChecks($s['id'] ?? '');
        $this->ok(t('common.created'), admin_url('mail/senders'));
    }

    public function update(Request $r, string $id): void
    {
        $s = Mail::sender($id);
        if (!$s) Response::notFound();
        $d = $this->collect($r, $s);
        if (is_string($d)) $this->fail($d, admin_url('mail/senders/' . $id . '/edit'));
        Mail::updateSender($id, $d);
        $this->log('update', 'mail_senders', $id, ['email' => $d['email']]);
        $this->runChecks($id);
        $this->ok(t('common.saved'), admin_url('mail/senders'));
    }

    public function destroy(Request $r, string $id): void
    {
        if (!Mail::sender($id)) Response::notFound();
        Mail::deleteSender($id);
        $this->log('delete', 'mail_senders', $id);
        $this->ok(t('common.deleted'), admin_url('mail/senders'));
    }

    /** SMTP connection + DNS authentication check */
    public function test(Request $r, string $id): void
    {
        $s = Mail::sender($id);
        if (!$s) Response::notFound();
        $res = $this->runChecks($id, true);
        if ($r->wantsJson()) Response::json(['ok' => $res['smtp']['ok'], 'smtp' => $res['smtp'], 'dns' => $res['dns']]);
        \App\Core\Session::flash($res['smtp']['ok'] ? 'success' : 'error', $res['smtp']['ok'] ? t('mail.test_ok') : t('mail.test_failed') . ' ' . $res['smtp']['error']);
        Response::back(admin_url('mail/senders'));
    }

    /** Send a real test email from this sender to the signed-in admin */
    public function sendTest(Request $r, string $id): void
    {
        $s = Mail::sender($id);
        if (!$s) Response::notFound();
        $to = \App\Core\Auth::user()['email'];
        $m = new Mailer($s);
        try {
            $res = $m->send(['to' => $to, 'toName' => \App\Core\Auth::user()['name'], 'subject' => 'Athar — test email from ' . $s['name'], 'html' => '<p style="font-family:Arial,sans-serif;font-size:15px">This is a test message from <strong>' . e($s['name']) . '</strong> (' . e($s['email']) . '). If you can read this in your inbox, the sender is ready for campaigns.</p>']);
            $m->close();
        } catch (\Throwable $e) {
            $res = ['ok' => false, 'error' => $e->getMessage()];
        }
        Mail::updateSender($id, ['last_test_at' => gmdate('c'), 'last_test_ok' => $res['ok']]);
        \App\Core\Session::flash($res['ok'] ? 'success' : 'error', $res['ok'] ? t('mail.test_sent', ['email' => $to]) : t('mail.test_failed') . ' ' . $res['error']);
        Response::back(admin_url('mail/senders'));
    }

    /** Generate a DKIM key pair and show the DNS record */
    public function dkim(Request $r, string $id): void
    {
        $s = Mail::sender($id);
        if (!$s) Response::notFound();
        [$priv, $txt] = Mailer::generateDkimKey();
        if ($priv === '') $this->fail(t('common.error'), admin_url('mail/senders/' . $id . '/edit'));
        $selector = preg_replace('/[^a-z0-9]/', '', strtolower($r->str('selector') ?: 'athar')) ?: 'athar';
        Mail::updateSender($id, ['dkim_selector' => $selector, 'dkim_private_key' => $priv]);
        $this->log('dkim', 'mail_senders', $id, ['selector' => $selector]);
        $this->ok(t('mail.dkim_generated'), admin_url('mail/senders/' . $id . '/edit'));
    }

    private function runChecks(string $id, bool $smtp = false): array
    {
        $s = Mail::sender($id);
        if (!$s) return ['smtp' => ['ok' => false, 'error' => ''], 'dns' => []];
        $domain = substr(strrchr((string)$s['email'], '@') ?: '', 1);
        $dns = Deliverability::checkDomain($domain, (string)($s['dkim_selector'] ?? ''));
        $upd = ['dns_check' => $dns];
        $res = ['ok' => (bool)$s['last_test_ok'], 'error' => ''];
        if ($smtp) {
            $res = Mailer::test($s);
            $upd['last_test_at'] = gmdate('c');
            $upd['last_test_ok'] = $res['ok'];
        }
        Mail::updateSender($id, $upd);
        return ['smtp' => $res, 'dns' => $dns];
    }

    private function collect(Request $r, ?array $existing): array|string
    {
        $d = [
            'name' => mb_substr(strip_tags($r->str('name')), 0, 100),
            'email' => mb_strtolower($r->str('email')),
            'reply_to' => mb_strtolower($r->str('reply_to')) ?: null,
            'smtp_host' => $r->str('smtp_host'),
            'smtp_port' => $r->int('smtp_port', 587),
            'smtp_user' => $r->str('smtp_user') ?: null,
            'smtp_secure' => in_array($r->str('smtp_secure'), ['tls', 'ssl', 'none'], true) ? $r->str('smtp_secure') : 'tls',
            'hourly_limit' => max(1, min(5000, $r->int('hourly_limit', 100))),
            'daily_limit' => max(1, min(100000, $r->int('daily_limit', 500))),
            'is_active' => $r->bool('is_active'),
            'sort_order' => $r->int('sort_order', 100),
        ];
        $pass = (string)$r->input('smtp_pass', '');
        if ($pass !== '') $d['smtp_pass'] = $pass;
        elseif (!$existing) $d['smtp_pass'] = null;
        $sel = preg_replace('/[^a-z0-9]/', '', strtolower($r->str('dkim_selector')));
        if ($existing) { if ($sel !== (string)($existing['dkim_selector'] ?? '')) $d['dkim_selector'] = $sel ?: null; }
        $v = Validator::make(['name' => $d['name'], 'email' => $d['email'], 'smtp_host' => $d['smtp_host']], ['name' => 'required|max:100', 'email' => 'required|email', 'smtp_host' => 'required|max:200']);
        if ($v->fails()) return $v->first();
        if ($d['reply_to'] && !filter_var($d['reply_to'], FILTER_VALIDATE_EMAIL)) return t('validation.email', ['field' => 'Reply-To']);
        return $d;
    }
}
