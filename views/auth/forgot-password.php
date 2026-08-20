<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;
?>
<div class="auth-compact"><strong>ZBIF</strong></div>
<div class="auth-shell">
  <aside class="auth-brand">
    <div class="brand"><span class="brand-mark">ZB</span> ZBIF</div>
    <h1>Reset your password</h1>
    <p>We will email a secure link if an account exists for that address.</p>
  </aside>
  <div class="auth-form"><div class="auth-form-inner">
    <form method="post" action="<?= View::e(Url::to('/password/forgot')) ?>" class="card card-static">
      <?= Csrf::field() ?>
      <h1>Forgot password</h1>
      <div class="form-group"><label for="email">Email</label><input id="email" type="email" name="email" required></div>
      <button class="btn btn-primary btn-block" type="submit">Send reset link</button>
    </form>
    <p class="muted" style="margin-top:1rem"><a href="<?= View::e(Url::to('/login.php')) ?>">Back to sign in</a></p>
  </div></div>
</div>
