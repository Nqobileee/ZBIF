<?php
declare(strict_types=1);

namespace App\Support;

/** Lightweight security headers for shared LAMP responses. */
final class SecurityHeaders
{
    public static function apply(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        if (
            Env::get('APP_ENV') === 'production'
            && (
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
                || Env::get('APP_FORCE_HTTPS', 'false') === 'true'
            )
        ) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
