<?php
declare(strict_types=1);

namespace App\Support;

final class Url
{
    public static function to(string $path = '/'): string
    {
        $base = rtrim(Env::get('APP_URL', 'http://localhost') ?: 'http://localhost', '/');
        $basePath = rtrim(Env::get('APP_BASE_PATH', '') ?: '', '/');
        if ($path === '') {
            $path = '/';
        }
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        return $base . $basePath . $path;
    }

    public static function asset(string $path): string
    {
        return self::to('/assets/' . ltrim($path, '/'));
    }
}
