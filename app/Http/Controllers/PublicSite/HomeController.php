<?php
declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Domain\EventContext;
use App\Domain\EventPresentation;
use App\Domain\ImpactMetrics;
use App\Support\Database;
use App\Support\FileCache;
use App\Support\View;

final class HomeController
{
    public function index(): void
    {
        $event = EventContext::current();
        $eventId = EventContext::id();

        $challenges = FileCache::remember('home_challenges', 180, static function () {
            return Database::fetchAll(
                "SELECT c.*, o.name AS org_name FROM challenges c
                 INNER JOIN organizations o ON o.id = c.owner_org_id
                 WHERE c.status = 'published' AND c.visibility = 'public'
                 ORDER BY c.published_at DESC LIMIT 6"
            );
        });
        $innovators = FileCache::remember('home_innovators', 180, static function () {
            return Database::fetchAll(
                "SELECT s.*, o.name AS org_name FROM solutions s
                 INNER JOIN organizations o ON o.id = s.owner_org_id
                 WHERE s.is_published = 1 ORDER BY s.id DESC LIMIT 6"
            );
        });
        $partners = FileCache::remember('home_partners_' . $eventId, 300, static function () use ($eventId) {
            return Database::fetchAll(
                'SELECT * FROM partners WHERE event_id = ? ORDER BY sort_order, name',
                [$eventId]
            );
        });
        $partners = EventPresentation::publicPartners($partners);

        $sponsors = FileCache::remember('home_sponsors_' . $eventId, 300, static function () use ($eventId) {
            return Database::fetchAll(
                'SELECT * FROM sponsors WHERE event_id = ? AND name NOT LIKE \'%Demo%\' ORDER BY sort_order',
                [$eventId]
            );
        });

        $deals = FileCache::remember('home_deals', 180, static function () {
            return Database::fetchAll(
                "SELECT * FROM deal_outcomes WHERE announced_publicly = 1 ORDER BY created_at DESC LIMIT 4"
            );
        });

        $speakers = [];
        try {
            $speakers = FileCache::remember('home_speakers_' . ($eventId ?: 0), 300, static function () use ($eventId) {
                if ($eventId) {
                    return Database::fetchAll(
                        'SELECT * FROM speakers WHERE event_id = ? ORDER BY sort_order, name LIMIT 8',
                        [$eventId]
                    );
                }
                return Database::fetchAll('SELECT * FROM speakers ORDER BY sort_order, name LIMIT 8');
            });
        } catch (\Throwable $e) {
            $speakers = [];
        }

        $news = [];
        try {
            $news = FileCache::remember('home_news', 300, static function () {
                return Database::fetchAll(
                    'SELECT * FROM news_posts WHERE published_at IS NOT NULL ORDER BY published_at DESC LIMIT 3'
                );
            });
        } catch (\Throwable $e) {
            $news = [];
        }

        $testimonials = [];
        try {
            $testimonials = FileCache::remember('home_testimonials', 300, static function () {
                return Database::fetchAll(
                    'SELECT * FROM testimonials WHERE is_published = 1 ORDER BY sort_order, id DESC LIMIT 6'
                );
            });
        } catch (\Throwable $e) {
            $testimonials = [];
        }

        $metrics = [];
        try {
            $metrics = ImpactMetrics::forEvent($eventId);
        } catch (\Throwable $e) {
            $metrics = [];
        }

        View::make('public/home', [
            'title' => 'ZBIF',
            'metaDescription' => 'Where industry problems meet local solutions. Submit challenges, match with innovators, and close deals at ZBIF ' . ($event['edition'] ?? '2026') . '.',
            'event' => $event,
            'challenges' => $challenges,
            'innovators' => $innovators,
            'partners' => $partners,
            'sponsors' => $sponsors,
            'deals' => $deals,
            'speakers' => $speakers,
            'news' => $news,
            'testimonials' => $testimonials,
            'metrics' => $metrics,
        ]);
    }
}
