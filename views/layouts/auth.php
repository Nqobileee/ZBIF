<?php
use App\Support\Url;
use App\Support\View;
use App\Support\Response;
$flash = Response::pullFlash();
$title = $title ?? 'Account';
$isRegister = ($title === 'Register');
$isLogin = ($title === 'Sign in');
$isVerify = in_array($title, ['Verify email', 'Email verified', 'Already verified', 'Link expired', 'Registration complete'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= View::e($title) ?> | ZBIF</title>
  <link rel="icon" href="<?= View::e(Url::asset('img/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="preload" href="<?= View::e(Url::asset('fonts/poppins-600.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/tokens.css')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/components.css')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/site.css')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/auth.css')) ?>">
  <?php if ($isLogin): ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <?php endif; ?>
  <?php if ($isRegister || $isVerify): ?>
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/register.css')) ?>">
  <?php endif; ?>
</head>
<body class="<?= $isRegister || $isVerify ? 'is-register' : ($isLogin ? 'is-login' : '') ?>">
<a class="skip-link" href="#main">Skip to content</a>
<main id="main">
  <?= $content ?>
</main>
<?php require ZBIF_ROOT . '/views/partials/flash-toasts.php'; ?>
<script src="<?= View::e(Url::asset('js/toast.js')) ?>" defer></script>
<script src="<?= View::e(Url::asset('js/auth.js')) ?>" defer></script>
<?php if ($isRegister): ?>
<script src="<?= View::e(Url::asset('js/nova.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
