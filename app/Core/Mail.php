<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Settings;

/** Sends notification e-mails via SMTP (if configured) or PHP mail(). */
final class Mail
{
    public static function send(string $to, string $subject, string $html): bool
    {
        $to = trim($to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
        $from = trim((string)Settings::get('mail_from', ''));
        if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $host = (string)parse_url(base_url(), PHP_URL_HOST);
            $from = 'no-reply@' . ($host ?: 'localhost');
        }
        $fromName = strip_tags(setting_tr('site_name', 'Athar'));
        $host = trim((string)Settings::get('smtp_host', ''));
        try {
            if ($host !== '') {
                return self::smtp($host, (int)Settings::get('smtp_port', 587), (string)Settings::get('smtp_user', ''), (string)Settings::get('smtp_pass', ''), (string)Settings::get('smtp_secure', 'tls'), $from, $fromName, $to, $subject, $html);
            }
            $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: " . self::encodeName($fromName) . " <{$from}>\r\n";
            return @mail($to, self::encodeName($subject), $html, $headers);
        } catch (\Throwable $e) {
            Log::error('Mail failed: ' . $e->getMessage());
            return false;
        }
    }

    private static function encodeName(string $s): string
    {
        return '=?UTF-8?B?' . base64_encode($s) . '?=';
    }

    private static function smtp(string $host, int $port, string $user, string $pass, string $secure, string $from, string $fromName, string $to, string $subject, string $html): bool
    {
        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $fp = @stream_socket_client($remote, $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) throw new \RuntimeException("SMTP connect failed: $errstr");
        stream_set_timeout($fp, 12);
        $read = static function () use ($fp): string {
            $data = '';
            while (($line = fgets($fp, 515)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $data;
        };
        $cmd = static function (string $c, array $ok) use ($fp, $read): string {
            fwrite($fp, $c . "\r\n");
            $r = $read();
            if (!in_array((int)substr($r, 0, 3), $ok, true)) throw new \RuntimeException('SMTP error: ' . trim($r));
            return $r;
        };
        $read();
        $cmd('EHLO ' . (parse_url(base_url(), PHP_URL_HOST) ?: 'localhost'), [250]);
        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new \RuntimeException('STARTTLS failed');
            $cmd('EHLO ' . (parse_url(base_url(), PHP_URL_HOST) ?: 'localhost'), [250]);
        }
        if ($user !== '') {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode($user), [334]);
            $cmd(base64_encode($pass), [235]);
        }
        $cmd('MAIL FROM:<' . $from . '>', [250]);
        $cmd('RCPT TO:<' . $to . '>', [250, 251]);
        $cmd('DATA', [354]);
        $headers = 'From: ' . self::encodeName($fromName) . " <{$from}>\r\nTo: <{$to}>\r\nSubject: " . self::encodeName($subject) . "\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\nDate: " . date('r') . "\r\n";
        $body = chunk_split(base64_encode($html));
        $cmd($headers . "\r\n" . $body . "\r\n.", [250]);
        $cmd('QUIT', [221]);
        fclose($fp);
        return true;
    }
}
