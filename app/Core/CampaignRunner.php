<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Mail;
use App\Models\Settings;

/**
 * Sends campaign batches with per-sender throttling, warm-up, sender rotation,
 * tracked links, open pixel, personalised merge tags and bounce handling.
 * Called by the admin "process" action, the cron endpoint and tools/mail-worker.php.
 */
final class CampaignRunner
{
    public const BATCH = 25;

    /** Process every due campaign; returns a summary. */
    public static function tick(int $maxPerRun = 60): array
    {
        $summary = [];
        foreach (Mail::dueCampaigns() as $c) {
            if ($c['status'] === 'scheduled') {
                if ($c['scheduled_at'] && strtotime($c['scheduled_at']) > time()) continue;
                Mail::updateCampaign($c['id'], ['status' => 'sending', 'started_at' => gmdate('c')]);
            }
            $summary[$c['id']] = self::process($c['id'], $maxPerRun);
        }
        return $summary;
    }

    /** Send up to $max queued emails of one campaign. */
    public static function process(string $campaignId, int $max = self::BATCH): array
    {
        $campaign = Mail::campaign($campaignId);
        if (!$campaign || !in_array($campaign['status'], ['sending', 'scheduled'], true)) return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'done' => true];
        $senders = [];
        foreach ((array)$campaign['sender_ids'] as $sid) {
            $s = Mail::sender((string)$sid);
            if ($s && $s['is_active']) $senders[$s['id']] = $s;
        }
        if (!$senders) {
            Mail::updateCampaign($campaignId, ['status' => 'paused']);
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'done' => false, 'error' => 'no active sender'];
        }
        // capacity per sender for this run (hourly limit, daily limit, campaign throttle, warm-up)
        $capacity = [];
        foreach ($senders as $id => $s) {
            $usage = Mail::senderUsage($id);
            $hourly = min((int)$s['hourly_limit'], (int)$campaign['throttle_per_hour']);
            $capacity[$id] = max(0, min($hourly - (int)$usage['hour'], (int)$s['daily_limit'] - (int)$usage['day']));
        }
        $rows = Mail::nextQueued($campaignId, $max);
        $sent = $failed = $skipped = 0;
        $connections = [];
        $html = (string)$campaign['html'];
        $settings = Settings::all();
        $suppressed = [];
        foreach (Mail::suppressions(5000) as $sup) $suppressed[$sup['email']] = true;

        foreach ($rows as $row) {
            if (isset($suppressed[mb_strtolower($row['email'])])) {
                Mail::updateQueue($row['id'], ['status' => 'skipped', 'error' => 'suppressed']);
                $skipped++;
                continue;
            }
            // choose a sender with capacity (preferred: the one assigned, else rotate)
            $sid = (string)($row['sender_id'] ?? '');
            if (!isset($capacity[$sid]) || $capacity[$sid] <= 0) {
                $sid = '';
                foreach ($capacity as $k => $cap) { if ($cap > 0) { $sid = $k; break; } }
            }
            if ($sid === '') break; // every sender is throttled for now
            $sender = $senders[$sid];
            try {
                $connections[$sid] ??= new Mailer($sender);
                $msg = self::compose($campaign, $row, $sender, $html, $settings);
                $res = $connections[$sid]->send($msg);
            } catch (\Throwable $e) {
                $res = ['ok' => false, 'error' => $e->getMessage(), 'permanent' => false, 'message_id' => ''];
                unset($connections[$sid]);
            }
            if ($res['ok']) {
                Mail::updateQueue($row['id'], ['status' => 'sent', 'sender_id' => $sid, 'sent_at' => gmdate('c'), 'attempts' => (int)$row['attempts'] + 1, 'error' => null]);
                $capacity[$sid]--;
                $sent++;
            } else {
                $attempts = (int)$row['attempts'] + 1;
                $permanent = !empty($res['permanent']);
                if ($permanent) {
                    Mail::updateQueue($row['id'], ['status' => 'failed', 'sender_id' => $sid, 'attempts' => $attempts, 'error' => mb_substr((string)$res['error'], 0, 300)]);
                    Mail::event($campaignId, $row['id'], 'bounce', '', '', mb_substr((string)$res['error'], 0, 200));
                    if (preg_match('/(user unknown|no such user|does not exist|mailbox unavailable|recipient rejected|5\.1\.1)/i', (string)$res['error'])) Mail::suppress($row['email'], 'bounce');
                    $failed++;
                } else {
                    Mail::updateQueue($row['id'], ['status' => $attempts >= 3 ? 'failed' : 'queued', 'attempts' => $attempts, 'error' => mb_substr((string)$res['error'], 0, 300)]);
                    if ($attempts >= 3) $failed++;
                    // connection-level problem: stop using this sender for this run
                    $capacity[$sid] = 0;
                }
            }
            usleep(150000); // gentle pacing: ~6 messages / second max
        }
        foreach ($connections as $c) $c->close();

        $stats = Mail::campaignStats($campaignId);
        $done = (int)($stats['queued'] ?? 0) === 0;
        Mail::updateCampaign($campaignId, ['stats' => $stats] + ($done ? ['status' => 'sent', 'finished_at' => gmdate('c')] : []));
        return ['sent' => $sent, 'failed' => $failed, 'skipped' => $skipped, 'done' => $done, 'remaining' => (int)($stats['queued'] ?? 0)];
    }

    /** Personalise, inject tracking and the compliance footer. */
    public static function compose(array $campaign, array $row, array $sender, string $html, array $settings, bool $preview = false): array
    {
        $base = base_url();
        $token = (string)$row['token'];
        $unsub = $base . '/m/u/' . $token;
        $name = trim((string)($row['name'] ?? ''));
        $firstName = $name !== '' ? explode(' ', $name)[0] : '';
        $siteName = tr($settings['site_name'] ?? 'Athar');
        $address = tr($settings['address'] ?? '');
        $vars = [
            '{{name}}' => $name !== '' ? $name : ($campaign['fallback_name'] ?? 'عميلنا الكريم'),
            '{{first_name}}' => $firstName !== '' ? $firstName : ($campaign['fallback_name'] ?? 'عميلنا الكريم'),
            '{{email}}' => (string)$row['email'],
            '{{company}}' => (string)($row['company'] ?? ''),
            '{{unsubscribe_url}}' => $unsub,
            '{{site_name}}' => $siteName,
            '{{site_url}}' => $base . '/',
            '{{address}}' => $address,
            '{{year}}' => date('Y'),
            '{{subject}}' => (string)$campaign['subject'],
        ];
        $body = strtr($html, $vars);
        $subject = strtr((string)$campaign['subject'], $vars);

        // click tracking: rewrite http(s) links (not unsubscribe / mailto / tel / anchors)
        if (!$preview) {
            $body = preg_replace_callback('/(<a\b[^>]*\bhref=["\'])(https?:\/\/[^"\']+)(["\'])/i', function ($m) use ($base, $token, $unsub) {
                $url = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
                if ($url === $unsub || str_contains($url, '/m/u/')) return $m[0];
                return $m[1] . $base . '/m/c/' . $token . '?u=' . rawurlencode($url) . $m[3];
            }, $body) ?? $body;
        }
        // compliance footer (unsubscribe + address) unless the template already has an unsubscribe link
        if (!preg_match('/\{\{\s*unsubscribe_url\s*\}\}|\/m\/u\//', $html)) {
            $lang = preg_match('/[\x{0600}-\x{06FF}]/u', $body) ? 'ar' : 'en';
            $footer = $lang === 'ar'
                ? '<p style="margin:28px 0 0;font:12px/1.7 Arial,sans-serif;color:#8a8478;text-align:center;direction:rtl">' . e($siteName) . ($address !== '' ? ' · ' . e($address) : '') . '<br>تلقيت هذه الرسالة لأنك تواصلت معنا أو اشتركت في قائمتنا. <a href="' . e($unsub) . '" style="color:#8a8478">إلغاء الاشتراك</a></p>'
                : '<p style="margin:28px 0 0;font:12px/1.7 Arial,sans-serif;color:#8a8478;text-align:center">' . e($siteName) . ($address !== '' ? ' · ' . e($address) : '') . '<br>You receive this email because you contacted us or joined our list. <a href="' . e($unsub) . '" style="color:#8a8478">Unsubscribe</a></p>';
            $body = preg_match('/<\/body>/i', $body) ? preg_replace('/<\/body>/i', $footer . '</body>', $body, 1) : $body . $footer;
        }
        // preheader + open pixel
        $pre = trim((string)($campaign['preheader'] ?? ''));
        $preHtml = $pre !== '' ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent">' . e(strtr($pre, $vars)) . '</div>' : '';
        $pixel = $preview ? '' : '<img src="' . e($base . '/m/o/' . $token . '.gif') . '" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0">';
        if (preg_match('/<body[^>]*>/i', $body)) {
            $body = preg_replace('/(<body[^>]*>)/i', '$1' . $preHtml, $body, 1);
            $body = preg_match('/<\/body>/i', $body) ? preg_replace('/<\/body>/i', $pixel . '</body>', $body, 1) : $body . $pixel;
        } else {
            $body = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head><body style="margin:0;background:#f4f1ea">' . $preHtml . $body . $pixel . '</body></html>';
        }
        return [
            'to' => (string)$row['email'],
            'toName' => $name,
            'subject' => $subject,
            'html' => $body,
            'text' => Mailer::htmlToText($body),
            'unsubscribeUrl' => $unsub,
            'campaignId' => (string)$campaign['id'],
        ];
    }

    /** Starter templates shown in the editor. */
    public static function starterTemplates(): array
    {
        $wrap = fn(string $inner, string $dir = 'rtl') => '<!DOCTYPE html><html lang="' . ($dir === 'rtl' ? 'ar' : 'en') . '" dir="' . $dir . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{subject}}</title></head>'
            . '<body style="margin:0;padding:0;background:#f4f1ea;font-family:Arial,Helvetica,sans-serif;color:#1b1711">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1ea"><tr><td align="center" style="padding:32px 16px">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:20px;overflow:hidden">'
            . $inner . '</table></td></tr></table></body></html>';
        $head = '<tr><td style="background:#0e0d0b;padding:28px 36px;text-align:center"><span style="font-family:Georgia,serif;font-size:34px;color:#c9a868;letter-spacing:2px">أثر</span><br><span style="font-size:12px;letter-spacing:3px;color:#bfb8a8">ATHAR WEB DESIGN</span></td></tr>';
        return [
            'announcement' => ['name' => 'إعلان فاخر / Luxury announcement', 'subject' => 'جديدنا في أثر: {{first_name}}، اكتشف ما يميز علامتك', 'html' => $wrap($head
                . '<tr><td style="padding:40px 36px 12px;text-align:right"><p style="margin:0 0 10px;font-size:13px;letter-spacing:2px;color:#a8853f">رسالة خاصة</p><h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:30px;line-height:1.3;color:#1b1711;font-weight:normal">مرحبًا {{first_name}}، حان وقت حضور رقمي يليق بعلامتك</h1><p style="margin:0;font-size:16px;line-height:1.8;color:#4a443a">نحن في أثر نساعد المطاعم والشركات في قطر على بناء مواقع فاخرة، والتصدر في نتائج جوجل، وإدارة حسابات التواصل الاجتماعي بمحتوى وتصوير احترافي.</p></td></tr>'
                . '<tr><td style="padding:12px 36px 36px;text-align:right"><a href="{{site_url}}" style="display:inline-block;background:#c9a868;color:#0e0d0b;text-decoration:none;font-weight:bold;padding:14px 28px;border-radius:999px;font-size:15px">تعرّف على خدماتنا</a></td></tr>'
                . '<tr><td style="padding:0 36px 36px;text-align:right;border-top:1px solid #efe9dc"><p style="margin:24px 0 0;font-size:14px;line-height:1.8;color:#6e6757">للاستفسار يمكنك الرد على هذه الرسالة مباشرة، وسنعود إليك خلال يوم عمل.</p></td></tr>')],
            'offer' => ['name' => 'عرض محدود / Offer', 'subject' => '{{first_name}}، عرض خاص لتصميم موقعك هذا الشهر', 'html' => $wrap($head
                . '<tr><td style="padding:40px 36px;text-align:right"><h1 style="margin:0 0 14px;font-family:Georgia,serif;font-size:28px;line-height:1.3;font-weight:normal">موقع فاخر ثنائي اللغة مع لوحة تحكم وتحسين لمحركات البحث</h1><p style="margin:0 0 20px;font-size:16px;line-height:1.8;color:#4a443a">خلال هذا الشهر نقدم باقة الإطلاق الكاملة: تصميم حصري، صفحات مهيأة لجوجل، وجلسة تصوير احترافية لمكانك.</p>'
                . '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background:#f7f3ea;border-radius:14px"><tr><td style="padding:20px 24px;text-align:right;font-size:15px;line-height:1.9;color:#1b1711">✦ تصميم موقع فاخر ثنائي اللغة<br>✦ تحسين محركات البحث لصفحات الخدمات<br>✦ جلسة تصوير احترافية<br>✦ شهر مجاني من إدارة السوشيال ميديا</td></tr></table>'
                . '<p style="margin:24px 0 0;text-align:right"><a href="{{site_url}}contact" style="display:inline-block;background:#0e0d0b;color:#f1eadc;text-decoration:none;font-weight:bold;padding:14px 28px;border-radius:999px;font-size:15px">اطلب عرض السعر</a></p></td></tr>')],
            'newsletter_en' => ['name' => 'Newsletter (English)', 'subject' => 'Three ideas to grow your restaurant online this month', 'html' => $wrap($head
                . '<tr><td style="padding:40px 36px 8px"><p style="margin:0 0 10px;font-size:13px;letter-spacing:2px;color:#a8853f">THIS MONTH</p><h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:30px;line-height:1.25;font-weight:normal">Hello {{first_name}}, three ideas to grow online</h1></td></tr>'
                . '<tr><td style="padding:0 36px"><h3 style="margin:18px 0 6px;font-size:18px">1. A menu people can find on Google</h3><p style="margin:0 0 12px;font-size:15px;line-height:1.8;color:#4a443a">Structured service pages and a fast website put you in front of people searching in Doha.</p><h3 style="margin:18px 0 6px;font-size:18px">2. Photography that sells</h3><p style="margin:0 0 12px;font-size:15px;line-height:1.8;color:#4a443a">A professional shoot gives you a month of content for Instagram and TikTok.</p><h3 style="margin:18px 0 6px;font-size:18px">3. Consistency</h3><p style="margin:0 0 24px;font-size:15px;line-height:1.8;color:#4a443a">A monthly content plan keeps your audience engaged and your brand consistent.</p></td></tr>'
                . '<tr><td style="padding:0 36px 36px"><a href="{{site_url}}en/contact" style="display:inline-block;background:#c9a868;color:#0e0d0b;text-decoration:none;font-weight:bold;padding:14px 28px;border-radius:999px;font-size:15px">Talk to us</a></td></tr>', 'ltr')],
        ];
    }
}
