<?php
use App\Domain\EventPresentation;
use App\Support\Url;
use App\Support\View;

$event = $event ?? [];
$edition = $event['edition'] ?? '2026';
$city = $event['city'] ?? 'Bulawayo';
$venue = $event['venue'] ?? '';
$dateLabel = EventPresentation::dateRangeLabel($event);
$starts = EventPresentation::countdownIso($event);
$venueLine = EventPresentation::venueShort($event);
$untilLine = EventPresentation::untilLine($event);
$metrics = $metrics ?? [];
$partners = $partners ?? [];
$challengeCount = (int) ($metrics['challenges_submitted'] ?? $metrics['challenges_published'] ?? 0);
$matchCount = (int) ($metrics['matches_made'] ?? 0);
$glance = [
  ['challenges_submitted', 'Challenges submitted'],
  ['innovators_registered', 'Innovators registered'],
  ['matches_made', 'Matches made'],
  ['pitches_confirmed', 'Pitches confirmed'],
];
$process = [
  ['Companies submit challenges', 'Industry players share real operational problems they need solved, with budget, timeline, and context.'],
  ['Innovators get matched', 'Startups, universities, and SMEs are paired with challenges that fit their skills and sector focus.'],
  ['Solutions are developed', 'Matched teams build products, process improvements, or training programmes with mentor support.'],
  ['Deals close at the forum', 'Final pitches happen live at ZBIF, where procurement, pilots, and partnerships get signed.'],
];
?>
<section class="hero-home">
  <div class="container hero-home-grid">
    <div class="hero-copy">
      <p class="eyebrow">ZBIF <?= View::e((string)$edition) ?> · <?= View::e($city) ?><?= $venue ? ' · ' . View::e($venue) : '' ?></p>
      <h1>Zimbabwe Business Innovation Forum</h1>
      <p class="lead">Where industry problems meet local solutions. Companies submit real challenges. Innovators build answers. Deals get made at the forum.</p>
      <div class="hero-actions">
        <a class="btn btn-primary btn-lg" href="<?= View::e(Url::to('/register')) ?>">Get started <span aria-hidden="true">→</span></a>
        <a class="btn btn-outline btn-lg" href="<?= View::e(Url::to('/how-it-works')) ?>">How it works</a>
      </div>
      <div class="hero-meta">
        <span><?= View::e($dateLabel) ?></span>
        <div class="countdown" id="countdown" data-target="<?= View::e($starts) ?>" aria-live="polite"></div>
      </div>
    </div>
    <div class="hero-panel" aria-hidden="true">
      <div class="hero-panel-card">
        <div class="eyebrow">Live marketplace</div>
        <strong>Challenge → Match → Pitch → Deal</strong>
        <p>A structured pipeline connecting Zimbabwe's industry with its innovation ecosystem.</p>
      </div>
    </div>
  </div>
</section>

<section class="section glance-band" id="platform">
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow">Platform at a glance</p>
      <h2>Momentum you can measure</h2>
      <p class="lead">Live figures from the ZBIF marketplace.</p>
    </div>
    <div class="glance-grid reveal">
      <?php foreach ($glance as [$key, $label]):
        $val = (int) ($metrics[$key] ?? 0);
      ?>
        <article class="glance-card">
          <strong data-count="<?= $val ?>"><?= $val ?></strong>
          <span><?= View::e($label) ?></span>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section container" id="process">
  <div class="section-head reveal">
    <p class="eyebrow">The process</p>
    <h2>How ZBIF works</h2>
    <p class="lead">A structured pipeline from challenge to deal, connecting Zimbabwe's industry with its innovation ecosystem.</p>
  </div>
  <div class="process-grid">
    <?php foreach ($process as $i => $step): ?>
      <article class="process-card reveal">
        <div class="process-num"><?= (int) $i + 1 ?></div>
        <h3><?= View::e($step[0]) ?></h3>
        <p class="muted"><?= View::e($step[1]) ?></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="section section-wash" id="get-involved">
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow is-navy">Get involved</p>
      <h2>Who is ZBIF for?</h2>
    </div>
    <div class="involve-grid">
      <article class="involve-card reveal">
        <h3>For companies</h3>
        <ul>
          <li>Submit a real business challenge</li>
          <li>Access vetted local innovators</li>
          <li>Pilot or procure proven solutions</li>
        </ul>
        <a class="btn btn-primary" href="<?= View::e(Url::to('/register')) ?>">Register your organisation</a>
      </article>
      <article class="involve-card reveal">
        <h3>For innovators</h3>
        <ul>
          <li>Register your capabilities</li>
          <li>Get matched to paid challenges</li>
          <li>Pitch at Zimbabwe's flagship forum</li>
        </ul>
        <a class="btn btn-outline" href="<?= View::e(Url::to('/register')) ?>">Register as innovator</a>
      </article>
    </div>
  </div>
</section>

<section class="section container" id="forum">
  <div class="forum-band reveal">
    <div class="forum-band-copy">
      <p class="eyebrow" style="color:var(--gold-500)">ZBIF <?= View::e((string)$edition) ?></p>
      <h2>The forum where deals happen</h2>
      <p>Live pitches, deal rooms, and networking in <?= View::e($city) ?>. Matched innovators present solutions to the companies that need them, with procurement and partnership decisions on the spot.</p>
      <ul class="forum-meta">
        <li><span class="forum-meta-ico" aria-hidden="true">◷</span><?= View::e($dateLabel) ?></li>
        <li><span class="forum-meta-ico" aria-hidden="true">⌖</span><?= View::e($venueLine) ?></li>
        <li><span class="forum-meta-ico" aria-hidden="true">✦</span><?= (int)$challengeCount ?> challenges · <?= (int)$matchCount ?> matches confirmed</li>
      </ul>
    </div>
    <div class="forum-band-aside">
      <p class="forum-count-label">Countdown to forum</p>
      <div class="countdown countdown-gold" id="countdown2" data-target="<?= View::e($starts) ?>" aria-live="polite"></div>
      <p class="forum-until"><?= View::e($untilLine) ?></p>
      <a class="btn btn-primary" href="<?= View::e(Url::to('/programme')) ?>">View full schedule <span aria-hidden="true">→</span></a>
    </div>
  </div>
</section>

<section class="section container partners-section" id="partners">
  <div class="section-head section-head-center reveal">
    <p class="eyebrow">Our partners</p>
    <h2>Powered by industry leaders</h2>
    <p class="lead">ZBIF is made possible through the support of leading organisations across banking, government, academia, and innovation.</p>
  </div>
  <div class="partners-grid reveal">
    <?php foreach ($partners as $sp): ?>
      <?php if (empty($sp['logo_path'])) continue; ?>
      <div class="partner-cell">
        <img src="<?= View::e(Url::asset(ltrim((string)$sp['logo_path'], '/'))) ?>" alt="<?= View::e($sp['name']) ?>" class="partner-logo" loading="lazy">
        <strong><?= View::e($sp['name']) ?></strong>
      </div>
    <?php endforeach; ?>
    <?php if (!$partners): ?>
      <p class="muted" style="grid-column:1/-1;text-align:center">Partner logos will appear here as confirmations land.</p>
    <?php endif; ?>
  </div>
  <p class="partners-cta">
    <button class="btn btn-outline" type="button" data-partner-soon>Become a partner</button>
  </p>
</section>

<section class="section container">
  <div class="cta-band reveal">
    <div>
      <h2>Ready to join ZBIF?</h2>
      <p>Create an account to submit challenges, register as an innovator, or apply to exhibit.</p>
    </div>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap">
      <a class="btn btn-primary" href="<?= View::e(Url::to('/register')) ?>">Get started</a>
      <a class="btn btn-on-dark" href="<?= View::e(Url::to('/login.php')) ?>">Sign in</a>
    </div>
  </div>
</section>
