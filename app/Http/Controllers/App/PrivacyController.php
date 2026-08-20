<?php
declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Auth\Auth;
use App\Domain\AuditLog;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;

final class PrivacyController
{
    public function export(): void
    {
        $user = Auth::requireLogin();
        $payload = [
            'user' => $user,
            'consents' => Database::fetchAll('SELECT * FROM consents WHERE user_id = ?', [$user['id']]),
            'profiles' => Database::fetchAll('SELECT * FROM participation_profiles WHERE user_id = ?', [$user['id']]),
            'notifications' => Database::fetchAll('SELECT id, title, body, created_at FROM notifications WHERE user_id = ?', [$user['id']]),
        ];
        unset($payload['user']['password']);
        AuditLog::record('privacy.export', 'user', (int) $user['id']);
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="zbif-data-export.json"');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function deleteRequest(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid();
        Database::query('UPDATE users SET deleted_at = NOW(), is_active = 0, updated_at = NOW() WHERE id = ?', [$user['id']]);
        AuditLog::record('privacy.delete_request', 'user', (int) $user['id']);
        Auth::logout();
        Response::flash('success', 'Deletion request processed. Your account has been deactivated.');
        Response::redirect('/');
    }
}
