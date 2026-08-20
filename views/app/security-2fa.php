<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;
?>
<section class="section container" style="max-width:720px">
  <h1>Two-factor authentication</h1>
  <?php if (!empty($enabled)): ?>
    <p class="muted">Authenticator app 2FA is enabled<?= !empty($required) ? ' and required for your role' : '' ?>.</p>
    <?php if (empty($required)): ?>
      <form method="post" action="<?= View::e(Url::to('/app/security/2fa/disable')) ?>">
        <?= Csrf::field() ?>
        <button class="btn btn-ghost" type="submit">Disable 2FA</button>
      </form>
    <?php endif; ?>
  <?php else: ?>
    <p class="muted">Add an authenticator app for stronger account protection<?= !empty($required) ? '. Your role requires 2FA.' : '.' ?></p>
    <form method="post" action="<?= View::e(Url::to('/app/security/2fa/enable')) ?>">
      <?= Csrf::field() ?>
      <button class="btn btn-primary" type="submit">Set up 2FA</button>
    </form>
  <?php endif; ?>
  <p style="margin-top:1.5rem"><a href="<?= View::e(Url::to('/app/profile')) ?>">Back to profile</a></p>
</section>
