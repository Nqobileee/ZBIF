<?php
use App\Support\Url;
use App\Support\View;

$first = View::e($user['first_name'] ?? 'there');
$stats = $stats ?? [];
$focus = $focus ?? [];
$isInnovator = !empty($isInnovator);
$isCorporate = !empty($isCorporate);
?>
<section class="home-hero">
  <div>
    <p class="eyebrow">Your workspace</p>
    <h1>Welcome back, <?= $first ?></h1>
    <p>Challenges, solutions, and deal rooms that match how you show up at ZBIF.</p>
  </div>
  <div class="home-actions">
    <?php if ($isCorporate): ?>
      <a class="btn btn-primary" href="<?= View::e(Url::to('/app/challenges/create')) ?>">Submit a challenge</a>
    <?php else: ?>
      <a class="btn btn-primary" href="<?= View::e(Url::to('/app/challenges')) ?>">Find challenges</a>
    <?php endif; ?>
    <a class="btn btn-outline" href="<?= View::e(Url::to('/app/deals')) ?>">Deal timetable</a>
    <a class="btn btn-ghost" href="<?= View::e(Url::to('/app/programme')) ?>">My agenda</a>
  </div>
</section>

<div class="dash-stat-row">
  <div class="dash-stat"><strong><?= (int) ($stats['challenges'] ?? 0) ?></strong><span>Relevant challenges</span></div>
  <div class="dash-stat"><strong><?= (int) ($stats['solutions'] ?? 0) ?></strong><span>Your solutions</span></div>
  <div class="dash-stat"><strong><?= (int) ($stats['deals'] ?? 0) ?></strong><span>Deal rooms</span></div>
  <div class="dash-stat"><strong><?= (int) ($stats['meetings'] ?? 0) ?></strong><span>Upcoming meetings</span></div>
</div>

<div class="dash-grid">
  <section class="dash-card">
    <h2><?= $isInnovator ? 'Challenges for your focus' : 'Open challenges near you' ?></h2>
    <?php if ($focus): ?>
      <div class="chip-row" style="margin-bottom:.85rem">
        <?php foreach (array_slice($focus, 0, 6) as $f): ?>
          <span class="chip"><?= View::e(ucwords($f)) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php foreach ($challenges as $c): ?>
      <div style="padding:.7rem 0;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center">
        <div>
          <a href="<?= View::e(Url::to('/app/challenges/' . $c['id'])) ?>"><strong><?= View::e($c['title']) ?></strong></a>
          <div class="muted" style="font-size:.85rem"><?= View::e($c['org_name'] ?? '') ?> · <?= View::e($c['sector'] ?? $c['category'] ?? '') ?></div>
        </div>
        <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/app/solutions/create?challenge_id=' . (int) $c['id'])) ?>">Apply</a>
      </div>
    <?php endforeach; ?>
    <?php if (!$challenges): ?><p class="muted">No published challenges match your profile yet. Update your capabilities or browse the full board.</p><?php endif; ?>
    <div class="dash-quick">
      <a class="btn btn-outline" href="<?= View::e(Url::to('/app/challenges')) ?>">All matching challenges</a>
      <a class="btn btn-ghost" href="<?= View::e(Url::to('/app/meetings')) ?>">Book a meeting</a>
    </div>
  </section>

  <aside>
    <section class="dash-card" style="margin-bottom:1rem">
      <h3>Notifications</h3>
      <?php foreach ($notifications as $n): ?>
        <div style="margin-bottom:.7rem">
          <strong><?= View::e($n['title']) ?></strong>
          <div class="muted" style="font-size:.86rem"><?= View::e($n['body']) ?></div>
        </div>
      <?php endforeach; ?>
      <?php if (!$notifications): ?><p class="muted">You are all caught up.</p><?php endif; ?>
      <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/app/notifications')) ?>">View all</a>
    </section>
    <section class="dash-card">
      <h3>Next at the forum</h3>
      <ul class="dash-tips">
        <li><?= (int) ($stats['agenda'] ?? 0) ?> sessions on your agenda</li>
        <li>Book in-person or online deal meetings</li>
        <li>Apply to exhibit if you have something to show</li>
      </ul>
      <div class="dash-quick">
        <a class="btn btn-secondary btn-sm" href="<?= View::e(Url::to('/app/exhibit/apply')) ?>">Exhibit</a>
        <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/app/profile')) ?>">Profile</a>
      </div>
    </section>
  </aside>
</div>
