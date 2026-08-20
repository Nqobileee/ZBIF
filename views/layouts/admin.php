<?php
use App\Auth\Auth;
use App\Rbac\Gate;
use App\Support\Response;
use App\Support\Url;
use App\Support\View;
$flash = Response::pullFlash();
$title = $title ?? 'Admin';
$user = Auth::user();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/dashboard', PHP_URL_PATH) ?: '/dashboard';
$roles = $user ? Gate::rolesFor((int) $user['id']) : [];
$roleLabel = $roles ? str_replace('_', ' ', $roles[0]) : 'Administrator';
$initials = strtoupper(substr((string)($user['first_name'] ?? 'A'), 0, 1) . substr((string)($user['last_name'] ?? 'Z'), 0, 1));
$navGroups = [
  'Command' => [
    ['Overview', '/dashboard', 'speedometer2'],
    ['Events & dates', '/admin/events', 'calendar-event'],
    ['Programme', '/admin/programme', 'calendar3'],
    ['Settings', '/admin/settings', 'gear'],
  ],
  'Marketplace' => [
    ['Challenge screening', '/admin/screening', 'funnel'],
    ['Matching · soon', '/admin/matching', 'arrow-left-right'],
    ['Deal rooms', '/admin/deals', 'briefcase'],
    ['Booths', '/admin/booths', 'shop'],
    ['Inquiries', '/admin/inquiries', 'inbox'],
  ],
  'Intelligence' => [
    ['Reports', '/admin/reports', 'bar-chart'],
    ['Impact', '/admin/impact', 'graph-up-arrow'],
    ['Surveys', '/admin/surveys', 'clipboard2-check'],
    ['Sponsorship', '/admin/sponsorship', 'award'],
    ['Awards', '/admin/awards', 'trophy'],
  ],
  'People & content' => [
    ['Registrations', '/admin/registrations', 'person-check'],
    ['Companies', '/admin/organizations', 'buildings'],
    ['Users', '/admin/users', 'people'],
    ['Comms', '/admin/comms', 'envelope'],
    ['CMS', '/admin/cms', 'pencil-square'],
    ['Audit log', '/admin/audit', 'journal-text'],
  ],
];
$isActive = static function (string $href) use ($path): bool {
    if ($href === '/dashboard') {
        return $path === '/dashboard' || $path === '/admin' || $path === '/admin/';
    }
    if ($href === '/admin/settings') {
        return str_starts_with($path, '/admin/settings') || str_starts_with($path, '/admin/system');
    }
    return str_starts_with($path, $href);
};
$subtitle = $subtitle ?? 'Organiser command center for ZBIF forum operations.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= View::e($title) ?> | ZBIF Admin</title>
  <link rel="icon" href="<?= View::e(Url::asset('img/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/tokens.css')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/components.css')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/admin.css')) ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="adm-body">
<div class="adm-shell is-nav-open" id="admShell">
  <div class="adm-nav-scrim" id="admNavScrim" hidden></div>
  <aside class="adm-side" id="admSide" aria-label="Admin navigation">
    <a class="adm-brand" href="<?= View::e(Url::to('/dashboard')) ?>">
      <img class="adm-brand-logo" src="<?= View::e(Url::asset('img/zbif-logo-white.png')) ?>" alt="ZBIF" width="140" height="42">
    </a>
    <?php
      $adminEvents = \App\Domain\EventContext::all();
      $adminActive = \App\Domain\EventContext::id();
    ?>
    <form class="adm-event" method="post" action="<?= View::e(Url::to('/admin/events/switch')) ?>">
      <?= \App\Support\Csrf::field() ?>
      <input type="hidden" name="redirect" value="<?= View::e($_SERVER['REQUEST_URI'] ?? '/dashboard') ?>">
      <label for="admEvent">Active event</label>
      <select id="admEvent" name="event_id" onchange="this.form.submit()">
        <?php foreach ($adminEvents as $ev): ?>
          <option value="<?= (int)$ev['id'] ?>" <?= (int)$ev['id']===(int)$adminActive?'selected':'' ?>><?= View::e($ev['edition'].' · '.$ev['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <nav class="adm-nav">
      <?php foreach ($navGroups as $label => $items): ?>
        <div class="adm-nav-label"><?= View::e($label) ?></div>
        <?php foreach ($items as [$name, $href, $ico]): ?>
          <a class="<?= $isActive($href) ? 'is-active' : '' ?>" href="<?= View::e(Url::to($href)) ?>" title="<?= View::e($name) ?>">
            <span class="nav-ico" aria-hidden="true"><i class="bi bi-<?= View::e($ico) ?>"></i></span><span class="nav-text"><?= View::e($name) ?></span>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="adm-side-foot">
      <div class="adm-user">
        <span class="adm-avatar"><?= View::e($initials) ?></span>
        <div>
          <strong><?= View::e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Organiser') ?></strong>
          <span><?= View::e(ucwords($roleLabel)) ?></span>
        </div>
      </div>
      <form method="post" action="<?= View::e(Url::to('/logout')) ?>">
        <?= \App\Support\Csrf::field() ?>
        <button class="btn btn-ghost btn-sm" type="submit" style="color:#fff;border-color:rgba(255,255,255,.18)"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sign out</button>
      </form>
    </div>
  </aside>
  <div class="adm-main">
    <header class="adm-top">
      <div class="adm-top-left">
        <button class="adm-nav-toggle" type="button" id="admNavToggle" aria-controls="admSide" aria-expanded="true" title="Toggle menu" aria-label="Toggle navigation"><i class="bi bi-list" aria-hidden="true"></i></button>
        <form class="adm-search" action="<?= View::e(Url::to('/admin/reports')) ?>" method="get" role="search">
          <span aria-hidden="true"><i class="bi bi-search"></i></span>
          <input type="search" name="q" placeholder="Search reports, users, deals…" aria-label="Search admin">
        </form>
      </div>
      <div class="adm-top-actions">
        <a class="btn btn-primary btn-sm" href="<?= View::e(Url::to('/admin/events')) ?>">Event dates</a>
        <a class="adm-icon-btn" href="<?= View::e(Url::to('/app')) ?>" title="Participant app" aria-label="Participant app"><i class="bi bi-person-workspace" aria-hidden="true"></i></a>
        <a class="adm-icon-btn" href="<?= View::e(Url::to('/')) ?>" title="Public site" aria-label="Public site"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
      </div>
    </header>
    <div class="adm-content">
      <div class="adm-page-head">
        <div>
          <p class="adm-crumb">ZBIF · Administration</p>
          <h1><?= View::e($title) ?></h1>
          <p><?= View::e($subtitle) ?></p>
        </div>
        <?php if (!empty($pageActions)): ?>
          <div class="adm-actions"><?= $pageActions ?></div>
        <?php endif; ?>
      </div>
      <?= $content ?>
    </div>
  </div>
</div>
<?php require ZBIF_ROOT . '/views/partials/flash-toasts.php'; ?>
<?php
$novaMode = 'assist';
$novaOpen = false;
$novaTitle = 'Nova · Admin assist';
$novaHint = 'Ask about forum operations, programme, and settings.';
require ZBIF_ROOT . '/views/partials/nova-dock.php';
?>
<script src="<?= View::e(Url::asset('js/toast.js')) ?>" defer></script>
<script src="<?= View::e(Url::asset('js/nova.js')) ?>" defer></script>
<script src="<?= View::e(Url::asset('js/admin.js')) ?>" defer></script>
</body>
</html>
