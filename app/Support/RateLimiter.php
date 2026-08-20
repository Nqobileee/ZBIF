<?php
declare(strict_types=1);

namespace App\Support;

final class RateLimiter
{
    public static function attempt(string $key, int $max, int $windowSeconds): bool
    {
        $bucket = date('YmdHi', (int) (floor(time() / $windowSeconds) * $windowSeconds));
        $row = Database::fetch(
            'SELECT id, hits FROM rate_limits WHERE rate_key = ? AND window_bucket = ?',
            [$key, $bucket]
        );
        if (!$row) {
            Database::query(
                'INSERT INTO rate_limits (rate_key, window_bucket, hits, created_at) VALUES (?, ?, 1, NOW())',
                [$key, $bucket]
            );
            return true;
        }
        if ((int) $row['hits'] >= $max) {
            return false;
        }
        Database::query('UPDATE rate_limits SET hits = hits + 1 WHERE id = ?', [$row['id']]);
        return true;
    }
}
