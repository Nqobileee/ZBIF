<?php
use App\Support\Url;
use App\Support\View;

$matches = $matches ?? [];
$open = $open ?? [];
?>
<div class="page-toolbar">
  <div>
    <p class="eyebrow">Matching</p>
    <h1 class="page-title">Suggested matches</h1>
    <p class="page-lead">Challenge fits for your solutions. Open a challenge or book a Deal Room when you are ready.</p>
  </div>
  <a class="btn btn-outline btn-sm" href="<?= View::e(Url::to('/app/deals')) ?>">Deal rooms</a>
</div>

<section class="dash-card" style="margin-bottom:1rem">
  <h2 class="section-title">Matches for your solutions</h2>
  <?php if ($matches): ?>
    <div class="match-list">
      <?php foreach ($matches as $m): ?>
        <article class="match-row">
          <div>
            <strong><?= View::e($m['challenge_title']) ?></strong>
            <div class="muted" style="margin-top:.2rem"><?= View::e($m['solution_name'] ?? '') ?> · <?= View::e($m['category'] ?? '') ?></div>
            <?php if (!empty($m['rationale'])): ?>
              <p class="match-note"><?= View::e($m['rationale']) ?></p>
            <?php endif; ?>
          </div>
          <div class="match-side">
            <span class="badge"><?= View::e((string) $m['score']) ?></span>
            <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/app/challenges/' . (int) $m['challenge_id'])) ?>">Open</a>
            <a class="btn btn-primary btn-sm" href="<?= View::e(Url::to('/app/deals')) ?>">Deal room</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="muted" style="margin:0">No stored matches yet. Browse open challenges below.</p>
  <?php endif; ?>
</section>

<section class="dash-card">
  <h2 class="section-title">Open published challenges</h2>
  <div class="match-open-grid">
    <?php foreach ($open as $c): ?>
      <a class="match-open-card" href="<?= View::e(Url::to('/app/challenges/' . (int) $c['id'])) ?>">
        <span class="badge"><?= View::e($c['category'] ?? $c['sector'] ?? 'Challenge') ?></span>
        <strong><?= View::e($c['title']) ?></strong>
        <span class="muted"><?= View::e($c['org_name'] ?? '') ?></span>
      </a>
    <?php endforeach; ?>
    <?php if (!$open): ?>
      <p class="muted">No published challenges right now.</p>
    <?php endif; ?>
  </div>
</section>
