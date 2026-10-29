<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Settings;

final class I18n
{
    public const LANGS = ['ar', 'en'];
    private static string $lang = 'ar';
    private static array $dict = [];

    public static function setLang(string $lang): void
    {
        self::$lang = in_array($lang, self::LANGS, true) ? $lang : 'ar';
    }

    public static function lang(): string { return self::$lang; }

    public static function defaultLang(): string
    {
        $d = (string)Settings::get('default_lang', 'ar');
        return in_array($d, self::LANGS, true) ? $d : 'ar';
    }

    public static function englishEnabled(): bool
    {
        return (bool)Settings::get('enable_english', true);
    }

    public static function dir(?string $lang = null): string
    {
        return ($lang ?? self::$lang) === 'ar' ? 'rtl' : 'ltr';
    }

    public static function other(): string
    {
        return self::$lang === 'ar' ? 'en' : 'ar';
    }

    /**
     * Detect the language from the request path and return the path without prefix.
     * /en/services -> ['en', '/services'], /services -> [default, '/services']
     */
    public static function detect(string $path): array
    {
        $default = self::defaultLang();
        $enabledEn = self::englishEnabled();
        foreach (self::LANGS as $l) {
            if ($path === '/' . $l || str_starts_with($path, '/' . $l . '/')) {
                $rest = substr($path, strlen($l) + 1) ?: '/';
                if ($l === 'en' && !$enabledEn) {
                    return [$default, $rest, true];
                }
                return [$l, $rest, false];
            }
        }
        return [$default, $path, false];
    }

    /** Public URL with language prefix for non-default languages. */
    public static function url(string $path = '/', ?string $lang = null): string
    {
        $lang ??= self::$lang;
        $path = '/' . ltrim($path, '/');
        $default = self::defaultLang();
        $prefix = ($lang === $default) ? '' : '/' . $lang;
        $full = $prefix . ($path === '/' ? ($prefix ? '' : '/') : $path);
        if ($full === '') $full = '/';
        return base_url() . $full;
    }

    public static function t(string $key, array $params = [], ?string $lang = null): string
    {
        $lang ??= self::$lang;
        if (!isset(self::$dict[$lang])) {
            $file = APP_PATH . '/lang/' . $lang . '.php';
            self::$dict[$lang] = is_file($file) ? (array)require $file : [];
        }
        $value = data_get(self::$dict[$lang], $key);
        if ($value === null && $lang !== 'en') {
            if (!isset(self::$dict['en'])) {
                $file = APP_PATH . '/lang/en.php';
                self::$dict['en'] = is_file($file) ? (array)require $file : [];
            }
            $value = data_get(self::$dict['en'], $key);
        }
        if (!is_string($value)) {
            return $key;
        }
        if ($params) {
            // Longest keys first so :to never eats :total
            uksort($params, fn($a, $b) => strlen((string)$b) <=> strlen((string)$a));
            $map = [];
            foreach ($params as $k => $v) $map[':' . $k] = (string)$v;
            $value = strtr($value, $map);
        }
        return $value;
    }

    /** Pick the value for the current language from a bilingual field, with fallback. */
    public static function tr(mixed $field, ?string $lang = null): string
    {
        if ($field === null) return '';
        if (is_string($field)) {
            $decoded = str_starts_with($field, '{') ? json_decode($field, true) : null;
            if (!is_array($decoded)) return $field;
            $field = $decoded;
        }
        if (!is_array($field)) return (string)$field;
        $lang ??= self::$lang;
        $v = $field[$lang] ?? '';
        if (is_string($v) && trim($v) !== '') return $v;
        foreach (self::LANGS as $l) {
            if (isset($field[$l]) && is_string($field[$l]) && trim($field[$l]) !== '') return $field[$l];
        }
        return '';
    }
}
