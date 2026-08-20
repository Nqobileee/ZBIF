<?php use App\Support\View; $events = $events ?? []; $eventId = $eventId ?? 0; ?>
<form method="get" class="adm-card" style="display:flex;gap:0.75rem;align-items:end;flex-wrap:wrap">
  <div class="form-group" style="margin:0;min-width:220px"><label>Edition</label>
    <select name="event_id" onchange="this.form.submit()">
      <?php foreach ($events as $e): ?>
        <option value="<?= (int)$e['id'] ?>" <?= (int)$e['id']===(int)$eventId?'selected':'' ?>><?= View::e($e['name'].' '.$e['edition']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</form>
<div class="adm-stat-row">
  <?php foreach ($metrics as $k => $v): ?>
    <div class="adm-stat"><strong><?= is_float($v) ? number_format($v) : (int)$v ?></strong><span><?= View::e(str_replace('_',' ',$k)) ?></span></div>
  <?php endforeach; ?>
</div>
<div class="adm-toolbar">
  <p class="muted" style="margin:0">Export impact reports for the selected edition.</p>
  <div class="adm-actions">
    <a class="btn btn-primary btn-sm" href="/admin/impact/export.csv?event_id=<?= (int)$eventId ?>">Export CSV</a>
    <a class="btn btn-outline btn-sm" href="/admin/impact/export.pdf?event_id=<?= (int)$eventId ?>">PDF report</a>
  </div>
</div>
<div class="adm-card" style="padding:0;overflow:auto">
  <div class="adm-panel-head" style="padding:1rem 1.25rem 0">
    <h2 style="margin:0">By sector / category</h2>
  </div>
  <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
    <thead><tr><th>Category</th><th>Challenges</th></tr></thead>
    <tbody>
      <?php foreach ($bySector as $row): ?>
        <tr><td><?= View::e($row['category']) ?></td><td><?= (int)$row['c'] ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$bySector): ?><tr><td colspan="2" class="muted">No sector data yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
