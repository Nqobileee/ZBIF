<?php
declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Domain\EventContext;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\View;

final class PageController
{
    public function about(): void
    {
        View::make('public/about', ['title' => 'About ZBIF', 'event' => EventContext::current()]);
    }

    public function howItWorks(): void
    {
        View::make('public/how-it-works', ['title' => 'How it works', 'event' => EventContext::current()]);
    }

    public function challenges(): void
    {
        $sector = $_GET['sector'] ?? null;
        $category = $_GET['category'] ?? null;
        $status = $_GET['status'] ?? 'published';
        $sql = "SELECT c.*, o.name AS org_name FROM challenges c
                INNER JOIN organizations o ON o.id = c.owner_org_id
                WHERE c.visibility = 'public'";
        $params = [];
        if ($status) {
            $sql .= ' AND c.status = ?';
            $params[] = $status;
        }
        if ($sector) {
            $sql .= ' AND c.sector = ?';
            $params[] = $sector;
        }
        if ($category) {
            $sql .= ' AND c.category = ?';
            $params[] = $category;
        }
        $sql .= ' ORDER BY c.updated_at DESC';
        View::make('public/challenges', [
            'title' => 'Business challenges',
            'challenges' => Database::fetchAll($sql, $params),
            'filters' => compact('sector', 'category', 'status'),
        ]);
    }

    public function challengeShow(string $id): void
    {
        $c = Database::fetch(
            "SELECT c.*, o.name AS org_name FROM challenges c
             INNER JOIN organizations o ON o.id = c.owner_org_id WHERE c.id = ?",
            [(int) $id]
        );
        if (!$c || $c['status'] !== 'published' || $c['visibility'] !== 'public') {
            http_response_code(404);
            View::make('public/404', ['title' => 'Not found']);
            return;
        }
        View::make('public/challenge-show', ['title' => $c['title'], 'challenge' => $c]);
    }

    public function innovators(): void
    {
        $solutions = Database::fetchAll(
            "SELECT s.*, o.name AS org_name FROM solutions s
             INNER JOIN organizations o ON o.id = s.owner_org_id
             WHERE s.is_published = 1 ORDER BY s.name ASC"
        );
        View::make('public/innovators', ['title' => 'Innovators and solutions', 'solutions' => $solutions]);
    }

    public function innovatorShow(string $id): void
    {
        $s = Database::fetch(
            "SELECT s.*, o.name AS org_name, o.website FROM solutions s
             INNER JOIN organizations o ON o.id = s.owner_org_id
             WHERE s.id = ? AND s.is_published = 1",
            [(int) $id]
        );
        if (!$s) {
            http_response_code(404);
            View::make('public/404', ['title' => 'Not found']);
            return;
        }
        View::make('public/innovator-show', [
            'title' => $s['name'],
            'solution' => $s,
            'metaDescription' => mb_substr(strip_tags((string) $s['description']), 0, 155),
        ]);
    }

    public function exhibitorShow(string $id): void
    {
        $booth = Database::fetch(
            'SELECT b.*, o.name AS org_name FROM exhibitor_booths b
             INNER JOIN organizations o ON o.id = b.organization_id WHERE b.id = ?',
            [(int) $id]
        );
        if (!$booth) {
            http_response_code(404);
            View::make('public/404', ['title' => 'Not found']);
            return;
        }
        View::make('public/exhibitor-show', ['title' => $booth['name'], 'booth' => $booth]);
    }

    public function programme(): void
    {
        $event = EventContext::current();
        $sessions = Database::fetchAll(
            'SELECT * FROM programme_sessions WHERE event_id = ? ORDER BY day_number, starts_at',
            [EventContext::id()]
        );
        $tab = strtolower((string) ($_GET['tab'] ?? 'agenda'));
        if ($tab === 'deal_rooms' || $tab === 'deals' || $tab === 'rooms') {
            $tab = 'deal-rooms';
        }
        if ($tab !== 'deal-rooms') {
            $tab = 'agenda';
        }
        View::make('public/programme', [
            'title' => 'Event schedule',
            'metaDescription' => 'ZBIF forum agenda and deal room timetable: keynotes, sector pitches, and 30-minute private deal sessions.',
            'sessions' => $sessions,
            'days' => \App\Domain\SchedulePresentation::groupByDay($sessions),
            'timetable' => \App\Domain\SchedulePresentation::dealRoomTimetable(),
            'stats' => \App\Domain\SchedulePresentation::stats($sessions, $event),
            'tab' => $tab,
            'event' => $event,
        ]);
    }

    public function speakers(): void
    {
        $speakers = Database::fetchAll('SELECT * FROM speakers WHERE event_id = ? ORDER BY sort_order, name', [EventContext::id()]);
        View::make('public/speakers', ['title' => 'Speakers', 'speakers' => $speakers]);
    }

    public function exhibitors(): void
    {
        $booths = Database::fetchAll('SELECT * FROM exhibitor_booths WHERE event_id = ? ORDER BY booth_code', [EventContext::id()]);
        View::make('public/exhibitors', ['title' => 'Exhibition', 'booths' => $booths]);
    }

    public function partners(): void
    {
        $partners = Database::fetchAll('SELECT * FROM partners WHERE event_id = ? ORDER BY sort_order, name', [EventContext::id()]);
        $partners = \App\Domain\EventPresentation::publicPartners($partners);
        View::make('public/partners', ['title' => 'Partners', 'partners' => $partners]);
    }

    public function sponsors(): void
    {
        $sponsors = Database::fetchAll(
            "SELECT * FROM sponsors WHERE event_id = ? AND name NOT LIKE '%Demo%' AND name NOT LIKE '%Rilpix%' ORDER BY sort_order",
            [EventContext::id()]
        );
        View::make('public/sponsors', ['title' => 'Sponsors', 'sponsors' => $sponsors]);
    }

    public function partnerInquiry(): void
    {
        Csrf::requireValid();
        if (!\App\Support\RateLimiter::attempt('partner:' . ($_SERVER['REMOTE_ADDR'] ?? 'ip'), 8, 300)) {
            Response::flash('error', 'Too many requests. Please try again later.');
            Response::redirect('/partners');
        }
        Database::query(
            'INSERT INTO partner_inquiries (name, email, organization, interest_type, message, status, created_at)
             VALUES (?, ?, ?, ?, ?, \'new\', NOW())',
            [
                trim($_POST['name'] ?? ''),
                trim($_POST['email'] ?? ''),
                trim($_POST['organization'] ?? ''),
                trim($_POST['interest_type'] ?? 'sponsor'),
                trim($_POST['message'] ?? ''),
            ]
        );
        Response::flash('success', 'Thank you. The partnerships team will respond shortly.');
        Response::redirect('/partners');
    }

    public function awards(): void
    {
        $categories = Database::fetchAll('SELECT * FROM award_categories WHERE event_id = ?', [EventContext::id()]);
        $winners = Database::fetchAll(
            "SELECT n.*, c.name AS category_name FROM award_nominations n
             INNER JOIN award_categories c ON c.id = n.category_id
             WHERE n.status = 'winner'"
        );
        View::make('public/awards', ['title' => 'Awards', 'categories' => $categories, 'winners' => $winners]);
    }

    public function deals(): void
    {
        $open = Database::fetchAll("SELECT * FROM challenges WHERE status = 'published' AND visibility = 'public' ORDER BY published_at DESC LIMIT 10");
        $announced = Database::fetchAll('SELECT * FROM deal_outcomes WHERE announced_publicly = 1 ORDER BY created_at DESC');
        View::make('public/deals', ['title' => 'Deals and opportunities', 'open' => $open, 'announced' => $announced]);
    }

    public function newsroom(): void
    {
        $posts = Database::fetchAll('SELECT * FROM news_posts WHERE published_at IS NOT NULL ORDER BY published_at DESC');
        View::make('public/newsroom', ['title' => 'Newsroom', 'posts' => $posts]);
    }

    public function newsShow(string $slug): void
    {
        $post = Database::fetch('SELECT * FROM news_posts WHERE slug = ?', [$slug]);
        if (!$post) {
            http_response_code(404);
            View::make('public/404', ['title' => 'Not found']);
            return;
        }
        View::make('public/news-show', ['title' => $post['title'], 'post' => $post]);
    }

    public function faq(): void
    {
        View::make('public/faq', ['title' => 'FAQ', 'faqs' => Database::fetchAll('SELECT * FROM faqs ORDER BY sort_order')]);
    }

    public function contact(): void
    {
        View::make('public/contact', ['title' => 'Contact']);
    }

    public function contactSubmit(): void
    {
        Csrf::requireValid();
        if (!\App\Support\RateLimiter::attempt('contact:' . ($_SERVER['REMOTE_ADDR'] ?? 'ip'), 8, 300)) {
            Response::flash('error', 'Too many requests. Please try again later.');
            Response::redirect('/contact');
        }
        Database::query(
            'INSERT INTO partner_inquiries (name, email, organization, interest_type, message, status, created_at)
             VALUES (?, ?, ?, \'contact\', ?, \'new\', NOW())',
            [trim($_POST['name'] ?? ''), trim($_POST['email'] ?? ''), trim($_POST['organization'] ?? ''), trim($_POST['message'] ?? '')]
        );
        Response::flash('success', 'Message received. We will get back to you.');
        Response::redirect('/contact');
    }

    public function venue(): void
    {
        View::make('public/venue', ['title' => 'Venue', 'event' => EventContext::current()]);
    }

    public function foresight(): void
    {
        $insights = [];
        try {
            $insights = \App\Support\FileCache::remember('foresight_public', 300, static function () {
                return Database::fetchAll(
                    'SELECT * FROM foresight_insights WHERE is_published = 1 ORDER BY sort_order, sector, id'
                );
            });
        } catch (\Throwable $e) {
            $insights = [];
        }
        View::make('public/foresight', ['title' => 'Foresight report', 'insights' => $insights]);
    }

    public function foresightCapture(): void
    {
        Csrf::requireValid();
        if (!\App\Support\RateLimiter::attempt('magnet:' . ($_SERVER['REMOTE_ADDR'] ?? 'ip'), 10, 600)) {
            Response::flash('error', 'Too many downloads. Try again later.');
            Response::redirect('/foresight');
        }
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::flash('error', 'Enter a valid email to download the foresight pack.');
            Response::redirect('/foresight');
        }
        try {
            Database::query(
                'INSERT INTO lead_magnet_captures (email, name, magnet_type, meta_json, created_at) VALUES (?, ?, \'foresight_pdf\', ?, NOW())',
                [$email, trim($_POST['name'] ?? ''), json_encode(['source' => 'foresight'])]
            );
        } catch (\Throwable $e) {
            // table may be missing until migrate; still allow download
        }
        Response::redirect('/foresight/report.pdf?ok=1');
    }

    public function foresightPdf(): void
    {
        $insights = [];
        try {
            $insights = Database::fetchAll('SELECT * FROM foresight_insights WHERE is_published = 1 ORDER BY sector, sort_order');
        } catch (\Throwable $e) {
            $insights = [];
        }
        $pdf = new \App\Support\SimplePdf('ZBIF Foresight Pack');
        $pdf->heading('ZBIF Foresight Pack');
        $pdf->text('Emerging-tech pathways for Zimbabwe industry. Generated ' . date('Y-m-d'));
        $pdf->spacer();
        foreach ($insights as $i) {
            $pdf->text($i['sector'] . ' | ' . $i['horizon'] . ' | ' . $i['title']);
            $pdf->text((string) ($i['summary'] ?: $i['body']));
            $pdf->spacer(10);
        }
        if (!$insights) {
            $pdf->text('Foresight insights will appear here once published in CMS.');
        }
        $pdf->footerBrand();
        $pdf->stream('zbif-foresight-pack.pdf');
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        $urls = ['/', '/about', '/how-it-works', '/challenges', '/innovators', '/schedule', '/speakers', '/exhibitors', '/sponsors', '/partners', '/awards', '/deals', '/newsroom', '/faq', '/contact', '/venue', '/foresight', '/register', '/style-guide'];
        foreach (Database::fetchAll("SELECT id FROM challenges WHERE status = 'published' AND visibility = 'public'") as $c) {
            $urls[] = '/challenges/' . $c['id'];
        }
        foreach (Database::fetchAll('SELECT id FROM solutions WHERE is_published = 1') as $s) {
            $urls[] = '/innovators/' . $s['id'];
        }
        foreach (Database::fetchAll('SELECT slug FROM news_posts WHERE published_at IS NOT NULL') as $n) {
            $urls[] = '/newsroom/' . $n['slug'];
        }
        foreach (Database::fetchAll('SELECT id FROM exhibitor_booths') as $b) {
            $urls[] = '/exhibitors/' . $b['id'];
        }
        echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            echo '<url><loc>' . htmlspecialchars(\App\Support\Url::to($u)) . '</loc></url>';
        }
        echo '</urlset>';
    }

    public function robots(): void
    {
        header('Content-Type: text/plain');
        echo "User-agent: *\nAllow: /\nSitemap: " . \App\Support\Url::to('/sitemap.xml') . "\n";
    }

    public function programmeIcs(string $id): void
    {
        $s = Database::fetch('SELECT * FROM programme_sessions WHERE id = ?', [(int) $id]);
        if (!$s) {
            http_response_code(404);
            return;
        }
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="zbif-session-' . $id . '.ics"');
        $dt = static fn (string $ts): string => gmdate('Ymd\THis\Z', strtotime($ts));
        echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//ZBIF//InnovaMatch//EN\r\nBEGIN:VEVENT\r\n";
        echo 'UID:session-' . $id . "@zbif\r\n";
        echo 'DTSTART:' . $dt($s['starts_at']) . "\r\n";
        echo 'DTEND:' . $dt($s['ends_at']) . "\r\n";
        echo 'SUMMARY:' . str_replace(["\n", ','], [' ', '\\,'], $s['title']) . "\r\n";
        echo 'LOCATION:' . str_replace(["\n", ','], [' ', '\\,'], (string) ($s['room'] ?? '')) . "\r\n";
        echo "END:VEVENT\r\nEND:VCALENDAR\r\n";
    }

    public function health(): void
    {
        $dbOk = true;
        try {
            Database::pdo()->query('SELECT 1');
        } catch (\Throwable $e) {
            $dbOk = false;
        }
        $pendingJobs = 0;
        if ($dbOk) {
            $pendingJobs = (int) (Database::fetch('SELECT COUNT(*) AS c FROM jobs WHERE completed_at IS NULL AND failed_at IS NULL')['c'] ?? 0);
        }
        Response::json([
            'status' => $dbOk ? 'ok' : 'degraded',
            'database' => $dbOk,
            'queue_depth' => $pendingJobs,
            'ai' => \App\Ai\LlmClient::health(),
            'request_id' => \App\Support\RequestId::get(),
        ], $dbOk ? 200 : 503);
    }

    public function styleGuide(): void
    {
        View::make('public/style-guide', [
            'title' => 'Style guide',
            'metaDescription' => 'ZBIF InnovaMatch design tokens, components, and accessibility checklist.',
            'event' => EventContext::current(),
        ]);
    }
}
