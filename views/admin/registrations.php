<?php use App\Support\Csrf; use App\Support\View; ?>
<div class="adm-toolbar">
  <p class="muted" style="margin:0">Review and approve participant registrations.</p>
  <a class="btn btn-outline btn-sm" href="/admin/registrations/export.csv">Export CSV</a>
</div>
<form method="post" action="/admin/registrations/action">
  <?= Csrf::field() ?>
  <div class="adm-toolbar">
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center">
      <select name="status">
        <option value="approved">Approve</option>
        <option value="rejected">Reject</option>
        <option value="submitted">Mark submitted</option>
      </select>
      <button class="btn btn-primary" type="submit">Apply to selected</button>
    </div>
  </div>
  <div class="adm-card" style="padding:0;overflow:auto">
    <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
      <thead><tr><th></th><th>Name</th><th>Email</th><th>Persona</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>"></td>
            <td><?= View::e($r['first_name'].' '.$r['last_name']) ?></td>
            <td><?= View::e($r['email']) ?></td>
            <td><?= View::e($r['persona']) ?></td>
            <td><span class="badge"><?= View::e($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="5" class="muted">No registrations.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</form>
