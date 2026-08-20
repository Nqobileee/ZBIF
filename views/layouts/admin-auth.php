<?php
use App\Support\Response;
use App\Support\Url;
use App\Support\View;
$flash = Response::pullFlash();
$title = $title ?? 'Admin sign in';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= View::e($title) ?> | ZBIF</title>
  <link rel="icon" href="<?= View::e(Url::asset('img/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/tokens.css')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/components.css')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/admin-auth.css')) ?>">
</head>
<body class="admin-auth-body">
  <section class="admin-auth-left">
    <div class="admin-auth-brand-row">
      <a class="admin-auth-logo" href="<?= View::e(Url::to('/')) ?>">
        <img src="<?= View::e(Url::asset('img/zbif-logo-main.png')) ?>" alt="ZBIF" width="148" height="44">
      </a>
      <a class="admin-auth-switch" href="<?= View::e(Url::to('/login.php')) ?>">Participant sign in →</a>
    </div>
    <p class="admin-auth-kicker">Platform administration</p>
    <h1>Admin Portal</h1>
    <p class="admin-auth-sub">Secure access to ZBIF organiser management</p>
    <?= $content ?>
  </section>
  <aside class="admin-auth-right" aria-hidden="false">
    <div class="admin-auth-hero-card">
      <h2>Command center for ZBIF</h2>
      <p>Configure forum dates, programme, SMTP, registrations, Deal Rooms, and reports from one secure control plane.</p>
    </div>
    <article class="admin-auth-feature">
      <span class="tag">Forum control</span>
      <h3>Event &amp; programme</h3>
      <p>Publish dates that update the public countdown, and keep the day schedule accurate across the site.</p>
    </article>
    <article class="admin-auth-feature">
      <span class="tag">Intelligence</span>
      <h3>Reports &amp; impact</h3>
      <p>Track registrations, companies, deal stages, and outcomes with exportable reports.</p>
    </article>
    <article class="admin-auth-feature">
      <span class="tag">Security</span>
      <h3>Audited access</h3>
      <p>Organiser login is separated from participant sign-in. Privileged actions are gated and logged.</p>
    </article>
  </aside>
  <?php require ZBIF_ROOT . '/views/partials/flash-toasts.php'; ?>
  <script src="<?= View::e(Url::asset('js/toast.js')) ?>" defer></script>
  <script src="<?= View::e(Url::asset('js/auth.js')) ?>" defer></script>
</body>
</html>
