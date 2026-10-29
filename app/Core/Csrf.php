<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token) || strlen($token) < 32) {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf', $token);
        }
        return $token;
    }

    public static function check(?string $given): bool
    {
        $token = Session::get('_csrf');
        return is_string($token) && is_string($given) && hash_equals($token, $given);
    }

    /** Validates the token from POST body or X-CSRF-Token header, aborts with 419 otherwise. */
    public static function verify(Request $request): void
    {
        $given = $request->input('_token') ?? $request->header('x-csrf-token');
        if (!self::check(is_string($given) ? $given : null)) {
            if ($request->wantsJson()) {
                Response::json(['ok' => false, 'error' => 'Session expired. Reload the page and try again.'], 419);
            }
            Response::abort(419, 'Your session expired. Go back, reload the page and try again.');
        }
    }
}
