<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\CampaignRunner;
use App\Core\Deliverability;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Models\Mail;
use App\Models\Settings;

final class MailCampaignsController extends AdminController
{
    public function index(Request $r): string
    {
        return $this->render('admin/mail/campaigns', ['title' => t('mail.campaigns'), 'rows' => Mail::campaigns(), 'overview' => Mail::overview(), 'senders' => Mail::senders(), 'lists' => Mail::lists(), 'useMail' => true]);
    }

    public function create(Request $r): string
    {
        $tpl = $r->query('template') ? Mail::template((string)$r->query('template')) : null;
        $c = ['id' => '', 'name' => '', 'subject' => $tpl['subject'] ?? '', 'preheader' => '', 'sender_ids' => [], 'list_ids' => [], 'template_id' => $tpl['id'] ?? null, 'html' => $tpl['html'] ?? '', 'status' => 'draft', 'scheduled_at' => null, 'throttle_per_hour' => 150, 'skip_risky' => true, 'score' => null, 'stats' => []];
        return $this->form($c);
    }

    public function store(Request $r): void
    {
        $d = $this->collect($r);
        if (is_string($d)) $this->fail($d, admin_url('mail/campaigns/create'), $r->all());
        $c = Mail::createCampaign($d);
        $this->log('create', 'mail_campaigns', $c['id'] ?? '', ['name' => $d['name']]);
        $this->ok(t('common.created'), admin_url('mail/campaigns/' . ($c['id'] ?? '') . '/edit'));
    }

    public function edit(Request $r, string $id): string
    {
        $c = Mail::campaign($id);
        if (!$c) Response::notFound();
        if (in_array($c['status'], ['sending', 'sent', 'scheduled', 'paused'], true)) Response::redirect(admin_url('mail/campaigns/' . $id));
        return $this->form($c);
    }

    public function update(Request $r, string $id): void
    {
        $c = Mail::campaign($id);
        if (!$c) Response::notFound();
        if (!in_array($c['status'], ['draft', 'cancelled'], true)) $this->fail(t('mail.locked'), admin_url('mail/campaigns/' . $id));
        $d = $this->collect($r);
        if (is_string($d)) {
            if ($r->wantsJson()) Response::json(['ok' => false, 'error' => $d], 422);
            $this->fail($d, admin_url('mail/campaigns/' . $id . '/edit'));
        }
        $d['status'] = 'draft';
        Mail::updateCampaign($id, $d);
        $this->log('update', 'mail_campaigns', $id);
        if ($r->wantsJson()) Response::json(['ok' => true]);
        $this->ok(t('common.saved'), admin_url('mail/campaigns/' . $id . '/edit'));
    }

    /** Report / control page */
    public function show(Request $r, string $id): string
    {
        $c = Mail::campaign($id);
        if (!$c) Response::notFound();
        $stats = Mail::campaignStats($id);
        $per = 50;
        $status = in_array($r->str('status'), ['queued', 'sent', 'failed', 'skipped'], true) ? $r->str('status') : '';
        [$rows, $total] = Mail::queueRows($id, max(1, $r->int('page', 1)), $per, $status);
        $senders = [];
        foreach ((array)$c['sender_ids'] as $sid) { if ($s = Mail::sender((string)$sid)) $senders[] = $s; }
        $lists = [];
        foreach ((array)$c['list_ids'] as $lid) { if ($l = Mail::list((string)$lid)) { $l['summary'] = Mail::listSummary($l['id']); $lists[] = $l; } }
        return $this->render('admin/mail/campaign', [
            'title' => $c['name'], 'c' => $c, 'stats' => $stats, 'rows' => $rows, 'pager' => $this->pager($r, $total, $per), 'status' => $status,
            'senders' => $senders, 'lists' => $lists, 'events' => Mail::events($id, 40), 'useMail' => true, 'useCharts' => true,
        ]);
    }

    /** Deliverability score (JSON for the editor, or full page refresh) */
    public function score(Request $r, string $id): void
    {
        $c = Mail::campaign($id);
        if (!$c) Response::notFound();
        if ($r->input('html') !== null) { // unsaved editor content
            $c['html'] = (string)$r->input('html'); $c['subject'] = $r->str('subject'); $c['preheader'] = $r->str('preheader');
            $c['sender_ids'] = (array)$r->input('sender_ids', []); $c['list_ids'] = (array)$r->input('list_ids', []); $c['throttle_per_hour'] = $r->int('throttle_per_hour', 150);
        }
        $score = $this->computeScore($c);
        if ($c['status'] === 'draft' && $r->input('html') === null) Mail::updateCampaign($id, ['score' => $score]);
        if ($r->wantsJson()) Response::json(['ok' => true, 'score' => $score]);
        Response::redirect(admin_url('mail/campaigns/' . $id . '/edit'));
    }

    /** Send a test to the signed-in admin (or a given address) */
    public function test(Request $r, string $id): void
    {
        $c = Mail::campaign($id);
        if (!$c) Response::notFound();
        if ($r->input('html') !== null) { $c['html'] = (string)$r->input('html'); $c['subject'] = $r->str('subject') ?: $c['subject']; $c['preheader'] = $r->str('preheader'); }
        $to = filter_var($r->str('to'), FILTER_VALIDATE_EMAIL) ? $r->str('to') : Auth::user()['email'];
        $senderId = $r->str('sender_id') ?: (string)(((array)$c['sender_ids'])[0] ?? '');
        $s = $senderId ? Mail::sender($senderId) : null;
        if (!$s) {
            if ($r->wantsJson()) Response::json(['ok' => false, 'error' => t('mail.no_sender')], 422);
            $this->fail(t('mail.no_sender'), admin_url('mail/campaigns/' . $id . '/edit'));
        }
        $row = ['email' => $to, 'name' => Auth::user()['name'], 'company' => '', 'token' => 'test' . bin2hex(random_bytes(10))];
        $msg = CampaignRunner::compose($c, $row, $s, (string)$c['html'], Settings::all(), true);
        $msg['subject'] = '[TEST] ' . $msg['subject'];
        try { $m = new Mailer($s); $res = $m->send($msg); $m->close(); } catch (\Throwable $e) { $res = ['ok' => false, 'error' => $e->getMessage()]; }
        if ($r->wantsJson()) Response::json(['ok' => $res['ok'], 'error' => $res['error'] ?? '', 'to' => $to]);
        if ($res['ok']) $this->ok(t('mail.test_sent', ['email' => $to]), admin_url('mail/campaigns/' . $id . '/edit'));
        $this->fail(t('mail.test_failed') . ' ' . ($res['error'] ?? ''), admin_url('mail/campaigns/' . $id . '/edit'));
    }

    /** Build the queue and start (now or scheduled) */
    public function launch(Request $r, string $id): void
    {
        $c = Mail::campaign($id);
        if (!$c) Response::notFound();
        if (!in_array($c['status'], ['draft', 'cancelled', 'paused'], true)) $this->fail(t('mail.locked'), admin_url('mail/campaigns/' . $id));
        $senderIds = array_values(array_filter((array)$c['sender_ids'], fn($s) => Mail::sender((string)$s)));
        $listIds = array_values(array_filter((array)$c['list_ids'], fn($l) => Mail::list((string)$l)));
        if (!$senderIds) $this->fail(t('mail.no_sender'), admin_url('mail/campaigns/' . $id . '/edit'));
        if (!$listIds) $this->fail(t('mail.no_list'), admin_url('mail/campaigns/' . $id . '/edit'));
        if (trim((string)$c['subject']) === '' || trim((string)$c['html']) === '') $this->fail(t('mail.no_content'), admin_url('mail/campaigns/' . $id . '/edit'));
        $score = $this->computeScore($c);
        if ($score['score'] < 50 && !$r->bool('force')) $this->fail(t('mail.score_too_low', ['score' => $score['score']]), admin_url('mail/campaigns/' . $id . '/edit'));
        if ($c['status'] !== 'paused') {
            Mail::clearQueue($id);
            $n = Mail::buildQueue($id, $listIds, (bool)$c['skip_risky'], $senderIds);
            if ($n === 0) $this->fail(t('mail.queue_empty'), admin_url('mail/campaigns/' . $id . '/edit'));
        }
        $when = $r->str('scheduled_at');
        $scheduled = $when !== '' ? \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $when, new \DateTimeZone(env('APP_TIMEZONE', 'Asia/Qatar'))) : false;
        $upd = ['score' => $score, 'stats' => Mail::campaignStats($id)];
        if ($scheduled && $scheduled->getTimestamp() > time() + 60) {
            $upd['status'] = 'scheduled'; $upd['scheduled_at'] = $scheduled->setTimezone(new \DateTimeZone('UTC'))->format('c');
        } else {
            $upd['status'] = 'sending'; $upd['started_at'] = gmdate('c'); $upd['scheduled_at'] = null;
        }
        Mail::updateCampaign($id, $upd);
        $this->log('launch', 'mail_campaigns', $id, ['status' => $upd['status']]);
        if ($upd['status'] === 'sending') {
            $res = CampaignRunner::process($id, CampaignRunner::BATCH);
            $this->ok(t('mail.sending_started', ['n' => $res['sent']]), admin_url('mail/campaigns/' . $id));
        }
        $this->ok(t('mail.scheduled_ok'), admin_url('mail/campaigns/' . $id));
    }

    /** Send the next batch (button / AJAX polling) */
    public function process(Request $r, string $id): void
    {
        $c = Mail::campaign($id);
        if (!$c) Response::notFound();
        if ($c['status'] === 'scheduled' && (!$c['scheduled_at'] || strtotime($c['scheduled_at']) <= time())) Mail::updateCampaign($id, ['status' => 'sending', 'started_at' => gmdate('c')]);
        $res = CampaignRunner::process($id, CampaignRunner::BATCH);
        $res['stats'] = Mail::campaignStats($id);
        $res['status'] = Mail::campaign($id)['status'] ?? '';
        if ($r->wantsJson()) Response::json(['ok' => true] + $res);
        $this->ok(t('mail.batch_sent', ['n' => $res['sent']]), admin_url('mail/campaigns/' . $id));
    }

    public function pause(Request $r, string $id): void
    {
        $c = Mail::campaign($id);
        if (!$c) Response::notFound();
        $to = in_array($c['status'], ['sending', 'scheduled'], true) ? 'paused' : ($c['status'] === 'paused' ? 'sending' : $c['status']);
        Mail::updateCampaign($id, ['status' => $to]);
        $this->log($to === 'paused' ? 'pause' : 'resume', 'mail_campaigns', $id);
        $this->ok(t('common.saved'), admin_url('mail/campaigns/' . $id));
    }

    public function cancel(Request $r, string $id): void
    {
        if (!Mail::campaign($id)) Response::notFound();
        Mail::updateCampaign($id, ['status' => 'cancelled', 'finished_at' => gmdate('c')]);
        $this->log('cancel', 'mail_campaigns', $id);
        $this->ok(t('common.saved'), admin_url('mail/campaigns/' . $id));
    }

    public function duplicate(Request $r, string $id): void
    {
        $c = Mail::campaign($id);
        if (!$c) Response::notFound();
        $new = Mail::createCampaign(['name' => $c['name'] . ' (copy)', 'subject' => $c['subject'], 'preheader' => $c['preheader'], 'sender_ids' => $c['sender_ids'], 'list_ids' => $c['list_ids'], 'template_id' => $c['template_id'], 'html' => $c['html'], 'throttle_per_hour' => $c['throttle_per_hour'], 'skip_risky' => $c['skip_risky'], 'status' => 'draft']);
        $this->ok(t('common.created'), admin_url('mail/campaigns/' . ($new['id'] ?? '') . '/edit'));
    }

    /** Follow-up campaign to contacts who did not open */
    public function resend(Request $r, string $id): void
    {
        $c = Mail::campaign($id);
        if (!$c || $c['status'] !== 'sent') Response::notFound();
        $new = Mail::createCampaign(['name' => $c['name'] . ' — ' . t('mail.resend_suffix'), 'subject' => $c['subject'], 'preheader' => $c['preheader'], 'sender_ids' => $c['sender_ids'], 'list_ids' => $c['list_ids'], 'template_id' => $c['template_id'], 'html' => $c['html'], 'throttle_per_hour' => $c['throttle_per_hour'], 'skip_risky' => $c['skip_risky'], 'status' => 'draft']);
        // queue only the unopened recipients
        $unopened = \App\Core\Db::from('mail_queue')->select('contact_id,email,name,sender_id')->eq('campaign_id', $id)->eq('status', 'sent')->is('opened_at', null)->limit(20000)->get();
        $rows = [];
        foreach ($unopened as $u) $rows[] = ['campaign_id' => $new['id'], 'contact_id' => $u['contact_id'], 'sender_id' => $u['sender_id'], 'email' => $u['email'], 'name' => $u['name'], 'token' => bin2hex(random_bytes(12))];
        foreach (array_chunk($rows, 300) as $chunk) \App\Core\Db::from('mail_queue')->insert($chunk);
        Mail::updateCampaign($new['id'], ['stats' => Mail::campaignStats($new['id']), 'status' => 'paused']);
        $this->ok(t('mail.resend_created', ['n' => count($rows)]), admin_url('mail/campaigns/' . $new['id']));
    }

    public function destroy(Request $r, string $id): void
    {
        $c = Mail::campaign($id);
        if (!$c) Response::notFound();
        if ($c['status'] === 'sending') $this->fail(t('mail.locked'), admin_url('mail/campaigns/' . $id));
        Mail::deleteCampaign($id);
        $this->log('delete', 'mail_campaigns', $id);
        $this->ok(t('common.deleted'), admin_url('mail/campaigns'));
    }

    // ------------------------------------------------------------ helpers
    private function form(array $c): string
    {
        return $this->render('admin/mail/campaign-edit', [
            'title' => $c['id'] ? t('mail.edit_campaign') : t('mail.new_campaign'),
            'c' => $c, 'senders' => Mail::senders(true), 'lists' => Mail::lists(), 'templates' => Mail::templates(),
            'starters' => CampaignRunner::starterTemplates(), 'suggested' => Deliverability::suggestSendTime(), 'useMail' => true,
        ]);
    }

    private function computeScore(array $c): array
    {
        $senders = []; foreach ((array)$c['sender_ids'] as $sid) { if ($s = Mail::sender((string)$sid)) $senders[] = $s; }
        $lists = []; foreach ((array)$c['list_ids'] as $lid) { if (Mail::list((string)$lid)) $lists[] = Mail::listSummary((string)$lid); }
        return Deliverability::score($c, $senders, $lists, ['address' => tr(Settings::get('address', ''))]);
    }

    private function collect(Request $r): array|string
    {
        $name = mb_substr(strip_tags($r->str('name')), 0, 150);
        if ($name === '') return t('validation.required', ['field' => t('mail.campaign_name')]);
        $senderIds = array_values(array_filter((array)$r->input('sender_ids', []), fn($s) => is_string($s) && $this->isUuid($s)));
        $listIds = array_values(array_filter((array)$r->input('list_ids', []), fn($s) => is_string($s) && $this->isUuid($s)));
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', (string)$r->input('html', '')) ?? '';
        if (strlen($html) > 400000) return t('mail.html_too_large');
        $tpl = $r->str('template_id');
        return [
            'name' => $name,
            'subject' => mb_substr(strip_tags($r->str('subject')), 0, 200),
            'preheader' => mb_substr(strip_tags($r->str('preheader')), 0, 200) ?: null,
            'sender_ids' => $senderIds,
            'list_ids' => $listIds,
            'template_id' => $this->isUuid($tpl) ? $tpl : null,
            'html' => $html,
            'throttle_per_hour' => max(10, min(2000, $r->int('throttle_per_hour', 150))),
            'skip_risky' => $r->bool('skip_risky'),
        ];
    }
}
