<?php
declare(strict_types=1);

namespace App\Rbac;

use App\Support\Database;

final class PermissionCatalog
{
    /** @return list<array{module: string, feature: string, name: string, description: string}> */
    public static function all(): array
    {
        $defs = [
            ['auth', 'login', 'auth.login', 'Sign in'],
            ['auth', 'register', 'auth.register', 'Register'],
            ['profile', 'manage', 'profile.manage', 'Manage own profile'],
            ['challenges', 'view', 'challenges.view', 'View challenges'],
            ['challenges', 'submit', 'challenges.submit', 'Submit challenges'],
            ['challenges', 'manage_own', 'challenges.manage_own', 'Manage own challenges'],
            ['challenges', 'screen', 'challenges.screen', 'Screen and prioritise challenges'],
            ['challenges', 'publish', 'challenges.publish', 'Publish challenges'],
            ['challenges', 'allocate', 'challenges.allocate', 'Allocate challenges'],
            ['solutions', 'view', 'solutions.view', 'View solutions'],
            ['solutions', 'create', 'solutions.create', 'Create solutions'],
            ['solutions', 'manage_own', 'solutions.manage_own', 'Manage own solutions'],
            ['workspaces', 'view', 'workspaces.view', 'View development workspaces'],
            ['workspaces', 'mentor', 'workspaces.mentor', 'Mentor in workspaces'],
            ['matching', 'view', 'matching.view', 'View match suggestions'],
            ['matching', 'manage', 'matching.manage', 'Admin matching console'],
            ['deals', 'view', 'deals.view', 'View deal rooms'],
            ['deals', 'participate', 'deals.participate', 'Participate in deal rooms'],
            ['deals', 'manage', 'deals.manage', 'Manage deals pipeline'],
            ['investor', 'deal_flow', 'investor.deal_flow', 'Investor deal flow'],
            ['meetings', 'manage', 'meetings.manage', 'Book and manage meetings'],
            ['programme', 'view', 'programme.view', 'View programme'],
            ['exhibition', 'manage_booth', 'exhibition.manage_booth', 'Manage exhibitor booth'],
            ['exhibition', 'capture_leads', 'exhibition.capture_leads', 'Capture QR leads'],
            ['awards', 'nominate', 'awards.nominate', 'Nominate for awards'],
            ['awards', 'judge', 'awards.judge', 'Judge awards'],
            ['surveys', 'respond', 'surveys.respond', 'Respond to surveys'],
            ['surveys', 'manage', 'surveys.manage', 'Build and manage surveys'],
            ['sponsorship', 'apply', 'sponsorship.apply', 'Apply for shortlist/sponsorship'],
            ['sponsorship', 'review', 'sponsorship.review', 'Review sponsorship applications'],
            ['cms', 'manage', 'cms.manage', 'Manage CMS content'],
            ['users', 'manage', 'users.manage', 'Manage users'],
            ['roles', 'manage', 'roles.manage', 'Manage roles and permissions'],
            ['analytics', 'impact', 'analytics.impact', 'View impact dashboard'],
            ['audit', 'view', 'audit.view', 'View audit log'],
            ['integrations', 'manage', 'integrations.manage', 'Manage integrations'],
            ['feature_flags', 'manage', 'feature_flags.manage', 'Manage feature flags'],
            ['exports', 'run', 'exports.run', 'Export data'],
            ['admin', 'access', 'admin.access', 'Access admin area'],
            ['comms', 'send', 'comms.send', 'Send segmented communications'],
        ];
        $out = [];
        foreach ($defs as [$module, $feature, $name, $description]) {
            $out[] = compact('module', 'feature', 'name', 'description');
        }
        return $out;
    }

    /** @return array<string, list<string>> */
    public static function roleBundles(): array
    {
        $all = array_column(self::all(), 'name');
        return [
            'guest' => [],
            'attendee' => ['profile.manage', 'programme.view', 'meetings.manage', 'surveys.respond', 'challenges.view', 'solutions.view'],
            'corporate' => ['profile.manage', 'challenges.view', 'challenges.submit', 'challenges.manage_own', 'solutions.view', 'matching.view', 'deals.view', 'deals.participate', 'workspaces.view', 'meetings.manage', 'surveys.respond', 'programme.view'],
            'innovator' => ['profile.manage', 'challenges.view', 'solutions.view', 'solutions.create', 'solutions.manage_own', 'workspaces.view', 'matching.view', 'deals.view', 'deals.participate', 'sponsorship.apply', 'meetings.manage', 'surveys.respond', 'awards.nominate', 'programme.view'],
            'university' => ['profile.manage', 'challenges.view', 'solutions.create', 'solutions.manage_own', 'workspaces.view', 'matching.view', 'deals.participate', 'meetings.manage', 'surveys.respond', 'programme.view'],
            'researcher' => ['profile.manage', 'challenges.view', 'solutions.create', 'solutions.manage_own', 'workspaces.view', 'matching.view', 'deals.participate', 'surveys.respond'],
            'innovation_hub' => ['profile.manage', 'workspaces.view', 'workspaces.mentor', 'solutions.view', 'challenges.view', 'meetings.manage', 'surveys.respond'],
            'mentor' => ['profile.manage', 'workspaces.view', 'workspaces.mentor', 'meetings.manage', 'surveys.respond'],
            'investor' => ['profile.manage', 'investor.deal_flow', 'deals.view', 'deals.participate', 'matching.view', 'meetings.manage', 'surveys.respond', 'programme.view'],
            'exhibitor' => ['profile.manage', 'exhibition.manage_booth', 'exhibition.capture_leads', 'programme.view', 'surveys.respond', 'meetings.manage'],
            'government' => ['profile.manage', 'programme.view', 'meetings.manage', 'surveys.respond', 'challenges.view', 'solutions.view'],
            'student' => ['profile.manage', 'programme.view', 'surveys.respond', 'challenges.view', 'solutions.view'],
            'technical_committee' => ['profile.manage', 'admin.access', 'challenges.view', 'challenges.screen', 'challenges.publish', 'challenges.allocate', 'matching.view', 'matching.manage', 'workspaces.view', 'audit.view', 'exports.run'],
            'organizer' => array_values(array_diff($all, ['roles.manage', 'feature_flags.manage', 'integrations.manage'])),
            'super_admin' => $all,
        ];
    }
}
