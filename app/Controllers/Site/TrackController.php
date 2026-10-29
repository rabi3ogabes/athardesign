<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Analytics;
use App\Core\I18n;
use App\Core\Log;
use App\Core\Request;
use App\Core\Response;

/** Receives click beacons from the public site (navigator.sendBeacon). */
final class TrackController
{
    public function click(Request $request): void
    {
        header('Cache-Control: no-store');
        $body = $request->json();
        if (!$body) {
            $raw = (string)file_get_contents('php://input');
            $body = json_decode($raw, true) ?: [];
        }
        $event = preg_replace('/[^a-z_]/', '', strtolower((string)($body['event'] ?? '')));
        if ($event === '') {
            Response::json(['ok' => false], 400);
        }
        // Same-origin check (Origin/Referer must match the site)
        $origin = (string)($request->header('origin') ?: $request->referer());
        $host = (string)parse_url(base_url(), PHP_URL_HOST);
        if ($origin !== '' && $host !== '' && !str_contains($origin, $host)) {
            Response::json(['ok' => false], 403);
        }
        try {
            Analytics::click(
                $request,
                $event,
                (string)($body['target'] ?? ''),
                (string)($body['label'] ?? ''),
                (string)($body['path'] ?? $request->referer()),
                in_array($body['lang'] ?? '', I18n::LANGS, true) ? $body['lang'] : I18n::lang()
            );
        } catch (\Throwable $e) {
            Log::error('Click track failed: ' . $e->getMessage());
        }
        Response::json(['ok' => true]);
    }
}
