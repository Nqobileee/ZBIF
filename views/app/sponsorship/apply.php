<?php use App\Support\Csrf; use App\Support\View; $tiers = $tiers ?? []; $applications = $applications ?? []; ?>
<form method="post" class="card" style="max-width:720px;margin-bottom:1.5rem" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <h2>Apply for sponsorship</h2>
  <div class="form-group"><label>Company name</label><input name="company_name" required></div>
  <div class="form-group"><label>Website</label><input name="website" placeholder="https://"></div>
  <div class="grid grid-2">
    <div class="form-group"><label>Tier</label>
      <select name="tier_requested">
        <?php foreach ($tiers as $k => $t): ?>
          <option value="<?= $k ?>"><?= View::e($t['label']) ?> (from USD <?= number_format((float)$t['from']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Budget USD</label><input type="number" step="0.01" name="budget_usd"></div>
  </div>
  <div class="form-group"><label>Logo</label><input type="file" name="logo" accept="image/*,.pdf"></div>
  <div class="form-group"><label>Pitch / activation ideas</label><textarea name="pitch" rows="5" required></textarea></div>
  <button class="btn btn-primary" type="submit">Submit application</button>
</form>
<?php if ($applications): ?>
  <h2>Your applications</h2>
  <?php foreach ($applications as $a): ?>
    <article class="card" style="margin-bottom:0.75rem">
      <span class="badge"><?= View::e($a['status']) ?></span>
      <strong><?= View::e($a['company_name'] ?: 'Application') ?></strong>
      <div class="muted"><?= View::e($a['tier_requested'] ?? '') ?> · <?= View::e((string)($a['budget_usd'] ?? '')) ?></div>
      <p><?= View::e(mb_substr($a['pitch'], 0, 160)) ?></p>
      <?php if ($a['status'] === 'sponsored'): ?>
        <a class="btn btn-secondary" href="/app/sponsor-portal">Open sponsor portal</a>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
<?php endif; ?>
