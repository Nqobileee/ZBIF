<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;

$solutions = $solutions ?? [];
$inbound = $inbound ?? [];
$isCorporate = !empty($isCorporate);
?>
<div class="page-toolbar">
  <div>
    <p class="eyebrow"><?= $isCorporate ? 'Industry inbox' : 'Your pipeline' ?></p>
    <h1 class="page-title"><?= $isCorporate ? 'Matched solutions' : 'My solutions' ?></h1>
    <p class="page-lead"><?= $isCorporate
      ? 'Review innovator proposals matched to your challenges, then open a Deal Room.'
      : 'View, edit, and track solutions you have submitted.' ?></p>
  </div>
  <?php if (!$isCorporate): ?>
    <a class="btn btn-primary" href="<?= View::e(Url::to('/app/solutions/create')) ?>">Add solution</a>
  <?php endif; ?>
</div>

<?php if ($isCorporate): ?>
  <div class="sol-list">
    <?php foreach ($inbound as $row): ?>
      <article class="sol-card">
        <div class="sol-card-top">
          <span class="badge badge-gold"><?= View::e((string) ($row['score'] ?? 'match')) ?> match</span>
          <span class="muted"><?= View::e($row['sector'] ?? '') ?></span>
        </div>
        <h2><?= View::e($row['solution_name']) ?></h2>
        <p class="muted" style="margin:0">For challenge: <strong><?= View::e($row['challenge_title']) ?></strong></p>
        <p class="sol-excerpt"><?= View::e(mb_substr((string) ($row['description'] ?? ''), 0, 220)) ?><?= mb_strlen((string) ($row['description'] ?? '')) > 220 ? '…' : '' ?></p>
        <div class="sol-meta">
          <span><?= View::e(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: 'Innovator') ?></span>
          <span><?= View::e(str_replace('_', ' ', (string) ($row['stage'] ?? ''))) ?></span>
        </div>
        <div class="chal-actions">
          <form method="post" action="<?= View::e(Url::to('/app/connections/request')) ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="to_user_id" value="<?= (int) ($row['created_by'] ?? 0) ?>">
            <input type="hidden" name="challenge_id" value="<?= (int) ($row['challenge_id'] ?? 0) ?>">
            <input type="hidden" name="solution_id" value="<?= (int) ($row['solution_id'] ?? 0) ?>">
            <input type="hidden" name="message" value="We would like to open a Deal Room on this matched solution.">
            <button class="btn btn-primary btn-sm" type="submit">Request Deal Room</button>
          </form>
          <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/app/challenges/' . (int) ($row['challenge_id'] ?? 0))) ?>">View challenge</a>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (!$inbound): ?>
      <div class="dash-card"><p class="muted" style="margin:0">No matched solutions yet. Publish a challenge to start attracting innovators.</p></div>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="sol-list">
    <?php foreach ($solutions as $s): ?>
      <article class="sol-card">
        <div class="sol-card-top">
          <span class="badge badge-gold"><?= View::e(str_replace('_', ' ', (string) $s['stage'])) ?></span>
          <span class="muted"><?= View::e((string) $s['sector']) ?></span>
        </div>
        <h2><?= View::e($s['name']) ?></h2>
        <p class="muted" style="margin:0">
          <?= !empty($s['challenge_id'])
            ? 'Linked to: ' . View::e($s['challenge_title'] ?: ('Challenge #' . (int) $s['challenge_id']))
            : 'Standalone offering' ?>
        </p>
        <p class="sol-excerpt"><?= View::e(mb_substr((string) $s['description'], 0, 220)) ?><?= mb_strlen((string) $s['description']) > 220 ? '…' : '' ?></p>
        <div class="chal-actions">
          <a class="btn btn-primary btn-sm" href="<?= View::e(Url::to('/app/solutions/' . (int) $s['id'] . '/edit')) ?>">Edit</a>
          <?php if (!empty($s['challenge_id'])): ?>
            <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/app/challenges/' . (int) $s['challenge_id'])) ?>">View challenge</a>
          <?php endif; ?>
          <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/app/matches')) ?>">See matches</a>
          <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/app/deals')) ?>">Deal rooms</a>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (!$solutions): ?>
      <div class="dash-card">
        <p class="muted" style="margin:0 0 .75rem">No solutions yet. Add a product or apply to a published challenge.</p>
        <a class="btn btn-primary btn-sm" href="<?= View::e(Url::to('/app/solutions/create')) ?>">Add solution</a>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
