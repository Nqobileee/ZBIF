<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Ai\PublicAssist;
use App\Ai\RegistrationChat;
use App\Ai\LlmClient;
use App\Auth\Auth;
use App\Domain\MatchingService;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\RateLimiter;
use App\Support\Response;

final class AiController
{
    public function chatStart(): void
    {
        $this->rate();
        Response::json(RegistrationChat::start($_POST['mode'] ?? 'registration'));
    }

    public function chatMessage(): void
    {
        $this->rate();
        $token = $_POST['session_token'] ?? '';
        $message = trim($_POST['message'] ?? '');
        if ($token === '' || $message === '') {
            Response::json(['error' => ['code' => 'invalid', 'message' => 'session_token and message required', 'details' => []]], 422);
            return;
        }
        Response::json(RegistrationChat::message($token, $message));
    }

    public function ask(): void
    {
        $this->rate();
        $q = trim($_POST['question'] ?? $_GET['q'] ?? '');
        if ($q === '') {
            Response::json(['error' => ['code' => 'invalid', 'message' => 'question required', 'details' => []]], 422);
            return;
        }
        // Public assist only (sanitized). Internal RAG remains for staff tooling if needed later.
        Response::json(PublicAssist::answer($q));
    }

    public function health(): void
    {
        Response::json(['providers' => LlmClient::health()]);
    }

    public function recomputeMatch(string $challengeId): void
    {
        if (!Auth::check()) {
            Response::json(['error' => ['code' => 'unauthorized', 'message' => 'Login required', 'details' => []]], 401);
            return;
        }
        MatchingService::persistSuggestions((int) $challengeId);
        $rows = Database::fetchAll(
            'SELECT m.*, s.name AS solution_name FROM matches m
             INNER JOIN solutions s ON s.id = m.solution_id
             WHERE m.challenge_id = ? ORDER BY m.score DESC',
            [(int) $challengeId]
        );
        Response::json(['matches' => $rows]);
    }

    private function rate(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        if (!RateLimiter::attempt('ai:' . $ip, 60, 60)) {
            Response::json(['error' => ['code' => 'rate_limited', 'message' => 'Too many AI requests', 'details' => []]], 429);
            exit;
        }
    }
}
