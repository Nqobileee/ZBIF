<?php
declare(strict_types=1);

namespace App\Domain;

use App\Support\Database;
use RuntimeException;

final class ChallengeStateMachine
{
    public const STATES = [
        'submitted', 'screening', 'prioritised', 'published', 'allocated',
        'in_development', 'solution_ready', 'presented', 'in_deal',
        'adopted', 'piloted', 'closed_won', 'closed_lost', 'archived',
    ];

    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'submitted' => ['screening', 'archived'],
        'screening' => ['prioritised', 'archived'],
        'prioritised' => ['published', 'screening', 'archived'],
        'published' => ['allocated', 'archived'],
        'allocated' => ['in_development', 'archived'],
        'in_development' => ['solution_ready', 'archived'],
        'solution_ready' => ['presented', 'archived'],
        'presented' => ['in_deal', 'archived'],
        'in_deal' => ['adopted', 'piloted', 'closed_won', 'closed_lost', 'archived'],
        'adopted' => ['archived'],
        'piloted' => ['adopted', 'closed_won', 'archived'],
        'closed_won' => ['archived'],
        'closed_lost' => ['archived'],
        'archived' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function transition(int $challengeId, string $to, ?int $actorId = null, ?string $notes = null): void
    {
        $challenge = Database::fetch('SELECT * FROM challenges WHERE id = ?', [$challengeId]);
        if (!$challenge) {
            throw new RuntimeException('Challenge not found.');
        }
        $from = $challenge['status'];
        if (!self::canTransition($from, $to)) {
            throw new RuntimeException("Illegal transition from {$from} to {$to}.");
        }
        $publishedAt = $to === 'published' ? date('Y-m-d H:i:s') : $challenge['published_at'];
        Database::query(
            'UPDATE challenges SET status = ?, published_at = COALESCE(?, published_at), updated_at = NOW() WHERE id = ?',
            [$to, $publishedAt, $challengeId]
        );
        Database::query(
            'INSERT INTO challenge_state_events (challenge_id, from_status, to_status, actor_id, notes, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [$challengeId, $from, $to, $actorId, $notes]
        );
        AuditLog::record('challenge.transition', 'challenge', $challengeId, [
            'from' => $from,
            'to' => $to,
            'notes' => $notes,
        ], $actorId);

        \App\Notify\Notifier::event('challenge_status', [
            'challenge_id' => $challengeId,
            'status' => $to,
        ]);
    }
}
