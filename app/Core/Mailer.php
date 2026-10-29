<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Campaign mailer: SMTP with persistent connections, full MIME (text + HTML),
 * deliverability headers (Message-ID, List-Unsubscribe one-click, Precedence,
 * Feedback-ID) and optional DKIM signing (RSA-SHA256, relaxed/relaxed).
 */
final class Mailer
{
    /** @var resource|null */
    private $fp = null;
    private array $sender;
    private string $ehloHost;

    public function __construct(array $sender)
    {
        $this->sender = $sender;
        $this->ehloHost = (string)(parse_url(base_url(), PHP_URL_HOST) ?: 'localhost');
    }

    // ------------------------------------------------------------ connection
    public function connect(): void
    {
        if ($this->fp) return;
        $host = trim((string)$this->sender['smtp_host']);
        $port = (int)($this->sender['smtp_port'] ?: 587);
        $secure = (string)($this->sender['smtp_secure'] ?? 'tls');
        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) throw new \RuntimeException("SMTP connect failed ({$host}:{$port}): {$errstr}");
        stream_set_timeout($fp, 20);
        $this->fp = $fp;
        $this->expect([220]);
        $this->cmd('EHLO ' . $this->ehloHost, [250]);
        if ($secure === 'tls') {
            $this->cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new \RuntimeException('STARTTLS negotiation failed');
            $this->cmd('EHLO ' . $this->ehloHost, [250]);
        }
        $user = (string)($this->sender['smtp_user'] ?? '');
        if ($user !== '') {
            $pass = (string)($this->sender['smtp_pass'] ?? '');
            // AUTH PLAIN first, fall back to LOGIN
            $r = $this->cmd('AUTH PLAIN ' . base64_encode("\0" . $user . "\0" . $pass), [235, 334, 500, 501, 502, 503, 504, 535]);
            if ((int)substr($r, 0, 3) !== 235) {
                $this->cmd('AUTH LOGIN', [334]);
                $this->cmd(base64_encode($user), [334]);
                $this->cmd(base64_encode($pass), [235]);
            }
        }
    }

    public function close(): void
    {
        if (!$this->fp) return;
        try { $this->cmd('QUIT', [221, 250]); } catch (\Throwable) {}
        @fclose($this->fp);
        $this->fp = null;
    }

    private function read(): string
    {
        $data = '';
        while (($line = fgets($this->fp, 1024)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        if ($data === '') throw new \RuntimeException('SMTP server closed the connection');
        return $data;
    }

    private function expect(array $ok): string
    {
        $r = $this->read();
        if (!in_array((int)substr($r, 0, 3), $ok, true)) throw new \RuntimeException('SMTP: ' . trim($r));
        return $r;
    }

    private function cmd(string $c, array $ok): string
    {
        fwrite($this->fp, $c . "\r\n");
        return $this->expect($ok);
    }

    // ------------------------------------------------------------ sending
    /**
     * @param array{to:string,toName?:string,subject:string,html:string,text?:string,unsubscribeUrl?:string,campaignId?:string,headers?:array} $m
     * @return array{ok:bool,error:string,message_id:string,permanent:bool}
     */
    public function send(array $m): array
    {
        $this->connect();
        $from = (string)$this->sender['email'];
        [$raw, $messageId] = $this->build($m);
        try {
            $this->cmd('MAIL FROM:<' . $from . '>', [250]);
            $r = $this->cmd('RCPT TO:<' . $m['to'] . '>', [250, 251, 450, 451, 452, 550, 551, 552, 553, 554]);
            $code = (int)substr($r, 0, 3);
            if ($code >= 500) { $this->cmd('RSET', [250]); return ['ok' => false, 'error' => trim($r), 'message_id' => $messageId, 'permanent' => true]; }
            if ($code >= 400) { $this->cmd('RSET', [250]); return ['ok' => false, 'error' => trim($r), 'message_id' => $messageId, 'permanent' => false]; }
            $this->cmd('DATA', [354]);
            $data = preg_replace('/^\./m', '..', $raw); // dot stuffing
            fwrite($this->fp, $data . "\r\n.\r\n");
            $this->expect([250]);
            return ['ok' => true, 'error' => '', 'message_id' => $messageId, 'permanent' => false];
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            $permanent = (bool)preg_match('/SMTP: 5\d\d/', $msg);
            $this->close();
            return ['ok' => false, 'error' => $msg, 'message_id' => $messageId, 'permanent' => $permanent];
        }
    }

    /** Builds the raw RFC 5322 message (CRLF line endings). Returns [raw, messageId]. */
    public function build(array $m): array
    {
        $from = (string)$this->sender['email'];
        $fromName = (string)($this->sender['name'] ?? '');
        $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
        $messageId = '<' . bin2hex(random_bytes(12)) . '.' . time() . '@' . $domain . '>';
        $boundary = 'b' . bin2hex(random_bytes(14));
        $text = (string)($m['text'] ?? '');
        if ($text === '') $text = self::htmlToText((string)$m['html']);

        $headers = [
            'Date' => date('r'),
            'From' => self::address($from, $fromName),
            'To' => self::address((string)$m['to'], (string)($m['toName'] ?? '')),
            'Subject' => self::encodeHeader((string)$m['subject']),
            'Message-ID' => $messageId,
            'MIME-Version' => '1.0',
            'Content-Type' => 'multipart/alternative; boundary="' . $boundary . '"',
        ];
        $replyTo = trim((string)($this->sender['reply_to'] ?? ''));
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) $headers['Reply-To'] = self::address($replyTo, $fromName);
        if (!empty($m['unsubscribeUrl'])) {
            $headers['List-Unsubscribe'] = '<' . $m['unsubscribeUrl'] . '>, <mailto:' . ($replyTo ?: $from) . '?subject=unsubscribe>';
            $headers['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
            $headers['Precedence'] = 'bulk';
        }
        if (!empty($m['campaignId'])) $headers['Feedback-ID'] = substr((string)$m['campaignId'], 0, 8) . ':athar:campaign:' . preg_replace('/[^a-z0-9]/i', '', $domain);
        foreach (($m['headers'] ?? []) as $k => $v) $headers[$k] = $v;

        $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n"
            . quoted_printable_encode(str_replace(["\r\n", "\n"], ["\n", "\r\n"], $text)) . "\r\n"
            . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n"
            . quoted_printable_encode(str_replace(["\r\n", "\n"], ["\n", "\r\n"], (string)$m['html'])) . "\r\n"
            . "--{$boundary}--\r\n";

        $headerLines = [];
        foreach ($headers as $k => $v) $headerLines[] = $k . ': ' . $v;
        $selector = trim((string)($this->sender['dkim_selector'] ?? ''));
        $key = trim((string)($this->sender['dkim_private_key'] ?? ''));
        if ($selector !== '' && $key !== '') {
            $sig = self::dkimSign($headers, $body, $domain, $selector, $key);
            if ($sig !== '') array_unshift($headerLines, $sig);
        }
        return [implode("\r\n", $headerLines) . "\r\n\r\n" . $body, $messageId];
    }

    // ------------------------------------------------------------ helpers
    public static function address(string $email, string $name = ''): string
    {
        $name = trim($name);
        if ($name === '') return '<' . $email . '>';
        return self::encodeHeader($name) . ' <' . $email . '>';
    }

    public static function encodeHeader(string $s): string
    {
        $s = str_replace(["\r", "\n"], ' ', $s);
        return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : (preg_match('/[":<>@,;\\\\]/', $s) ? '"' . addcslashes($s, '"\\') . '"' : $s);
    }

    /** Plain-text alternative from HTML (links kept as "text (url)"). */
    public static function htmlToText(string $html): string
    {
        $t = preg_replace('/<(style|script|head)[^>]*>.*?<\/\1>/is', '', $html);
        $t = preg_replace('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '$2 ($1)', $t);
        $t = preg_replace('/<(br|\/p|\/div|\/h[1-6]|\/li|\/tr)[^>]*>/i', "\n", $t);
        $t = html_entity_decode(strip_tags((string)$t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = preg_replace('/[ \t]+/', ' ', $t);
        $t = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $t);
        return trim((string)$t);
    }

    /** DKIM-Signature header (relaxed/relaxed, rsa-sha256). Returns '' if the key is invalid. */
    public static function dkimSign(array $headers, string $body, string $domain, string $selector, string $privateKey): string
    {
        $pkey = @openssl_pkey_get_private($privateKey);
        if (!$pkey) return '';
        // body canonicalisation (relaxed)
        $b = str_replace("\r\n", "\n", $body);
        $lines = explode("\n", $b);
        foreach ($lines as &$l) { $l = rtrim(preg_replace('/[ \t]+/', ' ', $l), " \t"); }
        unset($l);
        $b = implode("\r\n", $lines);
        $b = rtrim($b, "\r\n") . "\r\n";
        $bh = base64_encode(hash('sha256', $b, true));
        $signed = ['from', 'to', 'subject', 'date', 'message-id', 'mime-version', 'content-type', 'reply-to', 'list-unsubscribe', 'list-unsubscribe-post'];
        $canon = []; $names = [];
        foreach ($headers as $k => $v) {
            $lk = strtolower($k);
            if (!in_array($lk, $signed, true)) continue;
            $names[] = $lk;
            $canon[] = $lk . ':' . trim(preg_replace('/[ \t]+/', ' ', str_replace(["\r\n", "\n"], '', $v)));
        }
        $dkim = 'v=1; a=rsa-sha256; c=relaxed/relaxed; d=' . $domain . '; s=' . $selector . '; t=' . time() . '; h=' . implode(':', $names) . '; bh=' . $bh . '; b=';
        $toSign = implode("\r\n", $canon) . "\r\n" . 'dkim-signature:' . $dkim;
        if (!openssl_sign($toSign, $sig, $pkey, OPENSSL_ALGO_SHA256)) return '';
        return 'DKIM-Signature: ' . $dkim . base64_encode($sig);
    }

    /** Generates a DKIM key pair: [privatePem, dnsTxtValue]. */
    public static function generateDkimKey(): array
    {
        $opts = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        $res = @openssl_pkey_new($opts);
        if (!$res) {
            // Windows builds of PHP often lack a default openssl.cnf: supply a minimal one
            $cnf = STORAGE_PATH . '/cache/openssl.cnf';
            if (!is_file($cnf)) @file_put_contents($cnf, "[req]\ndistinguished_name = req_distinguished_name\n[req_distinguished_name]\n");
            $opts['config'] = $cnf;
            $res = @openssl_pkey_new($opts);
            if (!$res) return ['', ''];
        }
        if (!@openssl_pkey_export($res, $priv, null, $opts)) return ['', ''];
        $details = openssl_pkey_get_details($res);
        $pub = preg_replace('/-----[^-]+-----|\s+/', '', (string)($details['key'] ?? ''));
        return [(string)$priv, 'v=DKIM1; k=rsa; p=' . $pub];
    }

    /** Quick connection + auth test. */
    public static function test(array $sender): array
    {
        $m = new self($sender);
        try {
            $m->connect();
            $m->close();
            return ['ok' => true, 'error' => ''];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
