<?php use App\Support\Csrf; use App\Support\View; ?>
<?php if (!$applications): ?>
  <div class="adm-empty">No sponsorship applications yet.</div>
<?php endif; ?>
<?php foreach ($applications as $a): ?>
  <article class="adm-card">
    <div class="adm-panel-head">
      <div>
        <h3 style="margin:0"><?= View::e($a['first_name'].' '.$a['last_name']) ?></h3>
        <p class="muted" style="margin:.35rem 0 0">
          <?= View::e($a['email']) ?> · <span class="badge"><?= View::e($a['status']) ?></span>
          <?php if (!empty($a['company_name'])): ?> · <?= View::e($a['company_name']) ?><?php endif; ?>
          <?php if (!empty($a['tier_requested'])): ?> · <?= View::e($a['tier_requested']) ?><?php endif; ?>
          <?php if (!empty($a['budget_usd'])): ?> · USD <?= View::e((string)$a['budget_usd']) ?><?php endif; ?>
        </p>
      </div>
    </div>
    <p><?= View::e($a['pitch']) ?></p>
    <form method="post" class="grid grid-2" style="align-items:end">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
      <div class="form-group" style="margin:0"><label>Status</label>
        <select name="status">
          <?php foreach (['under_review','shortlisted','sponsored','declined'] as $st): ?>
            <option value="<?= $st ?>" <?= $a['status']===$st?'selected':'' ?>><?= $st ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0"><label>Reviewer notes</label>
        <input name="reviewer_notes" placeholder="Reviewer notes" value="<?= View::e($a['reviewer_notes'] ?? '') ?>">
      </div>
      <div class="form-group" style="margin:0;grid-column:1/-1">
        <button class="btn btn-secondary" type="submit">Update</button>
      </div>
    </form>
    <?php if ($a['status'] === 'sponsored' && !empty($a['sponsor_id'])): ?>
      <p class="muted" style="margin:.75rem 0 0">Linked sponsor #<?= (int)$a['sponsor_id'] ?></p>
    <?php endif; ?>
  </article>
<?php endforeach; ?>
