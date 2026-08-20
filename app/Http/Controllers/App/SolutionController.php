<?php
declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Auth\Auth;
use App\Domain\EventContext;
use App\Rbac\Gate;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\Str;
use App\Support\View;

final class SolutionController
{
    public function index(): void
    {
        Auth::requireLogin();
        $uid = (int) Auth::id();
        $roles = Gate::rolesFor($uid);
        $isCorporate = in_array('corporate', $roles, true) || in_array('government', $roles, true);

        if ($isCorporate) {
            $inbound = Database::fetchAll(
                "SELECT m.score, m.rationale, m.solution_id, m.challenge_id,
                        s.name AS solution_name, s.description, s.stage, s.sector, s.created_by,
                        c.title AS challenge_title,
                        u.first_name, u.last_name
                 FROM matches m
                 INNER JOIN challenges c ON c.id = m.challenge_id
                 INNER JOIN solutions s ON s.id = m.solution_id
                 INNER JOIN users u ON u.id = s.created_by
                 INNER JOIN organization_user ou ON ou.organization_id = c.owner_org_id
                 WHERE ou.user_id = ? AND c.status = 'published'
                 ORDER BY m.score DESC, m.id DESC
                 LIMIT 50",
                [$uid]
            );
            View::make('app/solutions/index', [
                'title' => 'Matched solutions',
                'isCorporate' => true,
                'inbound' => $inbound,
                'solutions' => [],
                'hidePageHead' => true,
            ], 'layouts/app');
            return;
        }

        $mine = Database::fetchAll(
            'SELECT s.*, c.title AS challenge_title
             FROM solutions s
             LEFT JOIN challenges c ON c.id = s.challenge_id
             WHERE s.created_by = ?
             ORDER BY s.updated_at DESC',
            [$uid]
        );
        View::make('app/solutions/index', [
            'title' => 'My solutions',
            'isCorporate' => false,
            'solutions' => $mine,
            'inbound' => [],
            'hidePageHead' => true,
        ], 'layouts/app');
    }

    public function create(): void
    {
        Gate::authorize('solutions.create');
        Auth::requireVerified();
        $challengeId = (int) ($_GET['challenge_id'] ?? 0);
        $challenge = null;
        if ($challengeId > 0) {
            $challenge = Database::fetch(
                "SELECT c.*, o.name AS org_name FROM challenges c
                 INNER JOIN organizations o ON o.id = c.owner_org_id
                 WHERE c.id = ? AND c.status = 'published'",
                [$challengeId]
            );
        }
        View::make('app/solutions/create', [
            'title' => $challenge ? 'Apply with a solution' : 'Add solution / product',
            'challenge' => $challenge,
            'hidePageHead' => true,
        ], 'layouts/app');
    }

    public function store(): void
    {
        Gate::authorize('solutions.create');
        Csrf::requireValid();
        $user = Auth::requireVerified();
        $org = Database::fetch(
            'SELECT o.* FROM organizations o INNER JOIN organization_user ou ON ou.organization_id = o.id WHERE ou.user_id = ? LIMIT 1',
            [$user['id']]
        );
        $name = trim($_POST['name'] ?? '');
        $stage = $_POST['stage'] ?? 'prototype';
        if (!in_array($stage, ['idea', 'prototype', 'pilot', 'market_ready', 'scaling'], true)) {
            $stage = 'prototype';
        }
        $challengeId = (int) ($_POST['challenge_id'] ?? 0) ?: null;
        $sector = trim($_POST['sector'] ?? 'General');
        $problem = trim($_POST['problem_solved'] ?? '');
        if ($challengeId) {
            $c = Database::fetch("SELECT * FROM challenges WHERE id = ? AND status = 'published'", [$challengeId]);
            if ($c) {
                if ($sector === '' || $sector === 'General') {
                    $sector = (string) ($c['sector'] ?? 'General');
                }
                if ($problem === '') {
                    $problem = (string) ($c['problem_statement'] ?? '');
                }
            } else {
                $challengeId = null;
            }
        }
        Database::query(
            'INSERT INTO solutions (event_id, owner_org_id, created_by, challenge_id, name, slug, sector, stage, description, problem_solved, traction, ask_type, is_published, investor_visible, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())',
            [
                EventContext::id(),
                $org['id'] ?? 1,
                $user['id'],
                $challengeId,
                $name,
                Str::slug($name) . '-' . $user['id'] . '-' . time(),
                $sector,
                $stage,
                trim($_POST['description'] ?? ''),
                $problem,
                trim($_POST['traction'] ?? ''),
                trim($_POST['ask_type'] ?? 'pilot'),
                isset($_POST['investor_visible']) ? 1 : 0,
            ]
        );
        $sid = (int) Database::lastId();
        \App\Domain\MatchingService::onSolutionSaved($sid);
        if ($challengeId) {
            $owners = Database::fetchAll(
                'SELECT ou.user_id FROM organization_user ou
                 INNER JOIN challenges c ON c.owner_org_id = ou.organization_id
                 WHERE c.id = ?',
                [$challengeId]
            );
            foreach ($owners as $o) {
                \App\Notify\Notifier::notifyUser(
                    (int) $o['user_id'],
                    'New solution matched',
                    $name . ' was submitted against your challenge.',
                    '/app/solutions',
                    ['in_app']
                );
            }
        }
        Response::flash('success', $challengeId ? 'Solution submitted against the challenge.' : 'Solution published to the innovator directory.');
        Response::redirect('/app/solutions');
    }

    public function edit(string $id): void
    {
        Gate::authorize('solutions.create');
        $user = Auth::requireVerified();
        $solution = Database::fetch('SELECT * FROM solutions WHERE id = ? AND created_by = ?', [(int) $id, $user['id']]);
        if (!$solution) {
            Response::flash('error', 'Solution not found.');
            Response::redirect('/app/solutions');
        }
        View::make('app/solutions/edit', [
            'title' => 'Edit solution',
            'solution' => $solution,
            'hidePageHead' => true,
        ], 'layouts/app');
    }

    public function update(string $id): void
    {
        Gate::authorize('solutions.create');
        Csrf::requireValid();
        $user = Auth::requireVerified();
        $solution = Database::fetch('SELECT * FROM solutions WHERE id = ? AND created_by = ?', [(int) $id, $user['id']]);
        if (!$solution) {
            Response::flash('error', 'Solution not found.');
            Response::redirect('/app/solutions');
        }
        $name = trim($_POST['name'] ?? '');
        $stage = $_POST['stage'] ?? 'prototype';
        if (!in_array($stage, ['idea', 'prototype', 'pilot', 'market_ready', 'scaling'], true)) {
            $stage = 'prototype';
        }
        Database::query(
            'UPDATE solutions SET name = ?, sector = ?, stage = ?, description = ?, problem_solved = ?, traction = ?, ask_type = ?, investor_visible = ?, updated_at = NOW() WHERE id = ? AND created_by = ?',
            [
                $name,
                trim($_POST['sector'] ?? 'General'),
                $stage,
                trim($_POST['description'] ?? ''),
                trim($_POST['problem_solved'] ?? ''),
                trim($_POST['traction'] ?? ''),
                trim($_POST['ask_type'] ?? 'pilot'),
                isset($_POST['investor_visible']) ? 1 : 0,
                (int) $id,
                $user['id'],
            ]
        );
        \App\Domain\MatchingService::onSolutionSaved((int) $id);
        Response::flash('success', 'Solution updated.');
        Response::redirect('/app/solutions');
    }

    public function matches(): void
    {
        Gate::authorize('matching.view');
        $user = Auth::requireLogin();
        $roles = Gate::rolesFor((int) $user['id']);
        $isCorporate = in_array('corporate', $roles, true) || in_array('government', $roles, true);

        if ($isCorporate) {
            $rows = Database::fetchAll(
                "SELECT m.*, c.title AS challenge_title, c.category, s.name AS solution_name
                 FROM matches m
                 INNER JOIN challenges c ON c.id = m.challenge_id
                 INNER JOIN solutions s ON s.id = m.solution_id
                 INNER JOIN organization_user ou ON ou.organization_id = c.owner_org_id
                 WHERE ou.user_id = ?
                 ORDER BY m.score DESC",
                [$user['id']]
            );
            $open = Database::fetchAll(
                "SELECT c.*, o.name AS org_name FROM challenges c
                 INNER JOIN organizations o ON o.id = c.owner_org_id
                 INNER JOIN organization_user ou ON ou.organization_id = o.id
                 WHERE ou.user_id = ? AND c.status = 'published'
                 ORDER BY c.published_at DESC LIMIT 12",
                [$user['id']]
            );
            View::make('app/solutions/matches', [
                'title' => 'Suggested matches',
                'matches' => $rows,
                'open' => $open,
                'hidePageHead' => true,
            ], 'layouts/app');
            return;
        }

        $solutions = Database::fetchAll('SELECT id FROM solutions WHERE created_by = ?', [$user['id']]);
        $ids = array_column($solutions, 'id') ?: [0];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::fetchAll(
            "SELECT m.*, c.title AS challenge_title, c.category, s.name AS solution_name
             FROM matches m
             INNER JOIN challenges c ON c.id = m.challenge_id
             INNER JOIN solutions s ON s.id = m.solution_id
             WHERE m.solution_id IN ($placeholders)
             ORDER BY m.score DESC",
            $ids
        );
        $open = Database::fetchAll("SELECT c.*, o.name AS org_name FROM challenges c INNER JOIN organizations o ON o.id = c.owner_org_id WHERE c.status = 'published' ORDER BY c.published_at DESC LIMIT 12");
        View::make('app/solutions/matches', [
            'title' => 'Suggested matches',
            'matches' => $rows,
            'open' => $open,
            'hidePageHead' => true,
        ], 'layouts/app');
    }
}
