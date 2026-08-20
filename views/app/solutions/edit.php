<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;

$solution = $solution ?? [];
$challenge = $challenge ?? null;
?>
<div class="page-toolbar">
  <div>
    <p class="eyebrow">Edit</p>
    <h1 class="page-title">Update solution</h1>
    <p class="page-lead">Keep your proposal current so industry partners can evaluate it.</p>
  </div>
  <a class="btn btn-ghost" href="<?= View::e(Url::to('/app/solutions')) ?>">Back</a>
</div>

<form method="post" action="<?= View::e(Url::to('/app/solutions/' . (int) $solution['id'])) ?>" class="dash-card" style="max-width:720px">
  <?= Csrf::field() ?>
  <div class="form-group"><label>Solution name</label><input name="name" required value="<?= View::e($solution['name'] ?? '') ?>"></div>
  <div class="form-group"><label>Sector</label><input name="sector" required value="<?= View::e($solution['sector'] ?? '') ?>"></div>
  <div class="form-group"><label>Stage</label>
    <select name="stage">
      <?php foreach (['idea','prototype','pilot','market_ready','scaling'] as $st): ?>
        <option value="<?= $st ?>" <?= ($solution['stage'] ?? '') === $st ? 'selected' : '' ?>><?= str_replace('_',' ',$st) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label>Description</label><textarea name="description" rows="5" required><?= View::e($solution['description'] ?? '') ?></textarea></div>
  <div class="form-group"><label>Problem addressed</label><textarea name="problem_solved" rows="3"><?= View::e($solution['problem_solved'] ?? '') ?></textarea></div>
  <div class="form-group"><label>Traction</label><textarea name="traction" rows="2"><?= View::e($solution['traction'] ?? '') ?></textarea></div>
  <div class="form-group"><label>Ask</label>
    <select name="ask_type">
      <?php foreach (['customers','pilot','capital','distribution','partnership'] as $a): ?>
        <option value="<?= $a ?>" <?= ($solution['ask_type'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <label><input type="checkbox" name="investor_visible" value="1" <?= !empty($solution['investor_visible']) ? 'checked' : '' ?>> Visible in investor deal flow</label>
  <div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
    <button class="btn btn-primary" type="submit">Save changes</button>
    <a class="btn btn-ghost" href="<?= View::e(Url::to('/app/solutions')) ?>">Cancel</a>
  </div>
</form>
