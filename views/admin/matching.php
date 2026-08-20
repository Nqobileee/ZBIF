<?php use App\Support\View; ?>
<section class="adm-card" style="max-width:760px">
  <p class="eyebrow" style="color:var(--gold-600);margin:0 0 .5rem">Match engine</p>
  <h2 style="margin:0 0 .5rem">Coming soon</h2>
  <p class="muted" style="margin:0 0 1rem">
    Automated challenge-to-innovator matching is being finalised. Until it ships, organisers can continue screening challenges and managing Deal Rooms manually.
  </p>
  <ul class="adm-feature-list">
    <li>Challenge screening and allocation stay available now</li>
    <li>Deal room status and reports remain fully usable</li>
    <li>Match scoring and recompute will appear here when ready</li>
  </ul>
  <div class="dash-quick" style="margin-top:1.25rem">
    <a class="btn btn-primary" href="/admin/screening">Open screening</a>
    <a class="btn btn-outline" href="/admin/deals">Deal rooms</a>
  </div>
</section>
<?php if (!empty($challenges)): ?>
  <p class="muted" style="margin-top:1.25rem"><?= (int) count($challenges) ?> challenge(s) are in matching-eligible status and will feed this engine later.</p>
<?php endif; ?>
