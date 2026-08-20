<?php
declare(strict_types=1);

namespace App\Support;

final class Logger
{
    public static function log(string $level, string $message, array $context = []): void
    {
        $dir = ZBIF_ROOT . '/storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $line = json_encode([
            'ts' => date('c'),
            'level' => $level,
            'request_id' => RequestId::get(),
            'message' => $message,
            'context' => self::redact($context),
        ], JSON_UNESCAPED_SLASHES);
        file_put_contents($dir . '/app.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /** @param array<string, mixed> $context */
    private static function redact(array $context): array
    {
        $keys = ['password', 'token', 'api_key', 'secret', 'authorization'];
        foreach ($context as $k => $v) {
            foreach ($keys as $secret) {
                if (stripos((string) $k, $secret) !== false) {
                    $context[$k] = '[redacted]';
                }
            }
        }
        return $context;
    }
}
