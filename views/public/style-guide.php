<?php
use App\Support\Url;
use App\Support\View;
$greens = [
  '900' => '#06271D', '800' => '#0A3527', '700' => '#0B3D2E',
  '600' => '#0F4F3B', '500' => '#14684E', '100' => '#DCEAE4', '50' => '#EEF4F1',
];
$golds = ['600' => '#B8941F', '500' => '#C9A227', '100' => '#F5EAC8'];
$neutrals = [
  'Ink' => '#101915', 'Muted' => '#5B6B63', 'Bg' => '#FFFFFF',
  'Bg alt' => '#F7F9F8', 'Bg dark' => '#06271D',
];
$status = [
  'Success' => '#1E7F51', 'Warning' => '#C9821F', 'Danger' => '#B23A3A', 'Info' => '#2A6F97',
];
?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Design system</p>
    <h1>ZBIF style guide</h1>
    <p class="lead" style="margin:0;max-width:52ch">Tokens, components, and states for the public site. Poppins only. Green carries brand. Gold is the spark.</p>
  </div>
</section>

<section class="section container">
  <div class="section-head">
    <p class="eyebrow">Color</p>
    <h2>Primary green ramp</h2>
  </div>
  <div class="grid grid-4">
    <?php foreach ($greens as $k => $hex): ?>
      <div class="card card-static">
        <div style="height:72px;border-radius:var(--radius-md);background:<?= $hex ?>;border:1px solid var(--line)"></div>
        <strong>green-<?= View::e($k) ?></strong>
        <div class="muted"><?= View::e($hex) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="section-head" style="margin-top:2.5rem">
    <p class="eyebrow">Accent</p>
    <h2>Gold ramp</h2>
  </div>
  <div class="grid grid-3">
    <?php foreach ($golds as $k => $hex): ?>
      <div class="card card-static">
        <div style="height:72px;border-radius:var(--radius-md);background:<?= $hex ?>;border:1px solid var(--line)"></div>
        <strong>gold-<?= View::e($k) ?></strong>
        <div class="muted"><?= View::e($hex) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="grid grid-2" style="margin-top:2rem">
    <div>
      <h3>Neutrals</h3>
      <div class="grid grid-3" style="margin-top:1rem">
        <?php foreach ($neutrals as $name => $hex): ?>
          <div>
            <div style="height:48px;border-radius:var(--radius-sm);background:<?= $hex ?>;border:1px solid var(--line)"></div>
            <strong><?= View::e($name) ?></strong>
            <div class="muted"><?= View::e($hex) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div>
      <h3>Status</h3>
      <div class="grid grid-2" style="margin-top:1rem">
        <?php foreach ($status as $name => $hex): ?>
          <div>
            <div style="height:48px;border-radius:var(--radius-sm);background:<?= $hex ?>"></div>
            <strong><?= View::e($name) ?></strong>
            <div class="muted"><?= View::e($hex) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="section section-wash">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow is-green">Typography</p>
      <h2>Poppins scale</h2>
      <p class="lead">One family for display, UI, and body. Fluid clamp sizes.</p>
    </div>
    <p class="eyebrow">Eyebrow / caption</p>
    <p style="font-size:var(--text-display);font-weight:700;letter-spacing:-0.02em;line-height:1.05;margin:0 0 1rem">Display</p>
    <h1 style="margin:0 0 .75rem">Heading 1</h1>
    <h2 style="margin:0 0 .75rem">Heading 2</h2>
    <h3 style="margin:0 0 .75rem">Heading 3</h3>
    <h4 style="margin:0 0 .75rem">Heading 4</h4>
    <p class="lead">Lead: short supporting sentence under a section heading.</p>
    <p style="max-width:72ch">Body copy stays within 72ch. Voice is direct and peer-to-peer. Never use em-dashes. Prefer commas, colons, or parentheses.</p>
    <p class="muted" style="font-size:var(--text-small)">Small / helper text for metadata and captions.</p>
  </div>
</section>

<section class="section container">
  <div class="section-head">
    <p class="eyebrow">Spacing and shape</p>
    <h2>Rhythm tokens</h2>
  </div>
  <div class="grid grid-3">
    <div class="card card-static">
      <h3>Spacing</h3>
      <p class="muted">4px base: 4, 8, 12, 16, 20, 24, 32, 40, 48, 64, 80, 96, 128. Section Y: 96 / 64 / 48.</p>
    </div>
    <div class="card card-static">
      <h3>Radius</h3>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.75rem">
        <div style="width:48px;height:48px;background:var(--green-100);border-radius:var(--radius-sm)"></div>
        <div style="width:48px;height:48px;background:var(--green-100);border-radius:var(--radius-md)"></div>
        <div style="width:48px;height:48px;background:var(--green-100);border-radius:var(--radius-lg)"></div>
        <div style="width:48px;height:48px;background:var(--green-100);border-radius:var(--radius-xl)"></div>
      </div>
    </div>
    <div class="card card-static">
      <h3>Shadows</h3>
      <div style="display:grid;gap:.75rem;margin-top:.75rem">
        <div style="padding:1rem;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);border:1px solid var(--line)">sm</div>
        <div style="padding:1rem;border-radius:var(--radius-md);box-shadow:var(--shadow-md);border:1px solid var(--line)">md</div>
        <div style="padding:1rem;border-radius:var(--radius-md);box-shadow:var(--shadow-lg);border:1px solid var(--line)">lg</div>
      </div>
    </div>
  </div>
</section>

<section class="section container">
  <div class="section-head">
    <p class="eyebrow">Buttons</p>
    <h2>Interactive states</h2>
  </div>
  <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem">
    <button class="btn btn-primary" type="button">Primary</button>
    <button class="btn btn-secondary" type="button">Secondary</button>
    <button class="btn btn-outline" type="button">Outline</button>
    <button class="btn btn-ghost" type="button">Ghost</button>
    <button class="btn btn-link" type="button">Link</button>
    <button class="btn btn-primary" type="button" disabled>Disabled</button>
  </div>
  <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
    <button class="btn btn-primary btn-sm" type="button">Small</button>
    <button class="btn btn-primary" type="button">Medium</button>
    <button class="btn btn-primary btn-lg" type="button">Large</button>
  </div>
  <div style="max-width:320px;margin-top:1.25rem">
    <button class="btn btn-primary btn-block" type="button">Full width</button>
  </div>
</section>

<section class="section section-wash">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow is-green">Cards and badges</p>
      <h2>Marketplace surfaces</h2>
    </div>
    <div class="grid grid-3">
      <article class="card">
        <span class="badge">open</span>
        <h3 style="margin-top:.65rem">Challenge card</h3>
        <p class="muted">One-line problem · Owner org</p>
        <div style="display:flex;gap:.5rem;margin-top:1rem">
          <a class="btn btn-ghost btn-sm" href="#">View</a>
          <a class="btn btn-secondary btn-sm" href="#">Express interest</a>
        </div>
      </article>
      <article class="card">
        <span class="badge badge-gold">pilot ready</span>
        <h3 style="margin-top:.65rem">Solution card</h3>
        <p class="muted">Stage · Sector · Ask</p>
      </article>
      <article class="card">
        <h3>Speaker card</h3>
        <p class="muted">Title · Organisation</p>
      </article>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1.5rem">
      <span class="badge">open</span>
      <span class="badge">in development</span>
      <span class="badge badge-gold">in deal</span>
      <span class="badge">closed</span>
    </div>
  </div>
</section>

<section class="section container">
  <div class="section-head">
    <p class="eyebrow">Forms</p>
    <h2>Inputs and feedback</h2>
  </div>
  <form class="card" style="max-width:520px" onsubmit="return false">
    <div class="form-group">
      <label for="sg-name">Full name</label>
      <input id="sg-name" type="text" placeholder="Ada Ncube">
    </div>
    <div class="form-group">
      <label for="sg-role">Persona</label>
      <select id="sg-role">
        <option>Corporate</option>
        <option>Innovator</option>
        <option>Investor</option>
        <option>University</option>
      </select>
    </div>
    <div class="form-group">
      <label for="sg-notes">Notes</label>
      <textarea id="sg-notes" rows="3" placeholder="Optional context"></textarea>
    </div>
    <p class="muted" style="font-size:var(--text-small)">Autosave hint · Draft saved just now</p>
    <button class="btn btn-primary" type="button">Continue</button>
  </form>
  <div style="margin-top:1.5rem;display:grid;gap:.75rem;max-width:520px">
    <div class="flash flash-success" role="status">Success toast example</div>
    <div class="flash flash-error" role="status">Error toast example</div>
  </div>
</section>

<section class="section container">
  <div class="section-head">
    <p class="eyebrow">Signature</p>
    <h2>Marketplace stepper</h2>
  </div>
  <div class="journey">
    <article><div class="step">1</div><h3>Submit</h3><p class="muted">Companies post challenges</p></article>
    <article><div class="step">2</div><h3>Build</h3><p class="muted">Innovators develop</p></article>
    <article><div class="step">3</div><h3>Present</h3><p class="muted">Forum pitches</p></article>
    <article><div class="step">4</div><h3>Close</h3><p class="muted">Deal Rooms</p></article>
  </div>
</section>

<section class="section container">
  <div class="section-head">
    <p class="eyebrow">Auth</p>
    <h2>Account components</h2>
    <p class="lead">Password strength, persona cards, and OTP inputs used on registration and login.</p>
  </div>
  <div class="grid grid-2">
    <div class="card card-static">
      <label for="sg-pwd">Password with strength</label>
      <div class="password-wrap">
        <input id="sg-pwd" type="password" value="ExamplePass1">
        <button class="password-toggle" type="button" data-toggle-password="sg-pwd">Show</button>
      </div>
      <div class="strength" aria-hidden="true"><i class="on"></i><i class="on"></i><i class="on good"></i><i></i></div>
    </div>
    <div class="card card-static">
      <div class="otp-inputs" aria-hidden="true">
        <input value="1" readonly><input value="2" readonly><input value="3" readonly>
        <input value="4" readonly><input value="5" readonly><input value="6" readonly>
      </div>
      <p class="muted" style="text-align:center">OTP input pattern</p>
    </div>
  </div>
  <p style="margin-top:1rem"><a href="<?= View::e(Url::to('/register')) ?>">Open registration</a> · <a href="<?= View::e(Url::to('/login')) ?>">Open login</a></p>
</section>

<section class="section section-wash">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow is-green">Accessibility</p>
      <h2>Checklist</h2>
    </div>
    <ul>
      <li>AA contrast on text and controls (verify gold-on-white; darken if needed)</li>
      <li>Visible <code>:focus-visible</code> rings on interactive elements</li>
      <li>Keyboard: mega-menu, drawer, command search (Ctrl/Cmd-K), accordion, carousel</li>
      <li>Skip-to-content link, landmarks, heading order</li>
      <li>Labels on inputs; alt text on meaningful images</li>
      <li><code>prefers-reduced-motion</code> honored (instant fade, no Ken Burns)</li>
      <li>Tap targets at least 44px; no horizontal overflow</li>
    </ul>
    <p style="margin-top:1.5rem"><a class="btn btn-secondary" href="<?= View::e(Url::to('/')) ?>">Back to home</a></p>
  </div>
</section>
