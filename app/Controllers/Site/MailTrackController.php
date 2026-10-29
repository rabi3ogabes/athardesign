<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\CampaignRunner;
use App\Core\I18n;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\Mail;

/** Public endpoints used by campaign emails: open pixel, click redirect, unsubscribe, cron. */
final class MailTrackController
{
    public function open(Request $r, string $token): void
    {
        $q = Mail::queueByToken($token);
        if ($q) {
            if (empty($q['opened_at'])) Mail::updateQueue($q['id'], ['opened_at' => gmdate('c')]);
            Mail::event($q['campaign_id'], $q['id'], 'open', '', $r->ip(), $r->userAgent());
        }
        header('Content-Type: image/gif');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }

    public function click(Request $r, string $token): void
    {
        $url = (string)$r->query('u', '');
        if (!preg_match('#^https?://#i', $url)) $url = base_url() . '/';
        $q = Mail::queueByToken($token);
        if ($q) {
            $upd = ['clicked_at' => gmdate('c')];
            if (empty($q['opened_at'])) $upd['opened_at'] = gmdate('c');
            Mail::updateQueue($q['id'], $upd);
            Mail::event($q['campaign_id'], $q['id'], 'click', $url, $r->ip(), $r->userAgent());
        }
        header('Cache-Control: no-store');
        Response::redirect($url, 302);
    }

    /** GET shows a confirmation page; POST (form or RFC 8058 one-click) unsubscribes. */
    public function unsubscribe(Request $r, string $token): void
    {
        $q = Mail::queueByToken($token);
        $lang = I18n::detect($r->fullPath)[0];
        I18n::setLang($lang);
        $done = false;
        if ($q && ($r->isPost() || $r->query('confirm') === '1')) {
            Mail::suppress((string)$q['email'], 'unsubscribe');
            Mail::event($q['campaign_id'], $q['id'], 'unsubscribe', '', $r->ip(), $r->userAgent());
            $done = true;
            if ($r->isPost() && $r->input('List-Unsubscribe') === 'One-Click') { http_response_code(200); echo 'OK'; exit; }
        }
        http_response_code($q ? 200 : 404);
        header('X-Robots-Tag: noindex');
        echo View::make('site/unsubscribe', ['valid' => (bool)$q, 'done' => $done, 'email' => $q['email'] ?? '', 'token' => $token]);
        exit;
    }

    /** Cron endpoint: /cron/mail/{key} where key = first 32 chars of sha256(APP_SECRET) */
    public function cron(Request $r, string $key): void
    {
        $expected = substr(hash('sha256', 'mail-cron|' . (string)env('APP_SECRET', '')), 0, 32);
        if (!hash_equals($expected, $key)) Response::abort(403);
        $summary = CampaignRunner::tick(60);
        Response::json(['ok' => true, 'campaigns' => $summary, 'at' => gmdate('c')]);
    }
}
