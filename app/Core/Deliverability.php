<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Inbox-placement scoring: sender authentication (SPF / DKIM / DMARC), sender setup,
 * content hygiene (spam triggers, links, images, unsubscribe, text version), list quality
 * and sending strategy. Produces a 0–100 score with actionable fixes.
 */
final class Deliverability
{
    public const SPAM_WORDS = ['free money', '100% free', 'act now', 'winner', 'you have been selected', 'click here', 'buy now', 'limited time', 'urgent', 'risk-free', 'guarantee', 'no obligation', 'cash bonus', 'earn money', 'make money', 'double your', 'lowest price', 'unsecured', 'viagra', 'casino', 'lottery', 'congratulations', 'dear friend', 'this is not spam', 'not spam', 'order now', 'special promotion', 'million dollars', '$$$', '!!!', 'cheap', 'bonus', 'prize', 'credit card', 'loan', 'weight loss', 'miracle', 'امسح الديون', 'اربح', 'جائزة', 'مجاناً 100%', 'اضغط هنا', 'عاجل جداً', 'فائز'];
    private const DKIM_SELECTORS = ['default', 'google', 'selector1', 'selector2', 'k1', 'k2', 'k3', 'mail', 's1', 's2', 'dkim', 'smtp', 'mandrill', 'mailjet', 'sendgrid', 'zoho', 'zmail', 'protonmail', 'mx', 'email', 'athar'];

    /** DNS authentication checks for a sending domain. */
    public static function checkDomain(string $domain, string $dkimSelector = ''): array
    {
        $domain = strtolower(trim($domain));
        $out = ['domain' => $domain, 'mx' => false, 'spf' => null, 'dkim' => null, 'dkim_selector' => null, 'dmarc' => null, 'dmarc_policy' => null, 'checked_at' => gmdate('c')];
        if ($domain === '') return $out;
        $out['mx'] = EmailVerifier::mx($domain) !== [];
        foreach ((@dns_get_record($domain, DNS_TXT) ?: []) as $r) {
            $txt = (string)($r['txt'] ?? '');
            if (stripos($txt, 'v=spf1') === 0) { $out['spf'] = $txt; break; }
        }
        $selectors = $dkimSelector !== '' ? array_unique(array_merge([$dkimSelector], self::DKIM_SELECTORS)) : self::DKIM_SELECTORS;
        foreach ($selectors as $sel) {
            foreach ((@dns_get_record($sel . '._domainkey.' . $domain, DNS_TXT) ?: []) as $r) {
                $txt = (string)($r['txt'] ?? '');
                if (stripos($txt, 'v=dkim1') !== false || stripos($txt, 'p=') !== false) { $out['dkim'] = substr($txt, 0, 80) . '…'; $out['dkim_selector'] = $sel; break 2; }
            }
            if ($dkimSelector !== '' && $sel === $dkimSelector && $out['dkim'] === null) {
                // the configured selector must exist; keep scanning only for information
            }
        }
        foreach ((@dns_get_record('_dmarc.' . $domain, DNS_TXT) ?: []) as $r) {
            $txt = (string)($r['txt'] ?? '');
            if (stripos($txt, 'v=dmarc1') === 0) {
                $out['dmarc'] = $txt;
                if (preg_match('/\bp=(none|quarantine|reject)/i', $txt, $m)) $out['dmarc_policy'] = strtolower($m[1]);
                break;
            }
        }
        return $out;
    }

    /**
     * Pre-flight campaign score.
     * @param array $campaign  subject, preheader, html
     * @param array $senders   sender rows (with dns_check, last_test_ok, dkim_selector)
     * @param array $lists     list summaries (valid/risky/invalid/pending/total)
     */
    public static function score(array $campaign, array $senders, array $lists, array $settings = []): array
    {
        $checks = [];
        $add = function (string $key, string $state, int $weight, string $tip = '') use (&$checks) {
            $checks[] = ['key' => $key, 'state' => $state, 'weight' => $weight, 'tip' => $tip];
        };
        $html = (string)($campaign['html'] ?? '');
        $subject = trim((string)($campaign['subject'] ?? ''));
        $text = Mailer::htmlToText($html);
        $lower = mb_strtolower($subject . ' ' . $text);

        // ---- authentication (40) ----
        $spf = $dkim = $dmarc = $tested = true; $anyDns = false;
        foreach ($senders as $s) {
            $d = is_array($s['dns_check'] ?? null) ? $s['dns_check'] : [];
            if ($d) $anyDns = true;
            if (empty($d['spf'])) $spf = false;
            if (empty($d['dkim']) && empty($s['dkim_selector'])) $dkim = false;
            if (empty($d['dmarc'])) $dmarc = false;
            if (empty($s['last_test_ok'])) $tested = false;
        }
        if (!$senders) { $spf = $dkim = $dmarc = $tested = false; }
        $add('senders', $senders ? 'pass' : 'fail', 6, 'Add at least one SMTP sender.');
        $add('smtp_tested', $tested ? 'pass' : 'fail', 6, 'Test every sender connection in Senders.');
        $add('spf', !$anyDns ? 'warn' : ($spf ? 'pass' : 'fail'), 10, 'Add an SPF TXT record on the sending domain that includes your SMTP provider.');
        $add('dkim', !$anyDns ? 'warn' : ($dkim ? 'pass' : 'fail'), 12, 'Publish a DKIM key: generate one in the sender settings and add the DNS TXT record.');
        $add('dmarc', !$anyDns ? 'warn' : ($dmarc ? 'pass' : 'fail'), 6, 'Add a _dmarc TXT record (start with p=none, then quarantine).');

        // ---- sender identity (8) ----
        $hasName = $senders && array_reduce($senders, fn($c, $s) => $c && trim((string)$s['name']) !== '', true);
        $add('from_name', $hasName ? 'pass' : 'fail', 4, 'Give every sender a human-readable name.');
        $freeFrom = $senders && array_reduce($senders, fn($c, $s) => $c || (bool)preg_match('/@(gmail|yahoo|hotmail|outlook|live|icloud)\./i', (string)$s['email']), false);
        $add('business_domain', $freeFrom ? 'warn' : 'pass', 4, 'Send from your own domain, not a free mailbox.');

        // ---- subject (10) ----
        $len = mb_strlen($subject);
        $add('subject_length', $len === 0 ? 'fail' : (($len >= 20 && $len <= 65) ? 'pass' : 'warn'), 4, 'Keep the subject between 20 and 65 characters.');
        $capsRatio = $subject !== '' ? (preg_match_all('/\p{Lu}/u', $subject) / max(1, preg_match_all('/\p{L}/u', $subject))) : 0;
        $add('subject_caps', ($capsRatio > .5 && $len > 8) || str_contains($subject, '!!') ? 'fail' : 'pass', 3, 'Avoid ALL CAPS and repeated punctuation in the subject.');
        $add('preheader', trim((string)($campaign['preheader'] ?? '')) !== '' ? 'pass' : 'warn', 3, 'Add a preheader: the preview line shown next to the subject.');

        // ---- content (30) ----
        $hits = [];
        foreach (self::SPAM_WORDS as $w) { if (str_contains($lower, mb_strtolower($w))) $hits[] = $w; }
        $add('spam_words', count($hits) === 0 ? 'pass' : (count($hits) <= 2 ? 'warn' : 'fail'), 8, $hits ? 'Spam triggers found: ' . implode(', ', array_slice($hits, 0, 6)) : '');
        $words = str_word_count(preg_replace('/[^\p{L}\s]/u', ' ', $text) ?: '');
        $arabicWords = preg_match_all('/[\x{0600}-\x{06FF}]+/u', $text);
        $wordCount = max($words, $arabicWords);
        $add('text_amount', $wordCount >= 40 ? 'pass' : ($wordCount >= 15 ? 'warn' : 'fail'), 5, 'Write at least 40 words of real text; image-only emails are often filtered.');
        preg_match_all('/<a\b[^>]*href=["\']([^"\']+)/i', $html, $links);
        $linkCount = count($links[1]);
        $shorteners = array_filter($links[1], fn($u) => (bool)preg_match('#https?://(bit\.ly|tinyurl\.com|t\.co|goo\.gl|is\.gd|cutt\.ly|rb\.gy)/#i', $u));
        $add('links', $linkCount <= 8 ? 'pass' : ($linkCount <= 15 ? 'warn' : 'fail'), 3, 'Keep links under 8 per email.');
        $add('shorteners', $shorteners ? 'fail' : 'pass', 3, 'Do not use URL shorteners; they are heavily associated with spam.');
        preg_match_all('/<img\b[^>]*>/i', $html, $imgs);
        $imgNoAlt = count(array_filter($imgs[0], fn($i) => !preg_match('/\balt=["\'][^"\']+["\']/i', $i)));
        $add('image_alt', $imgNoAlt === 0 ? 'pass' : 'warn', 2, 'Add alt text to every image.');
        $add('image_ratio', (count($imgs[0]) > 0 && $wordCount < 25) ? 'fail' : 'pass', 3, 'Balance images with text.');
        $add('no_scripts', preg_match('/<(script|form|iframe|object|embed)\b/i', $html) ? 'fail' : 'pass', 3, 'Remove scripts, forms and iframes from the email.');
        $add('size', strlen($html) < 100 * 1024 ? 'pass' : 'warn', 3, 'Keep the HTML under 100 KB (Gmail clips larger messages).');

        // ---- compliance (10) ----
        $hasUnsub = (bool)preg_match('/\{\{\s*unsubscribe(_url)?\s*\}\}|unsubscribe|إلغاء الاشتراك/iu', $html);
        $add('unsubscribe', 'pass', 5, $hasUnsub ? '' : 'An unsubscribe link is added automatically in the footer; keep it visible.');
        $address = trim((string)($settings['address'] ?? ''));
        $add('postal_address', $address !== '' || preg_match('/\{\{\s*address\s*\}\}/i', $html) ? 'pass' : 'warn', 5, 'Add your business address in Settings → Contact; it is shown in the email footer.');

        // ---- list quality (12) ----
        $total = $valid = $risky = $invalid = $pending = 0;
        foreach ($lists as $l) { $total += (int)($l['total'] ?? 0); $valid += (int)($l['valid'] ?? 0); $risky += (int)($l['risky'] ?? 0); $invalid += (int)($l['invalid'] ?? 0); $pending += (int)($l['pending'] ?? 0); }
        $add('list_selected', $total > 0 ? 'pass' : 'fail', 2, 'Select at least one list with contacts.');
        $add('list_verified', $total > 0 && $pending === 0 ? 'pass' : ($total > 0 && $pending / $total < .2 ? 'warn' : 'fail'), 5, 'Verify the list before sending; unverified addresses bounce and hurt reputation.');
        $add('list_quality', $total > 0 && ($valid / max(1, $total)) >= .85 ? 'pass' : ($total > 0 && ($valid / max(1, $total)) >= .6 ? 'warn' : 'fail'), 5, 'Remove invalid and risky contacts; aim for 85%+ valid.');

        // ---- sending strategy (6) ----
        $throttle = (int)($campaign['throttle_per_hour'] ?? 200);
        $add('throttle', $throttle > 0 && $throttle <= 300 ? 'pass' : 'warn', 3, 'Send at most 300 emails per hour per sender; new domains should start around 50.');
        $add('multi_sender', count($senders) > 1 || $total < 300 ? 'pass' : 'warn', 3, 'For large lists add a second sender to spread the volume.');

        $max = 0; $got = 0;
        foreach ($checks as $c) { $max += $c['weight']; $got += match ($c['state']) { 'pass' => $c['weight'], 'warn' => $c['weight'] * .5, default => 0 }; }
        $score = (int)round($got / max(1, $max) * 100);
        $grade = $score >= 90 ? 'A' : ($score >= 75 ? 'B' : ($score >= 60 ? 'C' : 'D'));
        return ['score' => $score, 'grade' => $grade, 'checks' => $checks, 'spam_hits' => $hits, 'checked_at' => gmdate('c')];
    }

    /** Best send windows for Qatar business audiences (Sun–Thu, 9:00–11:30 and 13:30–15:00 Doha time). */
    public static function suggestSendTime(): \DateTimeImmutable
    {
        $tz = new \DateTimeZone('Asia/Qatar');
        $now = new \DateTimeImmutable('now', $tz);
        for ($i = 0; $i < 8; $i++) {
            $d = $now->modify('+' . $i . ' day');
            $dow = (int)$d->format('N'); // 1 Mon … 7 Sun
            if (in_array($dow, [5, 6], true)) continue; // Fri, Sat
            $slot = $d->setTime(9, 30);
            if ($i === 0 && $now >= $slot) { $slot = $d->setTime(13, 30); if ($now >= $slot) continue; }
            return $slot;
        }
        return $now->modify('+1 day')->setTime(9, 30);
    }
}
