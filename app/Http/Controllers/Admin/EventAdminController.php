<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Auth\Auth;
use App\Domain\EventContext;
use App\Domain\AuditLog;
use App\Rbac\Gate;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\FileCache;
use App\Support\Response;
use App\Support\Str;
use App\Support\View;

final class EventAdminController
{
    private function guard(): void
    {
        Auth::requireAdmin();
    }

    public function index(): void
    {
        $this->guard();
        View::make('admin/events', [
            'title' => 'Events / editions',
            'events' => EventContext::all(),
            'activeId' => EventContext::id(),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $this->guard();
        Csrf::requireValid();
        $name = trim($_POST['name'] ?? 'ZBIF Edition');
        $slug = Str::slug(trim($_POST['slug'] ?? $name));
        if (Database::fetch('SELECT id FROM events WHERE slug = ?', [$slug])) {
            $slug .= '-' . time();
        }
        $starts = self::normalizeDateTime($_POST['starts_at'] ?? date('Y-m-d H:i:s', strtotime('+30 days')));
        $ends = self::normalizeDateTime($_POST['ends_at'] ?? date('Y-m-d H:i:s', strtotime('+32 days')));
        Database::query(
            'INSERT INTO events (slug, name, edition, theme, starts_at, ends_at, venue, city, country, status, registration_fee, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $slug,
                $name,
                trim($_POST['edition'] ?? date('Y')),
                trim($_POST['theme'] ?? ''),
                $starts,
                $ends,
                trim($_POST['venue'] ?? 'ZITF grounds'),
                trim($_POST['city'] ?? 'Bulawayo'),
                'Zimbabwe',
                $_POST['status'] ?? 'draft',
                (float) ($_POST['registration_fee'] ?? 0),
            ]
        );
        $newId = (int) Database::lastId();
        if (!empty($_POST['clone_from'])) {
            EventContext::cloneCatalogue((int) $_POST['clone_from'], $newId);
        }
        FileCache::flush();
        AuditLog::record('event.create', 'event', $newId, ['slug' => $slug]);
        Response::flash('success', 'Event created. Homepage countdown uses the active event dates.');
        Response::redirect('/admin/events');
    }

    public function update(string $id): void
    {
        $this->guard();
        Csrf::requireValid();
        Database::query(
            'UPDATE events SET name = ?, edition = ?, theme = ?, starts_at = ?, ends_at = ?, venue = ?, city = ?, status = ?, registration_fee = ?, updated_at = NOW() WHERE id = ?',
            [
                trim($_POST['name'] ?? ''),
                trim($_POST['edition'] ?? ''),
                trim($_POST['theme'] ?? ''),
                self::normalizeDateTime($_POST['starts_at'] ?? ''),
                self::normalizeDateTime($_POST['ends_at'] ?? ''),
                trim($_POST['venue'] ?? ''),
                trim($_POST['city'] ?? 'Bulawayo'),
                $_POST['status'] ?? 'draft',
                (float) ($_POST['registration_fee'] ?? 0),
                (int) $id,
            ]
        );
        FileCache::flush();
        AuditLog::record('event.update', 'event', (int) $id, [
            'starts_at' => $_POST['starts_at'] ?? null,
            'ends_at' => $_POST['ends_at'] ?? null,
        ]);
        Response::flash('success', 'Event updated. Countdown and all date appearances now use these values.');
        Response::redirect('/admin/events');
    }

    public function switchEvent(): void
    {
        $this->guard();
        Csrf::requireValid();
        $eid = (int) ($_POST['event_id'] ?? 0);
        if (Database::fetch('SELECT id FROM events WHERE id = ?', [$eid])) {
            EventContext::setActive($eid);
            FileCache::flush();
            Response::flash('success', 'Active event switched. Public countdown follows this edition.');
        }
        Response::redirect($_POST['redirect'] ?? '/admin/events');
    }

    /** Accept datetime-local or MySQL datetime. */
    private static function normalizeDateTime(string $raw): string
    {
        $raw = trim(str_replace('T', ' ', $raw));
        if ($raw === '') {
            return date('Y-m-d H:i:s');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $raw)) {
            $raw .= ':00';
        }
        $ts = strtotime($raw);
        return $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
    }
}
