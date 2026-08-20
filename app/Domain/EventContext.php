<?php
declare(strict_types=1);

namespace App\Domain;

use App\Support\Database;

final class EventContext
{
    private const SESSION_KEY = 'zbif_active_event_id';

    public static function current(): ?array
    {
        $id = self::activeId();
        if ($id > 0) {
            $row = Database::fetch('SELECT * FROM events WHERE id = ?', [$id]);
            if ($row) {
                return $row;
            }
        }
        return Database::fetch("SELECT * FROM events WHERE status IN ('published','live') ORDER BY starts_at ASC LIMIT 1")
            ?: Database::fetch('SELECT * FROM events ORDER BY id ASC LIMIT 1');
    }

    public static function id(): int
    {
        $e = self::current();
        return (int) ($e['id'] ?? 1);
    }

    public static function activeId(): int
    {
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION[self::SESSION_KEY])) {
            return (int) $_SESSION[self::SESSION_KEY];
        }
        return 0;
    }

    public static function setActive(int $eventId): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[self::SESSION_KEY] = $eventId;
        }
    }

    public static function clearActive(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[self::SESSION_KEY]);
        }
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return Database::fetchAll('SELECT * FROM events ORDER BY starts_at DESC');
    }

    public static function bySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM events WHERE slug = ?', [$slug]);
    }

    /** Clone catalogue entities into a new edition. */
    public static function cloneCatalogue(int $fromEventId, int $toEventId): void
    {
        $sponsors = Database::fetchAll('SELECT * FROM sponsors WHERE event_id = ?', [$fromEventId]);
        foreach ($sponsors as $s) {
            Database::query(
                'INSERT INTO sponsors (event_id, name, slug, tier, contribution_usd, logo_path, website, benefits_json, sort_order, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [
                    $toEventId,
                    $s['name'],
                    $s['slug'] . '-e' . $toEventId,
                    $s['tier'],
                    $s['contribution_usd'],
                    $s['logo_path'],
                    $s['website'],
                    $s['benefits_json'],
                    $s['sort_order'],
                ]
            );
        }
        $cats = Database::fetchAll('SELECT * FROM award_categories WHERE event_id = ?', [$fromEventId]);
        foreach ($cats as $c) {
            Database::query(
                'INSERT INTO award_categories (event_id, name, description, created_at) VALUES (?, ?, ?, NOW())',
                [$toEventId, $c['name'], $c['description']]
            );
        }
        $sessions = Database::fetchAll('SELECT * FROM programme_sessions WHERE event_id = ?', [$fromEventId]);
        foreach ($sessions as $p) {
            Database::query(
                'INSERT INTO programme_sessions (event_id, day_number, title, session_type, track, room, starts_at, ends_at, capacity, description, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [
                    $toEventId,
                    $p['day_number'],
                    $p['title'],
                    $p['session_type'],
                    $p['track'] ?? null,
                    $p['room'] ?? null,
                    $p['starts_at'],
                    $p['ends_at'],
                    $p['capacity'] ?? null,
                    $p['description'] ?? null,
                ]
            );
        }
    }
}
