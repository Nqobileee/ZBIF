<?php
declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Auth\Auth;
use App\Domain\DealPipeline;
use App\Domain\EventContext;
use App\Domain\AuditLog;
use App\Notify\Notifier;
use App\Rbac\Gate;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\UploadGuard;
use App\Support\View;

final class DealController
{
    public const INVESTOR_STAGES = ['interest', 'diligence', 'term_sheet', 'closed', 'passed'];

    public const DEFAULT_CHECKLIST = [
        'NDA acknowledged by both parties',
        'Problem statement aligned',
        'Solution demo scheduled',
        'Commercial terms draft shared',
        'Pilot scope agreed',
        'Outcome tagged',
    ];

    public function index(): void
    {
        Auth::requireLogin();
        $uid = (int) Auth::id();
        $rooms = Database::fetchAll(
            'SELECT dr.* FROM deal_rooms dr
             INNER JOIN deal_room_participants p ON p.deal_room_id = dr.id
             WHERE p.user_id = ? ORDER BY dr.updated_at DESC',
            [$uid]
        );
        $pipeline = [];
        foreach (DealPipeline::STAGES as $stage) {
            $pipeline[$stage] = array_values(array_filter($rooms, static fn ($r) => $r['stage'] === $stage));
        }
        $requests = Database::fetchAll(
            "SELECT cr.*, u.first_name, u.last_name FROM connection_requests cr
             INNER JOIN users u ON u.id = cr.from_user_id
             WHERE cr.to_user_id = ? AND cr.status = 'pending' ORDER BY cr.id DESC",
            [$uid]
        );
        $venueRooms = [
            ['key' => 'a', 'name' => 'Room A', 'focus' => 'Fintech & banking'],
            ['key' => 'b', 'name' => 'Room B', 'focus' => 'Agritech & health'],
            ['key' => 'c', 'name' => 'Room C', 'focus' => 'Trade, energy & water'],
        ];
        $slotStarts = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '14:00', '14:30'];
        $day = date('Y-m-d');
        $meetings = Database::fetchAll(
            "SELECT m.* FROM meetings m
             LEFT JOIN meeting_participants mp ON mp.meeting_id = m.id
             WHERE (m.organizer_id = ? OR mp.user_id = ?)
               AND DATE(m.starts_at) = ?
               AND m.status IN ('pending','accepted')
             GROUP BY m.id
             ORDER BY m.starts_at ASC",
            [$uid, $uid, $day]
        );
        $timetable = [];
        foreach ($venueRooms as $vr) {
            $slots = [];
            foreach ($slotStarts as $start) {
                $end = date('H:i', strtotime($start . ' +30 minutes'));
                $match = null;
                foreach ($meetings as $m) {
                    $loc = mb_strtolower((string) ($m['location'] ?? ''));
                    $mStart = date('H:i', strtotime((string) $m['starts_at']));
                    if ($mStart === $start && (str_contains($loc, mb_strtolower($vr['name'])) || str_contains($loc, 'room ' . $vr['key']))) {
                        $match = $m;
                        break;
                    }
                }
                $status = 'available';
                $label = 'Open slot';
                if ($match) {
                    $now = time();
                    $ms = strtotime((string) $match['starts_at']);
                    $me = strtotime((string) $match['ends_at']);
                    if ($now >= $ms && $now <= $me) {
                        $status = 'live';
                        $label = $match['title'];
                    } elseif ($ms > $now && $ms <= $now + 1800) {
                        $status = 'next';
                        $label = $match['title'];
                    } elseif (($match['status'] ?? '') === 'accepted') {
                        $status = 'confirmed';
                        $label = $match['title'];
                    } else {
                        $status = 'scheduled';
                        $label = $match['title'];
                    }
                }
                $slots[] = [
                    'start' => $start,
                    'end' => $end,
                    'status' => $status,
                    'label' => $label,
                    'meeting' => $match,
                ];
            }
            $timetable[] = $vr + ['slots' => $slots];
        }
        $users = Database::fetchAll('SELECT id, first_name, last_name, email FROM users WHERE is_active = 1 ORDER BY first_name LIMIT 200');
        View::make('app/deals/index', [
            'title' => 'Deal room timetable',
            'pipeline' => $pipeline,
            'rooms' => $rooms,
            'requests' => $requests,
            'timetable' => $timetable,
            'users' => $users,
            'hidePageHead' => true,
        ], 'layouts/app');
    }

    public function show(string $id): void
    {
        Auth::requireLogin();
        $room = Database::fetch('SELECT * FROM deal_rooms WHERE id = ?', [(int) $id]);
        if (!$room || !$this->isParticipant((int) $id)) {
            http_response_code(403);
            View::make('public/403', ['title' => 'Forbidden'], 'layouts/app');
            return;
        }
        $this->ensureChecklist((int) $id);
        $me = Database::fetch(
            'SELECT * FROM deal_room_participants WHERE deal_room_id = ? AND user_id = ?',
            [(int) $id, Auth::id()]
        );
        $sponsor = $room['sponsor_id']
            ? Database::fetch('SELECT * FROM sponsors WHERE id = ?', [$room['sponsor_id']])
            : Database::fetch("SELECT * FROM sponsors WHERE tier = 'deal_room' ORDER BY id ASC LIMIT 1");
        $challenge = $room['challenge_id']
            ? Database::fetch('SELECT id, title FROM challenges WHERE id = ?', [$room['challenge_id']])
            : null;
        $solution = $room['solution_id']
            ? Database::fetch('SELECT id, name FROM solutions WHERE id = ?', [$room['solution_id']])
            : null;
        View::make('app/deals/show', [
            'title' => $room['title'],
            'room' => $room,
            'sponsor' => $sponsor,
            'challenge' => $challenge,
            'solution' => $solution,
            'ndaAccepted' => !empty($me['nda_accepted_at']),
            'messages' => Database::fetchAll(
                'SELECT m.*, u.first_name, u.last_name FROM deal_room_messages m INNER JOIN users u ON u.id = m.user_id WHERE deal_room_id = ? ORDER BY m.id ASC',
                [(int) $id]
            ),
            'participants' => Database::fetchAll(
                'SELECT p.*, u.first_name, u.last_name, u.email FROM deal_room_participants p INNER JOIN users u ON u.id = p.user_id WHERE deal_room_id = ?',
                [(int) $id]
            ),
            'outcomes' => Database::fetchAll('SELECT * FROM deal_outcomes WHERE deal_room_id = ?', [(int) $id]),
            'files' => Database::fetchAll('SELECT * FROM deal_room_files WHERE deal_room_id = ? ORDER BY id DESC', [(int) $id]),
            'timeline' => Database::fetchAll(
                'SELECT e.*, u.first_name, u.last_name FROM deal_stage_events e
                 LEFT JOIN users u ON u.id = e.actor_id
                 WHERE e.deal_room_id = ? ORDER BY e.id ASC',
                [(int) $id]
            ),
            'checklist' => Database::fetchAll(
                'SELECT * FROM deal_checklist_items WHERE deal_room_id = ? ORDER BY sort_order, id',
                [(int) $id]
            ),
            'stages' => DealPipeline::STAGES,
        ], 'layouts/app');
    }

    public function postMessage(string $id): void
    {
        Auth::requireVerified();
        Csrf::requireValid();
        if (!$this->isParticipant((int) $id)) {
            Response::json(['error' => ['code' => 'forbidden', 'message' => 'Not a participant', 'details' => []]], 403);
            return;
        }
        if (!\App\Support\RateLimiter::attempt('deal_msg:' . Auth::id(), 40, 60)) {
            Response::json(['error' => ['code' => 'rate_limited', 'message' => 'Too many messages', 'details' => []]], 429);
            return;
        }
        $body = trim($_POST['body'] ?? '');
        if ($body === '') {
            Response::json(['error' => ['code' => 'invalid', 'message' => 'Empty message', 'details' => []]], 422);
            return;
        }
        Database::query(
            'INSERT INTO deal_room_messages (deal_room_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())',
            [(int) $id, Auth::id(), $body]
        );
        Database::query('UPDATE deal_room_participants SET last_seen_at = NOW() WHERE deal_room_id = ? AND user_id = ?', [(int) $id, Auth::id()]);
        Notifier::event('deal_message', [
            'deal_room_id' => (int) $id,
            'from_user_id' => Auth::id(),
            'preview' => mb_substr($body, 0, 140),
        ]);
        Response::json(['ok' => true]);
    }

    public function pollMessages(string $id): void
    {
        Auth::requireLogin();
        if (!$this->isParticipant((int) $id)) {
            Response::json(['error' => ['code' => 'forbidden', 'message' => 'Not a participant', 'details' => []]], 403);
            return;
        }
        $after = (int) ($_GET['after'] ?? 0);
        $rows = Database::fetchAll(
            'SELECT m.*, u.first_name, u.last_name FROM deal_room_messages m
             INNER JOIN users u ON u.id = m.user_id
             WHERE m.deal_room_id = ? AND m.id > ? ORDER BY m.id ASC',
            [(int) $id, $after]
        );
        Response::json(['messages' => $rows]);
    }

    public function moveStage(string $id): void
    {
        Auth::requireVerified();
        Csrf::requireValid();
        if (!$this->isParticipant((int) $id)) {
            Response::flash('error', 'Not allowed.');
            Response::redirect('/app/deals');
        }
        DealPipeline::move((int) $id, (string) ($_POST['stage'] ?? 'in_discussion'), Auth::id(), trim($_POST['notes'] ?? ''));
        Response::redirect('/app/deals/' . $id);
    }

    public function tagOutcome(string $id): void
    {
        Auth::requireVerified();
        Csrf::requireValid();
        $type = $_POST['outcome_type'] ?? 'partnership';
        $allowed = ['mou', 'pilot', 'investment', 'procurement', 'adoption', 'partnership'];
        if (!in_array($type, $allowed, true)) {
            $type = 'partnership';
        }
        Database::query(
            'INSERT INTO deal_outcomes (deal_room_id, outcome_type, amount_usd, notes, announced_publicly, public_title, public_summary, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                (int) $id,
                $type,
                $_POST['amount_usd'] !== '' ? (float) $_POST['amount_usd'] : null,
                trim($_POST['notes'] ?? ''),
                isset($_POST['announced_publicly']) ? 1 : 0,
                trim($_POST['public_title'] ?? ''),
                trim($_POST['public_summary'] ?? ''),
            ]
        );
        if (trim($_POST['agreement_summary'] ?? '') !== '') {
            Database::query('UPDATE deal_rooms SET agreement_summary = ?, updated_at = NOW() WHERE id = ?', [trim($_POST['agreement_summary']), (int) $id]);
        }
        AuditLog::record('deal.outcome', 'deal_room', (int) $id, ['type' => $type]);
        if (in_array($type, ['mou', 'pilot', 'adoption'], true)) {
            DealPipeline::move((int) $id, 'mou_or_pilot', Auth::id(), 'Outcome tagged: ' . $type);
        }
        Response::flash('success', 'Outcome recorded.');
        Response::redirect('/app/deals/' . $id);
    }

    public function uploadFile(string $id): void
    {
        Auth::requireVerified();
        Csrf::requireValid();
        if (!$this->isParticipant((int) $id)) {
            Response::flash('error', 'Not allowed.');
            Response::redirect('/app/deals');
        }
        if (empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            Response::flash('error', 'No file uploaded.');
            Response::redirect('/app/deals/' . $id);
        }
        $check = UploadGuard::validate($_FILES['file']);
        if (!$check['ok']) {
            Response::flash('error', $check['error'] ?? 'Invalid file.');
            Response::redirect('/app/deals/' . $id);
        }
        $dir = ZBIF_ROOT . '/storage/uploads/deals/' . (int) $id;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = $dir . '/' . bin2hex(random_bytes(4)) . '_' . $check['safe_name'];
        move_uploaded_file($_FILES['file']['tmp_name'], $path);
        Database::query(
            'INSERT INTO deal_room_files (deal_room_id, uploaded_by, file_path, original_name, created_at) VALUES (?, ?, ?, ?, NOW())',
            [(int) $id, Auth::id(), 'deals/' . (int) $id . '/' . basename($path), (string) ($_FILES['file']['name'] ?? $check['safe_name'])]
        );
        Response::flash('success', 'File shared in deal room.');
        Response::redirect('/app/deals/' . $id);
    }

    public function downloadFile(string $id, string $fileId): void
    {
        Auth::requireLogin();
        if (!$this->isParticipant((int) $id)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }
        $file = Database::fetch(
            'SELECT * FROM deal_room_files WHERE id = ? AND deal_room_id = ?',
            [(int) $fileId, (int) $id]
        );
        if (!$file) {
            http_response_code(404);
            echo 'Not found';
            return;
        }
        $full = ZBIF_ROOT . '/storage/uploads/' . ltrim((string) $file['file_path'], '/');
        if (!is_file($full)) {
            http_response_code(404);
            echo 'File missing';
            return;
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', (string) $file['original_name']) . '"');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
    }

    public function acceptNda(string $id): void
    {
        Auth::requireVerified();
        Csrf::requireValid();
        if (!$this->isParticipant((int) $id)) {
            Response::flash('error', 'Not allowed.');
            Response::redirect('/app/deals');
        }
        Database::query(
            'UPDATE deal_room_participants SET nda_accepted_at = NOW() WHERE deal_room_id = ? AND user_id = ?',
            [(int) $id, Auth::id()]
        );
        Response::flash('success', 'NDA acknowledged.');
        Response::redirect('/app/deals/' . $id);
    }

    public function toggleChecklist(string $id): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        if (!$this->isParticipant((int) $id)) {
            Response::flash('error', 'Not allowed.');
            Response::redirect('/app/deals');
        }
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $item = Database::fetch('SELECT * FROM deal_checklist_items WHERE id = ? AND deal_room_id = ?', [$itemId, (int) $id]);
        if ($item) {
            $done = (int) $item['is_done'] ? 0 : 1;
            Database::query(
                'UPDATE deal_checklist_items SET is_done = ?, done_by = ?, done_at = IF(?=1, NOW(), NULL) WHERE id = ?',
                [$done, Auth::id(), $done, $itemId]
            );
        }
        Response::redirect('/app/deals/' . $id);
    }

    public function bookMeeting(string $id): void
    {
        Auth::requireVerified();
        Csrf::requireValid();
        if (!$this->isParticipant((int) $id)) {
            Response::flash('error', 'Not allowed.');
            Response::redirect('/app/deals');
        }
        $room = Database::fetch('SELECT title FROM deal_rooms WHERE id = ?', [(int) $id]);
        Database::query(
            'INSERT INTO meetings (event_id, organizer_id, title, meeting_type, starts_at, ends_at, location, status, notes, created_at, updated_at)
             VALUES (?, ?, ?, \'one_to_one\', ?, ?, \'Deal Room\', \'pending\', ?, NOW(), NOW())',
            [
                EventContext::id(),
                Auth::id(),
                'Deal: ' . ($room['title'] ?? ('Room #' . $id)),
                $_POST['starts_at'] ?? date('Y-m-d H:i:s', strtotime('+1 day')),
                $_POST['ends_at'] ?? date('Y-m-d H:i:s', strtotime('+1 day +1 hour')),
                'Deal room #' . $id,
            ]
        );
        $mid = (int) Database::lastId();
        $parts = Database::fetchAll('SELECT user_id FROM deal_room_participants WHERE deal_room_id = ? AND user_id <> ?', [(int) $id, Auth::id()]);
        foreach ($parts as $p) {
            Database::query('INSERT INTO meeting_participants (meeting_id, user_id, status, created_at) VALUES (?, ?, \'invited\', NOW())', [$mid, $p['user_id']]);
            Notifier::notifyUser((int) $p['user_id'], 'Deal meeting proposed', 'A meeting was proposed in your deal room.', '/app/meetings');
        }
        if (($_POST['advance_stage'] ?? '') === '1') {
            DealPipeline::move((int) $id, 'meeting_booked', Auth::id(), 'Meeting proposed from deal room');
        }
        Response::flash('success', 'Meeting proposed to deal room participants.');
        Response::redirect('/app/deals/' . $id);
    }

    public function requestConnection(): void
    {
        Auth::requireVerified();
        Csrf::requireValid();
        $toUser = (int) ($_POST['to_user_id'] ?? 0);
        $challengeId = (int) ($_POST['challenge_id'] ?? 0) ?: null;
        $solutionId = (int) ($_POST['solution_id'] ?? 0) ?: null;
        $message = trim((string) ($_POST['message'] ?? 'We would like to open a Deal Room.'));
        if ($toUser <= 0 || $toUser === (int) Auth::id()) {
            Response::flash('error', 'Could not send the Deal Room request.');
            Response::redirect('/app/solutions');
        }
        Database::query(
            'INSERT INTO connection_requests (from_user_id, to_user_id, challenge_id, solution_id, message, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, \'pending\', NOW(), NOW())',
            [Auth::id(), $toUser, $challengeId, $solutionId, $message]
        );
        Notifier::notifyUser(
            $toUser,
            'Deal Room request',
            $message,
            '/app/deals',
            ['in_app', 'email']
        );
        Response::flash('success', 'Deal Room request sent. They can accept it from Deal rooms.');
        Response::redirect('/app/solutions');
    }

    public function acceptConnection(string $requestId): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        $req = Database::fetch('SELECT * FROM connection_requests WHERE id = ?', [(int) $requestId]);
        if (!$req || (int) $req['to_user_id'] !== (int) Auth::id()) {
            Response::flash('error', 'Request not found.');
            Response::redirect('/app/deals');
        }
        $dealSponsor = Database::fetch("SELECT id FROM sponsors WHERE tier = 'deal_room' ORDER BY id ASC LIMIT 1");
        $challengeTitle = $req['challenge_id']
            ? (Database::fetch('SELECT title FROM challenges WHERE id = ?', [$req['challenge_id']])['title'] ?? null)
            : null;
        $solutionName = $req['solution_id']
            ? (Database::fetch('SELECT name FROM solutions WHERE id = ?', [$req['solution_id']])['name'] ?? null)
            : null;
        $title = trim(($challengeTitle ?: 'Marketplace match') . ($solutionName ? ' × ' . $solutionName : ''));
        Database::query(
            'INSERT INTO deal_rooms (event_id, title, challenge_id, solution_id, sponsor_id, stage, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, \'introduced\', ?, NOW(), NOW())',
            [
                EventContext::id(),
                $title,
                $req['challenge_id'],
                $req['solution_id'],
                $dealSponsor['id'] ?? null,
                Auth::id(),
            ]
        );
        $roomId = (int) Database::lastId();
        foreach ([(int) $req['from_user_id'], (int) $req['to_user_id']] as $uid) {
            Database::query(
                'INSERT INTO deal_room_participants (deal_room_id, user_id, created_at) VALUES (?, ?, NOW())',
                [$roomId, $uid]
            );
            Notifier::notifyUser($uid, 'Deal room opened', 'A new private deal room is ready: ' . $title, '/app/deals/' . $roomId);
        }
        $this->ensureChecklist($roomId);
        Database::query(
            'UPDATE connection_requests SET status = \'accepted\', deal_room_id = ?, updated_at = NOW() WHERE id = ?',
            [$roomId, $req['id']]
        );
        Response::flash('success', 'Deal room opened.');
        Response::redirect('/app/deals/' . $roomId);
    }

    public function investorFlow(): void
    {
        Gate::authorize('investor.deal_flow');
        $solutions = Database::fetchAll('SELECT s.*, o.name AS org_name FROM solutions s INNER JOIN organizations o ON o.id = s.owner_org_id WHERE s.investor_visible = 1');
        $watchRows = Database::fetchAll(
            'SELECT w.*, s.name AS solution_name, o.name AS org_name
             FROM investor_watchlist w
             INNER JOIN solutions s ON s.id = w.solution_id
             INNER JOIN organizations o ON o.id = s.owner_org_id
             WHERE w.investor_user_id = ?',
            [Auth::id()]
        );
        $pipeline = [];
        foreach (self::INVESTOR_STAGES as $st) {
            $pipeline[$st] = array_values(array_filter($watchRows, static fn ($r) => ($r['pipeline_stage'] ?? 'interest') === $st));
        }
        $notes = Database::fetchAll(
            'SELECT n.*, s.name AS solution_name FROM investor_notes n
             LEFT JOIN solutions s ON s.id = n.solution_id
             WHERE n.investor_user_id = ? ORDER BY n.updated_at DESC',
            [Auth::id()]
        );
        View::make('app/deals/investor', [
            'title' => 'Investor deal flow',
            'solutions' => $solutions,
            'watch' => array_column($watchRows, 'solution_id'),
            'pipeline' => $pipeline,
            'stages' => self::INVESTOR_STAGES,
            'notes' => $notes,
        ], 'layouts/app');
    }

    public function watchlistToggle(): void
    {
        Gate::authorize('investor.deal_flow');
        Csrf::requireValid();
        $sid = (int) ($_POST['solution_id'] ?? 0);
        $exists = Database::fetch('SELECT id FROM investor_watchlist WHERE investor_user_id = ? AND solution_id = ?', [Auth::id(), $sid]);
        if ($exists) {
            Database::query('DELETE FROM investor_watchlist WHERE id = ?', [$exists['id']]);
        } else {
            Database::query(
                'INSERT INTO investor_watchlist (investor_user_id, solution_id, pipeline_stage, created_at) VALUES (?, ?, \'interest\', NOW())',
                [Auth::id(), $sid]
            );
        }
        Response::redirect('/app/investor');
    }

    public function investorStage(): void
    {
        Gate::authorize('investor.deal_flow');
        Csrf::requireValid();
        $sid = (int) ($_POST['solution_id'] ?? 0);
        $stage = (string) ($_POST['pipeline_stage'] ?? 'interest');
        if (!in_array($stage, self::INVESTOR_STAGES, true)) {
            $stage = 'interest';
        }
        Database::query(
            'UPDATE investor_watchlist SET pipeline_stage = ? WHERE investor_user_id = ? AND solution_id = ?',
            [$stage, Auth::id(), $sid]
        );
        Response::redirect('/app/investor');
    }

    public function saveNote(): void
    {
        Gate::authorize('investor.deal_flow');
        Csrf::requireValid();
        Database::query(
            'INSERT INTO investor_notes (investor_user_id, solution_id, note, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())',
            [Auth::id(), (int) ($_POST['solution_id'] ?? 0) ?: null, trim($_POST['note'] ?? '')]
        );
        Response::redirect('/app/investor');
    }

    public function requestIntro(): void
    {
        Gate::authorize('investor.deal_flow');
        Csrf::requireValid();
        $sid = (int) ($_POST['solution_id'] ?? 0);
        $sol = Database::fetch('SELECT * FROM solutions WHERE id = ?', [$sid]);
        if (!$sol) {
            Response::flash('error', 'Solution not found.');
            Response::redirect('/app/investor');
        }
        $toUser = (int) ($sol['created_by'] ?? 0);
        if (!$toUser || $toUser === Auth::id()) {
            Response::flash('error', 'Could not find a contact for intro.');
            Response::redirect('/app/investor');
        }
        Database::query(
            'INSERT INTO connection_requests (from_user_id, to_user_id, challenge_id, solution_id, message, status, created_at, updated_at)
             VALUES (?, ?, NULL, ?, ?, \'pending\', NOW(), NOW())',
            [Auth::id(), $toUser, $sid, 'Investor intro request for ' . $sol['name']]
        );
        Notifier::notifyUser($toUser, 'Investor intro request', 'An investor wants an introduction regarding ' . $sol['name'], '/app/deals');
        Database::query(
            'UPDATE investor_watchlist SET pipeline_stage = \'diligence\' WHERE investor_user_id = ? AND solution_id = ?',
            [Auth::id(), $sid]
        );
        Response::flash('success', 'Intro request sent.');
        Response::redirect('/app/investor');
    }

    public function investorPdf(string $solutionId): void
    {
        Gate::authorize('investor.deal_flow');
        $s = Database::fetch(
            'SELECT s.*, o.name AS org_name FROM solutions s INNER JOIN organizations o ON o.id = s.owner_org_id WHERE s.id = ?',
            [(int) $solutionId]
        );
        if (!$s) {
            http_response_code(404);
            return;
        }
        $pdf = new \App\Support\SimplePdf('Investor one-pager');
        $pdf->heading($s['name']);
        $pdf->text('Organisation: ' . $s['org_name']);
        $pdf->text('Sector: ' . ($s['sector'] ?? '') . ' | Stage: ' . str_replace('_', ' ', (string) ($s['stage'] ?? '')));
        $pdf->spacer();
        $pdf->text(strip_tags((string) ($s['summary'] ?? $s['description'] ?? 'No summary')));
        $pdf->footerBrand();
        $pdf->stream('zbif-investor-' . $solutionId . '.pdf');
    }

    private function ensureChecklist(int $roomId): void
    {
        $count = (int) (Database::fetch('SELECT COUNT(*) AS c FROM deal_checklist_items WHERE deal_room_id = ?', [$roomId])['c'] ?? 0);
        if ($count > 0) {
            return;
        }
        foreach (self::DEFAULT_CHECKLIST as $i => $label) {
            Database::query(
                'INSERT INTO deal_checklist_items (deal_room_id, label, is_done, sort_order, created_at) VALUES (?, ?, 0, ?, NOW())',
                [$roomId, $label, $i]
            );
        }
    }

    private function isParticipant(int $roomId): bool
    {
        $row = Database::fetch(
            'SELECT id FROM deal_room_participants WHERE deal_room_id = ? AND user_id = ?',
            [$roomId, Auth::id()]
        );
        return (bool) $row || Gate::allows('deals.manage');
    }
}
