<?php
declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Auth\Auth;
use App\Domain\ChallengeStateMachine;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\View;

final class WorkspaceController
{
    public function index(): void
    {
        Auth::requireLogin();
        $rows = Database::fetchAll(
            'SELECT w.*, c.title AS challenge_title, s.name AS solution_name
             FROM development_workspaces w
             INNER JOIN challenges c ON c.id = w.challenge_id
             INNER JOIN solutions s ON s.id = w.solution_id
             ORDER BY w.updated_at DESC'
        );
        View::make('app/workspaces/index', ['title' => 'Development workspaces', 'workspaces' => $rows], 'layouts/app');
    }

    public function show(string $id): void
    {
        Auth::requireLogin();
        $w = Database::fetch(
            'SELECT w.*, c.title AS challenge_title, c.id AS challenge_id, s.name AS solution_name
             FROM development_workspaces w
             INNER JOIN challenges c ON c.id = w.challenge_id
             INNER JOIN solutions s ON s.id = w.solution_id
             WHERE w.id = ?',
            [(int) $id]
        );
        if (!$w) {
            http_response_code(404);
            View::make('public/404', ['title' => 'Not found'], 'layouts/app');
            return;
        }
        View::make('app/workspaces/show', [
            'title' => 'Workspace',
            'workspace' => $w,
            'mentors' => Database::fetchAll(
                'SELECT wm.*, u.first_name, u.last_name FROM workspace_mentors wm INNER JOIN users u ON u.id = wm.user_id WHERE workspace_id = ?',
                [(int) $id]
            ),
            'milestones' => Database::fetchAll('SELECT * FROM workspace_milestones WHERE workspace_id = ? ORDER BY sort_order', [(int) $id]),
            'feedback' => Database::fetchAll(
                'SELECT f.*, u.first_name, u.last_name FROM workspace_feedback f INNER JOIN users u ON u.id = f.user_id WHERE workspace_id = ? ORDER BY f.id ASC',
                [(int) $id]
            ),
            'coaching' => Database::fetchAll('SELECT * FROM coaching_sessions WHERE workspace_id = ? ORDER BY starts_at', [(int) $id]),
        ], 'layouts/app');
    }

    public function addFeedback(string $id): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        Database::query(
            'INSERT INTO workspace_feedback (workspace_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())',
            [(int) $id, Auth::id(), trim($_POST['body'] ?? '')]
        );
        Response::redirect('/app/workspaces/' . $id);
    }

    public function updateMilestone(string $id): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        $mid = (int) ($_POST['milestone_id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        if (!in_array($status, ['pending', 'in_progress', 'done'], true)) {
            $status = 'pending';
        }
        Database::query('UPDATE workspace_milestones SET status = ?, updated_at = NOW() WHERE id = ? AND workspace_id = ?', [$status, $mid, (int) $id]);
        Response::redirect('/app/workspaces/' . $id);
    }

    public function markReady(string $id): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        $w = Database::fetch('SELECT * FROM development_workspaces WHERE id = ?', [(int) $id]);
        if ($w) {
            ChallengeStateMachine::transition((int) $w['challenge_id'], 'solution_ready', Auth::id(), 'Marked ready from workspace');
            Database::query('UPDATE development_workspaces SET status = \'completed\', updated_at = NOW() WHERE id = ?', [(int) $id]);
        }
        Response::flash('success', 'Solution marked ready to present.');
        Response::redirect('/app/workspaces/' . $id);
    }

    public function bookCoaching(string $id): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        Database::query(
            'INSERT INTO coaching_sessions (workspace_id, mentor_id, starts_at, ends_at, status, notes, created_at)
             VALUES (?, ?, ?, ?, \'scheduled\', ?, NOW())',
            [
                (int) $id,
                (int) ($_POST['mentor_id'] ?? Auth::id()),
                $_POST['starts_at'] ?? date('Y-m-d H:i:s', strtotime('+2 days')),
                $_POST['ends_at'] ?? date('Y-m-d H:i:s', strtotime('+2 days +1 hour')),
                trim($_POST['notes'] ?? 'Pitch coaching'),
            ]
        );
        Response::flash('success', 'Coaching session booked.');
        Response::redirect('/app/workspaces/' . $id);
    }
}
