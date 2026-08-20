<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;
$token = $token ?? '';
?>
<div class="auth-compact"><strong>ZBIF</strong></div>
<div class="auth-shell">
  <aside class="auth-brand">
    <div class="brand"><span class="brand-mark">ZB</span> ZBIF</div>
    <h1>Choose a new password</h1>
    <p>Use at least 10 characters with letters and numbers.</p>
  </aside>
  <div class="auth-form"><div class="auth-form-inner">
    <form method="post" action="<?= View::e(Url::to('/password/reset')) ?>" class="card card-static">
      <?= Csrf::field() ?>
      <input type="hidden" name="token" value="<?= View::e($token) ?>">
      <h1>Reset password</h1>
      <div class="form-group">
        <label for="password">New password</label>
        <div class="password-wrap">
          <input id="password" type="password" name="password" required minlength="10" autocomplete="new-password">
          <button class="password-toggle" type="button" data-toggle-password="password">Show</button>
        </div>
        <div class="strength" id="strengthMeter" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
      </div>
      <div class="form-group">
        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required minlength="10" autocomplete="new-password">
      </div>
      <button class="btn btn-primary btn-block" type="submit">Update password</button>
    </form>
  </div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const pwd = document.getElementById('password');
  const meter = document.getElementById('strengthMeter');
  if (pwd && meter) {
    pwd.addEventListener('input', () => {
      const v = pwd.value;
      let score = 0;
      if (v.length >= 10) score++;
      if (v.length >= 14) score++;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
      if (/[0-9]/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;
      score = Math.min(4, score);
      [...meter.children].forEach((el, i) => el.classList.toggle('on', i < score));
    });
  }
});
</script>
