<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Domain\SurveyEngine;
use App\Notify\Notifier;
use App\Support\Database;
use App\Support\Logger;
use App\Support\Url;

final class Worker
{
    public static function run(int $max = 20): int
    {
        $jobs = Database::fetchAll(
            'SELECT * FROM jobs WHERE completed_at IS NULL AND failed_at IS NULL AND available_at <= NOW()
             ORDER BY id ASC LIMIT ?',
            [$max]
        );
        $done = 0;
        foreach ($jobs as $job) {
            Database::query('UPDATE jobs SET reserved_at = NOW(), attempts = attempts + 1 WHERE id = ?', [$job['id']]);
            try {
                self::handle($job);
                Database::query('UPDATE jobs SET completed_at = NOW() WHERE id = ?', [$job['id']]);
                $done++;
            } catch (\Throwable $e) {
                Logger::log('error', 'Job failed', ['job' => $job['id'], 'error' => $e->getMessage()]);
                if ((int) $job['attempts'] >= 3) {
                    Database::query('UPDATE jobs SET failed_at = NOW(), last_error = ? WHERE id = ?', [substr($e->getMessage(), 0, 500), $job['id']]);
                } else {
                    Database::query(
                        'UPDATE jobs SET available_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE), last_error = ?, reserved_at = NULL WHERE id = ?',
                        [substr($e->getMessage(), 0, 500), $job['id']]
                    );
                }
            }
        }
        return $done;
    }

    /** @param array<string, mixed> $job */
    private static function handle(array $job): void
    {
        $payload = json_decode($job['payload_json'] ?: '{}', true) ?: [];
        match ($job['job_type']) {
            'notify_event' => null,
            'log_email', 'sms_skipped' => null,
            'meeting_reminder' => self::meetingReminder($payload),
            'survey_reminder' => self::surveyReminder($payload),
            'daily_digest' => self::dailyDigest($payload),
            default => null,
        };
    }

    /** @param array<string, mixed> $payload */
    private static function meetingReminder(array $payload): void
    {
        $meetingId = (int) ($payload['meeting_id'] ?? 0);
        $meeting = Database::fetch('SELECT * FROM meetings WHERE id = ?', [$meetingId]);
        if (!$meeting) {
            return;
        }
        $userIds = array_unique(array_merge(
            [(int) $meeting['organizer_id']],
            array_map('intval', array_column(Database::fetchAll('SELECT user_id FROM meeting_participants WHERE meeting_id = ?', [$meetingId]), 'user_id'))
        ));
        foreach ($userIds as $uid) {
            Notifier::notifyUser(
                $uid,
                'Meeting reminder',
                'Upcoming: ' . $meeting['title'] . ' at ' . $meeting['starts_at'],
                '/app/meetings',
                ['in_app', 'email', 'sms']
            );
        }
    }

    /** @param array<string, mixed> $payload */
    private static function surveyReminder(array $payload): void
    {
        $surveyId = (int) ($payload['survey_id'] ?? 0);
        $userId = (int) ($payload['user_id'] ?? 0);
        $survey = Database::fetch('SELECT * FROM surveys WHERE id = ?', [$surveyId]);
        if (!$survey) {
            return;
        }
        $url = '/survey/' . $surveyId . '?t=' . SurveyEngine::publicToken($surveyId);
        if ($userId > 0) {
            Notifier::notifyUser($userId, 'Survey reminder', 'Please complete: ' . $survey['title'], $url, ['in_app', 'email']);
            return;
        }
        // Expand to active users (audience blast) when only survey_id provided
        $users = Database::fetchAll('SELECT id FROM users WHERE is_active = 1 AND deleted_at IS NULL LIMIT 500');
        foreach ($users as $u) {
            Notifier::notifyUser((int) $u['id'], 'Survey reminder', 'Please complete: ' . $survey['title'], $url, ['in_app', 'email']);
        }
    }

    /** @param array<string, mixed> $payload */
    private static function dailyDigest(array $payload): void
    {
        $userId = (int) ($payload['user_id'] ?? 0);
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            return;
        }
        $prefs = Notifier::prefs($userId);
        if (empty($prefs['digest_enabled']) || empty($prefs['email_enabled'])) {
            return;
        }
        $unread = Database::fetchAll(
            'SELECT title, body, link, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY id DESC LIMIT 12',
            [$userId]
        );
        $matches = (int) (Database::fetch(
            'SELECT COUNT(*) AS c FROM matches m
             INNER JOIN challenges c ON c.id = m.challenge_id
             WHERE c.created_by = ? AND m.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)',
            [$userId]
        )['c'] ?? 0);
        $dealMsgs = (int) (Database::fetch(
            'SELECT COUNT(*) AS c FROM deal_room_messages m
             INNER JOIN deal_room_participants p ON p.deal_room_id = m.deal_room_id
             WHERE p.user_id = ? AND m.user_id <> ? AND m.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)',
            [$userId, $userId]
        )['c'] ?? 0);
        if (!$unread && $matches === 0 && $dealMsgs === 0) {
            return;
        }
        $lines = ['Your ZBIF daily digest:', '', 'New matches (24h): ' . $matches, 'Deal messages (24h): ' . $dealMsgs, ''];
        foreach ($unread as $n) {
            $lines[] = '- ' . $n['title'] . ': ' . mb_substr((string) $n['body'], 0, 80);
        }
        $html = Notifier::renderEmail('ZBIF daily digest', implode("\n", $lines), '/app');
        Notifier::sendEmail((string) $user['email'], 'ZBIF daily digest', $html);
        Notifier::inApp($userId, 'Daily digest sent', 'Your marketplace digest was emailed.', '/app/notifications');
    }
}
