<?php
declare(strict_types=1);

/**
 * Exercises the dashboard, analytics and settings code paths against the configured
 * MySQL database without a browser session:  php tools/verify-live.php
 */
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Cache;
use App\Models\SeoTasks;
use App\Models\Settings;
use App\Models\Users;
use App\Models\Visitors;

$ok = true;
$line = function (string $label, bool $pass, string $note = '') use (&$ok): void {
    $ok = $ok && $pass;
    echo ($pass ? '[ OK ] ' : '[FAIL] ') . $label . ($note !== '' ? ' — ' . $note : '') . PHP_EOL;
};

$s = Db::settings();
echo 'Database: ' . $s['name'] . '@' . ($s['socket'] !== '' ? $s['socket'] : $s['host']) . PHP_EOL . PHP_EOL;

$line('Admin accounts', Users::count() >= 1, Users::count() . ' user(s)');

$from = gmdate('c', time() - 30 * 86400); $to = gmdate('c', time() + 86400);
$o = Visitors::overview($from, $to, false);
$line('dashboard_overview() RPC', isset($o['pageviews'], $o['daily']), 'pageviews=' . ($o['pageviews'] ?? '?') . ', visitors=' . ($o['visitors'] ?? '?') . ', clicks=' . ($o['clicks'] ?? '?') . ', whatsapp=' . ($o['whatsapp_clicks'] ?? '?'));
$line('Daily series', is_array($o['daily'] ?? null) && count($o['daily']) >= 30, count($o['daily'] ?? []) . ' days');
$line('Clicks by destination', is_array($o['clicks_by_event'] ?? null), json_encode(array_map(fn($c) => $c['event'] . ':' . $c['clicks'], $o['clicks_by_event'] ?? [])));
$line('Top pages', !empty($o['top_pages']), json_encode(array_column($o['top_pages'] ?? [], 'path'), JSON_UNESCAPED_SLASHES));
$line('Traffic sources', !empty($o['top_referrers']), json_encode(array_column($o['top_referrers'] ?? [], 'referrer'), JSON_UNESCAPED_SLASHES));

[$visitors, $total] = Visitors::paginate(1, 5, []);
$line('Visitors list', $total >= 1, $total . ' visitor(s)');
if ($visitors) {
    $tl = Visitors::timeline($visitors[0]['id']);
    $line('Visitor timeline RPC', is_array($tl) && count($tl) >= 1, count($tl) . ' events for the latest visitor');
}
[$clicks, $ctotal] = Visitors::clicks(1, 5, []);
$line('Clicks list', $ctotal >= 1, $ctotal . ' click(s)');
$line('Live visitors count', Visitors::liveCount(30) >= 0, Visitors::liveCount(30) . ' in the last 30 min');

$h = SeoTasks::health();
$line('seo_health() RPC', isset($h['pages_total']), 'pages=' . ($h['pages_total'] ?? '?') . ', open tasks=' . ($h['tasks_open'] ?? '?'));

// settings round trip (restored afterwards)
$before = (string)Settings::get('theme', 'ink');
Settings::save(['theme' => 'oud', 'accent_color' => '#D4AF6A'], 'appearance');
Cache::flush();
$fresh = [];
foreach (\App\Core\Db::from('settings')->select('key,value')->in('key', ['theme', 'accent_color'])->get() as $row) $fresh[$row['key']] = $row['value'];
$line('Settings save → MySQL', ($fresh['theme'] ?? '') === 'oud' && ($fresh['accent_color'] ?? '') === '#D4AF6A', json_encode($fresh));
$html = (string)@file_get_contents(rtrim((string)env('APP_URL', 'http://localhost:8080'), '/') . '/');
$line('Website renders the saved theme', str_contains($html, 'data-theme="oud"'));
Settings::save(['theme' => $before, 'accent_color' => ''], 'appearance');
Cache::flush();
$html = (string)@file_get_contents(rtrim((string)env('APP_URL', 'http://localhost:8080'), '/') . '/');
$line('Theme restored to "' . $before . '"', str_contains($html, 'data-theme="' . $before . '"'));

echo PHP_EOL . ($ok ? 'Live verification passed.' : 'Some live checks failed.') . PHP_EOL;
exit($ok ? 0 : 1);
