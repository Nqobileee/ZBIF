<?php
declare(strict_types=1);

namespace App\Support;

final class I18n
{
    private static string $locale = 'en';
    /** @var array<string, string> */
    private static array $lines = [];

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function boot(?string $locale = null): void
    {
        self::$locale = $locale ?: Env::get('APP_LOCALE', 'en') ?: 'en';
        $path = ZBIF_ROOT . '/lang/' . self::$locale . '.php';
        if (is_file($path)) {
            $lines = require $path;
            self::$lines = is_array($lines) ? $lines : [];
        }
    }

    public static function get(string $key, ?string $default = null): string
    {
        if (self::$lines === []) {
            self::boot();
        }
        return self::$lines[$key] ?? $default ?? $key;
    }
}

function __(string $key, ?string $default = null): string
{
    return I18n::get($key, $default);
}
