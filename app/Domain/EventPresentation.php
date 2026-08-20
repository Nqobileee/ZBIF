<?php
declare(strict_types=1);

namespace App\Domain;

/** Shared public presentation helpers for the active forum event. */
final class EventPresentation
{
    public static function dateRangeLabel(?array $event, string $fallback = '19–22 October 2026'): string
    {
        if (!$event || empty($event['starts_at'])) {
            return $fallback;
        }
        $start = strtotime((string) $event['starts_at']);
        $end = !empty($event['ends_at']) ? strtotime((string) $event['ends_at']) : false;
        if (!$start) {
            return $fallback;
        }
        if (!$end || $end < $start) {
            return date('j F Y', $start);
        }
        $sameYear = date('Y', $start) === date('Y', $end);
        $sameMonth = $sameYear && date('m', $start) === date('m', $end);
        $sameDay = $sameMonth && date('d', $start) === date('d', $end);
        if ($sameDay) {
            return date('j F Y', $start);
        }
        if ($sameMonth) {
            return date('j', $start) . '–' . date('j F Y', $end);
        }
        if ($sameYear) {
            return date('j F', $start) . ' – ' . date('j F Y', $end);
        }
        return date('j F Y', $start) . ' – ' . date('j F Y', $end);
    }

    public static function countdownIso(?array $event): string
    {
        $raw = $event['starts_at'] ?? '2026-10-19 09:00:00';
        return str_replace(' ', 'T', (string) $raw);
    }

    public static function venueShort(?array $event): string
    {
        $venue = trim((string) ($event['venue'] ?? 'Zimbabwe International Trade Fair (ZITF)'));
        $city = trim((string) ($event['city'] ?? 'Bulawayo'));
        $country = trim((string) ($event['country'] ?? 'Zimbabwe'));
        if ($venue === '') {
            $venue = 'Zimbabwe International Trade Fair (ZITF)';
        }
        // Prefer a clear ZITF label when the stored venue is the grounds shorthand.
        if (stripos($venue, 'ZITF') !== false || stripos($venue, 'Trade Fair') !== false) {
            $venue = 'Zimbabwe International Trade Fair (ZITF)';
        }
        return $venue . ', ' . $city . ', ' . $country;
    }

    public static function untilLine(?array $event): string
    {
        $edition = (string) ($event['edition'] ?? '2026');
        $city = (string) ($event['city'] ?? 'Bulawayo');
        $country = (string) ($event['country'] ?? 'Zimbabwe');
        return 'Until ZBIF ' . $edition . ' · ' . $city . ', ' . $country;
    }

    /** Filter partners/sponsors for public walls (no Rilpix, no demo tier labels). */
    public static function publicPartners(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            if (stripos($name, 'rilpix') !== false) {
                continue;
            }
            if (stripos($name, 'demo') !== false && stripos($name, 'sponsor') !== false) {
                continue;
            }
            $out[] = $row;
        }
        return $out;
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $a = strtoupper(substr($parts[0] ?? 'Z', 0, 1));
        $b = strtoupper(substr($parts[1] ?? ($parts[0] ?? 'B'), 0, 1));
        return $a . $b;
    }
}
