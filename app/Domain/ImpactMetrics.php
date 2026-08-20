<?php
declare(strict_types=1);

namespace App\Domain;

use App\Support\Database;

final class ImpactMetrics
{
    /** @return array<string, int|float> */
    public static function forEvent(?int $eventId = null): array
    {
        $key = 'impact_metrics_' . ($eventId ?? 'all');
        return \App\Support\FileCache::remember($key, 120, static function () use ($eventId) {
            return self::compute($eventId);
        });
    }

    /** @return array<string, int|float> */
    private static function compute(?int $eventId = null): array
    {
        $challenges = (int) (Database::fetch('SELECT COUNT(*) AS c FROM challenges WHERE 1=1' . ($eventId ? ' AND event_id = ?' : ''), $eventId ? [$eventId] : [])['c'] ?? 0);
        $innovators = (int) (Database::fetch(
            "SELECT COUNT(DISTINCT u.id) AS c FROM users u
             INNER JOIN model_has_roles mhr ON mhr.user_id = u.id
             INNER JOIN roles r ON r.id = mhr.role_id
             WHERE r.slug IN ('innovator','university','researcher')"
        )['c'] ?? 0);
        $matches = (int) (Database::fetch('SELECT COUNT(*) AS c FROM matches')['c'] ?? 0);
        $pitches = (int) (Database::fetch(
            "SELECT COUNT(*) AS c FROM programme_sessions WHERE session_type IN ('pitch','demo','showcase')"
            . ($eventId ? ' AND event_id = ?' : ''),
            $eventId ? [$eventId] : []
        )['c'] ?? 0);
        // Fallback: count deal rooms at pitch-ready stages if no programme pitches yet
        if ($pitches === 0) {
            $pitches = (int) (Database::fetch(
                "SELECT COUNT(*) AS c FROM deal_rooms WHERE stage IN ('pitch','mou_or_pilot','in_discussion')"
            )['c'] ?? 0);
        }
        $universities = (int) (Database::fetch("SELECT COUNT(*) AS c FROM organizations WHERE type = 'university'")['c'] ?? 0);
        $solutions = (int) (Database::fetch('SELECT COUNT(*) AS c FROM solutions' . ($eventId ? ' WHERE event_id = ?' : ''), $eventId ? [$eventId] : [])['c'] ?? 0);
        $partnerships = (int) (Database::fetch("SELECT COUNT(*) AS c FROM deal_outcomes WHERE outcome_type = 'partnership'")['c'] ?? 0);
        $adopted = (int) (Database::fetch("SELECT COUNT(*) AS c FROM deal_outcomes WHERE outcome_type = 'adoption'")['c'] ?? 0);
        $pilots = (int) (Database::fetch("SELECT COUNT(*) AS c FROM deal_outcomes WHERE outcome_type = 'pilot'")['c'] ?? 0);
        $mous = (int) (Database::fetch("SELECT COUNT(*) AS c FROM deal_outcomes WHERE outcome_type = 'mou'")['c'] ?? 0);
        $investment = (float) (Database::fetch("SELECT COALESCE(SUM(amount_usd),0) AS s FROM deal_outcomes WHERE outcome_type = 'investment'")['s'] ?? 0);
        $attendance = (int) (Database::fetch('SELECT COUNT(*) AS c FROM participation_profiles WHERE status = \'approved\'' . ($eventId ? ' AND event_id = ?' : ''), $eventId ? [$eventId] : [])['c'] ?? 0);
        $media = (int) (Database::fetch('SELECT COUNT(*) AS c FROM news_posts WHERE published_at IS NOT NULL')['c'] ?? 0);

        return [
            'challenges_submitted' => $challenges,
            'innovators_participating' => $innovators,
            'innovators_registered' => $innovators,
            'matches_made' => $matches,
            'pitches_confirmed' => $pitches,
            'universities_engaged' => $universities,
            'solutions_developed' => $solutions,
            'partnerships_formed' => $partnerships,
            'solutions_adopted' => $adopted,
            'pilots_initiated' => $pilots,
            'mous_signed' => $mous,
            'investment_commitments_usd' => $investment,
            'media_reach_items' => $media,
            'attendance_numbers' => $attendance,
        ];
    }
}
