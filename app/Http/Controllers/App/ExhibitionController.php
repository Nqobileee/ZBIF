<?php
declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Auth\Auth;
use App\Rbac\Gate;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\View;

final class ExhibitionController
{
    public const LEAD_STATUSES = ['new', 'contacted', 'qualified', 'meeting', 'won', 'lost'];

    public function booth(): void
    {
        Gate::authorize('exhibition.manage_booth');
        $booth = Database::fetch('SELECT * FROM exhibitor_booths WHERE owner_user_id = ? LIMIT 1', [Auth::id()]);
        $status = $_GET['status'] ?? '';
        $leads = [];
        if ($booth) {
            if ($status !== '' && in_array($status, self::LEAD_STATUSES, true)) {
                $leads = Database::fetchAll(
                    'SELECT l.*, u.first_name, u.last_name, u.email FROM leads l INNER JOIN users u ON u.id = l.attendee_user_id WHERE booth_id = ? AND l.status = ? ORDER BY l.id DESC',
                    [$booth['id'], $status]
                );
            } else {
                $leads = Database::fetchAll(
                    'SELECT l.*, u.first_name, u.last_name, u.email FROM leads l INNER JOIN users u ON u.id = l.attendee_user_id WHERE booth_id = ? ORDER BY l.id DESC',
                    [$booth['id']]
                );
            }
        }
        $counts = [];
        foreach (self::LEAD_STATUSES as $st) {
            $counts[$st] = 0;
        }
        if ($booth) {
            $rows = Database::fetchAll('SELECT status, COUNT(*) AS c FROM leads WHERE booth_id = ? GROUP BY status', [$booth['id']]);
            foreach ($rows as $r) {
                $counts[$r['status']] = (int) $r['c'];
            }
        }
        View::make('app/exhibition/booth', [
            'title' => 'Exhibitor booth CRM',
            'booth' => $booth,
            'leads' => $leads,
            'counts' => $counts,
            'statuses' => self::LEAD_STATUSES,
            'filter' => $status,
        ], 'layouts/app');
    }

    public function captureLead(): void
    {
        Gate::authorize('exhibition.capture_leads');
        Csrf::requireValid();
        $booth = Database::fetch('SELECT * FROM exhibitor_booths WHERE owner_user_id = ? LIMIT 1', [Auth::id()]);
        if (!$booth) {
            Response::flash('error', 'No booth assigned.');
            Response::redirect('/app/exhibition');
        }
        $token = trim($_POST['qr_token'] ?? '');
        $attendee = Database::fetch('SELECT * FROM users WHERE qr_badge_token = ?', [$token]);
        if (!$attendee) {
            Response::flash('error', 'Badge token not recognised.');
            Response::redirect('/app/exhibition');
        }
        Database::query(
            'INSERT INTO leads (booth_id, attendee_user_id, captured_by, notes, status, tags, consented, created_at) VALUES (?, ?, ?, ?, \'new\', ?, 1, NOW())',
            [$booth['id'], $attendee['id'], Auth::id(), trim($_POST['notes'] ?? ''), trim($_POST['tags'] ?? '')]
        );
        Response::flash('success', 'Lead captured.');
        Response::redirect('/app/exhibition');
    }

    public function updateLead(): void
    {
        Gate::authorize('exhibition.capture_leads');
        Csrf::requireValid();
        $booth = Database::fetch('SELECT * FROM exhibitor_booths WHERE owner_user_id = ? LIMIT 1', [Auth::id()]);
        $leadId = (int) ($_POST['lead_id'] ?? 0);
        $lead = $booth ? Database::fetch('SELECT * FROM leads WHERE id = ? AND booth_id = ?', [$leadId, $booth['id']]) : null;
        if (!$lead) {
            Response::flash('error', 'Lead not found.');
            Response::redirect('/app/exhibition');
        }
        $status = (string) ($_POST['status'] ?? 'new');
        if (!in_array($status, self::LEAD_STATUSES, true)) {
            $status = 'new';
        }
        $follow = trim($_POST['follow_up_at'] ?? '');
        Database::query(
            'UPDATE leads SET status = ?, tags = ?, notes = ?, follow_up_at = ? WHERE id = ?',
            [
                $status,
                trim($_POST['tags'] ?? ''),
                trim($_POST['notes'] ?? ''),
                $follow !== '' ? $follow : null,
                $leadId,
            ]
        );
        Response::flash('success', 'Lead updated.');
        Response::redirect('/app/exhibition');
    }

    public function saveBooth(): void
    {
        Gate::authorize('exhibition.manage_booth');
        Csrf::requireValid();
        $booth = Database::fetch('SELECT * FROM exhibitor_booths WHERE owner_user_id = ? LIMIT 1', [Auth::id()]);
        if (!$booth) {
            Response::flash('error', 'No booth assigned.');
            Response::redirect('/app/exhibition');
        }
        $media = ['brochure' => trim($_POST['brochure_url'] ?? ''), 'video' => trim($_POST['video_url'] ?? '')];
        Database::query(
            'UPDATE exhibitor_booths SET name = ?, description = ?, launching_at_forum = ?, media_json = ?, floor_x = ?, floor_y = ?, updated_at = NOW() WHERE id = ?',
            [
                trim($_POST['name'] ?? $booth['name']),
                trim($_POST['description'] ?? ''),
                isset($_POST['launching_at_forum']) ? 1 : 0,
                json_encode($media),
                $_POST['floor_x'] !== '' ? (float) $_POST['floor_x'] : null,
                $_POST['floor_y'] !== '' ? (float) $_POST['floor_y'] : null,
                $booth['id'],
            ]
        );
        Response::flash('success', 'Booth updated.');
        Response::redirect('/app/exhibition');
    }

    public function exportLeads(): void
    {
        Gate::authorize('exhibition.capture_leads');
        $booth = Database::fetch('SELECT * FROM exhibitor_booths WHERE owner_user_id = ? LIMIT 1', [Auth::id()]);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="leads.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['name', 'email', 'status', 'tags', 'notes', 'follow_up_at', 'captured_at']);
        if ($booth) {
            $rows = Database::fetchAll(
                'SELECT u.first_name, u.last_name, u.email, l.status, l.tags, l.notes, l.follow_up_at, l.created_at
                 FROM leads l INNER JOIN users u ON u.id = l.attendee_user_id WHERE booth_id = ?',
                [$booth['id']]
            );
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['first_name'] . ' ' . $r['last_name'],
                    $r['email'],
                    $r['status'] ?? 'new',
                    $r['tags'] ?? '',
                    $r['notes'],
                    $r['follow_up_at'] ?? '',
                    $r['created_at'],
                ]);
            }
        }
        fclose($out);
    }

    /** Any logged-in participant can apply / offer to exhibit. */
    public function applyForm(): void
    {
        $user = Auth::requireLogin();
        $apps = [];
        try {
            $apps = Database::fetchAll(
                'SELECT * FROM exhibit_applications WHERE user_id = ? ORDER BY id DESC LIMIT 20',
                [$user['id']]
            );
        } catch (\Throwable $e) {
            $apps = [];
        }
        View::make('app/exhibition/apply', [
            'title' => 'Apply to exhibit',
            'user' => $user,
            'applications' => $apps,
            'hidePageHead' => true,
        ], 'layouts/app');
    }

    public function applySubmit(): void
    {
        $user = Auth::requireVerified();
        Csrf::requireValid();
        $org = trim((string) ($_POST['organisation_name'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        $desc = trim((string) ($_POST['description'] ?? ''));
        if ($org === '' || $title === '' || strlen($desc) < 20) {
            Response::flash('error', 'Organisation, exhibit title, and a short description (20+ characters) are required.');
            Response::redirect('/app/exhibit/apply');
        }
        $type = (string) ($_POST['exhibit_type'] ?? 'product');
        $allowed = ['product', 'service', 'demo', 'startup_booth', 'university_showcase', 'other'];
        if (!in_array($type, $allowed, true)) {
            $type = 'product';
        }
        Database::query(
            'INSERT INTO exhibit_applications
             (user_id, event_id, organisation_name, contact_name, contact_email, contact_phone, exhibit_type, title, description, launching_at_forum, space_preference, website, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'submitted\', NOW(), NOW())',
            [
                $user['id'],
                \App\Domain\EventContext::id(),
                $org,
                trim((string) ($_POST['contact_name'] ?? ($user['first_name'] . ' ' . $user['last_name']))),
                trim((string) ($_POST['contact_email'] ?? $user['email'])),
                trim((string) ($_POST['contact_phone'] ?? ($user['phone'] ?? ''))),
                $type,
                $title,
                $desc,
                isset($_POST['launching_at_forum']) ? 1 : 0,
                trim((string) ($_POST['space_preference'] ?? '')) ?: null,
                trim((string) ($_POST['website'] ?? '')) ?: null,
            ]
        );
        $appId = (int) Database::lastId();
        \App\Domain\AuditLog::record('exhibit.apply', 'exhibit_application', $appId, [
            'title' => $title,
        ], (int) $user['id']);
        \App\Notify\Notifier::inApp((int) $user['id'], 'Exhibit application received', 'Your exhibit application is awaiting admin approval.', '/app/exhibit/apply');
        $admins = Database::fetchAll(
            "SELECT DISTINCT u.id FROM users u
             INNER JOIN model_has_roles mhr ON mhr.user_id = u.id
             INNER JOIN roles r ON r.id = mhr.role_id
             WHERE r.slug IN ('super_admin','organizer') AND u.deleted_at IS NULL"
        );
        foreach ($admins as $admin) {
            \App\Notify\Notifier::notifyUser(
                (int) $admin['id'],
                'Exhibit application to review',
                $org . ' submitted “' . $title . '” for exhibition approval.',
                '/dashboard',
                ['in_app']
            );
        }
        Response::flash('success', 'Exhibit application submitted. An admin must approve it before a booth is assigned.');
        Response::redirect('/app/exhibit/apply');
    }
}
