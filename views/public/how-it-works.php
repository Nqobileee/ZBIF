<?php
use App\Support\Url;
use App\Support\View;

$steps = [
  [
    'n' => '01',
    'title' => 'Companies submit',
    'text' => 'Industry players post real operational challenges with context, timelines, and what a good outcome looks like.',
    'icon' => 'submit',
  ],
  [
    'n' => '02',
    'title' => 'Innovators build',
    'text' => 'Startups, universities, and hubs are matched to fit challenges, then develop solutions in guided workspaces.',
    'icon' => 'build',
  ],
  [
    'n' => '03',
    'title' => 'Present at the forum',
    'text' => 'Matched teams pitch and demo live in front of decision-makers, buyers, and investors.',
    'icon' => 'present',
  ],
  [
    'n' => '04',
    'title' => 'Deal Rooms close',
    'text' => 'Private Deal Rooms turn conversations into MOUs, pilots, procurement, or investment.',
    'icon' => 'deal',
  ],
];

$personas = [
  ['Corporate / Industry', 'Submit challenges, shortlist solvers, open Deal Rooms, and signal procurement or partnership intent.'],
  ['Innovator / Startup', 'Claim challenges, build with mentors, pitch at the forum, and close commercial outcomes.'],
  ['University / Academia', 'Field research teams, showcase innovation, and partner with industry on pilots.'],
  ['Investor / DFI', 'Review curated deal flow, keep private notes, and engage in pitch sessions.'],
  ['Innovation Hub', 'Offer mentors and incubation across a portfolio of innovators.'],
  ['Government / Policy', 'Join curated networking and ecosystem analytics that show marketplace outcomes.'],
];

$icons = [
  'submit' => '<svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><rect x="8" y="10" width="32" height="28" rx="4" stroke="currentColor" stroke-width="2"/><path d="M16 18h16M16 24h12M16 30h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="34" cy="34" r="8" fill="var(--navy-900)" stroke="var(--gold-500)" stroke-width="2"/><path d="M34 30v8M30 34h8" stroke="var(--gold-500)" stroke-width="2" stroke-linecap="round"/></svg>',
  'build' => '<svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M14 34V18l10-6 10 6v16" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M20 34V24h8v10" stroke="currentColor" stroke-width="2"/><path d="M10 34h28" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="24" cy="14" r="3" fill="var(--gold-500)"/></svg>',
  'present' => '<svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><rect x="8" y="10" width="32" height="20" rx="3" stroke="currentColor" stroke-width="2"/><path d="M18 38h12M24 30v8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M14 22l6-4 5 3 7-5" stroke="var(--gold-500)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
  'deal' => '<svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M12 28c4-6 8-9 12-9s8 3 12 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="18" cy="20" r="4" stroke="currentColor" stroke-width="2"/><circle cx="30" cy="20" r="4" stroke="currentColor" stroke-width="2"/><path d="M16 34h16" stroke="var(--gold-500)" stroke-width="2" stroke-linecap="round"/><path d="M20 34l4 4 8-8" stroke="var(--gold-500)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
];
?>
<section class="hiw-hero">
  <div class="container hiw-hero-inner">
    <p class="eyebrow">Marketplace</p>
    <h1>How it works</h1>
    <p class="lead">Four clear steps from an industry problem to a signed outcome. One pipeline. A path for every persona.</p>
  </div>
</section>

<section class="section container hiw-journey-section">
  <div class="section-head reveal">
    <p class="eyebrow">The pipeline</p>
    <h2>From challenge to deal</h2>
    <p class="lead">Follow the marketplace flow. Each step hands off cleanly to the next.</p>
  </div>

  <ol class="hiw-journey" aria-label="ZBIF marketplace steps">
    <?php foreach ($steps as $i => $step): ?>
      <li class="hiw-step reveal" style="--hiw-i:<?= (int) $i ?>">
        <div class="hiw-step-rail" aria-hidden="true">
          <span class="hiw-step-dot"><?= View::e($step['n']) ?></span>
          <?php if ($i < count($steps) - 1): ?><span class="hiw-step-line"></span><?php endif; ?>
        </div>
        <div class="hiw-step-body">
          <div class="hiw-step-icon"><?= $icons[$step['icon']] ?? $icons['submit'] ?></div>
          <div class="hiw-step-copy">
            <h3><?= View::e($step['title']) ?></h3>
            <p><?= View::e($step['text']) ?></p>
          </div>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>
</section>

<section class="hiw-flow-band">
  <div class="container">
    <div class="hiw-flow reveal" aria-hidden="true">
      <span>Submit</span>
      <i></i>
      <span>Match</span>
      <i></i>
      <span>Build</span>
      <i></i>
      <span>Pitch</span>
      <i></i>
      <span>Close</span>
    </div>
    <p class="hiw-flow-caption reveal">ZBIF is a marketplace, not a conference brochure. Progress is measured in matches, pitches, and closed deals.</p>
  </div>
</section>

<section class="section container">
  <div class="section-head reveal">
    <p class="eyebrow is-navy">Persona paths</p>
    <h2>Find your lane</h2>
    <p class="lead">Same marketplace. Different entry points depending on who you are.</p>
  </div>
  <div class="hiw-personas">
    <?php foreach ($personas as $p): ?>
      <article class="hiw-persona reveal">
        <h3><?= View::e($p[0]) ?></h3>
        <p><?= View::e($p[1]) ?></p>
      </article>
    <?php endforeach; ?>
  </div>
  <div class="hiw-cta reveal">
    <a class="btn btn-primary" href="<?= View::e(Url::to('/register')) ?>">Register</a>
    <a class="btn btn-outline" href="<?= View::e(Url::to('/challenges')) ?>">Browse challenges</a>
  </div>
</section>
