<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public readonly string $method;
    public string $path;               // path without language prefix
    public readonly string $fullPath;  // original request path
    private ?array $json = null;
    public array $params = [];

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $p = '/' . trim(rawurldecode(current_path()), '/');
        $this->fullPath = $p;
        $this->path = $p;
    }

    public function isPost(): bool { return $this->method === 'POST'; }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /** POST field, JSON body field or query value */
    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_POST)) return $_POST[$key];
        $json = $this->json();
        if (array_key_exists($key, $json)) return $json[$key];
        return $_GET[$key] ?? $default;
    }

    /** Trimmed string input */
    public function str(string $key, string $default = ''): string
    {
        $v = $this->input($key, $default);
        return is_scalar($v) ? trim((string)$v) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->input($key);
        return is_numeric($v) ? (int)$v : $default;
    }

    public function bool(string $key): bool
    {
        $v = $this->input($key, false);
        return filter_var($v, FILTER_VALIDATE_BOOL) ?? false;
    }

    /** Bilingual field posted as name[ar] / name[en] */
    public function i18n(string $key): array
    {
        $v = $this->input($key, []);
        $v = is_array($v) ? $v : ['ar' => (string)$v, 'en' => ''];
        return ['ar' => trim((string)($v['ar'] ?? '')), 'en' => trim((string)($v['en'] ?? ''))];
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST, $this->json());
    }

    public function json(): array
    {
        if ($this->json !== null) return $this->json;
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (!str_contains($ct, 'json')) {
            return $this->json = [];
        }
        $raw = file_get_contents('php://input');
        $data = json_decode((string)$raw, true);
        return $this->json = is_array($data) ? $data : [];
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($_SERVER[$key]) ? (string)$_SERVER[$key] : null;
    }

    public function wantsJson(): bool
    {
        return str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            || ($this->header('x-requested-with') === 'XMLHttpRequest')
            || str_contains((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'json');
    }

    public function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR'] as $h) {
            if (!empty($_SERVER[$h])) {
                $ip = trim(explode(',', (string)$_SERVER[$h])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function userAgent(): string
    {
        return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
    }

    public function referer(): string
    {
        return substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 500);
    }

    public function file(string $key): ?array
    {
        $f = $_FILES[$key] ?? null;
        if (!$f || !isset($f['tmp_name']) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        return $f;
    }

    public function url(): string
    {
        return base_url() . ($_SERVER['REQUEST_URI'] ?? '/');
    }
}
