<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;

$challenge = $challenge ?? null;
?>
<?php if ($challenge): ?>
  <div class="dash-card" style="margin-bottom:1rem">
    <p class="eyebrow">Applying to</p>
    <h2 style="margin:.2rem 0"><?= View::e($challenge['title']) ?></h2>
    <p class="muted" style="margin:0"><?= View::e($challenge['org_name'] ?? '') ?> · <?= View::e($challenge['sector'] ?? $challenge['category'] ?? '') ?></p>
  </div>
<?php endif; ?>

<form method="post" action="<?= View::e(Url::to('/app/solutions')) ?>" class="dash-card" style="max-width:720px">
  <?= Csrf::field() ?>
  <?php if ($challenge): ?>
    <input type="hidden" name="challenge_id" value="<?= (int) $challenge['id'] ?>">
  <?php endif; ?>
  <div class="form-group"><label>Solution name</label><input name="name" required placeholder="What are you proposing?"></div>
  <div class="form-group"><label>Sector</label><input name="sector" value="<?= View::e($challenge['sector'] ?? '') ?>" required></div>
  <div class="form-group"><label>Stage</label>
    <select name="stage">
      <?php foreach (['idea','prototype','pilot','market_ready','scaling'] as $st): ?>
        <option value="<?= $st ?>"><?= str_replace('_',' ',$st) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label>Your approach / description</label><textarea name="description" rows="5" required placeholder="How does this solve the problem? What have you built so far?"></textarea></div>
  <div class="form-group"><label>Problem addressed</label><textarea name="problem_solved" rows="3"><?= View::e($challenge['problem_statement'] ?? '') ?></textarea></div>
  <div class="form-group"><label>Traction</label><textarea name="traction" rows="2" placeholder="Customers, pilots, revenue, users…"></textarea></div>
  <div class="form-group"><label>Ask</label>
    <select name="ask_type">
      <?php foreach (['customers','pilot','capital','distribution','partnership'] as $a): ?>
        <option value="<?= $a ?>"><?= $a ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <label><input type="checkbox" name="investor_visible" value="1"> Visible in investor deal flow</label>
  <div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
    <button class="btn btn-primary" type="submit"><?= $challenge ? 'Submit solution' : 'Publish' ?></button>
    <a class="btn btn-ghost" href="<?= View::e(Url::to($challenge ? '/app/challenges/' . (int)$challenge['id'] : '/app/solutions')) ?>">Cancel</a>
  </div>
</form>
