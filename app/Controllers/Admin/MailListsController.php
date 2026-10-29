<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\EmailVerifier;
use App\Core\Request;
use App\Core\Response;
use App\Models\Mail;
use App\Models\Settings;

final class MailListsController extends AdminController
{
    public function index(Request $r): string
    {
        $lists = Mail::lists();
        foreach ($lists as &$l) $l['summary'] = Mail::listSummary($l['id']);
        unset($l);
        return $this->render('admin/mail/lists', ['title' => t('mail.lists'), 'rows' => $lists, 'useMail' => true]);
    }

    public function store(Request $r): void
    {
        $name = mb_substr(strip_tags($r->str('name')), 0, 120);
        if ($name === '') $this->fail(t('validation.required', ['field' => t('mail.list_name')]), admin_url('mail/lists'));
        $l = Mail::createList(['name' => $name, 'description' => mb_substr(strip_tags($r->str('description')), 0, 500) ?: null]);
        $this->log('create', 'mail_lists', $l['id'] ?? '', ['name' => $name]);
        $this->ok(t('common.created'), admin_url('mail/lists/' . ($l['id'] ?? '')));
    }

    public function show(Request $r, string $id): string
    {
        $list = Mail::list($id);
        if (!$list) Response::notFound();
        $per = 50;
        $status = in_array($r->str('status'), ['pending', 'valid', 'risky', 'invalid', 'unsubscribed', 'bounced'], true) ? $r->str('status') : '';
        [$rows, $total] = Mail::contacts($id, max(1, $r->int('page', 1)), $per, $status, $r->str('q'));
        return $this->render('admin/mail/list', [
            'title' => $list['name'],
            'list' => $list,
            'summary' => Mail::listSummary($id),
            'rows' => $rows,
            'pager' => $this->pager($r, $total, $per),
            'status' => $status,
            'q' => $r->str('q'),
            'useMail' => true,
        ]);
    }

    public function update(Request $r, string $id): void
    {
        if (!Mail::list($id)) Response::notFound();
        Mail::updateList($id, ['name' => mb_substr(strip_tags($r->str('name')), 0, 120) ?: 'List', 'description' => mb_substr(strip_tags($r->str('description')), 0, 500) ?: null]);
        $this->ok(t('common.saved'), admin_url('mail/lists/' . $id));
    }

    public function destroy(Request $r, string $id): void
    {
        if (!Mail::list($id)) Response::notFound();
        Mail::deleteList($id);
        $this->log('delete', 'mail_lists', $id);
        $this->ok(t('common.deleted'), admin_url('mail/lists'));
    }

    /** Import from pasted text or an uploaded CSV/TXT file */
    public function import(Request $r, string $id): void
    {
        if (!Mail::list($id)) Response::notFound();
        $raw = (string)$r->input('contacts', '');
        if ($file = $r->file('file')) {
            if (($file['size'] ?? 0) > 8 * 1024 * 1024) $this->fail(t('media.err_size'), admin_url('mail/lists/' . $id));
            $raw .= "\n" . (string)file_get_contents($file['tmp_name']);
        }
        $raw = trim($raw);
        if ($raw === '') $this->fail(t('mail.import_empty'), admin_url('mail/lists/' . $id));
        if (!mb_check_encoding($raw, 'UTF-8')) $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1256, ISO-8859-1');
        $rows = EmailVerifier::parseList($raw);
        if (!$rows) $this->fail(t('mail.import_empty'), admin_url('mail/lists/' . $id));
        $added = Mail::importContacts($id, array_slice($rows, 0, 20000));
        $this->log('import', 'mail_lists', $id, ['parsed' => count($rows), 'added' => $added]);
        $this->ok(t('mail.imported', ['n' => $added, 'total' => count($rows)]), admin_url('mail/lists/' . $id));
    }

    /** Verify a batch of pending contacts (called repeatedly by the progress bar) */
    public function verify(Request $r, string $id): void
    {
        if (!Mail::list($id)) Response::notFound();
        if ($r->bool('reset')) Mail::resetVerification($id);
        $probe = $r->bool('smtp') && (bool)Settings::get('mail_smtp_probe', true);
        $batch = $probe ? 8 : 25;
        $pending = Mail::pendingContacts($id, $batch);
        $done = 0;
        $probeFrom = '';
        foreach (Mail::senders(true) as $s) { $probeFrom = (string)$s['email']; break; }
        foreach ($pending as $c) {
            $res = EmailVerifier::verify((string)$c['email'], $probe, $probeFrom);
            Mail::updateContact($c['id'], ['status' => $res['status'], 'score' => $res['score'], 'checks' => $res['checks'], 'verified_at' => gmdate('c')]);
            $done++;
        }
        $summary = Mail::listSummary($id);
        Response::json(['ok' => true, 'processed' => $done, 'remaining' => (int)($summary['pending'] ?? 0), 'summary' => $summary]);
    }

    public function contactUpdate(Request $r, string $id, string $cid): void
    {
        $c = Mail::contact($cid);
        if (!$c || $c['list_id'] !== $id) Response::notFound();
        if ($r->str('action') === 'delete') { Mail::deleteContact($cid); Mail::refreshListCount($id); }
        elseif ($r->str('action') === 'unsubscribe') Mail::suppress((string)$c['email'], 'unsubscribe');
        elseif ($r->str('action') === 'valid') Mail::updateContact($cid, ['status' => 'valid', 'score' => max(70, (int)($c['score'] ?? 70))]);
        if ($r->wantsJson()) Response::json(['ok' => true]);
        Response::back(admin_url('mail/lists/' . $id));
    }

    /** Remove all contacts of one status (e.g. invalid) */
    public function clean(Request $r, string $id): void
    {
        if (!Mail::list($id)) Response::notFound();
        $status = in_array($r->str('status'), ['invalid', 'risky', 'unsubscribed', 'bounced'], true) ? $r->str('status') : 'invalid';
        $n = Mail::deleteContactsByStatus($id, $status);
        Mail::refreshListCount($id);
        $this->log('clean', 'mail_lists', $id, ['status' => $status, 'removed' => $n]);
        $this->ok(t('mail.cleaned', ['n' => $n]), admin_url('mail/lists/' . $id));
    }

    public function export(Request $r, string $id): void
    {
        $list = Mail::list($id);
        if (!$list) Response::notFound();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="list-' . preg_replace('/[^a-z0-9]+/i', '-', $list['name']) . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['email', 'name', 'company', 'status', 'score', 'created_at'], ',', '"', '');
        foreach (Mail::allContacts($id) as $c) fputcsv($out, [$c['email'], $c['name'], $c['company'], $c['status'], $c['score'], $c['created_at']], ',', '"', '');
        fclose($out);
        exit;
    }

    public function suppressions(Request $r): string
    {
        return $this->render('admin/mail/suppressions', ['title' => t('mail.suppressions'), 'rows' => Mail::suppressions(), 'useMail' => true]);
    }

    public function unsuppress(Request $r): void
    {
        Mail::unsuppress($r->str('email'));
        $this->ok(t('common.deleted'), admin_url('mail/suppressions'));
    }
}
