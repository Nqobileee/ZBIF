<?php
declare(strict_types=1);

/**
 * ZBIF InnovaMatch database seeder
 * Usage: php database/seeds/seed.php
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Rbac\Gate;
use App\Rbac\PermissionCatalog;
use App\Support\Database;
use App\Support\Str;

$pdo = Database::pdo();
echo "Seeding ZBIF InnovaMatch...\n";

// RBAC
foreach (PermissionCatalog::all() as $p) {
    Database::query(
        'INSERT IGNORE INTO permissions (module, feature, name, description, created_at) VALUES (?, ?, ?, ?, NOW())',
        [$p['module'], $p['feature'], $p['name'], $p['description']]
    );
}
$roleMeta = [
    'guest' => 'Guest',
    'attendee' => 'Attendee',
    'corporate' => 'Corporate / Industry',
    'innovator' => 'Innovator / Startup',
    'university' => 'University / Academia',
    'researcher' => 'Researcher',
    'innovation_hub' => 'Innovation Hub',
    'mentor' => 'Mentor / Industry Expert',
    'investor' => 'Investor / Fund / DFI',
    'exhibitor' => 'Exhibitor',
    'government' => 'Government / Policy',
    'student' => 'Student / Young Entrepreneur',
    'technical_committee' => 'Technical Committee',
    'organizer' => 'Organizer / Admin',
    'super_admin' => 'Super Admin',
];
foreach ($roleMeta as $slug => $name) {
    Database::query('INSERT IGNORE INTO roles (name, slug, description, created_at) VALUES (?, ?, ?, NOW())', [$name, $slug, $name]);
}
$bundles = PermissionCatalog::roleBundles();
foreach ($bundles as $slug => $perms) {
    $role = Database::fetch('SELECT id FROM roles WHERE slug = ?', [$slug]);
    if (!$role) {
        continue;
    }
    foreach ($perms as $permName) {
        $perm = Database::fetch('SELECT id FROM permissions WHERE name = ?', [$permName]);
        if ($perm) {
            Database::query('INSERT IGNORE INTO role_permission (role_id, permission_id) VALUES (?, ?)', [$role['id'], $perm['id']]);
        }
    }
}

// Event
Database::query(
    'INSERT INTO events (slug, name, edition, theme, starts_at, ends_at, venue, city, country, status, created_at, updated_at)
     VALUES (\'zbif-2026\', \'Zimbabwe Business Innovation Forum 2026\', \'2026\',
     \'Connecting Industry Challenges to Local Innovation for Competitive Growth\',
     \'2026-10-19 09:00:00\', \'2026-10-22 17:00:00\',
     \'Zimbabwe International Trade Fair (ZITF)\', \'Bulawayo\', \'Zimbabwe\', \'published\', NOW(), NOW())
     ON DUPLICATE KEY UPDATE
       theme = VALUES(theme),
       starts_at = VALUES(starts_at),
       ends_at = VALUES(ends_at),
       venue = VALUES(venue),
       city = VALUES(city),
       country = VALUES(country),
       updated_at = NOW()'
);
$eventId = (int) (Database::fetch('SELECT id FROM events WHERE slug = \'zbif-2026\'')['id'] ?? Database::lastId());

$password = password_hash('Password123!', PASSWORD_DEFAULT);
$seedUsers = [
    ['super@zbif.test', 'Super', 'Admin', 'super_admin'],
    ['organizer@zbif.test', 'Olivia', 'Organizer', 'organizer'],
    ['committee@zbif.test', 'Theo', 'Committee', 'technical_committee'],
    ['corporate@zbif.test', 'Chipo', 'Corporate', 'corporate'],
    ['innovator@zbif.test', 'Tariro', 'Innovator', 'innovator'],
    ['university@zbif.test', 'Dr', 'Moyo', 'university'],
    ['researcher@zbif.test', 'Ada', 'Researcher', 'researcher'],
    ['hub@zbif.test', 'Hub', 'Lead', 'innovation_hub'],
    ['mentor@zbif.test', 'Mentor', 'Expert', 'mentor'],
    ['investor@zbif.test', 'Ivy', 'Investor', 'investor'],
    ['exhibitor@zbif.test', 'Ex', 'hibitor', 'exhibitor'],
    ['gov@zbif.test', 'Grace', 'Policy', 'government'],
    ['student@zbif.test', 'Sam', 'Student', 'student'],
    ['attendee@zbif.test', 'Alex', 'Attendee', 'attendee'],
];

$userIds = [];
foreach ($seedUsers as [$email, $first, $last, $role]) {
    $existing = Database::fetch('SELECT id FROM users WHERE email = ?', [$email]);
    if ($existing) {
        $userIds[$role] = (int) $existing['id'];
        Gate::assignRole((int) $existing['id'], $role);
        continue;
    }
    $qr = bin2hex(random_bytes(8));
    Database::query(
        'INSERT INTO users (email, password, first_name, last_name, phone, country, city, title, qr_badge_token, email_verified_at, is_active, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, \'Zimbabwe\', \'Harare\', ?, ?, NOW(), 1, NOW(), NOW())',
        [$email, $password, $first, $last, '+263771000000', $role, $qr]
    );
    $uid = (int) Database::lastId();
    $userIds[$role] = $uid;
    Gate::assignRole($uid, $role);
    Database::query(
        'INSERT INTO participation_profiles (user_id, event_id, persona, profile_json, registration_fee, payment_status, status, created_at, updated_at)
         VALUES (?, ?, ?, ?, 0, \'not_required\', \'approved\', NOW(), NOW())',
        [$uid, $eventId, $role, json_encode(['seed' => true])]
    );
    Database::query(
        'INSERT INTO notification_preferences (user_id, email_enabled, sms_enabled, whatsapp_enabled, in_app_enabled, updated_at)
         VALUES (?, 1, 1, 0, 1, NOW())',
        [$uid]
    );
}

// Organizations
$orgDefs = [
    ['ZB Holdings Demo Corp', 'corporate', 'corporate'],
    ['InnovaLabs Startup', 'startup', 'innovator'],
    ['NUST Innovation', 'university', 'university'],
    ['Harare Innovation Hub', 'hub', 'innovation_hub'],
    ['Savanna Capital', 'investor', 'investor'],
    ['TechExpo Booth Co', 'exhibitor', 'exhibitor'],
];
$orgIds = [];
foreach ($orgDefs as [$name, $type, $linkRole]) {
    $slug = Str::slug($name);
    $row = Database::fetch('SELECT id FROM organizations WHERE slug = ?', [$slug]);
    if (!$row) {
        Database::query(
            'INSERT INTO organizations (name, slug, type, industry, country, city, created_at, updated_at)
             VALUES (?, ?, ?, \'Multi-sector\', \'Zimbabwe\', \'Harare\', NOW(), NOW())',
            [$name, $slug, $type]
        );
        $oid = (int) Database::lastId();
    } else {
        $oid = (int) $row['id'];
    }
    $orgIds[$linkRole] = $oid;
    if (isset($userIds[$linkRole])) {
        Database::query(
            'INSERT IGNORE INTO organization_user (organization_id, user_id, org_role, is_primary, created_at) VALUES (?, ?, \'admin\', 1, NOW())',
            [$oid, $userIds[$linkRole]]
        );
    }
}

// Sponsors (admin packages — not shown as public partner badges)
$tiers = [
    ['platinum', 'Platinum Package', 25000, ['Naming rights', 'Keynote slot', 'Premium branding']],
    ['gold', 'Gold Package', 15000, ['Exhibition space', 'Speaking opportunities']],
    ['silver', 'Silver Package', 7500, ['Branding and networking access']],
    ['deal_room', 'Deal Room Package', 10000, ['Exclusive branding of the deal rooms']],
    ['innovation', 'Innovation Package', 5000, ['Innovation showcase visibility']],
    ['university', 'University Package', 3000, ['Academic participation branding']],
];
foreach ($tiers as $i => [$tier, $name, $usd, $benefits]) {
    $slug = Str::slug($name);
    $exists = Database::fetch('SELECT id FROM sponsors WHERE event_id = ? AND slug = ?', [$eventId, $slug]);
    if ($exists) {
        continue;
    }
    Database::query(
        'INSERT INTO sponsors (event_id, name, slug, tier, contribution_usd, benefits_json, sort_order, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
        [$eventId, $name, $slug, $tier, $usd, json_encode($benefits), $i]
    );
}

// Partners (public wall — ZB, POTRAZ, NVCCZ only)
Database::query('DELETE FROM partners WHERE event_id = ?', [$eventId]);
$partners = [
    ['financial', 'ZB', 'img/partners/zb-financial.png'],
    ['government_agencies', 'POTRAZ', 'img/partners/potraz.png'],
    ['financial', 'NVCCZ', 'img/partners/nvccz.png'],
];
foreach ($partners as $i => [$cat, $name, $logo]) {
    Database::query(
        'INSERT INTO partners (event_id, name, category, description, logo_path, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
        [$eventId, $name, $cat, 'Strategic partner for ZBIF 2026', $logo, $i]
    );
}

// Speakers (~20)
for ($i = 1; $i <= 20; $i++) {
    Database::query(
        'INSERT INTO speakers (event_id, name, title, organization, bio, sort_order, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())',
        [$eventId, "Speaker {$i}", 'Industry Leader', 'ZBIF Partner Org', 'Experienced voice on competitive growth and local innovation.', $i]
    );
}

// Programme — 4 forum days (matches public schedule)
foreach (\App\Domain\SchedulePresentation::canonicalDays() as $day) {
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

// Categories for challenges
$categories = ['Manufacturing','Agriculture','Mining','Banking and Fintech','Logistics','Healthcare','Retail','Energy','Smart Infrastructure'];
$statuses = ['submitted','screening','prioritised','published','allocated','in_development','solution_ready','presented','in_deal','adopted'];
$corpOrg = $orgIds['corporate'];
$corpUser = $userIds['corporate'];

// ~15 challenges
for ($i = 1; $i <= 15; $i++) {
    $cat = $categories[($i - 1) % count($categories)];
    $status = $statuses[($i - 1) % count($statuses)];
    $title = "Challenge {$i}: {$cat} efficiency";
    Database::query(
        'INSERT INTO challenges (event_id, owner_org_id, created_by, title, slug, problem_statement, sector, category, desired_outcome, engagement_type, visibility, status, published_at, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'pilot\', \'public\', ?, ?, NOW(), NOW())',
        [
            $eventId, $corpOrg, $corpUser, $title, Str::slug($title) . "-{$i}",
            "Industry operational challenge {$i} seeking a commercially viable local solution in {$cat}.",
            $cat, $cat, 'Pilot-ready solution within 90 days',
            $status, in_array($status, ['published','allocated','in_development','solution_ready','presented','in_deal','adopted'], true) ? date('Y-m-d H:i:s') : null,
        ]
    );
    $cid = (int) Database::lastId();
    Database::query(
        'INSERT INTO challenge_state_events (challenge_id, from_status, to_status, actor_id, notes, created_at) VALUES (?, NULL, ?, ?, \'Seed\', NOW())',
        [$cid, $status, $userIds['technical_committee']]
    );
}

// ~30 innovators/solutions
$innOrg = $orgIds['innovator'];
$innUser = $userIds['innovator'];
$publishedChallenges = Database::fetchAll("SELECT id FROM challenges WHERE status = 'published' LIMIT 5");
for ($i = 1; $i <= 30; $i++) {
    $cat = $categories[($i - 1) % count($categories)];
    $stage = ['idea','prototype','pilot','market_ready','scaling'][($i - 1) % 5];
    $name = "Solution {$i}: {$cat} toolkit";
    $challengeId = ($i <= 5 && isset($publishedChallenges[$i - 1])) ? $publishedChallenges[$i - 1]['id'] : null;
    Database::query(
        'INSERT INTO solutions (event_id, owner_org_id, created_by, challenge_id, name, slug, sector, stage, description, problem_solved, is_published, investor_visible, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())',
        [
            $eventId, $innOrg, $innUser, $challengeId, $name, Str::slug($name) . "-{$i}", $cat, $stage,
            "Market-facing solution {$i} addressing {$cat} constraints with local talent.",
            "Reduces cost and cycle time in {$cat}.",
            $i % 3 === 0 ? 1 : 0,
        ]
    );
}

// Workspaces
$alloc = Database::fetch("SELECT id FROM challenges WHERE status IN ('allocated','in_development') LIMIT 1");
$sol = Database::fetch('SELECT id FROM solutions WHERE challenge_id IS NOT NULL LIMIT 1');
if ($alloc && $sol) {
    Database::query(
        'INSERT INTO development_workspaces (challenge_id, solution_id, status, created_at, updated_at) VALUES (?, ?, \'active\', NOW(), NOW())',
        [$alloc['id'], $sol['id']]
    );
    $wid = (int) Database::lastId();
    Database::query('INSERT INTO workspace_mentors (workspace_id, user_id, notes, created_at) VALUES (?, ?, \'Seed mentor\', NOW())', [$wid, $userIds['mentor']]);
    foreach (['Discovery', 'Prototype', 'Pilot readiness'] as $i => $t) {
        Database::query(
            'INSERT INTO workspace_milestones (workspace_id, title, status, sort_order, created_at, updated_at) VALUES (?, ?, \'in_progress\', ?, NOW(), NOW())',
            [$wid, $t, $i]
        );
    }
    Database::query(
        'INSERT INTO workspace_feedback (workspace_id, user_id, body, created_at) VALUES (?, ?, \'Please align the prototype with our procurement constraints.\', NOW())',
        [$wid, $corpUser]
    );
}

// Matches
$pub = Database::fetch("SELECT id FROM challenges WHERE status = 'published' LIMIT 1");
if ($pub) {
    \App\Domain\MatchingService::persistSuggestions((int) $pub['id']);
}

// Deal room + closed outcomes
$dealSponsor = Database::fetch("SELECT id FROM sponsors WHERE tier = 'deal_room' LIMIT 1");
Database::query(
    'INSERT INTO deal_rooms (event_id, title, challenge_id, sponsor_id, stage, agreement_summary, created_by, created_at, updated_at)
     VALUES (?, \'Seed Deal Room: Logistics Pilot\', ?, ?, \'mou_or_pilot\', \'Pilot scoping agreed\', ?, NOW(), NOW())',
    [$eventId, $pub['id'] ?? null, $dealSponsor['id'] ?? null, $corpUser]
);
$roomId = (int) Database::lastId();
foreach ([$corpUser, $innUser, $userIds['investor']] as $uid) {
    Database::query('INSERT INTO deal_room_participants (deal_room_id, user_id, created_at) VALUES (?, ?, NOW())', [$roomId, $uid]);
}
Database::query(
    'INSERT INTO deal_room_messages (deal_room_id, user_id, body, created_at) VALUES (?, ?, \'Welcome to the deal room. Let us scope the pilot.\', NOW())',
    [$roomId, $corpUser]
);
Database::query(
    'INSERT INTO deal_outcomes (deal_room_id, outcome_type, amount_usd, notes, announced_publicly, public_title, public_summary, created_at)
     VALUES (?, \'mou\', NULL, \'MOU signed for logistics pilot\', 1, \'Logistics pilot MOU signed\', \'A corporate and innovator agreed a 90-day pilot.\', NOW()),
            (?, \'investment\', 250000, \'Seed investment interest\', 1, \'Investment commitment unlocked\', \'An investor committed follow-on diligence capital.\', NOW())',
    [$roomId, $roomId]
);

// Awards
foreach (['Most Commercially Ready Solution', 'Best University Innovation', 'Outstanding Industry Challenge', 'Youth Innovator Award'] as $name) {
    Database::query(
        'INSERT INTO award_categories (event_id, name, description, created_at) VALUES (?, ?, ?, NOW())',
        [$eventId, $name, 'ZBIF recognition category']
    );
}
$catId = (int) (Database::fetch('SELECT id FROM award_categories LIMIT 1')['id'] ?? 0);
if ($catId) {
    Database::query(
        'INSERT INTO award_nominations (category_id, nominee_name, nominee_org, nominated_by, rationale, status, created_at)
         VALUES (?, \'InnovaLabs Toolkit\', \'InnovaLabs Startup\', ?, \'Strong traction and clear market fit.\', \'shortlisted\', NOW())',
        [$catId, $userIds['attendee']]
    );
}

// Surveys
$surveyDefs = [
    ['Pre-event expectations', 'pre-event-expectations', ['What are your top goals?', 'Which sectors interest you most?', 'Who do you want to meet? (NPS-style readiness 0-10)']],
    ['Post-session feedback', 'post-session-feedback', ['Rate this session (1-5)', 'What worked well?', 'What should improve?']],
    ['Post-event experience and gains', 'post-event-gains', ['Overall NPS (0-10)', 'Quality of matches (1-5)', 'Deals or leads generated', 'Partnerships formed', 'Pilots initiated', 'Capital committed', 'Solutions adopted', 'Likelihood to return (1-5)', 'Testimonial (with consent)']],
];
foreach ($surveyDefs as [$title, $slug, $questions]) {
    Database::query(
        'INSERT INTO surveys (event_id, title, slug, audience, is_anonymous, status, opens_at, closes_at, created_at, updated_at)
         VALUES (?, ?, ?, \'all\', 0, \'open\', NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), NOW(), NOW())',
        [$eventId, $title, $slug]
    );
    $sid = (int) Database::lastId();
    foreach ($questions as $i => $prompt) {
        $type = str_contains(strtolower($prompt), 'nps') || str_contains($prompt, '0-10') ? 'nps' : (str_contains($prompt, '1-5') ? 'likert' : 'short_text');
        Database::query(
            'INSERT INTO survey_questions (survey_id, question_type, prompt, is_required, sort_order, created_at) VALUES (?, ?, ?, 1, ?, NOW())',
            [$sid, $type, $prompt, $i]
        );
    }
    Database::query('INSERT INTO survey_responses (survey_id, user_id, completed_at, created_at) VALUES (?, ?, NOW(), NOW())', [$sid, $userIds['attendee']]);
    $rid = (int) Database::lastId();
    $qs = Database::fetchAll('SELECT id, question_type FROM survey_questions WHERE survey_id = ?', [$sid]);
    foreach ($qs as $q) {
        Database::query(
            'INSERT INTO survey_answers (response_id, question_id, answer_text, numeric_value, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$rid, $q['id'], 'Seed response', $q['question_type'] === 'nps' ? 9 : ($q['question_type'] === 'likert' ? 4 : null)]
        );
    }
}

// Exhibitors
Database::query(
    'INSERT INTO exhibitor_booths (event_id, organization_id, owner_user_id, booth_code, name, description, launching_at_forum, floor_x, floor_y, created_at, updated_at)
     VALUES (?, ?, ?, \'A12\', \'TechExpo Booth\', \'Live demos of market-ready products.\', 1, 120, 80, NOW(), NOW())',
    [$eventId, $orgIds['exhibitor'], $userIds['exhibitor']]
);

// FAQs + KB + news + flags + pages
$faqs = [
    ['When is ZBIF 2026?', 'ZBIF takes place 19–22 October 2026 at the Zimbabwe International Trade Fair (ZITF) in Bulawayo.'],
    ['Is registration free?', 'Registration fees are configurable per participant type and default to free.'],
    ['How do Deal Rooms work?', 'After interest is accepted, participants enter a private room for messaging, meetings, and deal-stage tracking through to MOU or pilot.'],
    ['Who organises ZBIF?', 'ZBIF is organised by ZB Financial Holdings.'],
];
foreach ($faqs as $i => [$q, $a]) {
    Database::query('INSERT INTO faqs (question, answer, sort_order, created_at) VALUES (?, ?, ?, NOW())', [$q, $a, $i]);
    Database::query(
        'INSERT INTO knowledge_chunks (source, title, body, tags, created_at) VALUES (\'faq\', ?, ?, \'faq,event\', NOW())',
        [$q, $a]
    );
}
Database::query(
    'INSERT INTO knowledge_chunks (source, title, body, tags, created_at) VALUES
     (\'programme\', \'Two-day structure\', \'Day 1 covers opening, keynotes, panels, exhibitions, pitches, roundtables, and networking. Day 2 covers demonstrations, university showcase, investor sessions, deal rooms, awards, and closing.\', \'programme\', NOW()),
     (\'sectors\', \'Focus sectors\', \'Manufacturing, Agriculture, Mining, Banking and Fintech, Logistics, Healthcare, Retail, Energy, Smart Infrastructure.\', \'sectors\', NOW())'
);
Database::query(
    'INSERT INTO news_posts (title, slug, excerpt, body, published_at, created_at, updated_at)
     VALUES (\'University tour kicks off ZBIF roadshow\', \'university-tour-roadshow\', \'Campus engagements build the innovator pipeline.\',
     \'ZBIF teams visited universities to source challenges and solvers ahead of the September forum.\', NOW(), NOW(), NOW())'
);
foreach (['payments_enabled' => 0, 'sms_reminders' => 1, 'ai_matchmaking' => 1] as $flag => $on) {
    Database::query(
        'INSERT INTO feature_flags (flag_key, is_enabled, description, updated_at) VALUES (?, ?, ?, NOW())',
        [$flag, $on, $flag]
    );
}
foreach (['home','about','how-it-works','programme','faq'] as $slug) {
    Database::query(
        'INSERT INTO pages (slug, title, meta_description, status, created_at, updated_at) VALUES (?, ?, ?, \'published\', NOW(), NOW())',
        [$slug, ucwords(str_replace('-', ' ', $slug)), 'ZBIF InnovaMatch ' . $slug]
    );
}

echo "Seed complete.\n";
echo "Demo password for all seed logins: Password123!\n";
echo "Examples: corporate@zbif.test, innovator@zbif.test, committee@zbif.test, super@zbif.test\n";
