<?php
declare(strict_types=1);

use App\Core\I18n;
use App\Core\Session;
use App\Core\Csrf;
use App\Models\Settings;

/** Read an environment variable loaded from .env */
function env(string $key, mixed $default = null): mixed
{
    $v = $_ENV[$key] ?? getenv($key);
    if ($v === false || $v === null || $v === '') {
        return $default;
    }
    return $v;
}

/** HTML-escape */
function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Translate an interface string from app/lang/{lang}.php */
function t(string $key, array $params = [], ?string $lang = null): string
{
    return I18n::t($key, $params, $lang);
}

/** Pick the current-language value from a bilingual {"ar":..,"en":..} field */
function tr(mixed $field, ?string $lang = null): string
{
    return I18n::tr($field, $lang);
}

/** Current public language code */
function lang(): string
{
    return I18n::lang();
}

/** Build an absolute URL for the current language (or a given one) */
function url(string $path = '/', ?string $lang = null): string
{
    return I18n::url($path, $lang);
}

/** Admin panel URL */
function admin_url(string $path = ''): string
{
    $base = rtrim(env('ADMIN_PATH', '/admin'), '/');
    return base_url() . $base . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

/** Site base URL without trailing slash */
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $configured = trim((string)env('APP_URL', ''));
    if ($configured !== '') {
        return $base = rtrim($configured, '/');
    }
    return $base = detected_url();
}

/** Scheme + host of the current request (what the visitor typed), ignoring APP_URL */
function detected_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['HTTP_CF_VISITOR'] ?? '') !== '' && str_contains((string)$_SERVER['HTTP_CF_VISITOR'], 'https'));
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = preg_replace('/[^a-zA-Z0-9.\-:\[\]]/', '', (string)$host);
    return ($https ? 'https' : 'http') . '://' . $host;
}

/** Versioned asset URL */
function asset(string $path): string
{
    $path = '/' . ltrim($path, '/');
    $file = PUBLIC_PATH . str_replace('/', DIRECTORY_SEPARATOR, $path);
    $v = is_file($file) ? substr(md5((string)filemtime($file)), 0, 8) : '1';
    return base_url() . $path . '?v=' . $v;
}

/** Absolute URL for an uploaded/static image path or an external URL */
function media_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(https?:)?//#i', $path) || str_starts_with($path, 'data:')) {
        return $path;
    }
    return base_url() . '/' . ltrim($path, '/');
}

/** Inline an SVG icon from public/assets/icons */
function icon(string $name, string $class = '', ?string $title = null): string
{
    static $cache = [];
    $name = preg_replace('/[^a-z0-9\-]/', '', strtolower($name));
    if (!isset($cache[$name])) {
        $file = PUBLIC_PATH . '/assets/icons/' . $name . '.svg';
        $cache[$name] = is_file($file) ? (string)file_get_contents($file) : '';
    }
    $svg = $cache[$name];
    if ($svg === '') {
        return '';
    }
    $svg = preg_replace('/<title>.*?<\/title>/s', '', $svg);
    $attrs = ' aria-hidden="true" focusable="false" class="icon ' . e($class) . '"';
    if ($title !== null) {
        $attrs = ' role="img" aria-label="' . e($title) . '" focusable="false" class="icon ' . e($class) . '"';
    }
    return (string)preg_replace('/<svg\b/', '<svg' . $attrs, $svg, 1);
}

/** Read a site setting (cached) */
function setting(string $key, mixed $default = ''): mixed
{
    return Settings::get($key, $default);
}

/** Bilingual setting value in the current language */
function setting_tr(string $key, mixed $default = ''): string
{
    $v = Settings::get($key, null);
    if ($v === null) {
        return (string)$default;
    }
    return is_array($v) ? tr($v) : (string)$v;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function csrf_token(): string
{
    return Csrf::token();
}

/** Old form input after a validation redirect */
function old(string $key, mixed $default = ''): mixed
{
    $old = Session::get('_old', []);
    return array_key_exists($key, $old) ? $old[$key] : $default;
}

/** Nested value from array using dot notation */
function data_get(mixed $array, string $key, mixed $default = null): mixed
{
    if (!is_array($array)) {
        return $default;
    }
    foreach (explode('.', $key) as $seg) {
        if (is_array($array) && array_key_exists($seg, $array)) {
            $array = $array[$seg];
        } else {
            return $default;
        }
    }
    return $array;
}

/** URL-safe slug that keeps Arabic letters */
function slugify(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text); // tashkeel
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
    $text = trim((string)$text, '-');
    return $text === '' ? 'page-' . substr(bin2hex(random_bytes(3)), 0, 5) : $text;
}

/** Format a date for the dashboard */
function fmt_date(?string $iso, string $format = 'd M Y, H:i'): string
{
    if (!$iso) {
        return '';
    }
    try {
        $d = new DateTime($iso);
        $d->setTimezone(new DateTimeZone(env('APP_TIMEZONE', 'Asia/Qatar')));
        return $d->format($format);
    } catch (Throwable) {
        return (string)$iso;
    }
}

/** Human "x minutes ago" */
function time_ago(?string $iso): string
{
    if (!$iso) {
        return '';
    }
    $ts = strtotime($iso);
    if ($ts === false) {
        return (string)$iso;
    }
    $diff = time() - $ts;
    if ($diff < 60) return t('time.just_now');
    if ($diff < 3600) return t('time.minutes_ago', ['n' => intdiv($diff, 60)]);
    if ($diff < 86400) return t('time.hours_ago', ['n' => intdiv($diff, 3600)]);
    if ($diff < 86400 * 30) return t('time.days_ago', ['n' => intdiv($diff, 86400)]);
    return fmt_date($iso, 'd M Y');
}

/** Compact number formatting (1.2K) */
function fmt_num(int|float $n): string
{
    if ($n >= 1000000) return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'M';
    if ($n >= 10000) return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
    return number_format($n);
}

/** Truncate text */
function str_limit(string $text, int $limit = 160, string $end = '…'): string
{
    $text = trim(strip_tags($text));
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $limit)) . $end;
}

/** Build a WhatsApp link from a number + optional message */
function whatsapp_link(?string $number = null, ?string $message = null): string
{
    $number = preg_replace('/\D+/', '', (string)($number ?? setting('whatsapp_number', '')));
    if ($number === '') {
        return '';
    }
    $url = 'https://wa.me/' . $number;
    $message = $message ?? setting_tr('whatsapp_message', '');
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}

/** Normalised request path without query and language prefix handled elsewhere */
function current_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = (string)parse_url($uri, PHP_URL_PATH);
    return $path === '' ? '/' : $path;
}

function is_admin_request(): bool
{
    $adminPath = rtrim(env('ADMIN_PATH', '/admin'), '/');
    $p = current_path();
    return $p === $adminPath || str_starts_with($p, $adminPath . '/');
}

/** Render a partial view and return its HTML */
function view(string $template, array $data = []): string
{
    return App\Core\View::make($template, $data);
}

/** Active-state class helper for admin nav */
function active_if(string $prefix, string $class = 'is-active'): string
{
    $p = current_path();
    $base = rtrim(env('ADMIN_PATH', '/admin'), '/');
    $target = $base . '/' . trim($prefix, '/');
    if ($prefix === '' || $prefix === '/') {
        return $p === $base || $p === $base . '/' ? $class : '';
    }
    return ($p === $target || str_starts_with($p, $target . '/')) ? $class : '';
}

/** Static JSON options list used across admin forms */
function status_options(): array
{
    return ['published' => t('common.published'), 'draft' => t('common.draft')];
}

/** Country flag emoji from ISO code (for dashboard lists) */
function flag_emoji(?string $code): string
{
    $code = strtoupper(trim((string)$code));
    if (strlen($code) !== 2 || !ctype_alpha($code)) {
        return '';
    }
    $out = '';
    foreach (str_split($code) as $ch) {
        $out .= mb_chr(0x1F1E6 + ord($ch) - ord('A'), 'UTF-8');
    }
    return $out;
}
