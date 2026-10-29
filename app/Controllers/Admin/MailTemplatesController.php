<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CampaignRunner;
use App\Core\Request;
use App\Core\Response;
use App\Models\Mail;
use App\Models\Settings;

final class MailTemplatesController extends AdminController
{
    public function index(Request $r): string
    {
        return $this->render('admin/mail/templates', ['title' => t('mail.templates'), 'rows' => Mail::templates(), 'starters' => CampaignRunner::starterTemplates(), 'useMail' => true]);
    }

    public function create(Request $r): string
    {
        $starters = CampaignRunner::starterTemplates();
        $start = $starters[$r->str('starter')] ?? null;
        $t = ['id' => '', 'name' => $start['name'] ?? '', 'subject' => $start['subject'] ?? '', 'html' => $start['html'] ?? ''];
        return $this->render('admin/mail/template', ['title' => t('mail.new_template'), 't' => $t, 'useMail' => true]);
    }

    public function store(Request $r): void
    {
        $d = $this->collect($r);
        if (is_string($d)) $this->fail($d, admin_url('mail/templates/create'), $r->all());
        $t = Mail::createTemplate($d);
        $this->log('create', 'mail_templates', $t['id'] ?? '');
        $this->ok(t('common.created'), admin_url('mail/templates/' . ($t['id'] ?? '') . '/edit'));
    }

    public function edit(Request $r, string $id): string
    {
        $t = Mail::template($id);
        if (!$t) Response::notFound();
        return $this->render('admin/mail/template', ['title' => t('mail.edit_template'), 't' => $t, 'useMail' => true]);
    }

    public function update(Request $r, string $id): void
    {
        if (!Mail::template($id)) Response::notFound();
        $d = $this->collect($r);
        if (is_string($d)) $this->fail($d, admin_url('mail/templates/' . $id . '/edit'));
        Mail::updateTemplate($id, $d);
        $this->log('update', 'mail_templates', $id);
        if ($r->wantsJson()) Response::json(['ok' => true]);
        $this->ok(t('common.saved'), admin_url('mail/templates/' . $id . '/edit'));
    }

    public function destroy(Request $r, string $id): void
    {
        if (!Mail::template($id)) Response::notFound();
        Mail::deleteTemplate($id);
        $this->log('delete', 'mail_templates', $id);
        $this->ok(t('common.deleted'), admin_url('mail/templates'));
    }

    /** Live preview with merge tags filled (sandboxed iframe) */
    public function preview(Request $r): void
    {
        $html = (string)$r->input('html', '');
        if ($html === '' && $r->query('id')) { $t = Mail::template((string)$r->query('id')); $html = $t['html'] ?? ''; }
        if ($html === '' && $r->query('starter')) { $html = CampaignRunner::starterTemplates()[(string)$r->query('starter')]['html'] ?? ''; }
        $row = ['email' => 'client@example.com', 'name' => 'محمد العلي', 'company' => 'Sample Café', 'token' => str_repeat('0', 24)];
        $msg = CampaignRunner::compose(['id' => 'preview', 'subject' => (string)$r->input('subject', 'Preview'), 'preheader' => (string)$r->input('preheader', '')], $row, [], $html, Settings::all(), true);
        header('Content-Type: text/html; charset=UTF-8');
        header("Content-Security-Policy: default-src 'none'; img-src * data:; style-src 'unsafe-inline'; font-src *");
        echo $msg['html'];
        exit;
    }

    private function collect(Request $r): array|string
    {
        $name = mb_substr(strip_tags($r->str('name')), 0, 120);
        if ($name === '') return t('validation.required', ['field' => t('mail.template_name')]);
        $html = (string)$r->input('html', '');
        if (strlen($html) > 400000) return t('mail.html_too_large');
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        return ['name' => $name, 'subject' => mb_substr(strip_tags($r->str('subject')), 0, 200), 'html' => $html];
    }
}
