<?php
/**
 * Upsert the public 4-day ZBIF 2026 programme for the active event.
 * Usage: php scripts/seed-schedule-2026.php
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Domain\EventContext;
use App\Domain\SchedulePresentation;
use App\Support\Database;

$eventId = EventContext::id();
if ($eventId < 1) {
    fwrite(STDERR, "No active event.\n");
    exit(1);
}

Database::query('DELETE FROM programme_sessions WHERE event_id = ?', [$eventId]);
foreach (SchedulePresentation::canonicalDays() as $day) {
    foreach ($day['sessions'] as [$title, $type, $start, $end, $room]) {
        Database::query(
            'INSERT INTO programme_sessions (event_id, day_number, title, session_type, track, room, starts_at, ends_at, capacity, description, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 200, ?, NOW())',
            [
                $eventId,
                $day['day'],
                $title,
                $type,
                $day['theme'],
                $room,
                $day['date'] . ' ' . $start . ':00',
                $day['date'] . ' ' . $end . ':00',
                $day['theme'],
            ]
        );
    }
}

$count = Database::fetch('SELECT COUNT(*) AS c FROM programme_sessions WHERE event_id = ?', [$eventId]);
echo 'Programme sessions for event #' . $eventId . ': ' . (int) ($count['c'] ?? 0) . "\n";
