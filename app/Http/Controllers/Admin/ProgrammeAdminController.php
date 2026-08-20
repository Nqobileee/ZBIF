<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Auth\Auth;
use App\Domain\AuditLog;
use App\Domain\EventContext;
use App\Rbac\Gate;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\View;

final class ProgrammeAdminController
{
    private function guard(): void
    {
        Auth::requireAdmin();
        Gate::authorize('cms.manage');
    }

    public function index(): void
    {
        $this->guard();
        $eventId = EventContext::id();
        View::make('admin/programme', [
            'title' => 'Programme / schedule',
            'event' => EventContext::current(),
            'sessions' => Database::fetchAll(
                'SELECT * FROM programme_sessions WHERE event_id = ? ORDER BY day_number, starts_at',
                [$eventId]
            ),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $this->guard();
        Csrf::requireValid();
        $eventId = EventContext::id();
        Database::query(
            'INSERT INTO programme_sessions (event_id, day_number, title, session_type, track, room, starts_at, ends_at, capacity, description, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $eventId,
                max(1, (int) ($_POST['day_number'] ?? 1)),
                trim($_POST['title'] ?? 'Session'),
                trim($_POST['session_type'] ?? 'keynote'),
                trim($_POST['track'] ?? '') ?: null,
                trim($_POST['room'] ?? '') ?: null,
                self::dt($_POST['starts_at'] ?? ''),
                self::dt($_POST['ends_at'] ?? ''),
                ($_POST['capacity'] ?? '') !== '' ? (int) $_POST['capacity'] : null,
                trim($_POST['description'] ?? '') ?: null,
            ]
        );
        AuditLog::record('programme.create', 'programme_session', (int) Database::lastId(), []);
        Response::flash('success', 'Session added. Public programme and app schedule will show it.');
        Response::redirect('/admin/programme');
    }

    public function update(string $id): void
    {
        $this->guard();
        Csrf::requireValid();
        $sid = (int) $id;
        Database::query(
            'UPDATE programme_sessions SET day_number = ?, title = ?, session_type = ?, track = ?, room = ?, starts_at = ?, ends_at = ?, capacity = ?, description = ?
             WHERE id = ? AND event_id = ?',
            [
                max(1, (int) ($_POST['day_number'] ?? 1)),
                trim($_POST['title'] ?? 'Session'),
                trim($_POST['session_type'] ?? 'keynote'),
                trim($_POST['track'] ?? '') ?: null,
                trim($_POST['room'] ?? '') ?: null,
                self::dt($_POST['starts_at'] ?? ''),
                self::dt($_POST['ends_at'] ?? ''),
                ($_POST['capacity'] ?? '') !== '' ? (int) $_POST['capacity'] : null,
                trim($_POST['description'] ?? '') ?: null,
                $sid,
                EventContext::id(),
            ]
        );
        AuditLog::record('programme.update', 'programme_session', $sid, []);
        Response::flash('success', 'Session updated.');
        Response::redirect('/admin/programme');
    }

    public function delete(string $id): void
    {
        $this->guard();
        Csrf::requireValid();
        $sid = (int) $id;
        Database::query('DELETE FROM programme_sessions WHERE id = ? AND event_id = ?', [$sid, EventContext::id()]);
        AuditLog::record('programme.delete', 'programme_session', $sid, []);
        Response::flash('success', 'Session removed.');
        Response::redirect('/admin/programme');
    }

    private static function dt(string $raw): string
    {
        $raw = trim(str_replace('T', ' ', $raw));
        if ($raw === '') {
            return date('Y-m-d H:i:s');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $raw)) {
            $raw .= ':00';
        }
        return $raw;
    }
}
