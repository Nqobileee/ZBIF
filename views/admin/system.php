<?php use App\Support\Csrf; use App\Support\View; $payments = $payments ?? []; $failedJobs = $failedJobs ?? []; ?>
<div class="adm-stat-row">
  <div class="adm-stat"><strong><?= (int)$queue ?></strong><span>Queue depth</span></div>
  <div class="adm-stat"><strong><?= (int)($payments['pending'] ?? 0) ?></strong><span>Pending payments</span></div>
  <div class="adm-stat"><strong><?= (int)($payments['paid'] ?? 0) ?></strong><span>Paid transactions</span></div>
</div>
<div class="adm-grid adm-grid-2">
  <div class="adm-card">
    <h2>AI providers</h2>
    <ul class="adm-feature-list">
      <?php foreach ($ai as $name => $ok): ?>
        <li><?= View::e($name) ?>: <?= $ok ? 'configured' : 'missing key' ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="adm-card">
    <h2>Payments (PayNow)</h2>
    <ul class="adm-feature-list">
      <li>Enabled: <?= !empty($payments['enabled']) ? 'yes' : 'no (dormant / demo waive)' ?></li>
      <li>Integration ID: <?= !empty($payments['integration_id_set']) ? 'set' : 'missing' ?></li>
      <li>Key: <?= !empty($payments['integration_key_set']) ? 'set' : 'missing' ?></li>
    </ul>
  </div>
</div>
<div class="adm-card" style="padding:0;overflow:auto">
  <div class="adm-panel-head" style="padding:1rem 1.25rem 0">
    <h2 style="margin:0">Feature flags</h2>
  </div>
  <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
    <thead><tr><th>Flag</th><th>Enabled</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach ($flags as $f): ?>
        <tr>
          <td><?= View::e($f['flag_key']) ?><div class="muted"><?= View::e($f['description'] ?? '') ?></div></td>
          <td><?= (int)$f['is_enabled'] ? 'On' : 'Off' ?></td>
          <td>
            <form method="post" action="/admin/system/flags">
              <?= Csrf::field() ?>
              <input type="hidden" name="flag_key" value="<?= View::e($f['flag_key']) ?>">
              <button class="btn btn-ghost btn-sm" type="submit">Toggle</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<div class="adm-card" style="padding:0;overflow:auto">
  <div class="adm-panel-head" style="padding:1rem 1.25rem 0">
    <h2 style="margin:0">Failed jobs</h2>
  </div>
  <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
    <thead><tr><th>ID</th><th>Type</th><th>Error</th><th>When</th></tr></thead>
    <tbody>
      <?php foreach ($failedJobs as $j): ?>
        <tr>
          <td><?= (int)$j['id'] ?></td>
          <td><?= View::e($j['job_type']) ?></td>
          <td><?= View::e(mb_substr((string)$j['last_error'], 0, 120)) ?></td>
          <td><?= View::e($j['failed_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$failedJobs): ?><tr><td colspan="4" class="muted">None</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
