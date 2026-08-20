<?php
declare(strict_types=1);

namespace App\Domain;

use App\Support\Database;

final class AuditLog
{
    public static function record(string $action, ?string $entityType = null, ?int $entityId = null, array $meta = [], ?int $actorId = null): void
    {
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ($meta['user_agent'] ?? '')), 0, 250);
        unset($meta['user_agent']);
        try {
            Database::query(
                'INSERT INTO audit_logs (actor_id, action, entity_type, entity_id, meta_json, ip, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
                [
                    $actorId ?? \App\Auth\Auth::id(),
                    $action,
                    $entityType,
                    $entityId,
                    $meta ? json_encode($meta) : null,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $ua ?: null,
                ]
            );
        } catch (\Throwable $e) {
            Database::query(
                'INSERT INTO audit_logs (actor_id, action, entity_type, entity_id, meta_json, ip, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())',
                [
                    $actorId ?? \App\Auth\Auth::id(),
                    $action,
                    $entityType,
                    $entityId,
                    $meta ? json_encode($meta) : null,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                ]
            );
        }
    }
}
