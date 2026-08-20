<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;
$email = $email ?? '';
$masked = $email;
if ($email !== '' && str_contains($email, '@')) {
    [$local, $domain] = explode('@', $email, 2);
    $keep = max(1, min(2, (int) floor(strlen($local) / 2)));
    $masked = substr($local, 0, $keep) . str_repeat('*', max(1, strlen($local) - $keep)) . '@' . $domain;
}
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
    <div class="reg-intro">
      <h1>Verify your email</h1>
      <p class="lead">We sent a six-digit code to <strong><?= View::e($masked) ?></strong>. Enter it below to activate your account.</p>
    </div>

    <div class="reg-main">
      <div class="reg-card verify-card">
        <form method="post" action="<?= View::e(Url::to('/verify-email/otp')) ?>" id="otpForm">
          <?= Csrf::field() ?>
          <input type="hidden" name="code" id="otpCode" value="">
          <div class="otp-inputs" aria-label="Six digit code">
            <?php for ($i = 0; $i < 6; $i++): ?>
              <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>" aria-label="Digit <?= $i + 1 ?>">
            <?php endfor; ?>
          </div>
          <p class="field-hint verify-hint">The code expires in 10 minutes.</p>
          <button class="btn btn-primary btn-block" type="submit">Verify and continue</button>
        </form>

        <form method="post" action="<?= View::e(Url::to('/verify-email/resend')) ?>" class="verify-resend">
          <?= Csrf::field() ?>
          <input type="hidden" name="redirect" value="/verify-email/otp">
          <button class="btn btn-ghost btn-block" type="submit">Resend code</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof bindOtp === 'function') bindOtp(document);
});
</script>
