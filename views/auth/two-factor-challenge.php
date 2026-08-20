<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;
$action = $action ?? Url::to('/login/2fa');
$isAdmin = str_contains($action, '/admin/');
?>
<?php if ($isAdmin): ?>
<form method="post" action="<?= View::e($action) ?>" class="admin-auth-card">
  <?= Csrf::field() ?>
  <h2 style="margin:0 0 1rem;color:#fff;font-size:1.15rem">Authentication code</h2>
  <div class="form-group"><label for="code">6-digit code</label><input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required></div>
  <button class="btn btn-primary btn-block" type="submit">Verify and continue</button>
</form>
<?php else: ?>
<div class="auth-compact"><strong>ZBIF</strong></div>
<div class="auth-shell">
  <aside class="auth-brand">
    <div class="brand"><span class="brand-mark">ZB</span> ZBIF</div>
    <h1>Two-factor check</h1>
    <p>Enter the code from your authenticator app, or a recovery code.</p>
  </aside>
  <div class="auth-form"><div class="auth-form-inner">
    <form method="post" action="<?= View::e($action) ?>" class="card card-static">
      <?= Csrf::field() ?>
      <h1>Authentication code</h1>
      <div class="form-group"><label for="code">6-digit code</label><input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required></div>
      <button class="btn btn-primary btn-block" type="submit">Verify and continue</button>
    </form>
  </div></div>
</div>
<?php endif; ?>
