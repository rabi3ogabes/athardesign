<?php
declare(strict_types=1);

/**
 * Campaign worker. Add to cron every minute:
 *   * * * * * php /path/to/athar/tools/mail-worker.php >> /path/to/athar/storage/logs/mail-worker.log 2>&1
 * Hosts without shell cron can call the URL printed by `php tools/mail-worker.php --url` instead.
 */
require dirname(__DIR__) . '/app/bootstrap.php';

if (in_array('--url', $argv ?? [], true)) {
    echo base_url() . '/cron/mail/' . substr(hash('sha256', 'mail-cron|' . (string)env('APP_SECRET', '')), 0, 32) . PHP_EOL;
    exit;
}
$lock = STORAGE_PATH . '/cache/mail-worker.lock';
$fp = fopen($lock, 'c');
if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) { echo "[" . date('c') . "] another worker is running\n"; exit; }
$summary = App\Core\CampaignRunner::tick(120);
echo '[' . date('c') . '] ' . json_encode($summary) . PHP_EOL;
flock($fp, LOCK_UN);
