<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;

$draft = $draft ?? [];
$persona = $persona ?? ($draft['persona'] ?? 'innovator');
if (!in_array($persona, ['innovator', 'corporate'], true)) {
    $persona = in_array($persona, ['government', 'company'], true) ? 'corporate' : 'innovator';
}
$stepIndex = (int) ($step ?? 0);
$stepNames = ['Organisation', 'Account', 'Capabilities'];
$contacts = $draft['contacts'] ?? [['name' => '', 'email' => '', 'phone' => '', 'whatsapp' => '0']];
if (!is_array($contacts) || !$contacts) {
    $contacts = [['name' => '', 'email' => '', 'phone' => '', 'whatsapp' => '0']];
}
$focusSelected = $draft['focus_areas'] ?? [];
if (!is_array($focusSelected)) {
    $focusSelected = [];
}
$focusAreas = [
    'Software / SaaS', 'Hardware / IoT', 'AI / Machine learning', 'Agritech', 'Fintech', 'Healthtech',
    'Clean energy', 'Manufacturing', 'Mining tech', 'Education tech', 'Logistics', 'Climate / water',
    'Creative / media', 'Consulting / services',
];
$innovatorTypes = ['Startup', 'University', 'SME', 'Innovation Hub', 'Individual / freelance'];
$corporateTypes = ['Corporate', 'NGO / CSO', 'Government', 'Parastatal', 'Industry association', 'Other'];
$orgType = $draft['organization_type'] ?? ($persona === 'corporate' ? 'Corporate' : 'Startup');
$stages = [
    'idea' => 'Idea',
    'prototype' => 'Prototype',
    'pilot' => 'Pilot',
    'market_ready' => 'Market ready',
    'scaling' => 'Scaling',
];
$teamSizes = ['Just me', '2-5 people', '6-15 people', '16-50 people', '50+ people'];
$orgInitials = 'ZB';
$orgNameDraft = (string) ($draft['organization'] ?? '');
if ($orgNameDraft !== '') {
    $parts = preg_split('/\s+/', $orgNameDraft) ?: [];
    $orgInitials = strtoupper(substr($parts[0] ?? 'Z', 0, 1) . substr($parts[1] ?? ($parts[0] ?? 'B'), 0, 1));
}
?>
<div class="reg-page">
  <header class="reg-top">
    <a class="reg-logo" href="<?= View::e(Url::to('/')) ?>">
      <img src="<?= View::e(Url::asset('img/zbif-logo-main.png')) ?>" alt="ZBIF" width="140" height="42">
    </a>
    <div class="reg-top-actions">
      <span class="muted hide-sm">Already registered?</span>
      <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/login.php')) ?>">Sign in</a>
    </div>
  </header>

  <div class="reg-layout">
    <section class="reg-main">
      <div class="reg-intro">
        <h1>Create your account</h1>
        <p class="lead">Register as an innovator or as a company, organisation, or government body. Your details are saved to your profile.</p>
      </div>

      <div class="persona-grid reg-persona-2" role="radiogroup" aria-label="Participation type">
        <label class="persona-card <?= $persona === 'innovator' ? 'is-selected' : '' ?>">
          <input type="radio" name="persona_toggle" value="innovator" <?= $persona === 'innovator' ? 'checked' : '' ?> form="regForm">
          <span class="persona-ico" aria-hidden="true">◎</span>
          <strong>Innovator</strong>
          <span>Startups, universities &amp; SMEs building solutions</span>
        </label>
        <label class="persona-card <?= $persona === 'corporate' ? 'is-selected' : '' ?>">
          <input type="radio" name="persona_toggle" value="corporate" <?= $persona === 'corporate' ? 'checked' : '' ?> form="regForm">
          <span class="persona-ico" aria-hidden="true">▣</span>
          <strong>Company / Org / Government</strong>
          <span>Businesses, NGOs &amp; public bodies submitting challenges</span>
        </label>
      </div>

      <ol class="reg-progress" id="wizardSteps" aria-label="Registration steps">
        <?php foreach ($stepNames as $i => $label): ?>
          <li class="<?= $i === $stepIndex ? 'is-active' : ($i < $stepIndex ? 'is-done' : '') ?>" data-step="<?= $i ?>">
            <span class="reg-progress-num"><?= $i < $stepIndex ? '✓' : ($i + 1) ?></span>
            <span class="reg-progress-label"><?= View::e($label) ?></span>
          </li>
        <?php endforeach; ?>
      </ol>

      <form method="post" action="<?= View::e(Url::to('/register')) ?>" class="reg-card" id="regForm" enctype="multipart/form-data" novalidate data-draft-url="<?= View::e(Url::to('/register/draft')) ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="_draft_token" id="draftToken" value="<?= View::e($draft['_draft_token'] ?? '') ?>">
        <input type="hidden" name="_wizard_action" id="wizardAction" value="finalize">
        <input type="hidden" name="persona" id="persona" value="<?= View::e($persona) ?>" required>

        <!-- 1. Organisation -->
        <div class="wizard-panel <?= $stepIndex === 0 ? 'active' : '' ?>" data-panel="0">
          <h2 data-label-innovator>About your innovation team</h2>
          <h2 data-label-corporate hidden>About your organisation</h2>
          <p class="muted">Organisation details and the people we should reach.</p>

          <div class="grid grid-2">
            <div class="form-group">
              <label for="organization">Organisation name</label>
              <input id="organization" name="organization" value="<?= View::e($draft['organization'] ?? '') ?>" required autocomplete="organization">
            </div>
            <div class="form-group">
              <label for="organization_type">Organisation type</label>
              <select id="organization_type" name="organization_type" required>
                <optgroup label="Innovator" data-persona-group="innovator">
                  <?php foreach ($innovatorTypes as $t): ?>
                    <option value="<?= View::e($t) ?>" <?= $orgType === $t ? 'selected' : '' ?>><?= View::e($t) ?></option>
                  <?php endforeach; ?>
                </optgroup>
                <optgroup label="Company / Org / Government" data-persona-group="corporate">
                  <?php foreach ($corporateTypes as $t): ?>
                    <option value="<?= View::e($t) ?>" <?= $orgType === $t ? 'selected' : '' ?>><?= View::e($t) ?></option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Team / company logo</label>
            <p class="field-hint">PNG, JPG, SVG or WEBP. Square works best. Max 1.5 MB.</p>
            <div class="reg-logo-row">
              <div class="reg-logo-preview" id="logoPreview" aria-hidden="true"><?= View::e($orgInitials) ?></div>
              <div>
                <input type="file" name="logo" id="logoInput" accept=".png,.jpg,.jpeg,.svg,.webp,image/*" hidden>
                <button class="btn btn-outline btn-sm" type="button" id="logoPickBtn">Upload logo</button>
              </div>
            </div>
          </div>

          <div class="reg-contacts-head">
            <div>
              <h3>Team contacts</h3>
              <p class="field-hint">Primary contact becomes your profile name. Add as many teammates as you need.</p>
            </div>
            <button class="btn btn-outline btn-sm" type="button" id="addContactBtn">+ Add contact</button>
          </div>
          <div id="contactsList">
            <?php foreach ($contacts as $ci => $c): ?>
              <div class="reg-contact" data-contact-index="<?= (int)$ci ?>">
                <div class="reg-contact-top">
                  <strong>Contact <?= (int)$ci + 1 ?><?= $ci === 0 ? ' · primary' : '' ?></strong>
                  <?php if ($ci > 0): ?>
                    <button type="button" class="reg-contact-remove" data-remove-contact>Remove</button>
                  <?php endif; ?>
                </div>
                <div class="form-group">
                  <label>Full name</label>
                  <input name="contacts[<?= (int)$ci ?>][name]" value="<?= View::e($c['name'] ?? '') ?>" <?= $ci === 0 ? 'required' : '' ?> autocomplete="name">
                </div>
                <div class="grid grid-2">
                  <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="contacts[<?= (int)$ci ?>][email]" value="<?= View::e($c['email'] ?? '') ?>" <?= $ci === 0 ? 'required' : '' ?> autocomplete="email">
                  </div>
                  <div class="form-group">
                    <label>Phone</label>
                    <input name="contacts[<?= (int)$ci ?>][phone]" value="<?= View::e($c['phone'] ?? '') ?>" placeholder="+263 7X XXX XXXX" inputmode="tel" autocomplete="tel">
                    <label class="reg-whatsapp">
                      <input type="checkbox" name="contacts[<?= (int)$ci ?>][whatsapp]" value="1" <?= !empty($c['whatsapp']) ? 'checked' : '' ?>>
                      <span class="reg-wa-ico" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.4c-5.3 0-9.6 4.3-9.6 9.6 0 1.7.4 3.3 1.2 4.7L2.4 21.6l5.1-1.3c1.4.7 2.9 1.1 4.5 1.1 5.3 0 9.6-4.3 9.6-9.6S17.3 2.4 12 2.4zm5.6 13.6c-.2.7-1.3 1.2-2.1 1.4-.5.1-1.2.2-3.5-.7-2.8-1.2-4.6-4.1-4.8-4.3-.1-.2-1.2-1.6-1.2-3.1s.8-2.2 1-2.4c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4.2.5.7 1.7.8 1.9.1.2.1.3 0 .5-.1.2-.2.3-.3.5-.2.2-.3.3-.5.5-.2.2-.3.3-.1.6.2.3.9 1.4 1.9 2.3 1.3 1.1 2.4 1.5 2.7 1.6.3.1.5.1.7-.1.2-.2.8-.9 1-.12.2-.3.5-.3.8-.2.3.1 2 .9 2.3 1.1.3.2.5.3.6.4.1.2.1.9-.1 1.6z" fill="currentColor"/></svg>
                      </span>
                      <span>This number is available on WhatsApp</span>
                    </label>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="reg-actions">
            <a class="btn btn-ghost" href="<?= View::e(Url::to('/login.php')) ?>">Back to sign in</a>
            <button class="btn btn-primary" type="button" data-next>Continue</button>
          </div>
        </div>

        <!-- 2. Account -->
        <div class="wizard-panel <?= $stepIndex === 1 ? 'active' : '' ?>" data-panel="1">
          <h2>Account credentials</h2>
          <p class="muted" data-label-innovator>You will use these to sign in to your innovator dashboard.</p>
          <p class="muted" data-label-corporate hidden>You will use these to sign in to your organisation dashboard.</p>

          <div class="form-group">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" value="<?= View::e($draft['email'] ?? '') ?>" required autocomplete="email">
          </div>
          <div class="grid grid-2">
            <div class="form-group">
              <label for="password">Password</label>
              <div class="password-wrap">
                <input id="password" type="password" name="password" required minlength="10" autocomplete="new-password" aria-describedby="pwdHelp">
                <button class="password-toggle" type="button" data-toggle-password="password">Show</button>
              </div>
              <div class="strength" id="strengthMeter" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
              <p class="muted field-hint" id="pwdHelp">At least 10 characters with letters and numbers.</p>
            </div>
            <div class="form-group">
              <label for="password_confirmation">Confirm password</label>
              <div class="password-wrap">
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="10" autocomplete="new-password">
                <button class="password-toggle" type="button" data-toggle-password="password_confirmation">Show</button>
              </div>
              <p class="field-error" id="pwdMatchError" hidden>Passwords do not match.</p>
            </div>
          </div>

          <div class="reg-actions">
            <button class="btn btn-ghost" type="button" data-prev>Back</button>
            <button class="btn btn-primary" type="button" data-next>Continue</button>
          </div>
        </div>

        <!-- 3. Capabilities -->
        <div class="wizard-panel <?= $stepIndex === 2 ? 'active' : '' ?>" data-panel="2" data-final>
          <h2>Your capabilities</h2>
          <p class="muted">This helps matching and organiser screening. You can refine details later in your dashboard.</p>

          <div class="form-group">
            <label for="offer_text">
              <span data-label-innovator>What do you build or offer?</span>
              <span data-label-corporate hidden>What challenges are you looking to solve?</span>
            </label>
            <textarea id="offer_text" name="offer_text" rows="4" required placeholder="Describe your solutions, track record, and the problems you solve."><?= View::e($draft['offer_text'] ?? $draft['problem_solved'] ?? '') ?></textarea>
          </div>

          <fieldset class="form-group">
            <legend>Focus areas</legend>
            <div class="reg-chips" id="focusChips">
              <?php foreach ($focusAreas as $fa): ?>
                <label class="reg-chip <?= in_array($fa, $focusSelected, true) ? 'is-on' : '' ?>">
                  <input type="checkbox" name="focus_areas[]" value="<?= View::e($fa) ?>" <?= in_array($fa, $focusSelected, true) ? 'checked' : '' ?>>
                  <?= View::e($fa) ?>
                </label>
              <?php endforeach; ?>
            </div>
          </fieldset>

          <div class="grid grid-2">
            <div class="form-group">
              <label for="stage">
                <span data-label-innovator>Current stage</span>
                <span data-label-corporate hidden>Readiness</span>
              </label>
              <select id="stage" name="stage">
                <?php foreach ($stages as $val => $label): ?>
                  <option value="<?= View::e($val) ?>" <?= ($draft['stage'] ?? 'prototype') === $val ? 'selected' : '' ?>><?= View::e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="team_size">Team size</label>
              <select id="team_size" name="team_size">
                <?php foreach ($teamSizes as $ts): ?>
                  <option value="<?= View::e($ts) ?>" <?= ($draft['team_size'] ?? '2-5 people') === $ts ? 'selected' : '' ?>><?= View::e($ts) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="reg-consents">
            <label class="reg-check">
              <input type="checkbox" name="consent_privacy" value="1" required>
              <span>I agree to the privacy and data-use notice</span>
            </label>
            <label class="reg-check">
              <input type="checkbox" name="consent_conduct" value="1" required>
              <span>I agree to the code of conduct</span>
            </label>
            <label class="reg-check">
              <input type="checkbox" name="consent_marketing" value="1">
              <span>Send me challenge deadlines and speaker news</span>
            </label>
          </div>

          <div class="reg-actions">
            <button class="btn btn-ghost" type="button" data-prev>Back</button>
            <button class="btn btn-primary" type="submit" id="finishBtn">Create account</button>
          </div>
        </div>
      </form>
    </section>
  </div>
</div>

<template id="contactTemplate">
  <div class="reg-contact" data-contact-index="__i__">
    <div class="reg-contact-top">
      <strong>Contact __n__</strong>
      <button type="button" class="reg-contact-remove" data-remove-contact>Remove</button>
    </div>
    <div class="form-group">
      <label>Full name</label>
      <input name="contacts[__i__][name]" autocomplete="name">
    </div>
    <div class="grid grid-2">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="contacts[__i__][email]" autocomplete="email">
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input name="contacts[__i__][phone]" placeholder="+263 7X XXX XXXX" inputmode="tel" autocomplete="tel">
        <label class="reg-whatsapp">
          <input type="checkbox" name="contacts[__i__][whatsapp]" value="1">
          <span class="reg-wa-ico" aria-hidden="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.4c-5.3 0-9.6 4.3-9.6 9.6 0 1.7.4 3.3 1.2 4.7L2.4 21.6l5.1-1.3c1.4.7 2.9 1.1 4.5 1.1 5.3 0 9.6-4.3 9.6-9.6S17.3 2.4 12 2.4zm5.6 13.6c-.2.7-1.3 1.2-2.1 1.4-.5.1-1.2.2-3.5-.7-2.8-1.2-4.6-4.1-4.8-4.3-.1-.2-1.2-1.6-1.2-3.1s.8-2.2 1-2.4c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4.2.5.7 1.7.8 1.9.1.2.1.3 0 .5-.1.2-.2.3-.3.5-.2.2-.3.3-.5.5-.2.2-.3.3-.1.6.2.3.9 1.4 1.9 2.3 1.3 1.1 2.4 1.5 2.7 1.6.3.1.5.1.7-.1.2-.2.8-.9 1-.12.2-.3.5-.3.8-.2.3.1 2 .9 2.3 1.1.3.2.5.3.6.4.1.2.1.9-.1 1.6z" fill="currentColor"/></svg>
          </span>
          <span>This number is available on WhatsApp</span>
        </label>
      </div>
    </div>
  </div>
</template>

<?php
$novaMode = 'assist';
$novaOpen = !empty($_GET['assist']);
$novaTitle = 'Nova · Registration assist';
$novaHint = 'Guidance while you register for ZBIF.';
require ZBIF_ROOT . '/views/partials/nova-dock.php';
?>
