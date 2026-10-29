<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Procedures that used to run inside PostgreSQL (dashboard aggregates,
 * tracking writes, queue building). Called through Db::rpc('name', [...]).
 * Arguments are positional, in the order the old functions declared them.
 */
final class Rpc
{
    // ------------------------------------------------------------------ analytics

    public static function dashboard_overview(string $from, string $to, bool $bots = false): array
    {
        $from = Db::toSql($from);
        $to = Db::toSql($to);
        $botSql = $bots ? '' : ' AND is_bot = 0';
        $range = ' WHERE created_at >= ? AND created_at < ?';
        $p = [$from, $to];

        $v = Db::selectOne('SELECT COUNT(*) pv, COUNT(DISTINCT visitor_id) uv, COUNT(DISTINCT session_id) s FROM visits' . $range . $botSql, $p) ?? [];
        $c = Db::selectOne("SELECT COUNT(*) n, SUM(event = 'whatsapp') wa FROM clicks" . $range . $botSql, $p) ?? [];
        $leads = (int)Db::value('SELECT COUNT(*) FROM leads' . $range, $p);

        $span = max(1, strtotime($to) - strtotime($from));
        $prevFrom = gmdate('Y-m-d H:i:s', strtotime($from) - $span);
        $prev = Db::selectOne('SELECT COUNT(*) pv, COUNT(DISTINCT visitor_id) uv FROM visits' . $range . $botSql, [$prevFrom, $from]) ?? [];

        // daily series over the whole range (UTC days), zero-filled
        $daily = [];
        $start = strtotime(substr($from, 0, 10) . ' 00:00:00 UTC');
        $end = strtotime($to) - 1;
        for ($d = $start; $d <= $end; $d += 86400) {
            $daily[gmdate('Y-m-d', $d)] = ['day' => gmdate('Y-m-d', $d), 'pageviews' => 0, 'visitors' => 0, 'clicks' => 0];
        }
        foreach (Db::select('SELECT DATE(created_at) d, COUNT(*) pv, COUNT(DISTINCT visitor_id) uv FROM visits' . $range . $botSql . ' GROUP BY DATE(created_at)', $p) as $r) {
            if (isset($daily[$r['d']])) { $daily[$r['d']]['pageviews'] = (int)$r['pv']; $daily[$r['d']]['visitors'] = (int)$r['uv']; }
        }
        foreach (Db::select('SELECT DATE(created_at) d, COUNT(*) ck FROM clicks' . $range . $botSql . ' GROUP BY DATE(created_at)', $p) as $r) {
            if (isset($daily[$r['d']])) $daily[$r['d']]['clicks'] = (int)$r['ck'];
        }

        $list = fn(string $sql, callable $map) => array_map($map, Db::select($sql, $p));

        return [
            'pageviews'       => (int)($v['pv'] ?? 0),
            'visitors'        => (int)($v['uv'] ?? 0),
            'sessions'        => (int)($v['s'] ?? 0),
            'clicks'          => (int)($c['n'] ?? 0),
            'whatsapp_clicks' => (int)($c['wa'] ?? 0),
            'leads'           => $leads,
            'prev_pageviews'  => (int)($prev['pv'] ?? 0),
            'prev_visitors'   => (int)($prev['uv'] ?? 0),
            'daily'           => array_values($daily),
            'top_pages'       => $list('SELECT path, COUNT(*) n, COUNT(DISTINCT visitor_id) u FROM visits' . $range . $botSql . ' GROUP BY path ORDER BY n DESC LIMIT 10',
                fn($r) => ['path' => $r['path'], 'views' => (int)$r['n'], 'visitors' => (int)$r['u']]),
            'top_referrers'   => $list("SELECT COALESCE(NULLIF(referrer,''),'(direct)') referrer, COUNT(*) n FROM visits" . $range . $botSql . ' GROUP BY 1 ORDER BY n DESC LIMIT 10',
                fn($r) => ['referrer' => $r['referrer'], 'views' => (int)$r['n']]),
            'devices'         => $list("SELECT COALESCE(device,'unknown') device, COUNT(*) n FROM visits" . $range . $botSql . ' GROUP BY 1 ORDER BY n DESC',
                fn($r) => ['device' => $r['device'], 'views' => (int)$r['n']]),
            'browsers'        => $list("SELECT COALESCE(browser,'unknown') browser, COUNT(*) n FROM visits" . $range . $botSql . ' GROUP BY 1 ORDER BY n DESC LIMIT 8',
                fn($r) => ['browser' => $r['browser'], 'views' => (int)$r['n']]),
            'countries'       => $list("SELECT COALESCE(country,'Unknown') country, COALESCE(country_code,'') code, COUNT(DISTINCT visitor_id) n FROM visits" . $range . $botSql . ' GROUP BY 1,2 ORDER BY n DESC LIMIT 10',
                fn($r) => ['country' => $r['country'], 'code' => $r['code'], 'visitors' => (int)$r['n']]),
            'languages'       => $list("SELECT COALESCE(lang,'ar') lang, COUNT(*) n FROM visits" . $range . $botSql . ' GROUP BY 1 ORDER BY n DESC',
                fn($r) => ['lang' => $r['lang'], 'views' => (int)$r['n']]),
            'clicks_by_event' => $list('SELECT event, COUNT(*) n, COUNT(DISTINCT visitor_id) u FROM clicks' . $range . $botSql . ' GROUP BY event ORDER BY n DESC',
                fn($r) => ['event' => $r['event'], 'clicks' => (int)$r['n'], 'visitors' => (int)$r['u']]),
            'clicks_by_page'  => $list("SELECT COALESCE(path,'/') path, COUNT(*) n FROM clicks" . $range . $botSql . ' GROUP BY 1 ORDER BY n DESC LIMIT 10',
                fn($r) => ['path' => $r['path'], 'clicks' => (int)$r['n']]),
        ];
    }

    public static function visitor_timeline(string $visitorId, int $limit = 200): array
    {
        $limit = max(1, min(1000, $limit));
        $rows = Db::select(
            "(SELECT 'visit' kind, path, NULL event, NULL target, title label, lang, session_id, created_at FROM visits WHERE visitor_id = ? ORDER BY created_at DESC LIMIT {$limit})
             UNION ALL
             (SELECT 'click' kind, path, event, target, label, lang, session_id, created_at FROM clicks WHERE visitor_id = ? ORDER BY created_at DESC LIMIT {$limit})
             ORDER BY created_at DESC",
            [$visitorId, $visitorId]
        );
        foreach ($rows as &$r) $r['created_at'] = Db::toIso((string)$r['created_at']);
        return $rows;
    }

    public static function prune_analytics(int $days): array
    {
        $cut = gmdate('Y-m-d H:i:s', time() - max(1, $days) * 86400);
        $v = Db::exec('DELETE FROM visits WHERE created_at < ?', [$cut]);
        $c = Db::exec('DELETE FROM clicks WHERE created_at < ?', [$cut]);
        $g = Db::exec('DELETE FROM visitors WHERE last_seen < ?', [$cut]);
        $month = gmdate('Y-m-d H:i:s', time() - 30 * 86400);
        Db::exec('DELETE FROM geo_cache WHERE created_at < ?', [$month]);
        Db::exec('DELETE FROM login_attempts WHERE created_at < ?', [$month]);
        return ['visits' => $v, 'clicks' => $c, 'visitors' => $g];
    }

    public static function increment_redirect_hits(string $id): void
    {
        Db::exec('UPDATE redirects SET hits = hits + 1 WHERE id = ?', [$id]);
    }

    /** One round-trip per page view: upsert the visitor, insert the visit, report whether geo lookup is needed. */
    public static function track_pageview(array $p): array
    {
        $ip = (string)($p['ip'] ?? '');
        $isBot = filter_var($p['is_bot'] ?? false, FILTER_VALIDATE_BOOL) ? 1 : 0;
        return Db::transaction(function () use ($p, $ip, $isBot) {
            $g = $ip !== '' ? Db::selectOne('SELECT country, country_code, city FROM geo_cache WHERE ip = ?', [$ip]) : null;
            $needsGeo = $g === null && $ip !== '' && (string)($p['geo_enabled'] ?? 'true') === 'true';
            $now = Db::now();
            Db::exec(
                'INSERT INTO visitors (id, first_seen, last_seen, visits_count, country, country_code, city, ip, user_agent, device, browser, os, referrer, landing_page, lang, is_bot)
                 VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                   last_seen = VALUES(last_seen), visits_count = visits_count + 1, ip = VALUES(ip), user_agent = VALUES(user_agent),
                   device = VALUES(device), browser = VALUES(browser), os = VALUES(os), lang = VALUES(lang),
                   country = COALESCE(VALUES(country), country), country_code = COALESCE(VALUES(country_code), country_code), city = COALESCE(VALUES(city), city)',
                [
                    $p['visitor_id'], $now, $now, $g['country'] ?? null, $g['country_code'] ?? null, $g['city'] ?? null, $ip,
                    self::s($p, 'user_agent', 500), self::s($p, 'device', 20), self::s($p, 'browser', 40), self::s($p, 'os', 40),
                    self::s($p, 'referrer', 500), self::s($p, 'path', 300), self::s($p, 'lang', 5), $isBot,
                ]
            );
            Db::exec(
                'INSERT INTO visits (visitor_id, session_id, path, lang, title, referrer, utm_source, utm_medium, utm_campaign, ip, country, country_code, city, device, browser, os, user_agent, is_bot)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $p['visitor_id'], $p['session_id'], self::s($p, 'path', 300) ?? '/', self::s($p, 'lang', 5), self::s($p, 'title', 200), self::s($p, 'referrer', 500),
                    self::s($p, 'utm_source', 100), self::s($p, 'utm_medium', 100), self::s($p, 'utm_campaign', 100), $ip,
                    $g['country'] ?? null, $g['country_code'] ?? null, $g['city'] ?? null,
                    self::s($p, 'device', 20), self::s($p, 'browser', 40), self::s($p, 'os', 40), self::s($p, 'user_agent', 500), $isBot,
                ]
            );
            return ['needs_geo' => $needsGeo];
        });
    }

    public static function track_click(array $p): array
    {
        $ip = (string)($p['ip'] ?? '');
        $g = $ip !== '' ? Db::selectOne('SELECT country, country_code FROM geo_cache WHERE ip = ?', [$ip]) : null;
        Db::exec(
            'INSERT INTO clicks (visitor_id, session_id, event, target, label, path, lang, ip, country, country_code, device, is_bot) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                self::s($p, 'visitor_id', 36), self::s($p, 'session_id', 36), self::s($p, 'event', 40) ?? 'link', self::s($p, 'target', 500), self::s($p, 'label', 120),
                self::s($p, 'path', 300), self::s($p, 'lang', 5), $ip ?: null, $g['country'] ?? null, $g['country_code'] ?? null, self::s($p, 'device', 20),
                filter_var($p['is_bot'] ?? false, FILTER_VALIDATE_BOOL) ? 1 : 0,
            ]
        );
        return ['ok' => true];
    }

    public static function set_geo(string $ip, ?string $country, ?string $code, ?string $city): void
    {
        $code = $code !== null && $code !== '' ? substr($code, 0, 2) : null;
        Db::exec(
            'INSERT INTO geo_cache (ip, country, country_code, city, created_at) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE country = VALUES(country), country_code = VALUES(country_code), city = VALUES(city), created_at = VALUES(created_at)',
            [$ip, $country, $code, $city, Db::now()]
        );
        $dayAgo = gmdate('Y-m-d H:i:s', time() - 86400);
        Db::exec('UPDATE visitors SET country = ?, country_code = ?, city = ? WHERE ip = ? AND country IS NULL', [$country, $code, $city, $ip]);
        Db::exec('UPDATE visits SET country = ?, country_code = ?, city = ? WHERE ip = ? AND country IS NULL AND created_at > ?', [$country, $code, $city, $ip, $dayAgo]);
        Db::exec('UPDATE clicks SET country = ?, country_code = ? WHERE ip = ? AND country IS NULL AND created_at > ?', [$country, $code, $ip, $dayAgo]);
    }

    // ------------------------------------------------------------------ SEO

    public static function seo_health(): array
    {
        $ar = fn(string $col) => "COALESCE(JSON_UNQUOTE(JSON_EXTRACT($col, '$.ar')), '')";
        $en = fn(string $col) => "COALESCE(JSON_UNQUOTE(JSON_EXTRACT($col, '$.en')), '')";
        $slugs = fn(string $sql) => array_column(Db::select($sql), 'slug');
        return [
            'pages_total'       => (int)Db::value("SELECT COUNT(*) FROM pages WHERE status = 'published'"),
            'missing_description' => $slugs("SELECT slug FROM pages WHERE status = 'published' AND ({$ar('meta_description')} = '' OR {$en('meta_description')} = '')"),
            'missing_title'     => $slugs("SELECT slug FROM pages WHERE status = 'published' AND {$ar('meta_title')} = '' AND {$ar('title')} = ''"),
            'missing_keywords'  => $slugs("SELECT slug FROM pages WHERE status = 'published' AND ({$ar('meta_keywords')} = '' OR {$en('meta_keywords')} = '')"),
            'noindex'           => $slugs('SELECT slug FROM pages WHERE noindex = 1'),
            'services_missing_description' => $slugs("SELECT slug FROM services WHERE status = 'published' AND {$ar('meta_description')} = ''"),
            'projects_missing_cover' => $slugs("SELECT slug FROM projects WHERE status = 'published' AND COALESCE(cover_image, '') = ''"),
            'media_missing_alt' => (int)Db::value("SELECT COUNT(*) FROM media WHERE {$ar('alt')} = '' AND {$en('alt')} = ''"),
            'tasks_open'        => (int)Db::value("SELECT COUNT(*) FROM seo_tasks WHERE status <> 'done'"),
            'tasks_overdue'     => (int)Db::value("SELECT COUNT(*) FROM seo_tasks WHERE status <> 'done' AND due_date IS NOT NULL AND due_date < CURDATE()"),
        ];
    }

    // ------------------------------------------------------------------ email marketing

    public static function mail_campaign_stats(string $campaignId): array
    {
        $q = Db::selectOne(
            "SELECT COUNT(*) total, SUM(status = 'queued') queued, SUM(status = 'sent') sent, SUM(status = 'failed') failed, SUM(status = 'skipped') skipped,
                    SUM(opened_at IS NOT NULL) opened, SUM(clicked_at IS NOT NULL) clicked
             FROM mail_queue WHERE campaign_id = ?", [$campaignId]
        ) ?? [];
        $e = Db::selectOne("SELECT SUM(event = 'unsubscribe') unsubscribed, SUM(event = 'bounce') bounced FROM mail_events WHERE campaign_id = ?", [$campaignId]) ?? [];
        $out = [];
        foreach (['total', 'queued', 'sent', 'failed', 'skipped', 'opened', 'clicked'] as $k) $out[$k] = (int)($q[$k] ?? 0);
        $out['unsubscribed'] = (int)($e['unsubscribed'] ?? 0);
        $out['bounced'] = (int)($e['bounced'] ?? 0);
        return $out;
    }

    public static function mail_sender_usage(string $senderId): array
    {
        $r = Db::selectOne(
            "SELECT SUM(sent_at > ?) h, COUNT(*) d FROM mail_queue WHERE sender_id = ? AND status = 'sent' AND sent_at > ?",
            [gmdate('Y-m-d H:i:s', time() - 3600), $senderId, gmdate('Y-m-d H:i:s', time() - 86400)]
        ) ?? [];
        return ['hour' => (int)($r['h'] ?? 0), 'day' => (int)($r['d'] ?? 0)];
    }

    public static function mail_list_summary(string $listId): array
    {
        $r = Db::selectOne(
            "SELECT COUNT(*) total, SUM(status = 'valid') valid, SUM(status = 'risky') risky, SUM(status = 'invalid') invalid, SUM(status = 'pending') pending,
                    SUM(status = 'unsubscribed') unsubscribed, SUM(status = 'bounced') bounced, COALESCE(ROUND(AVG(score)), 0) avg_score
             FROM mail_contacts WHERE list_id = ?", [$listId]
        ) ?? [];
        $out = [];
        foreach (['total', 'valid', 'risky', 'invalid', 'pending', 'unsubscribed', 'bounced', 'avg_score'] as $k) $out[$k] = (int)($r[$k] ?? 0);
        return $out;
    }

    /**
     * Build the send queue of a campaign: one row per distinct e-mail across the chosen lists,
     * valid contacts preferred, suppressed addresses excluded, senders assigned round-robin.
     */
    public static function mail_build_queue(string $campaignId, array $listIds, bool $skipRisky, array $senderIds): int
    {
        $listIds = array_values(array_filter($listIds, 'is_string'));
        $senderIds = array_values(array_filter($senderIds, 'is_string'));
        if ($listIds === []) return 0;
        $statuses = $skipRisky ? ['valid', 'pending'] : ['valid', 'pending', 'risky'];
        $sql = 'SELECT c.id, c.email, c.name, c.status FROM mail_contacts c
                WHERE c.list_id IN (' . implode(',', array_fill(0, count($listIds), '?')) . ')
                  AND c.status IN (' . implode(',', array_fill(0, count($statuses), '?')) . ')
                  AND LOWER(c.email) NOT IN (SELECT LOWER(email) FROM mail_suppressions)
                ORDER BY LOWER(c.email), (c.status = \'valid\') DESC';
        $seen = [];
        $rows = [];
        foreach (Db::select($sql, array_merge($listIds, $statuses)) as $c) {
            $key = mb_strtolower((string)$c['email']);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $rows[] = $c;
        }
        usort($rows, fn($a, $b) => strcmp((string)$a['email'], (string)$b['email']));
        $n = 0;
        $senders = count($senderIds);
        Db::transaction(function () use ($rows, $campaignId, $senderIds, $senders, &$n) {
            foreach (array_chunk($rows, 200) as $chunk) {
                $values = [];
                $params = [];
                foreach ($chunk as $i => $c) {
                    $values[] = '(?, ?, ?, ?, ?, ?, ?)';
                    array_push($params, Db::uuid(), $campaignId, $c['id'], $senders ? $senderIds[$n % $senders] : null, $c['email'], $c['name'], bin2hex(random_bytes(12)));
                    $n++;
                }
                Db::exec('INSERT IGNORE INTO mail_queue (id, campaign_id, contact_id, sender_id, email, name, token) VALUES ' . implode(',', $values), $params);
            }
        });
        return $n;
    }

    // ------------------------------------------------------------------ helpers

    private static function s(array $p, string $key, int $max): ?string
    {
        $v = $p[$key] ?? null;
        if ($v === null || $v === '') return null;
        return mb_substr((string)$v, 0, $max);
    }
}
