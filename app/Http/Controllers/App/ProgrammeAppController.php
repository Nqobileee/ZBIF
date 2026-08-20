<?php
declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Auth\Auth;
use App\Domain\EventContext;
use App\Notify\Notifier;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\View;

final class ProgrammeAppController
{
    public function agenda(): void
    {
        Auth::requireLogin();
        $personal = [];
        try {
            $personal = Database::fetchAll(
                'SELECT ps.*
                 FROM agenda_items ai
                 INNER JOIN programme_sessions ps ON ps.id = ai.session_id
                 WHERE ai.user_id = ?
                 ORDER BY ps.starts_at ASC',
                [Auth::id()]
            );
        } catch (\Throwable $e) {
            $personal = [];
        }
        $event = EventContext::current();
        $start = !empty($event['starts_at']) ? strtotime((string) $event['starts_at']) : false;
        $forumDays = [];
        for ($i = 0; $i < 4; $i++) {
            $ts = $start ? strtotime('+' . $i . ' day', $start) : false;
            $forumDays[] = [
                'day' => $i + 1,
                'label' => $ts ? date('j F', $ts) : ('Day ' . ($i + 1)),
                'weekday' => $ts ? date('l', $ts) : '',
            ];
        }
        View::make('app/programme', [
            'title' => 'My agenda',
            'personal' => $personal,
            'forumDays' => $forumDays,
            'hidePageHead' => true,
        ], 'layouts/app');
    }

    public function toggleAgenda(): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        $sid = (int) ($_POST['session_id'] ?? 0);
        $exists = Database::fetch('SELECT id FROM agenda_items WHERE user_id = ? AND session_id = ?', [Auth::id(), $sid]);
        if ($exists) {
            Database::query('DELETE FROM agenda_items WHERE id = ?', [$exists['id']]);
        } else {
            Database::query('INSERT INTO agenda_items (user_id, session_id, created_at) VALUES (?, ?, NOW())', [Auth::id(), $sid]);
        }
        Response::redirect('/app/programme');
    }

    public function meetings(): void
    {
        Auth::requireLogin();
        View::make('app/meetings', [
            'title' => 'Meetings',
            'hidePageHead' => true,
        ], 'layouts/app');
    }

    public function requestMeeting(): void
    {
        Auth::requireVerified();
        Csrf::requireValid();
        $starts = $_POST['starts_at'] ?? '';
        $ends = $_POST['ends_at'] ?? '';
        $participantId = (int) ($_POST['participant_id'] ?? 0);
        // double-booking check
        $conflict = Database::fetch(
            'SELECT m.id FROM meetings m
             INNER JOIN meeting_participants mp ON mp.meeting_id = m.id
             WHERE mp.user_id IN (?, ?) AND m.status IN (\'pending\',\'accepted\')
             AND m.starts_at < ? AND m.ends_at > ?
             LIMIT 1',
            [Auth::id(), $participantId, $ends, $starts]
        );
        $redirect = trim((string) ($_POST['redirect'] ?? '/app/deals'));
        if (!str_starts_with($redirect, '/app/')) {
            $redirect = '/app/deals';
        }
        if ($conflict) {
            Response::flash('error', 'That time conflicts with an existing meeting.');
            Response::redirect($redirect);
        }
        Database::query(
            'INSERT INTO meetings (event_id, organizer_id, title, meeting_type, starts_at, ends_at, location, status, notes, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, \'pending\', ?, NOW(), NOW())',
            [
                EventContext::id(),
                Auth::id(),
                trim($_POST['title'] ?? 'Networking meeting'),
                $_POST['meeting_type'] ?? 'one_to_one',
                $starts,
                $ends,
                trim($_POST['location'] ?? 'Networking lounge'),
                trim($_POST['notes'] ?? ''),
            ]
        );
        $mid = (int) Database::lastId();
        Database::query('INSERT INTO meeting_participants (meeting_id, user_id, status, created_at) VALUES (?, ?, \'invited\', NOW())', [$mid, $participantId]);
        Notifier::inApp($participantId, 'Meeting request', 'You have a new meeting request.', '/app/deals');
        Notifier::queue('meeting_reminder', ['meeting_id' => $mid], 'default', new \DateTimeImmutable($starts . ' -1 hour'));
        Response::flash('success', 'Meeting requested. You will see it on the Deal room timetable.');
        Response::redirect($redirect);
    }

    public function respondMeeting(): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        $mid = (int) ($_POST['meeting_id'] ?? 0);
        $status = ($_POST['status'] ?? 'accepted') === 'declined' ? 'declined' : 'accepted';
        Database::query(
            'UPDATE meeting_participants SET status = ? WHERE meeting_id = ? AND user_id = ?',
            [$status === 'accepted' ? 'accepted' : 'declined', $mid, Auth::id()]
        );
        Database::query('UPDATE meetings SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $mid]);
        Response::redirect('/app/meetings');
    }

    public function rescheduleMeeting(): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        $mid = (int) ($_POST['meeting_id'] ?? 0);
        $m = Database::fetch('SELECT * FROM meetings WHERE id = ?', [$mid]);
        if (!$m || (int) $m['organizer_id'] !== Auth::id()) {
            Response::flash('error', 'Only the organizer can reschedule.');
            Response::redirect('/app/meetings');
        }
        $starts = $_POST['starts_at'] ?? '';
        $ends = $_POST['ends_at'] ?? '';
        Database::query(
            'UPDATE meetings SET starts_at = ?, ends_at = ?, status = \'rescheduled\', updated_at = NOW() WHERE id = ?',
            [$starts, $ends, $mid]
        );
        $participants = Database::fetchAll('SELECT user_id FROM meeting_participants WHERE meeting_id = ?', [$mid]);
        foreach ($participants as $p) {
            Notifier::inApp((int) $p['user_id'], 'Meeting rescheduled', $m['title'] . ' moved to ' . $starts, '/app/meetings');
        }
        Notifier::queue('meeting_reminder', ['meeting_id' => $mid], 'default', new \DateTimeImmutable($starts . ' -1 hour'));
        Response::flash('success', 'Meeting rescheduled.');
        Response::redirect('/app/meetings');
    }

    public function ics(string $id): void
    {
        Auth::requireLogin();
        $m = Database::fetch('SELECT * FROM meetings WHERE id = ?', [(int) $id]);
        if (!$m) {
            http_response_code(404);
            return;
        }
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="zbif-meeting-' . $id . '.ics"');
        $uid = 'meeting-' . $id . '@zbif';
        $dt = static function (string $ts): string {
            return gmdate('Ymd\THis\Z', strtotime($ts));
        };
        echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//ZBIF//InnovaMatch//EN\r\nBEGIN:VEVENT\r\n";
        echo 'UID:' . $uid . "\r\n";
        echo 'DTSTART:' . $dt($m['starts_at']) . "\r\n";
        echo 'DTEND:' . $dt($m['ends_at']) . "\r\n";
        echo 'SUMMARY:' . str_replace(["\n", ','], [' ', '\\,'], $m['title']) . "\r\n";
        echo 'LOCATION:' . str_replace(["\n", ','], [' ', '\\,'], (string) $m['location']) . "\r\n";
        echo "END:VEVENT\r\nEND:VCALENDAR\r\n";
    }
}
