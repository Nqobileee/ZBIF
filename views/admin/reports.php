<?php use App\Support\Url; use App\Support\View; $m = $metrics ?? []; ?>
<div class="adm-stat-row">
  <div class="adm-stat"><strong><?= (int)$orgCount ?></strong><span>Organizations</span></div>
  <div class="adm-stat"><strong><?= (int)$sessionCount ?></strong><span>Programme sessions</span></div>
  <div class="adm-stat"><strong><?= (int)($m['matches_made'] ?? 0) ?></strong><span>Matches made</span></div>
</div>

<div class="adm-grid adm-grid-2">
  <section class="adm-card">
    <div class="adm-panel-head">
      <h2>Impact snapshot</h2>
      <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/admin/impact')) ?>">Full impact</a>
    </div>
    <ul class="adm-feature-list">
      <?php foreach ($m as $k => $v): ?>
        <li><strong><?= View::e(str_replace('_',' ', (string)$k)) ?>:</strong> <?= View::e(is_scalar($v) ? (string)$v : json_encode($v)) ?></li>
      <?php endforeach; ?>
      <?php if (!$m): ?><li>No metrics yet.</li><?php endif; ?>
    </ul>
    <div class="dash-quick">
      <a class="btn btn-outline btn-sm" href="<?= View::e(Url::to('/admin/impact/export.csv')) ?>">CSV</a>
      <a class="btn btn-outline btn-sm" href="<?= View::e(Url::to('/admin/impact/export.pdf')) ?>">PDF</a>
    </div>
  </section>
  <section class="adm-card">
    <div class="adm-panel-head">
      <h2>Deal rooms by stage</h2>
      <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/admin/deals')) ?>">Board</a>
    </div>
    <table class="table">
      <thead><tr><th>Stage</th><th>Count</th></tr></thead>
      <tbody>
        <?php foreach ($dealStages as $row): ?>
          <tr><td><?= View::e(str_replace('_',' ', $row['stage'])) ?></td><td><?= (int)$row['c'] ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$dealStages): ?><tr><td colspan="2" class="muted">No rooms yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
    <a class="btn btn-outline btn-sm" href="<?= View::e(Url::to('/admin/deals/export.csv')) ?>">Outcomes CSV</a>
  </section>
  <section class="adm-card">
    <div class="adm-panel-head">
      <h2>Registrations by persona</h2>
      <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/admin/registrations')) ?>">All</a>
    </div>
    <table class="table">
      <thead><tr><th>Persona</th><th>Status</th><th>Count</th></tr></thead>
      <tbody>
        <?php foreach ($regByPersona as $row): ?>
          <tr><td><?= View::e($row['persona']) ?></td><td><?= View::e($row['status']) ?></td><td><?= (int)$row['c'] ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <a class="btn btn-outline btn-sm" href="<?= View::e(Url::to('/admin/registrations/export.csv')) ?>">Export CSV</a>
  </section>
  <section class="adm-card">
    <div class="adm-panel-head">
      <h2>Users by role</h2>
      <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/admin/users')) ?>">Manage</a>
    </div>
    <table class="table">
      <thead><tr><th>Role</th><th>Users</th></tr></thead>
      <tbody>
        <?php foreach ($usersByRole as $row): ?>
          <tr><td><?= View::e($row['name']) ?></td><td><?= (int)$row['c'] ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <a class="btn btn-outline btn-sm" href="<?= View::e(Url::to('/admin/organizations')) ?>">Companies</a>
  </section>
</div>
