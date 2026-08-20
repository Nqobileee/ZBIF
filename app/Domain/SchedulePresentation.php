<?php
declare(strict_types=1);

namespace App\Domain;

/**
 * Public schedule page helpers: deal-room timetable preview and forum-day themes.
 * Mirrors the public schedule experience on zbif.co.zw/schedule.
 */
final class SchedulePresentation
{
    /** @return list<array{key:string,name:string,focus:string,slots:list<array{start:string,end:string,status:string,label:string}>}> */
    public static function dealRoomTimetable(): array
    {
        $rooms = [
            ['key' => 'a', 'name' => 'Room A', 'focus' => 'Fintech & banking', 'starts' => ['09:00', '10:00', '11:00']],
            ['key' => 'b', 'name' => 'Room B', 'focus' => 'Agritech & health', 'starts' => ['10:30', '11:00']],
            ['key' => 'c', 'name' => 'Room C', 'focus' => 'Trade, energy & water', 'starts' => ['09:00']],
        ];
        $out = [];
        foreach ($rooms as $room) {
            $slots = [];
            foreach ($room['starts'] as $start) {
                $end = date('H:i', strtotime($start . ' +30 minutes'));
                $slots[] = [
                    'start' => $start,
                    'end' => $end,
                    'status' => 'available',
                    'label' => 'Open slot',
                ];
            }
            $out[] = [
                'key' => $room['key'],
                'name' => $room['name'],
                'focus' => $room['focus'],
                'slots' => $slots,
            ];
        }
        return $out;
    }

    /**
     * Canonical 4-day forum agenda used when organisers have not published sessions yet,
     * and as the seed shape for ZBIF 2026.
     *
     * @return list<array{day:int,date:string,theme:string,sessions:list<array{title:string,type:string,start:string,end:string,room:string}>}>
     */
    public static function canonicalDays(): array
    {
        return [
            [
                'day' => 1,
                'date' => '2026-10-19',
                'theme' => 'Opening & sector pitches',
                'sessions' => [
                    ['Registration & networking breakfast', 'networking', '08:00', '09:00', 'Main foyer'],
                    ['Official opening ceremony', 'keynote', '09:00', '09:45', 'Main Hall'],
                    ['Keynote: Innovation as industrial policy', 'keynote', '09:45', '10:30', 'Main Hall'],
                    ['Coffee break & exhibitor walkthrough', 'networking', '10:30', '11:00', 'Exhibition Hall'],
                    ['Sector pitch block — Fintech & Banking', 'pitch', '11:00', '12:30', 'Pitch Arena'],
                    ['Lunch & matchmaking lounge', 'networking', '12:30', '14:00', 'Pavilion'],
                    ['Deal room sessions — Block 1', 'networking', '14:00', '16:00', 'Deal Rooms'],
                    ['Day 1 wrap-up & networking drinks', 'networking', '17:00', '18:30', 'Pavilion'],
                ],
            ],
            [
                'day' => 2,
                'date' => '2026-10-20',
                'theme' => 'Industry challenges & demos',
                'sessions' => [
                    ['Morning networking & exhibition open', 'networking', '08:30', '09:15', 'Exhibition Hall'],
                    ['Corporate challenge showcase', 'panel', '09:15', '10:30', 'Main Hall'],
                    ['Live solution demos', 'demo', '10:45', '12:15', 'Demo Floor'],
                    ['Lunch & investor lounge', 'networking', '12:15', '13:30', 'Investor Lounge'],
                    ['Deal room sessions — Block 2', 'networking', '13:30', '16:00', 'Deal Rooms'],
                    ['University innovation showcase', 'showcase', '16:00', '17:15', 'Academic Pavilion'],
                    ['Evening networking reception', 'networking', '17:30', '19:00', 'Pavilion'],
                ],
            ],
            [
                'day' => 3,
                'date' => '2026-10-21',
                'theme' => 'Deals, pilots & partnerships',
                'sessions' => [
                    ['Deal room intensives — Block 3', 'networking', '09:00', '12:00', 'Deal Rooms'],
                    ['Procurement & partnership clinics', 'workshop', '10:00', '11:30', 'Breakout Rooms'],
                    ['Lunch & matchmaking', 'networking', '12:00', '13:15', 'Pavilion'],
                    ['Pitch finals — selected innovators', 'pitch', '13:15', '15:00', 'Pitch Arena'],
                    ['MOU & pilot announcement briefings', 'panel', '15:15', '16:30', 'Main Hall'],
                    ['Day 3 networking close', 'networking', '16:45', '18:00', 'Pavilion'],
                ],
            ],
            [
                'day' => 4,
                'date' => '2026-10-22',
                'theme' => 'Awards & closing',
                'sessions' => [
                    ['Final deal room catch-ups', 'networking', '09:00', '11:00', 'Deal Rooms'],
                    ['Awards and recognition ceremony', 'awards', '11:15', '12:30', 'Main Hall'],
                    ['Closing remarks & next steps', 'keynote', '12:30', '13:00', 'Main Hall'],
                    ['Farewell lunch & exhibition close', 'networking', '13:00', '14:30', 'Pavilion'],
                ],
            ],
        ];
    }

    /**
     * @param list<array<string,mixed>> $sessions
     * @return list<array{day:int,label:string,weekday:string,theme:string,sessions:list<array<string,mixed>>}>
     */
    public static function groupByDay(array $sessions): array
    {
        $byDay = [];
        foreach ($sessions as $s) {
            $d = (int) ($s['day_number'] ?? 1);
            $byDay[$d][] = $s;
        }
        ksort($byDay);
        $themes = [];
        foreach (self::canonicalDays() as $day) {
            $themes[$day['day']] = $day['theme'];
        }
        $out = [];
        foreach ($byDay as $day => $list) {
            $first = $list[0] ?? null;
            $ts = $first ? strtotime((string) ($first['starts_at'] ?? '')) : false;
            $out[] = [
                'day' => $day,
                'label' => $ts ? date('j F', $ts) : ('Day ' . $day),
                'weekday' => $ts ? date('l', $ts) : '',
                'theme' => $themes[$day] ?? (string) ($first['track'] ?? 'Forum programme'),
                'sessions' => $list,
            ];
        }
        return $out;
    }

    /** @param list<array<string,mixed>> $sessions */
    public static function stats(array $sessions, ?array $event): array
    {
        $days = [];
        foreach ($sessions as $s) {
            $days[(int) ($s['day_number'] ?? 0)] = true;
        }
        $forumDays = count(array_filter(array_keys($days)));
        if ($forumDays < 1 && $event) {
            $start = strtotime((string) ($event['starts_at'] ?? ''));
            $end = strtotime((string) ($event['ends_at'] ?? ''));
            if ($start && $end && $end >= $start) {
                $forumDays = (int) floor(($end - $start) / 86400) + 1;
            }
        }
        return [
            'forum_days' => max($forumDays, 1),
            'deal_rooms' => 3,
            'sessions_booked' => 0,
            'pitches_confirmed' => 0,
        ];
    }
}
