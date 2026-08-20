<?php
use App\Support\Url;
use App\Support\View;
?>
<div class="auth-compact"><strong>ZBIF</strong></div>
<div class="auth-shell">
  <aside class="auth-brand">
    <div class="brand"><span class="brand-mark">ZB</span> ZBIF</div>
    <h1>Reset link unavailable</h1>
  </aside>
  <div class="auth-form"><div class="auth-form-inner">
    <div class="card card-static">
      <h1>Invalid or expired link</h1>
      <p>Request a new password reset. Links expire after 60 minutes and can only be used once.</p>
      <a class="btn btn-primary" href="<?= View::e(Url::to('/password/forgot')) ?>">Request new link</a>
    </div>
  </div></div>
</div>
