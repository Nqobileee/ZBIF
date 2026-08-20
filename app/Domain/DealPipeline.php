<?php
declare(strict_types=1);

namespace App\Domain;

use App\Support\Database;

final class DealPipeline
{
    public const STAGES = [
        'introduced',
        'in_discussion',
        'meeting_booked',
        'proposal_pilot_scoping',
        'mou_or_pilot',
        'closed_won',
        'closed_lost',
    ];

    public static function move(int $dealRoomId, string $toStage, ?int $actorId = null, ?string $notes = null): void
    {
        if (!in_array($toStage, self::STAGES, true)) {
            throw new \InvalidArgumentException('Invalid deal stage.');
        }
        $room = Database::fetch('SELECT * FROM deal_rooms WHERE id = ?', [$dealRoomId]);
        if (!$room) {
            throw new \RuntimeException('Deal room not found.');
        }
        $from = $room['stage'];
        Database::query('UPDATE deal_rooms SET stage = ?, updated_at = NOW() WHERE id = ?', [$toStage, $dealRoomId]);
        Database::query(
            'INSERT INTO deal_stage_events (deal_room_id, from_stage, to_stage, actor_id, notes, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [$dealRoomId, $from, $toStage, $actorId, $notes]
        );
        AuditLog::record('deal.stage_change', 'deal_room', $dealRoomId, [
            'from' => $from,
            'to' => $toStage,
        ], $actorId);
        \App\Notify\Notifier::event('deal_stage_changed', ['deal_room_id' => $dealRoomId, 'stage' => $toStage]);
    }
}
