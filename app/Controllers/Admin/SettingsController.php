<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\Settings;

final class SettingsController extends AdminController
{
    private const I18N = ['site_name', 'tagline', 'header_cta_text', 'footer_text', 'whatsapp_message', 'address', 'working_hours', 'meta_title_suffix', 'default_meta_description', 'default_meta_keywords'];
    private const BOOL = ['custom_font_body', 'enable_english', 'show_whatsapp_button', 'maintenance_mode', 'analytics_enabled', 'analytics_track_bots', 'analytics_track_admins', 'analytics_geo', 'mail_smtp_probe'];
    private const INT = ['analytics_retention_days', 'smtp_port'];
    private const IMAGE = ['logo', 'favicon', 'og_image'];
    private const RAW = ['robots_txt', 'head_scripts'];

    public function edit(Request $r, string $group = 'general'): string
    {
        $groups = Settings::groups();
        if (!isset($groups[$group])) Response::notFound();
        return $this->render('admin/settings/edit', [
            'title' => t('settings.title') . ' — ' . t('settings.' . $group),
            'group' => $group,
            'groups' => array_keys($groups),
            'keys' => $groups[$group],
            'values' => Settings::all(),
        ]);
    }

    public function update(Request $r, string $group): void
    {
        $groups = Settings::groups();
        if (!isset($groups[$group])) Response::notFound();
        $values = [];
        foreach ($groups[$group] as $key) {
            // keys absent from the request keep their value (allows partial forms such as the dashboard theme switcher)
            if ($r->input($key) === null && !in_array($key, self::BOOL, true)) {
                continue;
            }
            if ($key === 'theme') {
                $values[$key] = isset(Settings::themes()[$r->str($key)]) ? $r->str($key) : 'ink';
            } elseif ($key === 'accent_color') {
                $v = strtoupper(trim($r->str($key)));
                $values[$key] = preg_match('/^#[0-9A-F]{6}$/', $v) ? $v : '';
            } elseif ($key === 'heading_font') {
                $values[$key] = in_array($r->str($key), ['sans', 'serif', 'custom'], true) ? $r->str($key) : 'serif';
            } elseif ($key === 'custom_font_regular' || $key === 'custom_font_bold') {
                continue; // managed by uploadFont() / removeFont()
            } elseif ($key === 'custom_font_name') {
                $values[$key] = mb_substr(trim(preg_replace('/[^\p{L}\p{N} \-]/u', '', $r->str($key))), 0, 40) ?: 'Custom';
            } elseif ($key === 'radius') {
                $values[$key] = $r->str($key) === 'sharp' ? 'sharp' : 'soft';
            } elseif (in_array($key, self::I18N, true)) {
                $values[$key] = array_map(fn($s) => mb_substr(strip_tags($s), 0, 1000), $r->i18n($key));
            } elseif (in_array($key, self::BOOL, true)) {
                $values[$key] = $r->bool($key);
            } elseif (in_array($key, self::INT, true)) {
                $values[$key] = $r->int($key, (int)Settings::defaults()[$key]);
            } elseif (in_array($key, self::IMAGE, true)) {
                $values[$key] = $this->imagePath($r->input($key));
            } elseif (in_array($key, self::RAW, true)) {
                $values[$key] = mb_substr((string)$r->input($key, ''), 0, 20000);
            } elseif ($key === 'smtp_pass') {
                $v = (string)$r->input($key, '');
                $values[$key] = $v === '' ? (string)Settings::get('smtp_pass', '') : mb_substr($v, 0, 300);
            } elseif ($key === 'whatsapp_number') {
                $values[$key] = preg_replace('/\D+/', '', $r->str($key));
            } elseif ($key === 'default_lang') {
                $values[$key] = $r->str($key) === 'en' ? 'en' : 'ar';
            } elseif ($key === 'smtp_secure') {
                $values[$key] = in_array($r->str($key), ['tls', 'ssl', 'none'], true) ? $r->str($key) : 'tls';
            } elseif (str_starts_with($key, 'social_') || in_array($key, ['map_link', 'header_cta_link'], true)) {
                $v = $r->str($key);
                $values[$key] = ($v === '' || preg_match('#^(https?://|/)#i', $v)) ? mb_substr($v, 0, 500) : '';
            } else {
                $values[$key] = mb_substr(strip_tags($r->str($key)), 0, 500);
            }
        }
        if ($group === 'general' && ($values['default_lang'] ?? '') === 'en' && empty($values['enable_english'])) {
            $values['enable_english'] = true;
        }
        if (isset($values['analytics_retention_days'])) $values['analytics_retention_days'] = max(7, min(3650, (int)$values['analytics_retention_days']));
        Settings::save($values, $group);
        $this->log('settings', 'settings', $group);
        \App\Core\Session::flash('success', t('settings.saved'));
        Response::back(admin_url('settings/' . $group));
    }
    /** Upload a custom font file (woff2 / woff / ttf / otf) into one of the two slots */
    public function uploadFont(Request $r): void
    {
        $slot = $r->str('slot') === 'bold' ? 'custom_font_bold' : 'custom_font_regular';
        $file = $_FILES['font'] ?? null;
        $path = $file ? self::storeFont($file) : '';
        if ($path === '') $this->fail(t('settings.font_invalid'), admin_url('settings/appearance'));
        $old = (string)Settings::get($slot, '');
        Settings::save([$slot => $path], 'appearance');
        self::unlinkFont($old);
        $this->log('font', 'settings', $slot, ['file' => $path]);
        $this->ok(t('settings.font_uploaded'), admin_url('settings/appearance'));
    }

    public function removeFont(Request $r): void
    {
        $slot = $r->str('slot') === 'bold' ? 'custom_font_bold' : 'custom_font_regular';
        $old = (string)Settings::get($slot, '');
        $values = [$slot => ''];
        if ($slot === 'custom_font_regular' && Settings::get('heading_font') === 'custom') $values['heading_font'] = 'serif';
        Settings::save($values, 'appearance');
        self::unlinkFont($old);
        $this->log('font', 'settings', $slot, ['removed' => $old]);
        $this->ok(t('settings.font_removed'), admin_url('settings/appearance'));
    }

    /** Validates by magic bytes and stores under public/uploads/fonts. Returns the public path or '' */
    private static function storeFont(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 5 * 1024 * 1024) return '';
        $tmp = (string)$file['tmp_name'];
        if (!is_uploaded_file($tmp)) return '';
        $head = (string)file_get_contents($tmp, false, null, 0, 4);
        $ext = match (true) {
            $head === 'wOF2' => 'woff2',
            $head === 'wOFF' => 'woff',
            $head === 'OTTO' => 'otf',
            $head === "\x00\x01\x00\x00" || $head === 'true' => 'ttf',
            default => '',
        };
        if ($ext === '') return '';
        $dir = UPLOADS_PATH . '/fonts';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) return '';
        $name = 'font-' . substr(hash_file('sha256', $tmp), 0, 12) . '.' . $ext;
        if (!move_uploaded_file($tmp, $dir . '/' . $name)) return '';
        return '/uploads/fonts/' . $name;
    }

    private static function unlinkFont(string $path): void
    {
        if ($path !== '' && preg_match('#^/uploads/fonts/font-[a-f0-9]{12}\.(woff2|woff|ttf|otf)$#', $path)) {
            $other = [(string)Settings::get('custom_font_regular', ''), (string)Settings::get('custom_font_bold', '')];
            if (!in_array($path, $other, true)) @unlink(PUBLIC_PATH . $path);
        }
    }
}
