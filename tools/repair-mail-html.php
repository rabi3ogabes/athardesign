<?php
declare(strict_types=1);

/**
 * Repairs mail campaigns and templates whose HTML was saved fully entity-escaped
 * (the editor used to read template markup as "&lt;html&gt;" before 2026-11-02).
 * Re-runnable: rows that already hold real markup are left alone.
 *
 * Run: php tools/repair-mail-html.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Cache;
use App\Core\Db;
use App\Models\Mail;

$fixed = 0;
foreach (['mail_campaigns', 'mail_templates'] as $table) {
    foreach (Db::from($table)->select('id,name,html')->get() as $row) {
        $html = (string)($row['html'] ?? '');
        $clean = Mail::unescapeHtml($html);
        if ($clean !== $html) {
            Db::from($table)->eq('id', $row['id'])->update(['html' => $clean]);
            echo "$table: repaired \"{$row['name']}\"\n";
            $fixed++;
        }
    }
}
Cache::flush();
echo "mail html: $fixed row(s) repaired\n";
