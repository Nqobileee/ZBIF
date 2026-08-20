<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;

$sessions = $sessions ?? [];
$currentHash = $currentHash ?? '';
?>
<?php if (empty($user['email_verified_at'])): ?>
  <div class="auth-banner" role="status">
    <div>
      <strong>Verify your email</strong>
      <div class="muted">Verify to submit challenges, publish solutions, join Deal Rooms, and book meetings.</div>
    </div>
    <form method="post" action="<?= View::e(Url::to('/verify-email/resend')) ?>">
      <?= Csrf::field() ?>
      <input type="hidden" name="redirect" value="/app/profile?verify=1">
      <button class="btn btn-primary btn-sm" type="submit">Resend verification</button>
    </form>
  </div>
<?php endif; ?>
<div class="grid grid-2">
<form method="post" class="dash-card">
  <?= Csrf::field() ?>
  <h2 style="margin:0 0 1rem">Profile</h2>
  <div class="grid grid-2">
    <div class="form-group"><label>First name</label><input name="first_name" value="<?= View::e($user['first_name']) ?>"></div>
    <div class="form-group"><label>Last name</label><input name="last_name" value="<?= View::e($user['last_name']) ?>"></div>
  </div>
  <div class="form-group"><label>Email</label><input value="<?= View::e($user['email']) ?>" disabled>
    <?php if (empty($user['email_verified_at'])): ?><span class="badge">Unverified</span><?php else: ?><span class="badge">Verified</span><?php endif; ?>
  </div>
  <div class="grid grid-2">
    <div class="form-group"><label>Phone</label><input name="phone" value="<?= View::e($user['phone'] ?? '') ?>"></div>
    <div class="form-group"><label>Title</label><input name="title" value="<?= View::e($user['title'] ?? '') ?>"></div>
  </div>
  <div class="grid grid-2">
    <div class="form-group"><label>City</label><input name="city" value="<?= View::e($user['city'] ?? '') ?>"></div>
    <div class="form-group"><label>Country</label><input name="country" value="<?= View::e($user['country'] ?? '') ?>"></div>
  </div>
  <div class="form-group"><label>LinkedIn</label><input name="linkedin_url" value="<?= View::e($user['linkedin_url'] ?? '') ?>"></div>
  <div class="form-group"><label>Website</label><input name="website_url" value="<?= View::e($user['website_url'] ?? '') ?>"></div>
  <div class="form-group"><label>Dietary needs</label><input name="dietary_needs" value="<?= View::e($user['dietary_needs'] ?? '') ?>"></div>
  <div class="form-group"><label>Accessibility needs</label><input name="accessibility_needs" value="<?= View::e($user['accessibility_needs'] ?? '') ?>"></div>
  <button class="btn btn-primary" type="submit">Save profile</button>
</form>
<div>
  <div class="dash-card" style="margin-bottom:1rem">
    <h3>Security</h3>
    <p><a href="<?= View::e(Url::to('/app/security/2fa')) ?>">Two-factor authentication</a></p>
    <form method="post" action="<?= View::e(Url::to('/logout-all')) ?>" onsubmit="return confirm('Sign out everywhere?')">
      <?= Csrf::field() ?>
      <button class="btn btn-ghost btn-sm" type="submit">Log out of all devices</button>
    </form>
  </div>
  <?php if ($sessions): ?>
  <div class="dash-card" style="margin-bottom:1rem">
    <h3>Active sessions</h3>
    <?php foreach ($sessions as $s): ?>
      <div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;margin:.65rem 0;flex-wrap:wrap">
        <div>
          <strong><?= ($s['session_id_hash'] ?? '') === $currentHash ? 'This device' : 'Other device' ?></strong>
          <div class="muted" style="font-size:.85rem"><?= View::e($s['ip'] ?? '') ?> · <?= View::e(mb_substr((string) ($s['user_agent'] ?? ''), 0, 60)) ?></div>
          <div class="muted" style="font-size:.8rem">Last seen <?= View::e($s['last_seen_at'] ?? '') ?></div>
        </div>
        <?php if (($s['session_id_hash'] ?? '') !== $currentHash): ?>
          <form method="post" action="<?= View::e(Url::to('/app/sessions/revoke')) ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="session_id" value="<?= (int) $s['id'] ?>">
            <button class="btn btn-ghost btn-sm" type="submit">Revoke</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <div class="dash-card">
    <h3>Your data</h3>
    <p class="muted">Download or request deletion of your account data.</p>
    <a class="btn btn-ghost" href="/app/privacy/export">Export my data (JSON)</a>
    <form method="post" action="/app/privacy/delete" style="margin-top:0.75rem" onsubmit="return confirm('Request account deletion?')">
      <?= Csrf::field() ?>
      <button class="btn btn-danger" type="submit">Request deletion</button>
    </form>
  </div>
</div>
</div>
