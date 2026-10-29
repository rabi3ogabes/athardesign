<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Settings;

/**
 * Visitor + click tracking. Page views are queued and written after the
 * response has been sent so visitors never wait for the database.
 */
final class Analytics
{
    private const VISITOR_COOKIE = 'athar_v';
    private const SESSION_COOKIE = 'athar_s';
    private static array $queue = [];
    private static ?string $visitorId = null;
    private static ?string $sessionId = null;

    public static function visitorId(): string
    {
        if (self::$visitorId !== null) return self::$visitorId;
        $c = $_COOKIE[self::VISITOR_COOKIE] ?? '';
        if (!is_string($c) || !preg_match('/^[0-9a-f\-]{36}$/', $c)) {
            $c = self::uuid();
            self::cookie(self::VISITOR_COOKIE, $c, time() + 86400 * 365);
        }
        return self::$visitorId = $c;
    }

    public static function sessionId(): string
    {
        if (self::$sessionId !== null) return self::$sessionId;
        $c = $_COOKIE[self::SESSION_COOKIE] ?? '';
        if (!is_string($c) || !preg_match('/^[0-9a-f\-]{36}$/', $c)) {
            $c = self::uuid();
            self::cookie(self::SESSION_COOKIE, $c, 0);
        }
        return self::$sessionId = $c;
    }

    private static function cookie(string $name, string $value, int $expires): void
    {
        if (headers_sent()) return;
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        setcookie($name, $value, ['expires' => $expires, 'path' => '/', 'secure' => $secure, 'httponly' => false, 'samesite' => 'Lax']);
        $_COOKIE[$name] = $value;
    }

    public static function uuid(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }

    public static function enabled(): bool
    {
        return (bool)Settings::get('analytics_enabled', true);
    }

    /** Queue a page view (written after the response is flushed). */
    public static function pageview(Request $request, string $title, string $lang): void
    {
        if (!self::enabled()) return;
        if (Auth::check() && !Settings::get('analytics_track_admins', false)) return;
        // requests for files (favicon.ico, .map, .php probes…) are not page views
        if (preg_match('/\.[a-z0-9]{2,5}$/i', $request->fullPath)) return;
        $ua = $request->userAgent();
        $parsed = self::parseUa($ua);
        if ($parsed['is_bot'] && !Settings::get('analytics_track_bots', false)) return;

        $ref = $request->referer();
        $host = parse_url(base_url(), PHP_URL_HOST);
        if ($ref !== '' && $host && str_contains($ref, (string)$host)) {
            $ref = ''; // internal navigation is not a referrer
        }
        self::$queue[] = ['kind' => 'pageview', 'payload' => [
            'visitor_id'   => self::visitorId(),
            'session_id'   => self::sessionId(),
            'path'         => substr($request->fullPath, 0, 300),
            'lang'         => $lang,
            'title'        => mb_substr($title, 0, 200),
            'referrer'     => $ref,
            'utm_source'   => self::clean($request->query('utm_source')),
            'utm_medium'   => self::clean($request->query('utm_medium')),
            'utm_campaign' => self::clean($request->query('utm_campaign')),
            'ip'           => $request->ip(),
            'user_agent'   => $ua,
            'device'       => $parsed['device'],
            'browser'      => $parsed['browser'],
            'os'           => $parsed['os'],
            'is_bot'       => $parsed['is_bot'],
            'geo_enabled'  => (bool)Settings::get('analytics_geo', true) ? 'true' : 'false',
        ]];
    }

    /** Record a click immediately (called from the /track endpoint). */
    public static function click(Request $request, string $event, string $target, string $label, string $path, string $lang): void
    {
        if (!self::enabled()) return;
        if (Auth::check() && !Settings::get('analytics_track_admins', false)) return;
        $parsed = self::parseUa($request->userAgent());
        if ($parsed['is_bot'] && !Settings::get('analytics_track_bots', false)) return;
        $allowed = ['whatsapp', 'phone', 'email', 'instagram', 'facebook', 'tiktok', 'snapchat', 'x', 'linkedin', 'youtube', 'threads', 'pinterest', 'behance', 'telegram', 'map', 'cta', 'form', 'link', 'service', 'project', 'package'];
        if (!in_array($event, $allowed, true)) $event = 'link';
        Db::rpc('track_click', ['p' => [
            'visitor_id' => self::visitorId(),
            'session_id' => self::sessionId(),
            'event'      => $event,
            'target'     => mb_substr($target, 0, 500),
            'label'      => mb_substr($label, 0, 120),
            'path'       => mb_substr($path, 0, 300),
            'lang'       => $lang,
            'ip'         => $request->ip(),
            'device'     => $parsed['device'],
            'is_bot'     => $parsed['is_bot'],
        ]]);
    }

    private static function clean(mixed $v): string
    {
        return is_string($v) ? mb_substr(trim($v), 0, 100) : '';
    }

    /** Write queued page views; called after output has been flushed. */
    public static function flush(): void
    {
        if (!self::$queue) return;
        $queue = self::$queue;
        self::$queue = [];
        ignore_user_abort(true);
        foreach ($queue as $job) {
            try {
                if ($job['kind'] === 'pageview') {
                    $res = Db::rpc('track_pageview', ['p' => $job['payload']]);
                    if (is_array($res) && !empty($res['needs_geo'])) {
                        self::geoLookup($job['payload']['ip']);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Analytics write failed: ' . $e->getMessage());
            }
        }
    }

    /** Resolve country/city for an IP (ipwho.is, free, HTTPS) and store it. */
    private static function geoLookup(string $ip): void
    {
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            // private / local address: remember as "Local" so we don't retry
            Db::rpc('set_geo', ['p_ip' => $ip, 'p_country' => 'Local', 'p_code' => '', 'p_city' => '']);
            return;
        }
        $ch = curl_init('https://ipwho.is/' . rawurlencode($ip) . '?fields=success,country,country_code,city');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3, CURLOPT_CONNECTTIMEOUT => 2]);
        $raw = curl_exec($ch);
        curl_close($ch);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data) || empty($data['success'])) {
            Db::rpc('set_geo', ['p_ip' => $ip, 'p_country' => 'Unknown', 'p_code' => '', 'p_city' => '']);
            return;
        }
        Db::rpc('set_geo', [
            'p_ip'      => $ip,
            'p_country' => mb_substr((string)($data['country'] ?? 'Unknown'), 0, 80),
            'p_code'    => strtoupper(substr((string)($data['country_code'] ?? ''), 0, 2)),
            'p_city'    => mb_substr((string)($data['city'] ?? ''), 0, 80),
        ]);
    }

    /** Tiny user-agent parser: device, browser, OS and bot detection. */
    public static function parseUa(string $ua): array
    {
        $u = strtolower($ua);
        $isBot = $ua === '' || (bool)preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|monitor|curl|wget|python|httpclient|lighthouse|pingdom|uptime|headless|scrapy|embedly|quora|pinterest|bingpreview|yandex|baidu|duckduck|semrush|ahrefs|mj12|dotbot|petalbot|applebot|google/i', $ua);

        $device = 'desktop';
        if (preg_match('/ipad|tablet|kindle|playbook|silk|(android(?!.*mobile))/i', $ua)) $device = 'tablet';
        elseif (preg_match('/mobile|iphone|ipod|android|blackberry|opera mini|iemobile|windows phone/i', $ua)) $device = 'mobile';

        $os = 'Other';
        if (str_contains($u, 'windows')) $os = 'Windows';
        elseif (str_contains($u, 'iphone') || str_contains($u, 'ipad') || str_contains($u, 'ipod')) $os = 'iOS';
        elseif (str_contains($u, 'android')) $os = 'Android';
        elseif (str_contains($u, 'mac os') || str_contains($u, 'macintosh')) $os = 'macOS';
        elseif (str_contains($u, 'linux')) $os = 'Linux';

        $browser = 'Other';
        if (str_contains($u, 'edg/') || str_contains($u, 'edge/')) $browser = 'Edge';
        elseif (str_contains($u, 'opr/') || str_contains($u, 'opera')) $browser = 'Opera';
        elseif (str_contains($u, 'samsungbrowser')) $browser = 'Samsung Internet';
        elseif (str_contains($u, 'instagram')) $browser = 'Instagram';
        elseif (str_contains($u, 'snapchat')) $browser = 'Snapchat';
        elseif (str_contains($u, 'fban') || str_contains($u, 'fbav')) $browser = 'Facebook';
        elseif (str_contains($u, 'tiktok') || str_contains($u, 'musical_ly')) $browser = 'TikTok';
        elseif (str_contains($u, 'chrome/') || str_contains($u, 'crios/')) $browser = 'Chrome';
        elseif (str_contains($u, 'firefox/') || str_contains($u, 'fxios/')) $browser = 'Firefox';
        elseif (str_contains($u, 'safari/') && !str_contains($u, 'chrome')) $browser = 'Safari';

        return ['device' => $device, 'browser' => $browser, 'os' => $os, 'is_bot' => $isBot];
    }
}
