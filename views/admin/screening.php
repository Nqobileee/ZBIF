<?php use App\Support\Csrf; use App\Support\View; ?>
<?php if (!$challenges): ?>
  <div class="adm-empty">No challenges awaiting screening right now.</div>
<?php endif; ?>
<?php foreach ($challenges as $c): ?>
  <article class="adm-card">
    <div class="adm-panel-head">
      <div>
        <h3 style="margin:0"><?= View::e($c['title']) ?></h3>
        <p class="muted" style="margin:.35rem 0 0"><?= View::e($c['org_name']) ?> · <span class="badge"><?= View::e($c['status']) ?></span> · score <?= View::e((string)($c['screening_score'] ?? 'n/a')) ?></p>
      </div>
    </div>
    <p style="margin:0 0 1rem"><?= View::e(mb_substr((string)$c['problem_statement'], 0, 280)) ?><?= mb_strlen((string)$c['problem_statement']) > 280 ? '…' : '' ?></p>
    <form method="post" class="grid grid-2">
      <?= Csrf::field() ?>
      <input type="hidden" name="challenge_id" value="<?= (int)$c['id'] ?>">
      <div class="form-group"><label>Score</label><input name="screening_score" type="number" step="0.1" min="0" max="100"></div>
      <div class="form-group"><label>Category</label>
        <select name="category"><?php foreach (['Manufacturing','Agriculture','Mining','Banking and Fintech','Logistics','Healthcare','Retail','Energy','Smart Infrastructure'] as $cat): ?>
          <option <?= $c['category']===$cat?'selected':'' ?>><?= $cat ?></option>
        <?php endforeach; ?></select>
      </div>
      <div class="form-group"><label>Sector</label><input name="sector" value="<?= View::e($c['sector']) ?>"></div>
      <div class="form-group"><label>Notes</label><input name="notes"></div>
      <div class="form-group"><label>Action</label>
        <select name="action">
          <option value="to_screening">Move to screening</option>
          <option value="prioritise">Prioritise</option>
          <option value="publish">Publish</option>
          <option value="allocate">Allocate</option>
          <option value="archive">Archive</option>
        </select>
      </div>
      <div class="form-group" style="display:flex;align-items:end"><button class="btn btn-primary" type="submit">Apply decision</button></div>
    </form>
  </article>
<?php endforeach; ?>
