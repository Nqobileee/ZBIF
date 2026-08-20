<?php use App\Support\Csrf; ?>
<form method="post" action="/app/challenges" class="card" style="max-width:760px">
  <?= Csrf::field() ?>
  <div class="form-group"><label>Title</label><input name="title" required></div>
  <div class="form-group"><label>Problem statement</label><textarea name="problem_statement" rows="5" required></textarea></div>
  <div class="grid grid-2">
    <div class="form-group"><label>Sector</label><input name="sector" value="Manufacturing"></div>
    <div class="form-group"><label>Category</label>
      <select name="category">
        <?php foreach (['Manufacturing','Agriculture','Mining','Banking and Fintech','Logistics','Healthcare','Retail','Energy','Smart Infrastructure'] as $c): ?>
          <option><?= $c ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="form-group"><label>Desired outcome</label><textarea name="desired_outcome" rows="2"></textarea></div>
  <div class="form-group"><label>Constraints</label><textarea name="constraints_text" rows="2"></textarea></div>
  <div class="grid grid-2">
    <div class="form-group"><label>Timeline</label><input name="timeline"></div>
    <div class="form-group"><label>Engagement type</label>
      <select name="engagement_type">
        <?php foreach (['procurement','pilot','partnership','investment','co-development'] as $e): ?>
          <option><?= $e ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="grid grid-2">
    <div class="form-group"><label>Visibility</label>
      <select name="visibility"><option value="public">Public</option><option value="private_invite">Private invite</option></select>
    </div>
    <div class="form-group"><label>Budget band (private)</label><input name="budget_band" placeholder="Optional"></div>
  </div>
  <button class="btn btn-primary" type="submit">Submit for screening</button>
</form>
