<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;
?>
<form method="post" action="<?= View::e(Url::to('/admin/login.php')) ?>" class="admin-auth-card" id="adminLoginForm" autocomplete="on">
  <?= Csrf::field() ?>
  <input type="hidden" name="redirect" value="<?= View::e($_GET['redirect'] ?? '/dashboard') ?>">
  <div class="form-group">
    <label for="admin_email">Email</label>
    <input id="admin_email" type="email" name="email" required autocomplete="username" placeholder="Enter your organiser email">
  </div>
  <div class="form-group">
    <div class="admin-auth-label-row">
      <label for="admin_password">Password</label>
      <a href="<?= View::e(Url::to('/password/forgot')) ?>">Forgot password?</a>
    </div>
    <div class="password-wrap">
      <input id="admin_password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
      <button class="password-toggle" type="button" data-toggle-password="admin_password" aria-label="Show password">Show</button>
    </div>
  </div>
  <label class="admin-auth-remember"><input type="checkbox" name="remember" value="1"> Remember me for 30 days</label>
  <button class="btn btn-primary btn-block" type="submit">Sign In</button>
  <a class="btn btn-outline" href="<?= View::e(Url::to('/login.php')) ?>">← Back to participant login</a>
  <p class="admin-auth-secure"><span aria-hidden="true">⛨</span> Secure admin access · Zimbabwe Business Innovation Forum</p>
</form>
