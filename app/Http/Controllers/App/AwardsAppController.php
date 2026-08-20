<?php
declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Auth\Auth;
use App\Domain\EventContext;
use App\Rbac\Gate;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\UploadGuard;
use App\Support\View;

final class AwardsAppController
{
    public const TIERS = [
        'platinum' => ['label' => 'Platinum', 'from' => 25000],
        'gold' => ['label' => 'Gold', 'from' => 15000],
        'silver' => ['label' => 'Silver', 'from' => 8000],
        'deal_room' => ['label' => 'Deal Room', 'from' => 10000],
        'innovation' => ['label' => 'Innovation', 'from' => 5000],
        'university' => ['label' => 'University', 'from' => 2500],
    ];

    public function nominate(): void
    {
        Auth::requireLogin();
        $categories = Database::fetchAll('SELECT * FROM award_categories WHERE event_id = ? ORDER BY name', [EventContext::id()]);
        View::make('app/awards/nominate', ['title' => 'Nominate', 'categories' => $categories], 'layouts/app');
    }

    public function storeNomination(): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        Database::query(
            'INSERT INTO award_nominations (category_id, nominee_name, nominee_org, nominated_by, rationale, status, created_at)
             VALUES (?, ?, ?, ?, ?, \'submitted\', NOW())',
            [
                (int) ($_POST['category_id'] ?? 0),
                trim($_POST['nominee_name'] ?? ''),
                trim($_POST['nominee_org'] ?? ''),
                Auth::id(),
                trim($_POST['rationale'] ?? ''),
            ]
        );
        Response::flash('success', 'Nomination submitted.');
        Response::redirect('/app/awards/nominate');
    }

    public function applySponsorship(): void
    {
        Auth::requireVerified();
        $mine = Database::fetchAll(
            'SELECT * FROM sponsorship_applications WHERE user_id = ? ORDER BY id DESC LIMIT 10',
            [Auth::id()]
        );
        View::make('app/sponsorship/apply', [
            'title' => 'Sponsorship application',
            'tiers' => self::TIERS,
            'applications' => $mine,
        ], 'layouts/app');
    }

    public function storeSponsorship(): void
    {
        Auth::requireVerified();
        Csrf::requireValid();
        $tier = (string) ($_POST['tier_requested'] ?? 'silver');
        if (!isset(self::TIERS[$tier])) {
            $tier = 'silver';
        }
        $logoPath = null;
        if (!empty($_FILES['logo']['tmp_name'])) {
            $check = UploadGuard::validate($_FILES['logo'], 2097152);
            if ($check['ok']) {
                $dir = ZBIF_ROOT . '/public/assets/uploads/sponsors';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $dest = $dir . '/' . bin2hex(random_bytes(4)) . '_' . $check['safe_name'];
                move_uploaded_file($_FILES['logo']['tmp_name'], $dest);
                $logoPath = 'assets/uploads/sponsors/' . basename($dest);
            }
        }
        Database::query(
            'INSERT INTO sponsorship_applications
             (user_id, pitch, tier_requested, budget_usd, company_name, logo_path, website, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, \'submitted\', NOW(), NOW())',
            [
                Auth::id(),
                trim($_POST['pitch'] ?? ''),
                $tier,
                $_POST['budget_usd'] !== '' ? (float) $_POST['budget_usd'] : self::TIERS[$tier]['from'],
                trim($_POST['company_name'] ?? ''),
                $logoPath,
                trim($_POST['website'] ?? ''),
            ]
        );
        Response::flash('success', 'Sponsorship application submitted for review.');
        Response::redirect('/app/sponsorship');
    }

    public function sponsorPortal(): void
    {
        Auth::requireLogin();
        $sponsor = $this->mySponsor();
        if (!$sponsor) {
            View::make('app/sponsorship/portal', [
                'title' => 'Sponsor portal',
                'sponsor' => null,
                'entitlements' => [],
                'leads' => 0,
                'dealRooms' => 0,
            ], 'layouts/app');
            return;
        }
        $ents = Database::fetchAll('SELECT * FROM sponsor_entitlements WHERE sponsor_id = ?', [$sponsor['id']]);
        $dealRooms = (int) (Database::fetch('SELECT COUNT(*) AS c FROM deal_rooms WHERE sponsor_id = ?', [$sponsor['id']])['c'] ?? 0);
        View::make('app/sponsorship/portal', [
            'title' => 'Sponsor portal',
            'sponsor' => $sponsor,
            'entitlements' => $ents,
            'leads' => 0,
            'dealRooms' => $dealRooms,
        ], 'layouts/app');
    }

    public function sponsorAssetsSave(): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        $sponsor = $this->mySponsor();
        if (!$sponsor) {
            Response::flash('error', 'No active sponsorship linked to your account.');
            Response::redirect('/app/sponsor-portal');
        }
        if (!empty($_FILES['logo']['tmp_name'])) {
            $check = UploadGuard::validate($_FILES['logo'], 2097152);
            if ($check['ok']) {
                $dir = ZBIF_ROOT . '/public/assets/uploads/sponsors';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $dest = $dir . '/' . bin2hex(random_bytes(4)) . '_' . $check['safe_name'];
                move_uploaded_file($_FILES['logo']['tmp_name'], $dest);
                Database::query('UPDATE sponsors SET logo_path = ? WHERE id = ?', ['assets/uploads/sponsors/' . basename($dest), $sponsor['id']]);
            }
        }
        Database::query('UPDATE sponsors SET website = ? WHERE id = ?', [trim($_POST['website'] ?? ''), $sponsor['id']]);
        Response::flash('success', 'Sponsor assets updated.');
        Response::redirect('/app/sponsor-portal');
    }

    private function mySponsor(): ?array
    {
        $app = Database::fetch(
            "SELECT sponsor_id FROM sponsorship_applications WHERE user_id = ? AND status = 'sponsored' AND sponsor_id IS NOT NULL ORDER BY id DESC LIMIT 1",
            [Auth::id()]
        );
        if ($app && $app['sponsor_id']) {
            return Database::fetch('SELECT * FROM sponsors WHERE id = ?', [$app['sponsor_id']]);
        }
        // fallback: match by email domain org name is hard; allow Gate
        if (Gate::allows('sponsorship.review')) {
            return Database::fetch('SELECT * FROM sponsors WHERE event_id = ? ORDER BY id DESC LIMIT 1', [EventContext::id()]);
        }
        return null;
    }
}
