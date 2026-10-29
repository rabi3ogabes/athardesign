<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Mail;
use App\Core\Request;
use App\Models\Settings;
use App\Models\Visitors;

final class ToolsController extends AdminController
{
    public function clearCache(Request $r): void
    {
        Cache::flush();
        $this->log('cache_clear');
        $this->ok(t('settings.cache_cleared'), admin_url('settings/general'));
    }

    public function prune(Request $r): void
    {
        $days = (int)Settings::get('analytics_retention_days', 365);
        $res = Visitors::prune($days);
        $this->log('prune_analytics', '', '', $res);
        $this->ok(t('settings.pruned', ['v' => (int)($res['visits'] ?? 0), 'c' => (int)($res['clicks'] ?? 0)]), admin_url('settings/analytics'));
    }

    public function testMail(Request $r): void
    {
        $to = Auth::user()['email'];
        $ok = Mail::send($to, 'Athar — test email', '<p>This is a test email from your website admin panel. SMTP works.</p>');
        if ($ok) $this->ok(t('settings.test_mail_sent', ['email' => $to]), admin_url('settings/mail'));
        $this->fail(t('settings.test_mail_failed'), admin_url('settings/mail'));
    }
}
