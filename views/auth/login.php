<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;
?>
<div class="login-stage">
  <div class="login-card">
    <aside class="login-pane login-pane-brand" aria-label="About ZBIF">
      <a class="login-brand" href="<?= View::e(Url::to('/')) ?>">
        <img src="<?= View::e(Url::asset('img/zbif-logo-white.png')) ?>" alt="ZBIF" width="148" height="44">
        <span>Zimbabwe Business Innovation Forum</span>
      </a>
      <div class="login-brand-copy">
        <h1>Build matches and close deals with confidence.</h1>
        <p>Access challenges, Deal Rooms, your agenda, and forum tools in one secure workspace.</p>
      </div>
      <div class="login-feature-list">
        <div class="login-feature">
          <i class="bi bi-lightning-charge" aria-hidden="true"></i>
          <span>Challenge → match → pitch → deal</span>
          <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </div>
        <div class="login-feature">
          <i class="bi bi-shield-check" aria-hidden="true"></i>
          <span>Enterprise-grade session security</span>
          <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </div>
      </div>
    </aside>

    <section class="login-pane login-pane-form">
      <div class="login-form-wrap">
        <div class="login-secure-badge"><i class="bi bi-lock-fill" aria-hidden="true"></i> Secure ZBIF sign-in</div>
        <h2>Welcome back.</h2>
        <p class="login-sub">Sign in to continue to your workspace.</p>

        <form method="post" action="<?= View::e(Url::to('/login.php')) ?>" id="loginForm" autocomplete="on">
          <?= Csrf::field() ?>
          <input type="hidden" name="redirect" value="<?= View::e($_GET['redirect'] ?? '/app') ?>">

          <div class="form-group">
            <label for="login_email">Email address</label>
            <div class="login-field">
              <i class="bi bi-envelope" aria-hidden="true"></i>
              <input id="login_email" type="email" name="email" required autocomplete="username" placeholder="you@organisation.co.zw">
            </div>
          </div>

          <div class="form-group">
            <label for="login_password">Password</label>
            <div class="login-field">
              <i class="bi bi-lock" aria-hidden="true"></i>
              <input id="login_password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
              <button class="login-eye" type="button" data-toggle-password="login_password" aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
          </div>

          <div class="login-row">
            <label class="login-remember"><input type="checkbox" name="remember" value="1"> Remember me</label>
            <a href="<?= View::e(Url::to('/password/forgot')) ?>">Forgot password?</a>
          </div>

          <button class="btn btn-primary btn-block login-submit" type="submit">
            Sign in <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
          </button>
        </form>

        <details class="login-magic">
          <summary>Prefer a one-time login link?</summary>
          <form method="post" action="<?= View::e(Url::to('/login/magic')) ?>">
            <?= Csrf::field() ?>
            <div class="form-group" style="margin-top:.75rem">
              <label for="magic_email">Email</label>
              <div class="login-field">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <input id="magic_email" type="email" name="email" required placeholder="you@organisation.co.zw">
              </div>
            </div>
            <button class="btn btn-outline btn-block" type="submit">Send login link</button>
          </form>
        </details>

        <div class="login-note">
          <i class="bi bi-rocket-takeoff" aria-hidden="true"></i>
          <div>
            <strong>Challenges, deal rooms, and live forum tools in one place.</strong>
            <div class="login-note-tags">
              <span><i class="bi bi-stars" aria-hidden="true"></i> Marketplace ready</span>
              <span><i class="bi bi-shield-lock" aria-hidden="true"></i> Secure by design</span>
              <span><i class="bi bi-people" aria-hidden="true"></i> Built for partners</span>
            </div>
          </div>
        </div>

        <p class="login-foot">No account? <a href="<?= View::e(Url::to('/register')) ?>">Register</a></p>
        <p class="login-copy">© <?= date('Y') ?> Zimbabwe Business Innovation Forum</p>
      </div>
    </section>
  </div>
</div>
