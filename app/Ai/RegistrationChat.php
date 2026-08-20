<?php
declare(strict_types=1);

namespace App\Ai;

use App\Support\Database;
use App\Domain\RegistrationService;

final class RegistrationChat
{
    private const PERSONA_SLOTS = [
        'attendee' => ['first_name', 'last_name', 'email', 'phone', 'organization', 'title', 'city', 'country'],
        'corporate' => ['first_name', 'last_name', 'email', 'phone', 'organization', 'title', 'industry', 'challenge_title', 'challenge_problem'],
        'innovator' => ['first_name', 'last_name', 'email', 'phone', 'organization', 'solution_name', 'sector', 'stage', 'problem_solved'],
        'university' => ['first_name', 'last_name', 'email', 'phone', 'institution', 'faculty', 'research_areas'],
        'innovation_hub' => ['first_name', 'last_name', 'email', 'phone', 'organization', 'portfolio_size', 'sectors_supported'],
        'investor' => ['first_name', 'last_name', 'email', 'phone', 'fund_name', 'ticket_range', 'sector_focus'],
        'exhibitor' => ['first_name', 'last_name', 'email', 'phone', 'organization', 'booth_preferences', 'products'],
        'government' => ['first_name', 'last_name', 'email', 'phone', 'organization', 'title', 'agency'],
        'student' => ['first_name', 'last_name', 'email', 'phone', 'institution', 'city'],
    ];

    public static function start(?string $mode = 'registration'): array
    {
        $token = bin2hex(random_bytes(16));
        $messages = [[
            'role' => 'assistant',
            'content' => 'Welcome to ZBIF. I am Nova. I can register you in a few minutes. Which participation type fits you best: Attendee, Corporate, Innovator, University, Innovation Hub, Investor, Exhibitor, Government, or Student?',
        ]];
        Database::query(
            'INSERT INTO chat_sessions (session_token, mode, slots_json, messages_json, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, \'active\', NOW(), NOW())',
            [$token, $mode ?? 'registration', json_encode(new \stdClass()), json_encode($messages)]
        );
        return ['session_token' => $token, 'messages' => $messages, 'complete' => false];
    }

    public static function message(string $token, string $userText): array
    {
        $session = Database::fetch('SELECT * FROM chat_sessions WHERE session_token = ?', [$token]);
        if (!$session) {
            return ['error' => 'Session not found'];
        }
        $messages = json_decode($session['messages_json'] ?: '[]', true) ?: [];
        $slots = json_decode($session['slots_json'] ?: '{}', true) ?: [];
        $messages[] = ['role' => 'user', 'content' => $userText];

        // FAQ interrupt
        if (preg_match('/\b(when|where|venue|fee|programme|agenda|date)\b/i', $userText)) {
            $ans = Rag::answer($userText);
            $reply = $ans['answer'] . ' Shall we continue with registration?';
            $messages[] = ['role' => 'assistant', 'content' => $reply];
            self::save($token, $slots, $messages, $session['persona']);
            return ['session_token' => $token, 'messages' => $messages, 'complete' => false, 'slots' => $slots];
        }

        if (empty($slots['persona'])) {
            $persona = self::detectPersona($userText);
            if ($persona) {
                $slots['persona'] = $persona;
                $next = self::nextMissingKey($persona, $slots);
                $label = str_replace('_', ' ', (string) $next);
                $reply = "Great, registering you as {$persona}. What is your {$label}?";
            } else {
                $reply = 'Please choose one: Attendee, Corporate, Innovator, University, Innovation Hub, Investor, Exhibitor, Government, or Student.';
            }
            $messages[] = ['role' => 'assistant', 'content' => $reply];
            self::save($token, $slots, $messages, $slots['persona'] ?? null);
            return ['session_token' => $token, 'messages' => $messages, 'complete' => false, 'slots' => $slots];
        }

        $persona = $slots['persona'];
        $missing = self::nextMissingKey($persona, $slots);
        if ($missing) {
            $slots[$missing] = trim($userText);
            $next = self::nextMissingKey($persona, $slots);
            if ($next) {
                $label = str_replace('_', ' ', $next);
                $reply = "Thanks. What is your {$label}?";
                $messages[] = ['role' => 'assistant', 'content' => $reply];
                self::save($token, $slots, $messages, $persona);
                return ['session_token' => $token, 'messages' => $messages, 'complete' => false, 'slots' => $slots];
            }
            $summary = self::summary($slots);
            $reply = "Please confirm this summary before I submit:\n{$summary}\nReply YES to submit, or FORM to switch to the classic form.";
            $slots['_awaiting_confirm'] = true;
            $messages[] = ['role' => 'assistant', 'content' => $reply];
            self::save($token, $slots, $messages, $persona);
            return ['session_token' => $token, 'messages' => $messages, 'complete' => false, 'slots' => $slots];
        }

        if (!empty($slots['_awaiting_confirm'])) {
            if (preg_match('/^\s*form\b/i', $userText)) {
                $draft = RegistrationService::saveDraft($slots['email'] ?? null, $persona, $slots);
                $messages[] = ['role' => 'assistant', 'content' => 'No problem. Continue on the form: /register?draft=' . $draft];
                self::save($token, $slots, $messages, $persona, 'handed_off');
                return ['session_token' => $token, 'messages' => $messages, 'complete' => false, 'handoff' => $draft, 'slots' => $slots];
            }
            if (preg_match('/^\s*y(es)?\b/i', $userText)) {
                try {
                    $result = RegistrationService::completeFromSlots($slots);
                    $messages[] = ['role' => 'assistant', 'content' => 'You are registered. Check your email for confirmation and QR badge. Next: browse challenges or submit your first challenge.'];
                    self::save($token, $slots, $messages, $persona, 'completed');
                    return ['session_token' => $token, 'messages' => $messages, 'complete' => true, 'user_id' => $result['user_id'], 'slots' => $slots];
                } catch (\Throwable $e) {
                    $messages[] = ['role' => 'assistant', 'content' => 'I could not complete registration: ' . $e->getMessage() . ' You can use /register instead.'];
                    self::save($token, $slots, $messages, $persona);
                    return ['session_token' => $token, 'messages' => $messages, 'complete' => false, 'slots' => $slots];
                }
            }
            $messages[] = ['role' => 'assistant', 'content' => 'Reply YES to submit, or FORM to use the classic form.'];
            self::save($token, $slots, $messages, $persona);
            return ['session_token' => $token, 'messages' => $messages, 'complete' => false, 'slots' => $slots];
        }

        $messages[] = ['role' => 'assistant', 'content' => 'How else can I help with ZBIF registration?'];
        self::save($token, $slots, $messages, $persona);
        return ['session_token' => $token, 'messages' => $messages, 'complete' => false, 'slots' => $slots];
    }

    private static function detectPersona(string $text): ?string
    {
        $map = [
            'corporate' => 'corporate', 'industry' => 'corporate',
            'innovator' => 'innovator', 'startup' => 'innovator',
            'university' => 'university', 'academia' => 'university',
            'hub' => 'innovation_hub', 'incubator' => 'innovation_hub',
            'investor' => 'investor', 'dfi' => 'investor',
            'exhibitor' => 'exhibitor',
            'government' => 'government', 'policy' => 'government',
            'student' => 'student',
            'attendee' => 'attendee',
        ];
        $lower = strtolower($text);
        foreach ($map as $needle => $persona) {
            if (str_contains($lower, $needle)) {
                return $persona;
            }
        }
        return null;
    }

    private static function nextMissingKey(string $persona, array $slots): ?string
    {
        foreach (self::PERSONA_SLOTS[$persona] ?? self::PERSONA_SLOTS['attendee'] as $slot) {
            if (empty($slots[$slot])) {
                return $slot;
            }
        }
        return null;
    }

    private static function summary(array $slots): string
    {
        $lines = [];
        foreach ($slots as $k => $v) {
            if (str_starts_with((string) $k, '_')) {
                continue;
            }
            $lines[] = str_replace('_', ' ', (string) $k) . ': ' . $v;
        }
        return implode("\n", $lines);
    }

    private static function save(string $token, array $slots, array $messages, ?string $persona, string $status = 'active'): void
    {
        Database::query(
            'UPDATE chat_sessions SET slots_json = ?, messages_json = ?, persona = ?, status = ?, updated_at = NOW() WHERE session_token = ?',
            [json_encode($slots), json_encode($messages), $persona, $status, $token]
        );
    }
}
