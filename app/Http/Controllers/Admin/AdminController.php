<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Auth\Auth;
use App\Domain\ChallengeStateMachine;
use App\Domain\ImpactMetrics;
use App\Domain\MatchingService;
use App\Domain\AuditLog;
use App\Notify\Notifier;
use App\Rbac\Gate;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\View;

final class AdminController
{
    private function guard(): array
    {
        $user = Auth::requireAdmin();
        return $user;
    }

    public function dashboard(): void
    {
        $this->guard();
        View::make('admin/dashboard', [
            'title' => 'Admin',
            'metrics' => ImpactMetrics::forEvent(),
            'pendingChallenges' => (int) (Database::fetch("SELECT COUNT(*) AS c FROM challenges WHERE status IN ('submitted','screening')")['c'] ?? 0),
            'pendingRegs' => (int) (Database::fetch("SELECT COUNT(*) AS c FROM participation_profiles WHERE status = 'submitted'")['c'] ?? 0),
            'queue' => (int) (Database::fetch('SELECT COUNT(*) AS c FROM jobs WHERE completed_at IS NULL AND failed_at IS NULL')['c'] ?? 0),
        ], 'layouts/admin');
    }

    public function screening(): void
    {
        $this->guard();
        Gate::authorize('challenges.screen');
        $rows = Database::fetchAll(
            "SELECT c.*, o.name AS org_name FROM challenges c
             INNER JOIN organizations o ON o.id = c.owner_org_id
             WHERE c.status IN ('submitted','screening','prioritised')
             ORDER BY c.created_at ASC"
        );
        View::make('admin/screening', ['title' => 'Challenge screening', 'challenges' => $rows], 'layouts/admin');
    }

    public function screenAction(): void
    {
        $this->guard();
        Gate::authorize('challenges.screen');
        Csrf::requireValid();
        $id = (int) ($_POST['challenge_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        $score = $_POST['screening_score'] !== '' ? (float) $_POST['screening_score'] : null;
        if ($score !== null) {
            Database::query('UPDATE challenges SET screening_score = ?, screening_notes = ?, updated_at = NOW() WHERE id = ?', [$score, $notes, $id]);
        }
        $map = [
            'to_screening' => 'screening',
            'prioritise' => 'prioritised',
            'publish' => 'published',
            'allocate' => 'allocated',
            'archive' => 'archived',
        ];
        if (isset($map[$action])) {
            $c = Database::fetch('SELECT status FROM challenges WHERE id = ?', [$id]);
            // allow jump screening->published via prioritised if needed
            try {
                if ($action === 'publish' && $c && $c['status'] === 'screening') {
                    ChallengeStateMachine::transition($id, 'prioritised', Auth::id(), $notes);
                }
                ChallengeStateMachine::transition($id, $map[$action], Auth::id(), $notes);
            } catch (\Throwable $e) {
                Response::flash('error', $e->getMessage());
                Response::redirect('/admin/screening');
            }
        }
        if (!empty($_POST['category'])) {
            Database::query('UPDATE challenges SET category = ?, sector = COALESCE(NULLIF(?, \'\'), sector), updated_at = NOW() WHERE id = ?', [
                $_POST['category'],
                $_POST['sector'] ?? '',
                $id,
            ]);
        }
        Response::flash('success', 'Challenge updated.');
        Response::redirect('/admin/screening');
    }

    public function matching(): void
    {
        $this->guard();
        Gate::authorize('matching.manage');
        $challenges = [];
        try {
            $challenges = Database::fetchAll(
                "SELECT id, title, status FROM challenges WHERE status IN ('published','allocated','in_development') ORDER BY updated_at DESC"
            );
        } catch (\Throwable $e) {
            $challenges = [];
        }
        View::make('admin/matching', [
            'title' => 'Matching console',
            'challenges' => $challenges,
            'comingSoon' => true,
        ], 'layouts/admin');
    }

    public function matchingRun(): void
    {
        $this->guard();
        Csrf::requireValid();
        Response::flash('error', 'Match engine is coming soon. Manual screening remains available.');
        Response::redirect('/admin/matching');
    }

    public function assignMentor(): void
    {
        $this->guard();
        Csrf::requireValid();
        Database::query(
            'INSERT INTO workspace_mentors (workspace_id, user_id, notes, created_at) VALUES (?, ?, ?, NOW())',
            [(int) $_POST['workspace_id'], (int) $_POST['user_id'], trim($_POST['notes'] ?? '')]
        );
        \App\Notify\Notifier::inApp((int) $_POST['user_id'], 'Mentor assignment', 'You have been assigned to a development workspace.', '/app/workspaces/' . (int) $_POST['workspace_id']);
        Response::flash('success', 'Mentor assigned.');
        Response::redirect('/admin/matching');
    }

    public function impact(): void
    {
        $this->guard();
        Gate::authorize('analytics.impact');
        $eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : \App\Domain\EventContext::id();
        $metrics = ImpactMetrics::forEvent($eventId);
        $bySector = Database::fetchAll(
            'SELECT category, COUNT(*) AS c FROM challenges WHERE event_id = ? GROUP BY category ORDER BY c DESC',
            [$eventId]
        );
        View::make('admin/impact', [
            'title' => 'Impact dashboard',
            'metrics' => $metrics,
            'bySector' => $bySector,
            'events' => \App\Domain\EventContext::all(),
            'eventId' => $eventId,
        ], 'layouts/admin');
    }

    public function impactExport(): void
    {
        $this->guard();
        Gate::authorize('exports.run');
        $eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : \App\Domain\EventContext::id();
        $metrics = ImpactMetrics::forEvent($eventId);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="zbif-impact-report.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['metric', 'value']);
        foreach ($metrics as $k => $v) {
            fputcsv($out, [$k, $v]);
        }
        fclose($out);
    }

    public function surveys(): void
    {
        $this->guard();
        Gate::authorize('surveys.manage');
        View::make('admin/surveys', [
            'title' => 'Survey builder',
            'surveys' => Database::fetchAll('SELECT * FROM surveys ORDER BY id DESC'),
        ], 'layouts/admin');
    }

    public function surveyStore(): void
    {
        $this->guard();
        Csrf::requireValid();
        $title = trim($_POST['title'] ?? 'Survey');
        $slug = \App\Support\Str::slug($title) . '-' . time();
        Database::query(
            'INSERT INTO surveys (event_id, title, slug, audience, is_anonymous, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, \'open\', NOW(), NOW())',
            [
                \App\Domain\EventContext::id(),
                $title,
                $slug,
                $_POST['audience'] ?? 'all',
                isset($_POST['is_anonymous']) ? 1 : 0,
            ]
        );
        $sid = (int) Database::lastId();
        $prompts = array_filter(array_map('trim', explode("\n", $_POST['questions'] ?? '')));
        foreach ($prompts as $i => $prompt) {
            Database::query(
                'INSERT INTO survey_questions (survey_id, question_type, prompt, is_required, sort_order, created_at)
                 VALUES (?, ?, ?, 1, ?, NOW())',
                [$sid, $_POST['default_type'] ?? 'short_text', $prompt, $i]
            );
        }
        Response::redirect('/admin/surveys/' . $sid . '/edit');
    }

    public function surveyResults(string $id): void
    {
        Response::redirect('/admin/surveys/' . $id . '/analytics');
    }

    public function sponsorshipBoard(): void
    {
        $this->guard();
        Gate::authorize('sponsorship.review');
        $rows = Database::fetchAll(
            'SELECT a.*, u.first_name, u.last_name, u.email FROM sponsorship_applications a
             INNER JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC'
        );
        View::make('admin/sponsorship', ['title' => 'Sponsorship review', 'applications' => $rows], 'layouts/admin');
    }

    public function sponsorshipAction(): void
    {
        $this->guard();
        Csrf::requireValid();
        $status = $_POST['status'] ?? 'under_review';
        $allowed = ['submitted', 'under_review', 'shortlisted', 'sponsored', 'declined'];
        if (!in_array($status, $allowed, true)) {
            $status = 'under_review';
        }
        $appId = (int) $_POST['id'];
        $app = Database::fetch('SELECT * FROM sponsorship_applications WHERE id = ?', [$appId]);
        Database::query(
            'UPDATE sponsorship_applications SET status = ?, reviewer_notes = ?, reviewed_by = ?, updated_at = NOW() WHERE id = ?',
            [$status, trim($_POST['reviewer_notes'] ?? ''), Auth::id(), $appId]
        );
        if ($status === 'sponsored' && $app) {
            $sponsorId = $this->promoteSponsor($app);
            Database::query('UPDATE sponsorship_applications SET sponsor_id = ? WHERE id = ?', [$sponsorId, $appId]);
            Notifier::notifyUser(
                (int) $app['user_id'],
                'Sponsorship approved',
                'Your ZBIF sponsorship was approved. Open the sponsor portal to manage assets.',
                '/app/sponsor-portal'
            );
        }
        AuditLog::record('sponsorship.review', 'sponsorship_application', $appId, ['status' => $status]);
        Response::redirect('/admin/sponsorship');
    }

    /** @param array<string, mixed> $app */
    private function promoteSponsor(array $app): int
    {
        $name = trim((string) ($app['company_name'] ?: 'Sponsor'));
        $tier = (string) ($app['tier_requested'] ?: 'silver');
        $slug = \App\Support\Str::slug($name) . '-' . time();
        $benefits = json_encode([
            'Logo on ZBIF site',
            'Deal Room / exhibition entitlements per tier',
            'Impact report access',
            'Comms mention in newsroom',
        ]);
        Database::query(
            'INSERT INTO sponsors (event_id, name, slug, tier, contribution_usd, logo_path, website, benefits_json, sort_order, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 10, NOW())',
            [
                \App\Domain\EventContext::id(),
                $name,
                $slug,
                $tier,
                (float) ($app['budget_usd'] ?? 0),
                $app['logo_path'] ?? null,
                $app['website'] ?? null,
                $benefits,
            ]
        );
        $sid = (int) Database::lastId();
        $ents = [
            'logo_placement' => 'homepage + sponsors page',
            'deal_room_branding' => $tier === 'deal_room' || in_array($tier, ['platinum', 'gold'], true) ? 'yes' : 'no',
            'leads_export' => 'yes',
            'impact_report' => 'yes',
        ];
        foreach ($ents as $k => $v) {
            Database::query(
                'INSERT INTO sponsor_entitlements (sponsor_id, entitlement_key, entitlement_value, created_at) VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE entitlement_value = VALUES(entitlement_value)',
                [$sid, $k, $v]
            );
        }
        return $sid;
    }

    public function awardsJudge(): void
    {
        $this->guard();
        Gate::authorize('awards.judge');
        $noms = Database::fetchAll(
            'SELECT n.*, c.name AS category_name FROM award_nominations n
             INNER JOIN award_categories c ON c.id = n.category_id ORDER BY n.id DESC'
        );
        View::make('admin/awards', ['title' => 'Awards judging', 'nominations' => $noms], 'layouts/admin');
    }

    public function awardsScore(): void
    {
        $this->guard();
        Csrf::requireValid();
        $rubric = [
            'impact' => (float) ($_POST['rubric_impact'] ?? 0),
            'feasibility' => (float) ($_POST['rubric_feasibility'] ?? 0),
            'innovation' => (float) ($_POST['rubric_innovation'] ?? 0),
            'scalability' => (float) ($_POST['rubric_scalability'] ?? 0),
        ];
        $score = isset($_POST['score']) && $_POST['score'] !== ''
            ? (float) $_POST['score']
            : round(array_sum($rubric) / max(1, count(array_filter($rubric, static fn ($v) => $v > 0))), 2);
        if ($score <= 0 && array_sum($rubric) > 0) {
            $score = round(array_sum($rubric) / 4, 2);
        }
        Database::query(
            'INSERT INTO award_scores (nomination_id, judge_id, score, rubric_json, comments, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE score = VALUES(score), rubric_json = VALUES(rubric_json), comments = VALUES(comments)',
            [(int) $_POST['nomination_id'], Auth::id(), $score, json_encode($rubric), trim($_POST['comments'] ?? '')]
        );
        if (($_POST['make_winner'] ?? '') === '1') {
            Database::query('UPDATE award_nominations SET status = \'winner\' WHERE id = ?', [(int) $_POST['nomination_id']]);
        }
        Response::redirect('/admin/awards');
    }

    public function users(): void
    {
        $this->guard();
        Gate::authorize('users.manage');
        $users = Database::fetchAll(
            'SELECT u.id, u.email, u.first_name, u.last_name, u.is_active, u.created_at,
                    GROUP_CONCAT(r.slug ORDER BY r.slug SEPARATOR ", ") AS roles
             FROM users u
             LEFT JOIN model_has_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             GROUP BY u.id
             ORDER BY u.id DESC LIMIT 300'
        );
        View::make('admin/users', [
            'title' => 'Users',
            'users' => $users,
            'roles' => Database::fetchAll('SELECT * FROM roles ORDER BY name'),
        ], 'layouts/admin');
    }

    public function createUser(): void
    {
        $this->guard();
        Gate::authorize('users.manage');
        Csrf::requireValid();
        $email = strtolower(trim($_POST['email'] ?? ''));
        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'attendee');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::flash('error', 'Valid email is required.');
            Response::redirect('/admin/users');
            return;
        }
        if (Database::fetch('SELECT id FROM users WHERE email = ?', [$email])) {
            Response::flash('error', 'Email already registered.');
            Response::redirect('/admin/users');
            return;
        }
        $errors = \App\Auth\PasswordPolicy::validate($password);
        if ($errors) {
            Response::flash('error', implode(' ', $errors));
            Response::redirect('/admin/users');
            return;
        }
        Database::query(
            'INSERT INTO users (email, password, first_name, last_name, is_active, email_verified_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, 1, NOW(), NOW(), NOW())',
            [$email, password_hash($password, PASSWORD_DEFAULT), $first ?: 'Admin', $last ?: 'User']
        );
        $uid = (int) Database::lastId();
        Gate::assignRole($uid, $role);
        AuditLog::record('user.create', 'user', $uid, ['role' => $role, 'email' => $email]);
        Response::flash('success', 'User created and role assigned.');
        Response::redirect('/admin/users');
    }

    public function organizations(): void
    {
        $this->guard();
        Gate::authorize('users.manage');
        View::make('admin/organizations', [
            'title' => 'Companies / organizations',
            'orgs' => Database::fetchAll(
                'SELECT o.*,
                        (SELECT COUNT(*) FROM organization_user ou WHERE ou.organization_id = o.id) AS member_count
                 FROM organizations o ORDER BY o.name ASC LIMIT 500'
            ),
        ], 'layouts/admin');
    }

    public function reports(): void
    {
        $this->guard();
        Gate::authorize('exports.run');
        $eventId = \App\Domain\EventContext::id();
        $dealStages = Database::fetchAll(
            'SELECT stage, COUNT(*) AS c FROM deal_rooms GROUP BY stage ORDER BY c DESC'
        );
        $regByPersona = Database::fetchAll(
            'SELECT persona, status, COUNT(*) AS c FROM participation_profiles WHERE event_id = ? GROUP BY persona, status',
            [$eventId]
        );
        $usersByRole = Database::fetchAll(
            'SELECT r.name, COUNT(ur.user_id) AS c FROM roles r
             LEFT JOIN model_has_roles ur ON ur.role_id = r.id
             GROUP BY r.id ORDER BY c DESC'
        );
        View::make('admin/reports', [
            'title' => 'Reports hub',
            'metrics' => ImpactMetrics::forEvent($eventId),
            'dealStages' => $dealStages,
            'regByPersona' => $regByPersona,
            'usersByRole' => $usersByRole,
            'orgCount' => (int) (Database::fetch('SELECT COUNT(*) AS c FROM organizations')['c'] ?? 0),
            'sessionCount' => (int) (Database::fetch('SELECT COUNT(*) AS c FROM programme_sessions WHERE event_id = ?', [$eventId])['c'] ?? 0),
        ], 'layouts/admin');
    }

    public function audit(): void
    {
        $this->guard();
        Gate::authorize('audit.view');
        View::make('admin/audit', [
            'title' => 'Audit log',
            'logs' => Database::fetchAll('SELECT * FROM audit_logs ORDER BY id DESC LIMIT 200'),
        ], 'layouts/admin');
    }

    public function cms(): void
    {
        $this->guard();
        Gate::authorize('cms.manage');
        View::make('admin/cms', [
            'title' => 'Content CMS',
            'pages' => Database::fetchAll('SELECT * FROM pages ORDER BY slug'),
            'faqs' => Database::fetchAll('SELECT * FROM faqs ORDER BY sort_order'),
            'news' => Database::fetchAll('SELECT id, title, slug, published_at FROM news_posts ORDER BY id DESC'),
            'testimonials' => Database::fetchAll('SELECT * FROM testimonials ORDER BY sort_order, id DESC'),
            'foresight' => Database::fetchAll('SELECT * FROM foresight_insights ORDER BY sector, sort_order'),
        ], 'layouts/admin');
    }

    public function cmsFaqSave(): void
    {
        $this->guard();
        Csrf::requireValid();
        Database::query(
            'INSERT INTO faqs (question, answer, sort_order, created_at) VALUES (?, ?, ?, NOW())',
            [trim($_POST['question'] ?? ''), trim($_POST['answer'] ?? ''), (int) ($_POST['sort_order'] ?? 0)]
        );
        Response::redirect('/admin/cms');
    }

    public function registrations(): void
    {
        $this->guard();
        $rows = Database::fetchAll(
            'SELECT p.*, u.email, u.first_name, u.last_name FROM participation_profiles p
             INNER JOIN users u ON u.id = p.user_id ORDER BY p.id DESC LIMIT 200'
        );
        View::make('admin/registrations', ['title' => 'Registrations', 'rows' => $rows], 'layouts/admin');
    }

    public function registrationsExport(): void
    {
        $this->guard();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="registrations.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['id', 'email', 'name', 'persona', 'status']);
        $rows = Database::fetchAll(
            'SELECT p.id, u.email, u.first_name, u.last_name, p.persona, p.status FROM participation_profiles p INNER JOIN users u ON u.id = p.user_id'
        );
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'], $r['email'], $r['first_name'] . ' ' . $r['last_name'], $r['persona'], $r['status']]);
        }
        fclose($out);
    }

    public function systemStatus(): void
    {
        $this->guard();
        $pay = new \App\Payments\PayNowGateway();
        View::make('admin/system', [
            'title' => 'System status',
            'ai' => \App\Ai\LlmClient::health(),
            'queue' => (int) (Database::fetch('SELECT COUNT(*) AS c FROM jobs WHERE completed_at IS NULL AND failed_at IS NULL')['c'] ?? 0),
            'failedJobs' => Database::fetchAll('SELECT id, job_type, last_error, failed_at FROM jobs WHERE failed_at IS NOT NULL ORDER BY id DESC LIMIT 20'),
            'payments' => $pay->health(),
            'flags' => Database::fetchAll('SELECT * FROM feature_flags ORDER BY flag_key'),
        ], 'layouts/admin');
    }

    public function dealsOversight(): void
    {
        $this->guard();
        $rooms = Database::fetchAll(
            'SELECT r.*,
                    (SELECT COUNT(*) FROM deal_room_participants p WHERE p.deal_room_id = r.id) AS participant_count
             FROM deal_rooms r ORDER BY r.updated_at DESC'
        );
        $byStage = [];
        foreach ($rooms as $r) {
            $st = (string) $r['stage'];
            $byStage[$st] = ($byStage[$st] ?? 0) + 1;
        }
        View::make('admin/deals', [
            'title' => 'Deal rooms status',
            'rooms' => $rooms,
            'byStage' => $byStage,
            'outcomes' => Database::fetchAll('SELECT * FROM deal_outcomes ORDER BY id DESC LIMIT 50'),
            'stages' => \App\Domain\DealPipeline::STAGES,
        ], 'layouts/admin');
    }

    public function dealForceStage(): void
    {
        $this->guard();
        Gate::authorize('deals.manage');
        Csrf::requireValid();
        $id = (int) ($_POST['deal_room_id'] ?? 0);
        \App\Domain\DealPipeline::move($id, (string) ($_POST['stage'] ?? 'in_discussion'), Auth::id(), 'Admin intervene: ' . trim($_POST['notes'] ?? ''));
        Response::flash('success', 'Deal stage updated by admin.');
        Response::redirect('/admin/deals');
    }

    public function dealsExport(): void
    {
        $this->guard();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="deal-outcomes.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['deal_room_id', 'title', 'stage', 'outcome_type', 'amount_usd', 'public', 'created_at']);
        $rows = Database::fetchAll(
            'SELECT o.*, r.title, r.stage FROM deal_outcomes o INNER JOIN deal_rooms r ON r.id = o.deal_room_id ORDER BY o.id DESC'
        );
        foreach ($rows as $r) {
            fputcsv($out, [$r['deal_room_id'], $r['title'], $r['stage'], $r['outcome_type'], $r['amount_usd'], $r['announced_publicly'], $r['created_at']]);
        }
        fclose($out);
    }

    public function inquiries(): void
    {
        $this->guard();
        $magnets = [];
        try {
            $magnets = Database::fetchAll('SELECT * FROM lead_magnet_captures ORDER BY id DESC LIMIT 100');
        } catch (\Throwable $e) {
            $magnets = [];
        }
        View::make('admin/inquiries', [
            'title' => 'Inquiries inbox',
            'rows' => Database::fetchAll('SELECT * FROM partner_inquiries ORDER BY id DESC LIMIT 200'),
            'magnets' => $magnets,
        ], 'layouts/admin');
    }

    public function inquiryAction(): void
    {
        $this->guard();
        Csrf::requireValid();
        Database::query('UPDATE partner_inquiries SET status = ? WHERE id = ?', [$_POST['status'] ?? 'contacted', (int) ($_POST['id'] ?? 0)]);
        Response::redirect('/admin/inquiries');
    }

    public function booths(): void
    {
        $this->guard();
        View::make('admin/booths', [
            'title' => 'Exhibition booths',
            'booths' => Database::fetchAll(
                'SELECT b.*, o.name AS org_name, u.email AS owner_email
                 FROM exhibitor_booths b
                 INNER JOIN organizations o ON o.id = b.organization_id
                 INNER JOIN users u ON u.id = b.owner_user_id
                 ORDER BY b.id DESC'
            ),
            'orgs' => Database::fetchAll('SELECT id, name FROM organizations ORDER BY name'),
            'users' => Database::fetchAll('SELECT id, email, first_name, last_name FROM users WHERE is_active = 1 ORDER BY first_name LIMIT 300'),
        ], 'layouts/admin');
    }

    public function boothAssign(): void
    {
        $this->guard();
        Csrf::requireValid();
        Database::query(
            'INSERT INTO exhibitor_booths (event_id, organization_id, owner_user_id, booth_code, name, description, launching_at_forum, floor_x, floor_y, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, NOW(), NOW())',
            [
                \App\Domain\EventContext::id(),
                (int) $_POST['organization_id'],
                (int) $_POST['owner_user_id'],
                trim($_POST['booth_code'] ?? 'B-' . time()),
                trim($_POST['name'] ?? 'Booth'),
                trim($_POST['description'] ?? ''),
                $_POST['floor_x'] !== '' ? (float) $_POST['floor_x'] : null,
                $_POST['floor_y'] !== '' ? (float) $_POST['floor_y'] : null,
            ]
        );
        Response::flash('success', 'Booth assigned.');
        Response::redirect('/admin/booths');
    }

    public function registrationAction(): void
    {
        $this->guard();
        Csrf::requireValid();
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $status = $_POST['status'] ?? 'approved';
        if (!in_array($status, ['approved', 'rejected', 'submitted'], true)) {
            $status = 'approved';
        }
        foreach ($ids as $id) {
            Database::query('UPDATE participation_profiles SET status = ?, updated_at = NOW() WHERE id = ?', [$status, (int) $id]);
            AuditLog::record('registration.status', 'participation_profile', (int) $id, ['status' => $status]);
        }
        Response::flash('success', 'Updated ' . count($ids) . ' registration(s).');
        Response::redirect('/admin/registrations');
    }

    public function assignUserRole(): void
    {
        $this->guard();
        Gate::authorize('users.manage');
        Csrf::requireValid();
        Gate::assignRole((int) ($_POST['user_id'] ?? 0), (string) ($_POST['role'] ?? 'attendee'));
        if (isset($_POST['is_active'])) {
            Database::query('UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?', [(int) $_POST['is_active'], (int) $_POST['user_id']]);
        }
        AuditLog::record('user.role_assign', 'user', (int) $_POST['user_id'], ['role' => $_POST['role'] ?? '']);
        Response::flash('success', 'User updated.');
        Response::redirect('/admin/users');
    }

    public function toggleFlag(): void
    {
        $this->guard();
        Csrf::requireValid();
        $key = (string) ($_POST['flag_key'] ?? '');
        $row = Database::fetch('SELECT * FROM feature_flags WHERE flag_key = ?', [$key]);
        if ($row) {
            $next = (int) $row['is_enabled'] ? 0 : 1;
            Database::query('UPDATE feature_flags SET is_enabled = ?, updated_at = NOW() WHERE id = ?', [$next, $row['id']]);
        }
        Response::redirect('/admin/system');
    }

    public function cmsNewsSave(): void
    {
        $this->guard();
        Csrf::requireValid();
        $title = trim($_POST['title'] ?? 'News');
        $slug = \App\Support\Str::slug($title) . '-' . time();
        Database::query(
            'INSERT INTO news_posts (title, slug, excerpt, body, published_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW(), NOW())',
            [$title, $slug, trim($_POST['excerpt'] ?? ''), trim($_POST['body'] ?? '')]
        );
        Response::redirect('/admin/cms');
    }

    public function cmsPageSave(): void
    {
        $this->guard();
        Csrf::requireValid();
        $slug = \App\Support\Str::slug(trim($_POST['slug'] ?? 'page'));
        $title = trim($_POST['title'] ?? 'Page');
        $existing = Database::fetch('SELECT id FROM pages WHERE slug = ?', [$slug]);
        if ($existing) {
            Database::query('UPDATE pages SET title = ?, meta_description = ?, updated_at = NOW() WHERE id = ?', [
                $title,
                trim($_POST['meta_description'] ?? ''),
                $existing['id'],
            ]);
            $pageId = (int) $existing['id'];
        } else {
            Database::query(
                'INSERT INTO pages (slug, title, meta_description, status, created_at, updated_at) VALUES (?, ?, ?, \'published\', NOW(), NOW())',
                [$slug, $title, trim($_POST['meta_description'] ?? '')]
            );
            $pageId = (int) Database::lastId();
        }
        if (trim($_POST['body'] ?? '') !== '') {
            Database::query(
                'INSERT INTO content_blocks (page_id, block_key, content_json, sort_order, updated_at)
                 VALUES (?, \'body\', ?, 0, NOW())',
                [$pageId, json_encode(['html' => trim($_POST['body'])])]
            );
        }
        Response::flash('success', 'Page saved.');
        Response::redirect('/admin/cms');
    }

    public function cmsTestimonialSave(): void
    {
        $this->guard();
        Csrf::requireValid();
        Database::query(
            'INSERT INTO testimonials (quote, author_name, author_role, org_name, is_published, sort_order, created_at)
             VALUES (?, ?, ?, ?, 1, ?, NOW())',
            [
                trim($_POST['quote'] ?? ''),
                trim($_POST['author_name'] ?? ''),
                trim($_POST['author_role'] ?? ''),
                trim($_POST['org_name'] ?? ''),
                (int) ($_POST['sort_order'] ?? 0),
            ]
        );
        Response::flash('success', 'Testimonial published.');
        \App\Support\FileCache::forget('home_testimonials');
        Response::redirect('/admin/cms');
    }

    public function cmsForesightSave(): void
    {
        $this->guard();
        Csrf::requireValid();
        Database::query(
            'INSERT INTO foresight_insights (sector, title, summary, body, horizon, is_published, sort_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())',
            [
                trim($_POST['sector'] ?? 'General'),
                trim($_POST['title'] ?? 'Insight'),
                trim($_POST['summary'] ?? ''),
                trim($_POST['body'] ?? ''),
                trim($_POST['horizon'] ?? 'near'),
                (int) ($_POST['sort_order'] ?? 0),
            ]
        );
        Response::flash('success', 'Foresight insight added.');
        \App\Support\FileCache::forget('foresight_public');
        Response::redirect('/admin/cms');
    }

    public function comms(): void
    {
        $this->guard();
        Gate::authorize('comms.send');
        View::make('admin/comms', [
            'title' => 'Comms center',
            'roles' => Database::fetchAll('SELECT * FROM roles ORDER BY name'),
        ], 'layouts/admin');
    }

    public function commsSend(): void
    {
        $this->guard();
        Gate::authorize('comms.send');
        Csrf::requireValid();
        $role = (string) ($_POST['role'] ?? '');
        $channel = (string) ($_POST['channel'] ?? 'in_app');
        $title = trim($_POST['title'] ?? 'ZBIF update');
        $body = trim($_POST['body'] ?? '');
        $users = $role !== ''
            ? Database::fetchAll(
                'SELECT u.* FROM users u
                 INNER JOIN model_has_roles mhr ON mhr.user_id = u.id
                 INNER JOIN roles r ON r.id = mhr.role_id
                 WHERE r.slug = ? AND u.deleted_at IS NULL',
                [$role]
            )
            : Database::fetchAll('SELECT * FROM users WHERE deleted_at IS NULL AND is_active = 1 LIMIT 500');
        foreach ($users as $u) {
            if ($channel === 'email') {
                \App\Notify\Notifier::sendEmail((string) $u['email'], $title, '<p>' . htmlspecialchars($body) . '</p>');
            } elseif ($channel === 'sms' && !empty($u['phone'])) {
                \App\Notify\Notifier::sendSms((string) $u['phone'], $title . ': ' . $body);
            } else {
                \App\Notify\Notifier::inApp((int) $u['id'], $title, $body);
            }
        }
        AuditLog::record('comms.send', null, null, ['channel' => $channel, 'role' => $role, 'count' => count($users)]);
        Response::flash('success', 'Message queued/sent to ' . count($users) . ' recipients.');
        Response::redirect('/admin/comms');
    }

    public function impactPdf(): void
    {
        $this->guard();
        $eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : \App\Domain\EventContext::id();
        $metrics = ImpactMetrics::forEvent($eventId);
        $pdf = new \App\Support\SimplePdf('ZBIF Impact Report');
        $pdf->heading('ZBIF InnovaMatch Impact Report');
        $pdf->text('Edition #' . $eventId . '. Generated ' . date('Y-m-d H:i'));
        $pdf->spacer();
        foreach ($metrics as $k => $v) {
            $pdf->text(str_replace('_', ' ', (string) $k) . ': ' . (string) $v);
        }
        $pdf->footerBrand();
        $pdf->stream('zbif-impact-report.pdf');
    }

    public function surveyExport(string $id): void
    {
        $this->guard();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="survey-' . $id . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['response_id', 'question', 'answer_text', 'numeric_value']);
        $rows = Database::fetchAll(
            'SELECT a.response_id, q.prompt, a.answer_text, a.numeric_value
             FROM survey_answers a
             INNER JOIN survey_questions q ON q.id = a.question_id
             INNER JOIN survey_responses r ON r.id = a.response_id
             WHERE r.survey_id = ?',
            [(int) $id]
        );
        foreach ($rows as $r) {
            fputcsv($out, [$r['response_id'], $r['prompt'], $r['answer_text'], $r['numeric_value']]);
        }
        fclose($out);
    }

    public function surveyPdf(string $id): void
    {
        $this->guard();
        $survey = Database::fetch('SELECT * FROM surveys WHERE id = ?', [(int) $id]);
        $analytics = \App\Domain\SurveyEngine::analytics((int) $id);
        $pdf = new \App\Support\SimplePdf('ZBIF Survey Report');
        $pdf->heading('Survey: ' . ($survey['title'] ?? 'Survey'));
        $pdf->text('Responses: ' . (int) ($analytics['responses'] ?? 0));
        $pdf->spacer();
        foreach ($analytics['questions'] as $q) {
            $pdf->text($q['prompt'] . ' [' . $q['type'] . ']');
            if ($q['avg'] !== null) {
                $pdf->text('  Average: ' . number_format((float) $q['avg'], 2));
            }
            if ($q['nps'] !== null) {
                $pdf->text('  NPS: ' . $q['nps']);
            }
            foreach ($q['distribution'] as $label => $count) {
                $pdf->text('  ' . $label . ': ' . $count);
            }
            $pdf->spacer(8);
        }
        $pdf->footerBrand();
        $pdf->stream('zbif-survey-' . $id . '.pdf');
    }
}
