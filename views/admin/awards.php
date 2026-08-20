<?php use App\Support\Csrf; use App\Support\View; ?>
<?php if (!$nominations): ?>
  <div class="adm-empty">No nominations to score.</div>
<?php endif; ?>
<?php foreach ($nominations as $n): ?>
  <article class="adm-card">
    <span class="badge badge-gold"><?= View::e($n['category_name']) ?></span>
    <h3 style="margin:.5rem 0"><?= View::e($n['nominee_name']) ?></h3>
    <p><?= View::e($n['rationale']) ?></p>
    <form method="post">
      <?= Csrf::field() ?>
      <input type="hidden" name="nomination_id" value="<?= (int)$n['id'] ?>">
      <div class="grid grid-2">
        <div class="form-group"><label>Impact (0-10)</label><input type="number" step="0.1" min="0" max="10" name="rubric_impact" value="7"></div>
        <div class="form-group"><label>Feasibility (0-10)</label><input type="number" step="0.1" min="0" max="10" name="rubric_feasibility" value="7"></div>
        <div class="form-group"><label>Innovation (0-10)</label><input type="number" step="0.1" min="0" max="10" name="rubric_innovation" value="7"></div>
        <div class="form-group"><label>Scalability (0-10)</label><input type="number" step="0.1" min="0" max="10" name="rubric_scalability" value="7"></div>
      </div>
      <div class="form-group"><label>Override total score (optional)</label><input type="number" step="0.1" name="score" placeholder="Auto-average of rubric"></div>
      <div class="form-group"><label>Comments</label><input name="comments" placeholder="Comments"></div>
      <label style="display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1rem"><input type="checkbox" name="make_winner" value="1"> Mark winner</label>
      <div><button class="btn btn-primary" type="submit">Score</button></div>
    </form>
  </article>
<?php endforeach; ?>
