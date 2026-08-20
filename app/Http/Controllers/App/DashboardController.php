<?php
declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Auth\Auth;
use App\Domain\EventContext;
use App\Rbac\Gate;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\View;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::requireLogin();
        $uid = (int) $user['id'];
        $roles = Gate::rolesFor($uid);
        $notifs = Database::fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 6',
            [$uid]
        );

        $focus = $this->userFocusTerms($uid);
        $challenges = $this->relevantChallenges($focus, 6);

        $stats = [
            'challenges' => count($challenges),
            'solutions' => (int) (Database::fetch('SELECT COUNT(*) AS c FROM solutions WHERE created_by = ?', [$uid])['c'] ?? 0),
            'deals' => (int) (Database::fetch(
                'SELECT COUNT(*) AS c FROM deal_rooms dr
                 INNER JOIN deal_room_participants p ON p.deal_room_id = dr.id
                 WHERE p.user_id = ?',
                [$uid]
            )['c'] ?? 0),
            'meetings' => (int) (Database::fetch(
                'SELECT COUNT(DISTINCT m.id) AS c FROM meetings m
                 LEFT JOIN meeting_participants mp ON mp.meeting_id = m.id
                 WHERE (m.organizer_id = ? OR mp.user_id = ?) AND m.status IN (\'pending\',\'accepted\')',
                [$uid, $uid]
            )['c'] ?? 0),
            'agenda' => (int) (Database::fetch('SELECT COUNT(*) AS c FROM agenda_items WHERE user_id = ?', [$uid])['c'] ?? 0),
        ];

        $isInnovator = (bool) array_intersect($roles, ['innovator', 'university', 'innovation_hub', 'student']);
        $isCorporate = (bool) array_intersect($roles, ['corporate', 'government', 'exhibitor']);

        View::make('app/dashboard', [
            'title' => 'Dashboard',
            'user' => $user,
            'roles' => $roles,
            'notifications' => $notifs,
            'challenges' => $challenges,
            'stats' => $stats,
            'focus' => $focus,
            'isInnovator' => $isInnovator,
            'isCorporate' => $isCorporate,
            'hidePageHead' => true,
        ], 'layouts/app');
    }

    /** @return list<string> */
    private function userFocusTerms(int $userId): array
    {
        $terms = [];
        $org = Database::fetch(
            'SELECT o.industry, o.type, o.description FROM organizations o
             INNER JOIN organization_user ou ON ou.organization_id = o.id
             WHERE ou.user_id = ? ORDER BY ou.is_primary DESC LIMIT 1',
            [$userId]
        );
        if ($org) {
            foreach ([$org['industry'] ?? '', $org['type'] ?? ''] as $v) {
                if ($v !== '') {
                    $terms[] = (string) $v;
                }
            }
        }
        $profile = Database::fetch(
            'SELECT profile_json FROM participation_profiles WHERE user_id = ? AND event_id = ? ORDER BY id DESC LIMIT 1',
            [$userId, EventContext::id()]
        );
        if ($profile && !empty($profile['profile_json'])) {
            $json = json_decode((string) $profile['profile_json'], true);
            if (is_array($json)) {
                foreach (['focus_areas', 'sector', 'industry'] as $key) {
                    if (empty($json[$key])) {
                        continue;
                    }
                    if (is_array($json[$key])) {
                        foreach ($json[$key] as $item) {
                            $terms[] = (string) $item;
                        }
                    } else {
                        $terms[] = (string) $json[$key];
                    }
                }
            }
        }
        $terms = array_values(array_unique(array_filter(array_map(static function ($t) {
            return trim(mb_strtolower((string) $t));
        }, $terms))));
        return $terms;
    }

    /** @param list<string> $focus */
    private function relevantChallenges(array $focus, int $limit = 6): array
    {
        $all = Database::fetchAll(
            "SELECT c.*, o.name AS org_name FROM challenges c
             INNER JOIN organizations o ON o.id = c.owner_org_id
             WHERE c.status = 'published'
             ORDER BY c.published_at DESC LIMIT 40"
        );
        if (!$focus) {
            return array_slice($all, 0, $limit);
        }
        $scored = [];
        foreach ($all as $c) {
            $hay = mb_strtolower(($c['sector'] ?? '') . ' ' . ($c['category'] ?? '') . ' ' . ($c['title'] ?? '') . ' ' . ($c['problem_statement'] ?? ''));
            $score = 0;
            foreach ($focus as $term) {
                $bits = preg_split('/[\s\/,&]+/', $term) ?: [];
                foreach ($bits as $bit) {
                    $bit = trim($bit);
                    if (mb_strlen($bit) < 3) {
                        continue;
                    }
                    if (str_contains($hay, $bit)) {
                        $score += 2;
                    }
                }
            }
            if ($score > 0) {
                $c['_score'] = $score;
                $scored[] = $c;
            }
        }
        usort($scored, static fn ($a, $b) => ($b['_score'] ?? 0) <=> ($a['_score'] ?? 0));
        if ($scored) {
            return array_slice($scored, 0, $limit);
        }
        return array_slice($all, 0, $limit);
    }

    public function styleGuide(): void
    {
        Auth::requireLogin();
        View::make('app/style-guide', ['title' => 'Style guide'], 'layouts/app');
    }

    public function profile(): void
    {
        $user = Auth::requireLogin();
        $sessions = [];
        try {
            $sessions = \App\Auth\AuthSessionStore::listForUser((int) $user['id']);
        } catch (\Throwable $e) {
            $sessions = [];
        }
        View::make('app/profile', [
            'title' => 'My profile',
            'user' => $user,
            'sessions' => $sessions,
            'currentHash' => $_SESSION['auth_session_hash'] ?? '',
        ], 'layouts/app');
    }

    public function revokeSession(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid();
        $id = (int) ($_POST['session_id'] ?? 0);
        if ($id > 0) {
            \App\Auth\AuthSessionStore::revokeById((int) $user['id'], $id);
            \App\Auth\AuthAudit::record('auth.session.revoke', (int) $user['id'], ['session_id' => $id]);
        }
        Response::flash('success', 'Session revoked.');
        Response::redirect('/app/profile');
    }

    public function profileSave(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid();
        Database::query(
            'UPDATE users SET first_name=?, last_name=?, phone=?, city=?, country=?, title=?, linkedin_url=?, website_url=?, dietary_needs=?, accessibility_needs=?, updated_at=NOW() WHERE id=?',
            [
                trim($_POST['first_name'] ?? ''),
                trim($_POST['last_name'] ?? ''),
                trim($_POST['phone'] ?? ''),
                trim($_POST['city'] ?? ''),
                trim($_POST['country'] ?? ''),
                trim($_POST['title'] ?? ''),
                trim($_POST['linkedin_url'] ?? ''),
                trim($_POST['website_url'] ?? ''),
                trim($_POST['dietary_needs'] ?? ''),
                trim($_POST['accessibility_needs'] ?? ''),
                $user['id'],
            ]
        );
        Response::flash('success', 'Profile updated.');
        Response::redirect('/app/profile');
    }

    public function notifications(): void
    {
        $user = Auth::requireLogin();
        $rows = Database::fetchAll('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 50', [$user['id']]);
        View::make('app/notifications', [
            'title' => 'Notifications',
            'notifications' => $rows,
            'hidePageHead' => true,
        ], 'layouts/app');
    }

    public function notificationsPoll(): void
    {
        $user = Auth::requireLogin();
        $rows = Database::fetchAll(
            'SELECT id, title, body, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 20',
            [$user['id']]
        );
        $unread = (int) (Database::fetch('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0', [$user['id']])['c'] ?? 0);
        Response::json(['items' => $rows, 'unread' => $unread]);
    }

    public function markNotificationRead(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            Database::query('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', [$id, $user['id']]);
        } else {
            Database::query('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$user['id']]);
        }
        Response::redirect('/app/notifications');
    }

    public function notificationPreferences(): void
    {
        $user = Auth::requireLogin();
        $prefs = Database::fetch('SELECT * FROM notification_preferences WHERE user_id = ?', [$user['id']]);
        View::make('app/notification-preferences', ['title' => 'Notification preferences', 'prefs' => $prefs], 'layouts/app');
    }

    public function notificationPreferencesSave(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid();
        Database::query(
            'INSERT INTO notification_preferences (user_id, email_enabled, sms_enabled, whatsapp_enabled, in_app_enabled, digest_enabled, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE email_enabled=VALUES(email_enabled), sms_enabled=VALUES(sms_enabled),
             whatsapp_enabled=VALUES(whatsapp_enabled), in_app_enabled=VALUES(in_app_enabled),
             digest_enabled=VALUES(digest_enabled), updated_at=NOW()',
            [
                $user['id'],
                isset($_POST['email_enabled']) ? 1 : 0,
                isset($_POST['sms_enabled']) ? 1 : 0,
                isset($_POST['whatsapp_enabled']) ? 1 : 0,
                isset($_POST['in_app_enabled']) ? 1 : 0,
                isset($_POST['digest_enabled']) ? 1 : 0,
            ]
        );
        Response::flash('success', 'Preferences saved.');
        Response::redirect('/app/notifications/preferences');
    }
}
