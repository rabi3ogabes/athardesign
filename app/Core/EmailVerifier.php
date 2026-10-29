<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Email verification with a 0–100 score:
 *  syntax (20) · MX records (30) · not disposable (15) · not role-based (10) · SMTP mailbox check (25, or 12 when unknown).
 */
final class EmailVerifier
{
    private static array $mxCache = [];
    private static array $catchAllCache = [];

    private const DISPOSABLE = ['mailinator.com', 'guerrillamail.com', 'guerrillamail.net', '10minutemail.com', '10minutemail.net', 'tempmail.com', 'temp-mail.org', 'temp-mail.io', 'throwawaymail.com', 'yopmail.com', 'yopmail.fr', 'trashmail.com', 'trashmail.de', 'getnada.com', 'dispostable.com', 'fakeinbox.com', 'maildrop.cc', 'mailnesia.com', 'mintemail.com', 'mohmal.com', 'sharklasers.com', 'spamgourmet.com', 'tempail.com', 'tempinbox.com', 'tempr.email', 'discard.email', 'emailondeck.com', 'mailcatch.com', 'mailtemp.net', 'moakt.com', 'burnermail.io', 'inboxkitten.com', 'tmpmail.org', 'tmpmail.net', 'mailsac.com', 'harakirimail.com', 'spam4.me', 'mytemp.email', 'tempmailo.com', 'eyepaste.com', 'emailfake.com', 'crazymailing.com', 'spambox.us', 'mail-temp.com', 'luxusmail.org', 'dropmail.me', 'nowmymail.com', 'jetable.org', 'mailexpire.com', 'tempsky.com', 'binkmail.com', 'bobmail.info', 'chammy.info', 'devnullmail.com', 'letthemeatspam.com', 'mailin8r.com', 'mailinator2.com', 'notmailinator.com', 'reallymymail.com', 'safetymail.info', 'sogetthis.com', 'spamherelots.com', 'spamhereplease.com', 'suremail.info', 'thisisnotmyrealemail.com', 'tradermail.info', 'veryrealemail.com', 'zippymail.info', 'fakemail.net', 'mailtothis.com', 'anonbox.net', 'guerrillamailblock.com', 'grr.la', 'pokemail.net', 'spam.la', 'owlpic.com', 'mailseal.de', 'wegwerfmail.de', 'wegwerfemail.de', 'trash-mail.com', 'kurzepost.de', 'objectmail.com', 'proxymail.eu', 'rcpt.at', 'spamfree24.org', 'tempemail.net', 'tempomail.fr', 'emailtemporanea.com', 'emailtemporario.com.br', 'correotemporal.org', 'tempmail.ninja', 'tempmail.plus', 'tempm.com', 'mail7.io', 'mailpoof.com', 'inboxbear.com', 'guerrillamail.org', 'guerrillamail.biz', 'guerrillamail.de', 'spamex.com', 'mailnull.com', 'e4ward.com', 'gishpuppy.com', 'mailmoat.com', 'spamspot.com', 'filzmail.com', 'incognitomail.com', 'mailzilla.com', 'mail.tm', 'internxt.com'];
    private const ROLE = ['admin', 'administrator', 'info', 'support', 'sales', 'contact', 'help', 'noreply', 'no-reply', 'donotreply', 'postmaster', 'webmaster', 'hostmaster', 'abuse', 'billing', 'marketing', 'office', 'hr', 'jobs', 'careers', 'team', 'hello', 'mail', 'newsletter', 'security', 'root', 'service', 'customerservice', 'press', 'media', 'legal', 'finance', 'accounts', 'enquiries', 'inquiries', 'reservations', 'booking', 'orders'];
    private const FREE = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com', 'icloud.com', 'me.com', 'aol.com', 'protonmail.com', 'proton.me', 'ymail.com', 'msn.com', 'mail.com', 'zoho.com', 'gmx.com', 'yandex.com'];
    private const TYPOS = ['gmial.com' => 'gmail.com', 'gmai.com' => 'gmail.com', 'gamil.com' => 'gmail.com', 'gnail.com' => 'gmail.com', 'gmail.co' => 'gmail.com', 'gmail.con' => 'gmail.com', 'gmali.com' => 'gmail.com', 'gmaill.com' => 'gmail.com', 'googlemail.co' => 'googlemail.com', 'hotmal.com' => 'hotmail.com', 'hotmai.com' => 'hotmail.com', 'hotmial.com' => 'hotmail.com', 'hotmail.co' => 'hotmail.com', 'hotmail.con' => 'hotmail.com', 'outlok.com' => 'outlook.com', 'outloo.com' => 'outlook.com', 'outlook.co' => 'outlook.com', 'yaho.com' => 'yahoo.com', 'yahooo.com' => 'yahoo.com', 'yahoo.co' => 'yahoo.com', 'yahoo.con' => 'yahoo.com', 'icloud.co' => 'icloud.com', 'iclod.com' => 'icloud.com', 'live.co' => 'live.com'];

    /**
     * @return array{score:int,status:string,checks:array}
     */
    public static function verify(string $email, bool $smtpProbe = false, string $probeFrom = ''): array
    {
        $email = mb_strtolower(trim($email));
        $checks = ['syntax' => false, 'mx' => false, 'disposable' => false, 'role' => false, 'free' => false, 'typo' => null, 'smtp' => 'skipped'];
        $score = 0;

        $checks['syntax'] = (bool)filter_var($email, FILTER_VALIDATE_EMAIL) && (bool)preg_match('/^[a-z0-9._%+\-]+@([a-z0-9\-]+\.)+[a-z]{2,}$/i', $email) && !preg_match('/\.\.|^\.|\.@/', $email);
        if (!$checks['syntax']) {
            return ['score' => 0, 'status' => 'invalid', 'checks' => $checks];
        }
        $score += 20;
        [$local, $domain] = explode('@', $email, 2);

        if (isset(self::TYPOS[$domain])) $checks['typo'] = $local . '@' . self::TYPOS[$domain];
        $checks['disposable'] = in_array($domain, self::DISPOSABLE, true);
        $checks['role'] = in_array($local, self::ROLE, true) || (bool)preg_match('/^(no-?reply|do-?not-?reply)/', $local);
        $checks['free'] = in_array($domain, self::FREE, true);

        $mx = self::mx($domain);
        $checks['mx'] = $mx !== [];
        if ($checks['mx']) $score += 30;
        if (!$checks['disposable']) $score += 15;
        if (!$checks['role']) $score += 10;

        if ($checks['mx'] && $smtpProbe && !$checks['disposable']) {
            $checks['smtp'] = self::probe($mx[0], $email, $domain, $probeFrom);
        } elseif ($checks['mx']) {
            $checks['smtp'] = 'unknown';
        }
        $score += match ($checks['smtp']) { 'ok' => 25, 'catch_all' => 15, 'unknown' => 12, 'rejected' => 0, default => 12 };
        if ($checks['smtp'] === 'rejected') $score = min($score, 25);

        $status = 'valid';
        if (!$checks['mx'] || $checks['smtp'] === 'rejected' || $checks['disposable']) $status = 'invalid';
        elseif ($checks['role'] || $checks['typo'] || $checks['smtp'] === 'catch_all' || $score < 70) $status = 'risky';
        return ['score' => max(0, min(100, $score)), 'status' => $status, 'checks' => $checks];
    }

    /** MX hosts for a domain (falls back to A record), cached per process. */
    public static function mx(string $domain): array
    {
        if (isset(self::$mxCache[$domain])) return self::$mxCache[$domain];
        $hosts = [];
        $weights = [];
        if (@getmxrr($domain, $hosts, $weights) && $hosts) {
            array_multisort($weights, SORT_ASC, $hosts);
        } elseif (@dns_get_record($domain, DNS_A) ?: @dns_get_record($domain, DNS_AAAA)) {
            $hosts = [$domain];
        }
        return self::$mxCache[$domain] = array_values(array_filter($hosts));
    }

    /** SMTP RCPT probe: ok | rejected | catch_all | unknown */
    private static function probe(string $mxHost, string $email, string $domain, string $probeFrom): string
    {
        $helo = (string)(parse_url(base_url(), PHP_URL_HOST) ?: 'localhost');
        $from = $probeFrom !== '' ? $probeFrom : 'verify@' . $helo;
        $rcpt = function (string $address) use ($mxHost, $helo, $from): ?int {
            $fp = @stream_socket_client('tcp://' . $mxHost . ':25', $errno, $errstr, 6);
            if (!$fp) return null;
            stream_set_timeout($fp, 8);
            $read = function () use ($fp): string { $d = ''; while (($l = fgets($fp, 512)) !== false) { $d .= $l; if (isset($l[3]) && $l[3] === ' ') break; } return $d; };
            $code = fn(string $r) => (int)substr($r, 0, 3);
            try {
                if ($code($read()) !== 220) return null;
                fwrite($fp, "EHLO {$helo}\r\n"); if ($code($read()) !== 250) return null;
                fwrite($fp, "MAIL FROM:<{$from}>\r\n"); if ($code($read()) !== 250) return null;
                fwrite($fp, "RCPT TO:<{$address}>\r\n"); $c = $code($read());
                fwrite($fp, "QUIT\r\n");
                return $c;
            } finally {
                @fclose($fp);
            }
        };
        $c = $rcpt($email);
        if ($c === null) return 'unknown';
        if ($c >= 500) return 'rejected';
        if ($c >= 400) return 'unknown'; // greylisting / temporary
        // accepted: is the domain a catch-all?
        if (!isset(self::$catchAllCache[$domain])) {
            $rand = $rcpt('athar-' . bin2hex(random_bytes(6)) . '@' . $domain);
            self::$catchAllCache[$domain] = ($rand !== null && $rand < 400);
        }
        return self::$catchAllCache[$domain] ? 'catch_all' : 'ok';
    }

    /** Parse pasted text / CSV into [[email, name, company], ...] */
    public static function parseList(string $raw): array
    {
        $rows = [];
        $seen = [];
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if ($line === '') continue;
            $cells = str_getcsv($line, str_contains($line, ';') && !str_contains($line, ',') ? ';' : ',', '"', '\\');
            $cells = array_map(fn($c) => trim((string)$c, " \t\"'"), $cells);
            $email = ''; $name = ''; $company = '';
            foreach ($cells as $idx => $c) {
                if ($email === '' && filter_var($c, FILTER_VALIDATE_EMAIL)) { $email = mb_strtolower($c); continue; }
                if (preg_match('/[\p{L}]/u', $c) && !filter_var($c, FILTER_VALIDATE_EMAIL)) { if ($name === '') $name = $c; elseif ($company === '') $company = $c; }
            }
            if ($email === '') {
                if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $line, $m)) $email = mb_strtolower($m[0]); else continue;
            }
            if (isset($seen[$email])) continue;
            $seen[$email] = true;
            $rows[] = ['email' => $email, 'name' => mb_substr($name, 0, 120), 'company' => mb_substr($company, 0, 120)];
        }
        return $rows;
    }
}
