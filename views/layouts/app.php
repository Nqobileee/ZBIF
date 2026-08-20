<?php
use App\Auth\Auth;
use App\Rbac\Gate;
use App\Support\Response;
use App\Support\Url;
use App\Support\View;
$user = Auth::user();
$flash = Response::pullFlash();
$title = $title ?? 'App';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/app', PHP_URL_PATH) ?: '/app';
$roles = $user ? Gate::rolesFor((int) $user['id']) : [];
$roleLabel = $roles ? str_replace('_', ' ', $roles[0]) : 'Participant';
$initials = strtoupper(substr((string)($user['first_name'] ?? 'Z'), 0, 1) . substr((string)($user['last_name'] ?? 'B'), 0, 1));
$unread = 0;
if ($user) {
    try {
        $unread = (int) (\App\Support\Database::fetch('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0', [$user['id']])['c'] ?? 0);
    } catch (\Throwable $e) {
        $unread = 0;
    }
}

// Role-aware nav: only show what is relevant
$isInnovator = in_array('innovator', $roles, true) || in_array('university', $roles, true) || in_array('innovation_hub', $roles, true) || in_array('student', $roles, true);
$isCorporate = in_array('corporate', $roles, true) || in_array('government', $roles, true) || in_array('exhibitor', $roles, true);
$navGroups = [
  'Workspace' => [
    ['Dashboard', '/app', 'speedometer2'],
  ],
];
if ($isCorporate || Gate::allows('challenges.submit') || !$roles) {
    $navGroups['Workspace'][] = ['Challenges', '/app/challenges', 'puzzle'];
}
if ($isCorporate || Gate::allows('solutions.view') || Gate::allows('solutions.create') || !$roles) {
    $navGroups['Workspace'][] = ['Solutions', '/app/solutions', 'lightbulb'];
}
if ($isInnovator || $isCorporate || Gate::allows('matching.view') || !$roles) {
    $navGroups['Workspace'][] = ['Matches', '/app/matches', 'arrow-left-right'];
}
$navGroups['Workspace'][] = ['Deal rooms', '/app/deals', 'briefcase'];
$navGroups['Workspace'][] = ['Workspaces', '/app/workspaces', 'kanban'];
$navGroups['Forum'] = [
  ['Exhibit', '/app/exhibit/apply', 'shop-window'],
  ['My agenda', '/app/programme', 'calendar3'],
  ['Meetings', '/app/meetings', 'people'],
  ['Surveys', '/app/surveys', 'clipboard2-check'],
];
if ($user && Gate::allows('investor.deal_flow')) {
    $navGroups['Forum'][] = ['Investor flow', '/app/investor', 'graph-up-arrow'];
}
if ($user && Gate::allows('exhibition.manage_booth')) {
    $navGroups['Forum'][] = ['Booth CRM', '/app/exhibition', 'building'];
}
$isActive = static function (string $href) use ($path): bool {
    if ($href === '/app') {
        return $path === '/app' || $path === '/app/';
    }
    return str_starts_with($path, $href);
};
$hidePageHead = !empty($hidePageHead) || str_starts_with($path, '/app/deals') || str_starts_with($path, '/app/programme') || $path === '/app' || $path === '/app/';
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
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/dashboard.css')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/app-pages.css')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/deals.css')) ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="app-body">
<a class="skip-link" href="#main">Skip to content</a>
<div class="dash-shell is-nav-open" id="dashShell">
  <div class="dash-nav-scrim" id="dashNavScrim" hidden></div>
  <aside class="dash-side" id="dashSide" aria-label="App navigation">
    <a class="dash-brand" href="<?= View::e(Url::to('/app')) ?>">
      <img src="<?= View::e(Url::asset('img/zbif-logo-white.png')) ?>" alt="ZBIF" width="148" height="44">
    </a>
    <nav class="dash-nav">
      <?php foreach ($navGroups as $label => $items): ?>
        <div class="dash-nav-label"><?= View::e($label) ?></div>
        <?php foreach ($items as [$name, $href, $ico]): ?>
          <a class="<?= $isActive($href) ? 'is-active' : '' ?>" href="<?= View::e(Url::to($href)) ?>" title="<?= View::e($name) ?>">
            <span class="nav-ico" aria-hidden="true"><i class="bi bi-<?= View::e($ico) ?>"></i></span><span class="nav-text"><?= View::e($name) ?></span>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="dash-side-foot">
      <strong><?= View::e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Member') ?></strong>
      <span><?= View::e(ucwords(str_replace('_', ' ', $roleLabel))) ?></span>
    </div>
  </aside>
  <div class="dash-main" id="main">
    <header class="dash-top">
      <div class="dash-top-left">
        <button class="dash-icon-btn" type="button" id="navRailToggle" aria-controls="dashSide" aria-expanded="true" title="Toggle menu" aria-label="Toggle navigation"><i class="bi bi-list" aria-hidden="true"></i></button>
        <div class="dash-top-title">
          <strong><?= View::e($title) ?></strong>
        </div>
      </div>
      <div class="dash-top-actions">
        <a class="dash-icon-btn" href="<?= View::e(Url::to('/')) ?>" target="_blank" rel="noopener noreferrer" title="Open public site" aria-label="Open public site"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
        <a class="dash-icon-btn" href="<?= View::e(Url::to('/app/notifications')) ?>" title="Notifications" aria-label="Notifications<?= $unread > 0 ? ' (' . $unread . ' unread)' : '' ?>">
          <i class="bi bi-bell" aria-hidden="true"></i><?php if ($unread > 0): ?><span class="dash-badge"><?= $unread > 9 ? '9+' : (int)$unread ?></span><?php endif; ?>
        </a>
        <a class="dash-icon-btn dash-avatar-btn" href="<?= View::e(Url::to('/app/profile')) ?>" title="Profile" aria-label="Profile"><?= View::e($initials) ?></a>
        <form method="post" action="<?= View::e(Url::to('/logout')) ?>" class="dash-logout-form">
          <?= \App\Support\Csrf::field() ?>
          <button class="dash-icon-btn" type="submit" title="Sign out" aria-label="Sign out"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></button>
        </form>
      </div>
    </header>
    <div class="dash-body">
      <?php if (!$hidePageHead): ?>
        <div class="dash-page-head">
          <h1><?= View::e($title) ?></h1>
        </div>
      <?php endif; ?>
      <?= $content ?>
    </div>
  </div>
</div>
<?php require ZBIF_ROOT . '/views/partials/flash-toasts.php'; ?>
<?php
$novaMode = 'assist';
$novaOpen = false;
$novaTitle = 'Nova · ZBIF assist';
$novaHint = 'Ask about challenges, deals, and the forum.';
require ZBIF_ROOT . '/views/partials/nova-dock.php';
?>
<script src="<?= View::e(Url::asset('js/toast.js')) ?>" defer></script>
<script src="<?= View::e(Url::asset('js/app.js')) ?>" defer></script>
<script src="<?= View::e(Url::asset('js/nova.js')) ?>" defer></script>
</body>
</html>
