<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;

$user = $user ?? [];
$applications = $applications ?? [];
?>
<div class="page-toolbar">
  <div>
    <p class="eyebrow">Forum floor</p>
    <h1 class="page-title">Exhibit at ZBIF</h1>
    <p class="page-lead">Show a product, service, or live demo. Applications are reviewed by the organising team before space is confirmed.</p>
  </div>
  <button type="button" class="btn btn-primary" id="openExhibitModal">Apply to exhibit</button>
</div>

<div class="ex-grid" style="grid-template-columns:1fr">
  <div class="dash-card">
    <h2 class="section-title">Why exhibit</h2>
    <ul class="dash-tips">
      <li>Meet buyers, partners, and investors on the floor</li>
      <li>Pair your demo with challenge conversations</li>
      <li>Book deal-room meetings from your booth</li>
    </ul>
  </div>

  <div class="dash-card">
    <h2 class="section-title">Your applications</h2>
    <?php if ($applications): ?>
      <?php foreach ($applications as $a): ?>
        <article class="sol-card" style="margin-bottom:.65rem">
          <div class="sol-card-top">
            <span class="badge"><?= View::e(str_replace('_', ' ', $a['exhibit_type'])) ?></span>
            <span class="badge badge-gold"><?= View::e(str_replace('_', ' ', $a['status'])) ?></span>
          </div>
          <h2 style="font-size:1.05rem;margin:.35rem 0"><?= View::e($a['title']) ?></h2>
          <p class="muted" style="margin:0"><?= View::e($a['organisation_name']) ?> · <?= View::e($a['created_at']) ?></p>
          <?php if (($a['status'] ?? '') === 'submitted'): ?>
            <p class="muted" style="margin:.4rem 0 0;font-size:.86rem">Awaiting admin approval.</p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="muted" style="margin:0">No applications yet. Use <strong>Apply to exhibit</strong> to submit one for admin review.</p>
    <?php endif; ?>
  </div>
</div>

<div class="zbif-modal" id="exhibitModal" hidden>
  <div class="zbif-modal-backdrop" data-close-exhibit></div>
  <div class="zbif-modal-panel zbif-modal-wide" role="dialog" aria-modal="true" aria-labelledby="exhibitModalTitle">
    <button type="button" class="zbif-modal-close" data-close-exhibit aria-label="Close">×</button>
    <p class="eyebrow" style="margin:0">Submission</p>
    <h2 id="exhibitModalTitle">Apply to exhibit</h2>
    <p>Your application is reviewed by admin before a booth is assigned.</p>
    <form method="post" action="<?= View::e(Url::to('/app/exhibit/apply')) ?>" style="margin-top:1rem">
      <?= Csrf::field() ?>
      <div class="form-group">
        <label for="organisation_name">Organisation</label>
        <input id="organisation_name" name="organisation_name" required>
      </div>
      <div class="grid grid-2">
        <div class="form-group">
          <label for="contact_name">Contact name</label>
          <input id="contact_name" name="contact_name" value="<?= View::e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>" required>
        </div>
        <div class="form-group">
          <label for="contact_email">Contact email</label>
          <input id="contact_email" type="email" name="contact_email" value="<?= View::e($user['email'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label for="contact_phone">Phone</label>
        <input id="contact_phone" name="contact_phone" value="<?= View::e($user['phone'] ?? '') ?>" placeholder="+263…">
      </div>
      <div class="grid grid-2">
        <div class="form-group">
          <label for="exhibit_type">What are you offering?</label>
          <select id="exhibit_type" name="exhibit_type">
            <option value="product">Product</option>
            <option value="service">Service</option>
            <option value="demo">Live demo</option>
            <option value="startup_booth">Startup booth</option>
            <option value="university_showcase">University showcase</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label for="space_preference">Space preference</label>
          <input id="space_preference" name="space_preference" placeholder="e.g. standard booth, demo pod">
        </div>
      </div>
      <div class="form-group">
        <label for="title">Exhibit title</label>
        <input id="title" name="title" required maxlength="255">
      </div>
      <div class="form-group">
        <label for="description">What will visitors see or experience?</label>
        <textarea id="description" name="description" rows="4" required minlength="20"></textarea>
      </div>
      <div class="form-group">
        <label for="website">Website (optional)</label>
        <input id="website" name="website" type="url" placeholder="https://">
      </div>
      <div class="form-group">
        <label><input type="checkbox" name="launching_at_forum" value="1"> We plan to launch something at the forum</label>
      </div>
      <div class="zbif-modal-actions">
        <button class="btn btn-primary" type="submit">Submit for approval</button>
        <button class="btn btn-ghost" type="button" data-close-exhibit>Cancel</button>
      </div>
    </form>
  </div>
</div>
<script>
(function(){
  var modal = document.getElementById('exhibitModal');
  function openM(){ if (modal) modal.hidden = false; }
  function closeM(){ if (modal) modal.hidden = true; }
  var btn = document.getElementById('openExhibitModal');
  if (btn) btn.addEventListener('click', openM);
  document.querySelectorAll('[data-close-exhibit]').forEach(function(el){
    el.addEventListener('click', closeM);
  });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeM(); });
  <?php if (!empty($_GET['apply'])): ?>openM();<?php endif; ?>
})();
</script>
