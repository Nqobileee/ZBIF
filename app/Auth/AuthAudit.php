<?php
declare(strict_types=1);

namespace App\Auth;

use App\Domain\AuditLog;

final class AuthAudit
{
    public static function record(string $event, ?int $userId = null, array $meta = []): void
    {
        $meta['user_agent'] = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);
        AuditLog::record($event, 'user', $userId, $meta, $userId);
    }
}
