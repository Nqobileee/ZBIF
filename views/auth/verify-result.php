<?php
use App\Support\Url;
use App\Support\View;
$state = $state ?? 'invalid';
?>
<div class="reg-page verify-page">
  <header class="reg-top">
    <a class="reg-logo" href="<?= View::e(Url::to('/')) ?>">
      <img src="<?= View::e(Url::asset('img/zbif-logo-main.png')) ?>" alt="ZBIF" width="120" height="36">
    </a>
    <div class="reg-top-actions">
      <a class="btn btn-ghost" href="<?= View::e(Url::to('/login.php')) ?>">Sign in</a>
    </div>
  </header>

  <div class="reg-layout">
    <div class="reg-main">
      <div class="reg-card verify-card verify-result">
        <?php if ($state === 'verified'): ?>
          <h2>Email verified</h2>
          <p class="muted">Your account is ready. Sign in with the email and password you registered with.</p>
          <a class="btn btn-primary btn-block" href="<?= View::e(Url::to('/login.php')) ?>">Go to sign in</a>
        <?php elseif ($state === 'already'): ?>
          <h2>Already verified</h2>
          <p class="muted">This email was already confirmed. Sign in to open your workspace.</p>
          <a class="btn btn-primary btn-block" href="<?= View::e(Url::to('/login.php')) ?>">Go to sign in</a>
        <?php else: ?>
          <h2><?= $state === 'expired' ? 'Link expired' : 'Link invalid' ?></h2>
          <p class="muted">Request a fresh code from the verification page, or register again if you no longer have access.</p>
          <a class="btn btn-primary btn-block" href="<?= View::e(Url::to('/verify-email/otp')) ?>">Enter verification code</a>
          <a class="btn btn-ghost btn-block" href="<?= View::e(Url::to('/login.php')) ?>" style="margin-top:.5rem">Sign in</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
