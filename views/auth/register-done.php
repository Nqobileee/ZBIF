<?php
use App\Support\Url;
use App\Support\View;
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
        <h2>Check your email</h2>
        <p class="muted">We sent a verification code to your inbox. Enter it to activate your account, then sign in.</p>
        <a class="btn btn-primary btn-block" href="<?= View::e(Url::to('/verify-email/otp')) ?>">Enter verification code</a>
      </div>
    </div>
  </div>
</div>
