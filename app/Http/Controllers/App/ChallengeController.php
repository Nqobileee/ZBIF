<?php
declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Auth\Auth;
use App\Domain\ChallengeStateMachine;
use App\Domain\EventContext;
use App\Domain\MatchingService;
use App\Rbac\Gate;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\Str;
use App\Support\View;

final class ChallengeController
{
    public function index(): void
    {
        $user = Auth::requireLogin();
        $uid = (int) $user['id'];
        $mine = Database::fetchAll('SELECT * FROM challenges WHERE created_by = ? ORDER BY updated_at DESC', [$uid]);
        $published = Database::fetchAll(
            "SELECT c.*, o.name AS org_name FROM challenges c
             INNER JOIN organizations o ON o.id = c.owner_org_id
             WHERE c.status = 'published'
             ORDER BY published_at DESC"
        );
        $focus = $this->userFocusTerms($uid);
        $matched = $this->filterByFocus($published, $focus);
        View::make('app/challenges/index', [
            'title' => 'Challenges',
            'mine' => $mine,
            'published' => $matched,
            'focus' => $focus,
            'totalPublished' => count($published),
        ], 'layouts/app');
    }

    /** @return list<string> */
    private function userFocusTerms(int $userId): array
    {
        $terms = [];
        $org = Database::fetch(
            'SELECT o.industry, o.type FROM organizations o
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
        return array_values(array_unique(array_filter(array_map(static fn ($t) => trim(mb_strtolower((string) $t)), $terms))));
    }

    /** @param list<array<string,mixed>> $rows @param list<string> $focus */
    private function filterByFocus(array $rows, array $focus): array
    {
        if (!$focus) {
            return $rows;
        }
        $scored = [];
        foreach ($rows as $c) {
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
        return $scored ?: $rows;
    }

    public function create(): void
    {
        Gate::authorize('challenges.submit');
        Auth::requireVerified();
        View::make('app/challenges/create', ['title' => 'Submit a challenge'], 'layouts/app');
    }

    public function store(): void
    {
        Gate::authorize('challenges.submit');
        Csrf::requireValid();
        $user = Auth::requireVerified();
        $org = Database::fetch(
            'SELECT o.* FROM organizations o INNER JOIN organization_user ou ON ou.organization_id = o.id WHERE ou.user_id = ? LIMIT 1',
            [$user['id']]
        );
        if (!$org) {
            Response::flash('error', 'Join or create an organization first.');
            Response::redirect('/app/challenges');
        }
        $title = trim($_POST['title'] ?? '');
        $slug = Str::slug($title) . '-' . $user['id'] . '-' . time();
        Database::query(
            'INSERT INTO challenges (event_id, owner_org_id, created_by, title, slug, problem_statement, sector, category, desired_outcome, constraints_text, timeline, engagement_type, visibility, budget_band, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'submitted\', NOW(), NOW())',
            [
                EventContext::id(),
                $org['id'],
                $user['id'],
                $title,
                $slug,
                trim($_POST['problem_statement'] ?? ''),
                trim($_POST['sector'] ?? 'General'),
                trim($_POST['category'] ?? 'Manufacturing'),
                trim($_POST['desired_outcome'] ?? ''),
                trim($_POST['constraints_text'] ?? ''),
                trim($_POST['timeline'] ?? ''),
                trim($_POST['engagement_type'] ?? 'partnership'),
                ($_POST['visibility'] ?? 'public') === 'private_invite' ? 'private_invite' : 'public',
                trim($_POST['budget_band'] ?? '') ?: null,
            ]
        );
        $id = (int) Database::lastId();
        Database::query(
            'INSERT INTO challenge_state_events (challenge_id, from_status, to_status, actor_id, notes, created_at) VALUES (?, NULL, \'submitted\', ?, NULL, NOW())',
            [$id, $user['id']]
        );
        MatchingService::onChallengeSaved($id);
        Response::flash('success', 'Challenge submitted for screening.');
        Response::redirect('/app/challenges/' . $id);
    }

    public function show(string $id): void
    {
        Auth::requireLogin();
        $c = Database::fetch(
            'SELECT c.*, o.name AS org_name FROM challenges c INNER JOIN organizations o ON o.id = c.owner_org_id WHERE c.id = ?',
            [(int) $id]
        );
        if (!$c) {
            http_response_code(404);
            View::make('public/404', ['title' => 'Not found'], 'layouts/app');
            return;
        }
        $matches = Database::fetchAll(
            'SELECT m.*, s.name AS solution_name, s.stage FROM matches m INNER JOIN solutions s ON s.id = m.solution_id WHERE m.challenge_id = ? ORDER BY m.score DESC',
            [(int) $id]
        );
        $solutions = Database::fetchAll('SELECT * FROM solutions WHERE challenge_id = ?', [(int) $id]);
        View::make('app/challenges/show', [
            'title' => $c['title'],
            'challenge' => $c,
            'matches' => $matches,
            'solutions' => $solutions,
        ], 'layouts/app');
    }

    public function claim(string $id): void
    {
        Gate::authorize('solutions.create');
        Csrf::requireValid();
        $user = Auth::requireVerified();
        $c = Database::fetch('SELECT * FROM challenges WHERE id = ? AND status = \'published\'', [(int) $id]);
        if (!$c) {
            Response::flash('error', 'Challenge is not available to claim.');
            Response::redirect('/app/challenges');
        }
        $org = Database::fetch(
            'SELECT o.* FROM organizations o INNER JOIN organization_user ou ON ou.organization_id = o.id WHERE ou.user_id = ? LIMIT 1',
            [$user['id']]
        );
        $name = trim($_POST['solution_name'] ?? ('Solution for ' . $c['title']));
        Database::query(
            'INSERT INTO solutions (event_id, owner_org_id, created_by, challenge_id, name, slug, sector, stage, description, problem_solved, is_published, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, \'prototype\', ?, ?, 1, NOW(), NOW())',
            [
                EventContext::id(),
                $org['id'] ?? 1,
                $user['id'],
                $c['id'],
                $name,
                Str::slug($name) . '-' . $user['id'],
                $c['sector'],
                trim($_POST['description'] ?? 'In development'),
                $c['problem_statement'],
            ]
        );
        $sid = (int) Database::lastId();
        ChallengeStateMachine::transition((int) $c['id'], 'allocated', (int) $user['id'], 'Claimed by innovator');
        Database::query(
            'INSERT INTO development_workspaces (challenge_id, solution_id, status, created_at, updated_at) VALUES (?, ?, \'active\', NOW(), NOW())',
            [$c['id'], $sid]
        );
        $wid = (int) Database::lastId();
        foreach (['Discovery', 'Prototype', 'Pilot readiness', 'Pitch deck'] as $i => $title) {
            Database::query(
                'INSERT INTO workspace_milestones (workspace_id, title, status, sort_order, created_at, updated_at) VALUES (?, ?, \'pending\', ?, NOW(), NOW())',
                [$wid, $title, $i]
            );
        }
        ChallengeStateMachine::transition((int) $c['id'], 'in_development', (int) $user['id'], 'Workspace opened');
        Response::flash('success', 'Challenge claimed. Your development workspace is ready.');
        Response::redirect('/app/workspaces/' . $wid);
    }

    public function expressInterest(string $id): void
    {
        Auth::requireVerified();
        Csrf::requireValid();
        $c = Database::fetch('SELECT * FROM challenges WHERE id = ?', [(int) $id]);
        if (!$c) {
            Response::flash('error', 'Challenge not found.');
            Response::redirect('/app/challenges');
        }
        Database::query(
            'INSERT INTO connection_requests (from_user_id, to_user_id, challenge_id, message, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, \'pending\', NOW(), NOW())',
            [Auth::id(), $c['created_by'], $c['id'], trim($_POST['message'] ?? 'I would like to explore a partnership.')]
        );
        Response::flash('success', 'Interest expressed. Awaiting acceptance to open a deal room.');
        Response::redirect('/app/challenges/' . $id);
    }

    public function recomputeMatches(string $id): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        MatchingService::persistSuggestions((int) $id);
        Response::flash('success', 'Matches recomputed.');
        Response::redirect('/app/challenges/' . $id);
    }
}
