<?php
declare(strict_types=1);

namespace App\Rbac;

use App\Auth\Auth;
use App\Support\Database;

final class Gate
{
    /** @var array<int, list<string>> */
    private static array $cache = [];

    public static function allows(string $permission, ?int $userId = null): bool
    {
        $userId = $userId ?? Auth::id();
        if ($userId === null) {
            return false;
        }
        $perms = self::permissionsFor($userId);
        return in_array($permission, $perms, true) || in_array('*', $perms, true);
    }

    public static function authorize(string $permission): void
    {
        if (Auth::id() === null) {
            \App\Support\Response::redirect('/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/app'));
        }
        if (!self::allows($permission)) {
            http_response_code(403);
            \App\Support\View::make('public/403', ['title' => 'Forbidden'], 'layouts/public');
            exit;
        }
    }

    /** @return list<string> */
    public static function permissionsFor(int $userId): array
    {
        if (isset(self::$cache[$userId])) {
            return self::$cache[$userId];
        }
        $rows = Database::fetchAll(
            'SELECT DISTINCT p.name
             FROM permissions p
             INNER JOIN role_permission rp ON rp.permission_id = p.id
             INNER JOIN model_has_roles mhr ON mhr.role_id = rp.role_id
             WHERE mhr.user_id = ?',
            [$userId]
        );
        $perms = array_column($rows, 'name');
        self::$cache[$userId] = $perms;
        return $perms;
    }

    /** @return list<string> */
    public static function rolesFor(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT r.slug FROM roles r
             INNER JOIN model_has_roles mhr ON mhr.role_id = r.id
             WHERE mhr.user_id = ?',
            [$userId]
        );
        return array_column($rows, 'slug');
    }

    public static function assignRole(int $userId, string $roleSlug): void
    {
        $role = Database::fetch('SELECT id FROM roles WHERE slug = ?', [$roleSlug]);
        if (!$role) {
            return;
        }
        $exists = Database::fetch(
            'SELECT id FROM model_has_roles WHERE user_id = ? AND role_id = ?',
            [$userId, $role['id']]
        );
        if (!$exists) {
            Database::query(
                'INSERT INTO model_has_roles (user_id, role_id, created_at) VALUES (?, ?, NOW())',
                [$userId, $role['id']]
            );
            unset(self::$cache[$userId]);
        }
    }
}
