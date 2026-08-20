<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;
?>
<section class="section container" style="max-width:720px">
  <h1>Confirm authenticator</h1>
  <p class="muted">Scan this secret in your authenticator app, then enter a code to confirm.</p>
  <div class="card">
    <p><strong>Secret:</strong> <code><?= View::e($secret) ?></code></p>
    <p class="muted" style="word-break:break-all;font-size:.85rem"><?= View::e($uri) ?></p>
    <h3>Recovery codes</h3>
    <p class="muted">Store these somewhere safe. Each works once.</p>
    <ul><?php foreach ($recovery as $c): ?><li><code><?= View::e($c) ?></code></li><?php endforeach; ?></ul>
    <form method="post" action="<?= View::e(Url::to('/app/security/2fa/confirm')) ?>">
      <?= Csrf::field() ?>
      <div class="form-group"><label for="code">Authentication code</label><input id="code" name="code" required inputmode="numeric" autocomplete="one-time-code"></div>
      <button class="btn btn-primary" type="submit">Confirm and enable</button>
    </form>
  </div>
</section>
