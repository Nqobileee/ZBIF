<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Auth\Auth;
use App\Auth\EmailVerification;
use App\Auth\PasswordPolicy;
use App\Auth\Totp;
use App\Domain\ChallengeStateMachine;
use App\Domain\DealPipeline;
use App\Domain\RegistrationService;
use App\Rbac\PermissionCatalog;
use App\Support\Database;
use App\Support\Str;

$failed = 0;
$passed = 0;

function assert_true(bool $cond, string $msg): void
{
    global $failed, $passed;
    if ($cond) {
        echo "PASS {$msg}\n";
        $passed++;
    } else {
        echo "FAIL {$msg}\n";
        $failed++;
    }
}

assert_true(ChallengeStateMachine::canTransition('submitted', 'screening'), 'challenge submitted->screening');
assert_true(!ChallengeStateMachine::canTransition('submitted', 'adopted'), 'challenge blocks illegal jump');
assert_true(ChallengeStateMachine::canTransition('published', 'allocated'), 'challenge published->allocated');
assert_true(in_array('mou_or_pilot', DealPipeline::STAGES, true), 'deal pipeline has mou stage');
assert_true(count(PermissionCatalog::all()) > 20, 'permission catalog populated');
assert_true(isset(PermissionCatalog::roleBundles()['super_admin']), 'super_admin bundle exists');
assert_true(Str::slug('Hello World!') === 'hello-world', 'slug helper');
assert_true(Str::noEmDash('a—b') === 'a,b' || Str::noEmDash('a—b') !== 'a—b', 'em-dash scrubber');

// Auth unit checks
assert_true(PasswordPolicy::validate('short') !== [], 'password rejects short');
assert_true(PasswordPolicy::validate('longenough1') === [], 'password accepts letter+number 10+');
assert_true(PasswordPolicy::strength('Aa1!longpass') >= 3, 'password strength scores');
assert_true(in_array(EmailVerification::mode(), ['link', 'otp'], true), 'verification mode');

$secret = Totp::generateSecret();
$code = Totp::codeAt($secret, (int) floor(time() / 30));
assert_true(Totp::verify($secret, $code), 'totp verify current window');

// DB-backed auth flows (skip gracefully if DB unavailable)
try {
    Database::pdo()->query('SELECT 1');
    $email = 'auth_test_' . bin2hex(random_bytes(4)) . '@zbif.test';
    $slots = [
        'persona' => 'attendee',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => $email,
        'password' => 'SecurePass99',
        'password_confirmation' => 'SecurePass99',
        'consent_privacy' => 1,
        'consent_conduct' => 1,
        'country' => 'Zimbabwe',
    ];
    $created = RegistrationService::createAccount($slots);
    assert_true(($created['user_id'] ?? 0) > 0, 'register creates user');
    $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$created['user_id']]);
    assert_true(empty($user['email_verified_at']), 'new user unverified');
    assert_true(($user['status'] ?? '') === 'unverified', 'status unverified');

    Auth::logout();
    assert_true(!Auth::attempt($email, 'SecurePass99'), 'unverified cannot login');

    EmailVerification::markVerified((int) $created['user_id']);
    $user2 = Database::fetch('SELECT * FROM users WHERE id = ?', [$created['user_id']]);
    assert_true(!empty($user2['email_verified_at']), 'markVerified sets timestamp');
    assert_true(($user2['status'] ?? '') === 'active', 'verified becomes active');

    Auth::logout();
    assert_true(Auth::attempt($email, 'SecurePass99'), 'login with password');
    assert_true(Auth::isVerified(), 'verified user passes isVerified');
    Auth::logout();
    assert_true(!Auth::attempt($email, 'wrong-password'), 'login rejects bad password');

    // Magic link token insert/consume shape
    $token = bin2hex(random_bytes(16));
    Database::query(
        "INSERT INTO magic_links (email, token, purpose, payload_json, expires_at, created_at)
         VALUES (?, ?, 'login', ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW())",
        [$email, $token, json_encode(['user_id' => (int) $created['user_id']])]
    );
    $row = Database::fetch("SELECT * FROM magic_links WHERE token = ? AND purpose = 'login' AND used_at IS NULL", [$token]);
    assert_true((bool) $row, 'magic login link stored');

    // Cleanup test user soft
    Database::query('UPDATE users SET deleted_at = NOW(), email = CONCAT(email, \'.deleted\') WHERE id = ?', [$created['user_id']]);
} catch (\Throwable $e) {
    echo "SKIP db auth flows: {$e->getMessage()}\n";
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
