<?php
use App\Rbac\Gate;
use App\Support\Csrf;
use App\Support\View;
?>
<span class="badge"><?= View::e($challenge['status']) ?></span>
<span class="badge"><?= View::e($challenge['category']) ?></span>
<p class="muted"><?= View::e($challenge['org_name']) ?></p>
<article class="card" style="margin:1rem 0">
  <h3>Problem</h3>
  <p><?= nl2br(View::e($challenge['problem_statement'])) ?></p>
</article>

<?php if (Gate::allows('solutions.create') && $challenge['status'] === 'published'): ?>
<div class="chal-actions" style="margin:1rem 0">
  <a class="btn btn-primary" href="/app/solutions/create?challenge_id=<?= (int)$challenge['id'] ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Submit solution</a>
</div>
<form method="post" action="/app/challenges/<?= (int)$challenge['id'] ?>/claim" class="card" style="margin-bottom:1rem">
  <?= Csrf::field() ?>
  <h3>Claim this challenge</h3>
  <div class="form-group"><label>Solution name</label><input name="solution_name" required></div>
  <div class="form-group"><label>Approach</label><textarea name="description" rows="3"></textarea></div>
  <button class="btn btn-secondary" type="submit">Claim and open workspace</button>
</form>
<?php endif; ?>

<form method="post" action="/app/challenges/<?= (int)$challenge['id'] ?>/interest" class="card" style="margin-bottom:1rem">
  <?= Csrf::field() ?>
  <h3>Express interest</h3>
  <div class="form-group"><textarea name="message" rows="2">I would like to explore a partnership.</textarea></div>
  <button class="btn btn-secondary" type="submit">Send request</button>
</form>

<form method="post" action="/app/challenges/<?= (int)$challenge['id'] ?>/matches" style="margin-bottom:1rem">
  <?= Csrf::field() ?>
  <button class="btn btn-ghost" type="submit">Recompute matches</button>
</form>

<h2>Suggested matches</h2>
<div class="grid">
  <?php foreach ($matches as $m): ?>
    <article class="card">
      <strong><?= View::e($m['solution_name']) ?></strong>
      <span class="badge"><?= View::e($m['source']) ?> · <?= View::e((string)$m['score']) ?></span>
      <p class="muted"><?= View::e($m['rationale']) ?></p>
    </article>
  <?php endforeach; ?>
  <?php if (!$matches): ?><p class="muted">No matches yet. Recompute to generate suggestions.</p><?php endif; ?>
</div>
