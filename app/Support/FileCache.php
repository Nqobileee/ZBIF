<?php
declare(strict_types=1);

namespace App\Support;

/** File-based cache for shared hosts (no Redis). */
final class FileCache
{
    private static function dir(): string
    {
        $dir = ZBIF_ROOT . '/storage/cache';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private static function path(string $key): string
    {
        return self::dir() . '/' . hash('sha256', $key) . '.cache';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $path = self::path($key);
        if (!is_file($path)) {
            return $default;
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return $default;
        }
        $payload = @unserialize($raw);
        if (!is_array($payload) || !isset($payload['expires'], $payload['value'])) {
            return $default;
        }
        if ((int) $payload['expires'] < time()) {
            @unlink($path);
            return $default;
        }
        return $payload['value'];
    }

    public static function put(string $key, mixed $value, int $ttlSeconds = 300): void
    {
        $payload = serialize(['expires' => time() + max(1, $ttlSeconds), 'value' => $value]);
        file_put_contents(self::path($key), $payload, LOCK_EX);
    }

    public static function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        $hit = self::get($key, '__miss__');
        if ($hit !== '__miss__') {
            return $hit;
        }
        $value = $callback();
        self::put($key, $value, $ttlSeconds);
        return $value;
    }

    public static function forget(string $key): void
    {
        $path = self::path($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function flush(): void
    {
        foreach (glob(self::dir() . '/*.cache') ?: [] as $f) {
            @unlink($f);
        }
    }
}
