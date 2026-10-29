<?php
declare(strict_types=1);

namespace App\Core;

class HttpException extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message, $status);
    }
}

final class Response
{
    public static function html(string $body, int $status = 200, array $headers = []): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        foreach ($headers as $k => $v) header($k . ': ' . $v);
        echo $body;
        self::finish();
    }

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        self::finish();
    }

    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $url);
        self::finish();
    }

    public static function back(string $fallback): never
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        $to = ($ref && str_starts_with($ref, base_url())) ? $ref : $fallback;
        self::redirect($to);
    }

    public static function text(string $body, string $contentType = 'text/plain; charset=UTF-8', int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: ' . $contentType);
        echo $body;
        self::finish();
    }

    public static function abort(int $status, string $message = ''): never
    {
        throw new HttpException($status, $message);
    }

    public static function notFound(): never
    {
        throw new HttpException(404);
    }

    /** Flush output to the client then run deferred jobs (analytics) before exiting. */
    public static function finish(): never
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            if (!headers_sent()) {
                header('Connection: close');
            }
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
        }
        Analytics::flush();
        exit;
    }
}
