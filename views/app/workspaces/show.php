<?php use App\Support\Csrf; use App\Support\View; ?>
<p class="muted">Challenge: <?= View::e($workspace['challenge_title']) ?> · Solution: <?= View::e($workspace['solution_name']) ?></p>

<div class="grid grid-2">
  <section class="card">
    <h2>Milestones</h2>
    <?php foreach ($milestones as $m): ?>
      <form method="post" action="/app/workspaces/<?= (int)$workspace['id'] ?>/milestone" style="display:flex;gap:0.5rem;align-items:center;margin-bottom:0.5rem">
        <?= Csrf::field() ?>
        <input type="hidden" name="milestone_id" value="<?= (int)$m['id'] ?>">
        <span style="flex:1"><?= View::e($m['title']) ?></span>
        <select name="status">
          <?php foreach (['pending','in_progress','done'] as $st): ?>
            <option value="<?= $st ?>" <?= $m['status']===$st?'selected':'' ?>><?= $st ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost" type="submit">Save</button>
      </form>
    <?php endforeach; ?>
  </section>
  <section class="card">
    <h2>Mentors</h2>
    <?php foreach ($mentors as $m): ?>
      <div><?= View::e($m['first_name'].' '.$m['last_name']) ?></div>
    <?php endforeach; ?>
    <?php if (!$mentors): ?><p class="muted">No mentors assigned yet.</p><?php endif; ?>
    <h3 style="margin-top:1rem">Coaching</h3>
    <?php foreach ($coaching as $c): ?>
      <div class="muted"><?= View::e($c['starts_at']) ?> · <?= View::e($c['status']) ?></div>
    <?php endforeach; ?>
    <form method="post" action="/app/workspaces/<?= (int)$workspace['id'] ?>/coaching" style="margin-top:0.75rem">
      <?= Csrf::field() ?>
      <input type="hidden" name="mentor_id" value="<?= (int)(\App\Auth\Auth::id()) ?>">
      <div class="form-group"><label>Starts</label><input type="datetime-local" name="starts_at"></div>
      <div class="form-group"><label>Ends</label><input type="datetime-local" name="ends_at"></div>
      <button class="btn btn-ghost" type="submit">Book pitch coaching</button>
    </form>
  </section>
</div>

<section class="card" style="margin-top:1rem">
  <h2>Industry feedback</h2>
  <?php foreach ($feedback as $f): ?>
    <div style="margin-bottom:0.75rem"><strong><?= View::e($f['first_name'].' '.$f['last_name']) ?></strong><div><?= View::e($f['body']) ?></div></div>
  <?php endforeach; ?>
  <form method="post" action="/app/workspaces/<?= (int)$workspace['id'] ?>/feedback">
    <?= Csrf::field() ?>
    <div class="form-group"><textarea name="body" rows="3" required placeholder="Practical alignment feedback"></textarea></div>
    <button class="btn btn-secondary" type="submit">Post feedback</button>
  </form>
</section>

<form method="post" action="/app/workspaces/<?= (int)$workspace['id'] ?>/ready" style="margin-top:1rem">
  <?= Csrf::field() ?>
  <button class="btn btn-primary" type="submit">Mark solution ready</button>
</form>
