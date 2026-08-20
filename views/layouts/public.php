<?php
use App\Support\Url;
use App\Support\View;
use App\Support\Response;
$flash = Response::pullFlash();
$title = $title ?? 'ZBIF';
$metaDescription = $metaDescription ?? 'ZBIF connects industry challenges to local innovators. Solutions are built, pitched, and deals close at Zimbabwe\'s flagship business innovation forum.';
$ogImage = $ogImage ?? Url::asset('img/zbif-logo-main.png');
$event = $event ?? \App\Domain\EventContext::current();
$forumLabel = 'Forum ' . ($event['edition'] ?? '2026');
$nav = [
  ['How it works', '/how-it-works'],
  ['Participate', '/register'],
  [$forumLabel, '/venue'],
  ['Partners', '/partners'],
  ['Schedule', '/schedule'],
];
?>
<!DOCTYPE html>
<html lang="<?= View::e(\App\Support\I18n::locale()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= View::e($title) ?> | ZBIF</title>
  <meta name="description" content="<?= View::e($metaDescription) ?>">
  <meta property="og:title" content="<?= View::e($title) ?>">
  <meta property="og:description" content="<?= View::e($metaDescription) ?>">
  <meta property="og:type" content="website">
  <meta property="og:image" content="<?= View::e($ogImage) ?>">
  <link rel="icon" href="<?= View::e(Url::asset('img/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="apple-touch-icon" href="<?= View::e(Url::asset('img/favicon.svg')) ?>">
  <link rel="preload" href="<?= View::e(Url::asset('fonts/poppins-600.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/tokens.css')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/components.css')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/site.css')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/auth.css')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/deals.css')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::asset('css/register.css')) ?>">
  <?php if (!empty($event)): ?>
  <script type="application/ld+json">
  <?= json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'Event',
      'name' => 'ZBIF ' . ($event['edition'] ?? '2026'),
      'startDate' => str_replace(' ', 'T', (string) ($event['starts_at'] ?? '2026-10-19 09:00:00')),
      'endDate' => str_replace(' ', 'T', (string) ($event['ends_at'] ?? '2026-10-22 17:00:00')),
      'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
      'location' => [
          '@type' => 'Place',
          'name' => $event['venue'] ?? 'ZITF grounds',
          'address' => ($event['city'] ?? 'Bulawayo') . ', Zimbabwe',
      ],
      'organizer' => ['@type' => 'Organization', 'name' => 'Zimbabwe Business Innovation Forum'],
      'description' => $event['theme'] ?? 'Where industry problems meet local solutions',
      'image' => $ogImage,
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>
  <?php endif; ?>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header" id="siteHeader">
  <div class="container nav-main">
    <a class="brand brand-logo" href="<?= View::e(Url::to('/')) ?>" aria-label="ZBIF home">
      <img src="<?= View::e(Url::asset('img/zbif-logo-main.png')) ?>" alt="ZBIF · Zimbabwe Business Innovation Forum" width="160" height="48">
    </a>

    <nav class="nav-simple" aria-label="Primary">
      <?php foreach ($nav as $item): ?>
        <a href="<?= View::e(Url::to($item[1])) ?>"><?= View::e($item[0]) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="nav-actions">
      <button class="dash-icon-btn nav-search-btn" type="button" id="searchOpen" aria-label="Search site" title="Search (Ctrl+K)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
      <a class="nav-text-link hide-sm" href="<?= View::e(Url::to('/login.php')) ?>">Sign in</a>
      <a class="btn btn-primary btn-sm" href="<?= View::e(Url::to('/register')) ?>">Get started <span aria-hidden="true">→</span></a>
      <button class="btn btn-ghost btn-sm nav-burger" type="button" id="navToggle" aria-expanded="false" aria-controls="mobileDrawer" aria-label="Open menu">Menu</button>
    </div>
  </div>
</header>

<div class="mobile-drawer" id="mobileDrawer" hidden>
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
    <strong>Menu</strong>
    <button class="btn btn-ghost btn-sm" type="button" id="navClose">Close</button>
  </div>
  <?php foreach ($nav as $item): ?>
    <a href="<?= View::e(Url::to($item[1])) ?>"><?= View::e($item[0]) ?></a>
  <?php endforeach; ?>
  <div class="drawer-ctas">
    <a class="btn btn-outline btn-block" href="<?= View::e(Url::to('/login.php')) ?>">Sign in</a>
    <a class="btn btn-primary btn-block" href="<?= View::e(Url::to('/register')) ?>">Get started</a>
  </div>
</div>

<div class="cmdk-backdrop" id="cmdk" role="dialog" aria-modal="true" aria-label="Search">
  <div class="cmdk">
    <input type="search" id="cmdkInput" placeholder="Search pages…" autocomplete="off">
    <div class="cmdk-list" id="cmdkList"></div>
  </div>
</div>

<main id="main">
  <?= $content ?>
</main>
<?php require ZBIF_ROOT . '/views/partials/flash-toasts.php'; ?>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <a class="brand brand-logo brand-logo-footer" href="<?= View::e(Url::to('/')) ?>">
          <img src="<?= View::e(Url::asset('img/zbif-logo-white.png')) ?>" alt="ZBIF" width="140" height="42">
        </a>
        <p class="muted" style="color:rgba(255,255,255,.65);margin-top:1rem">Where industry problems meet local solutions. Challenges, matches, pitches, and deals at Zimbabwe's flagship innovation forum.</p>
      </div>
      <div class="footer-col"><strong>Explore</strong>
        <a href="<?= View::e(Url::to('/how-it-works')) ?>">How it works</a>
        <a href="<?= View::e(Url::to('/register')) ?>">Participate</a>
        <a href="<?= View::e(Url::to('/schedule')) ?>">Schedule</a>
      </div>
      <div class="footer-col"><strong>Forum</strong>
        <a href="<?= View::e(Url::to('/venue')) ?>">Forum 2026</a>
        <a href="<?= View::e(Url::to('/partners')) ?>">Partners</a>
        <a href="<?= View::e(Url::to('/speakers')) ?>">Speakers</a>
      </div>
      <div class="footer-col"><strong>Account</strong>
        <a href="<?= View::e(Url::to('/login.php')) ?>">Sign in</a>
        <a href="<?= View::e(Url::to('/register')) ?>">Create account</a>
        <a href="<?= View::e(Url::to('/contact')) ?>">Contact</a>
      </div>
    </div>
    <div class="footer-news">
      <div>
        <strong style="color:#fff">Get forum updates</strong>
        <p style="margin:0.35rem 0 0;color:rgba(255,255,255,.65);max-width:36ch">Match announcements, deadlines, and innovator spotlights.</p>
      </div>
      <form method="post" action="<?= View::e(Url::to('/foresight/download')) ?>" class="footer-news-form">
        <?= \App\Support\Csrf::field() ?>
        <input type="hidden" name="name" value="Newsletter">
        <input type="email" name="email" required placeholder="email" aria-label="Email">
        <button class="btn btn-primary btn-sm" type="submit">Join</button>
      </form>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> Zimbabwe Business Innovation Forum</span>
      <span><a href="<?= View::e(Url::to('/faq')) ?>">Privacy</a> · <a href="<?= View::e(Url::to('/contact')) ?>">Terms</a></span>
    </div>
  </div>
</footer>

<div class="zbif-modal" id="partnerSoonModal" hidden>
  <div class="zbif-modal-backdrop" data-partner-soon-close></div>
  <div class="zbif-modal-panel" role="dialog" aria-modal="true" aria-labelledby="partnerSoonTitle">
    <button type="button" class="zbif-modal-close" data-partner-soon-close aria-label="Close">×</button>
    <p class="eyebrow">Partnerships</p>
    <h2 id="partnerSoonTitle">Coming soon</h2>
    <p>Company self-service partnership applications (name + logo) will open here shortly. For now, use Contact if you need to reach the partnerships team.</p>
    <div class="zbif-modal-actions">
      <button type="button" class="btn btn-primary" data-partner-soon-close>Got it</button>
      <a class="btn btn-ghost" href="<?= View::e(Url::to('/contact')) ?>">Contact us</a>
    </div>
  </div>
</div>

<?php
$novaMode = 'assist';
$novaOpen = false;
$novaTitle = 'Nova · ZBIF assist';
$novaHint = 'Questions about ZBIF, registration, and the forum.';
require ZBIF_ROOT . '/views/partials/nova-dock.php';
?>
<script src="<?= View::e(Url::asset('js/theme.js')) ?>" defer></script>
<script src="<?= View::e(Url::asset('js/toast.js')) ?>" defer></script>
<script src="<?= View::e(Url::asset('js/app.js')) ?>" defer></script>
<script src="<?= View::e(Url::asset('js/nova.js')) ?>" defer></script>
</body>
</html>
