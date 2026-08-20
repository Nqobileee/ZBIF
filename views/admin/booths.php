<?php use App\Support\Csrf; use App\Support\View; ?>
<form method="post" action="/admin/booths" class="adm-card" style="max-width:720px">
  <?= Csrf::field() ?>
  <h2>Assign booth</h2>
  <div class="form-group"><label>Organisation</label>
    <select name="organization_id" required>
      <?php foreach ($orgs as $o): ?><option value="<?= (int)$o['id'] ?>"><?= View::e($o['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label>Owner user</label>
    <select name="owner_user_id" required>
      <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= View::e($u['first_name'].' '.$u['last_name'].' ('.$u['email'].')') ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="grid grid-2">
    <div class="form-group"><label>Booth code</label><input name="booth_code" value="B-<?= date('His') ?>"></div>
    <div class="form-group"><label>Name</label><input name="name" required></div>
  </div>
  <div class="form-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
  <div class="grid grid-2">
    <div class="form-group"><label>Floor X</label><input name="floor_x"></div>
    <div class="form-group"><label>Floor Y</label><input name="floor_y"></div>
  </div>
  <button class="btn btn-primary" type="submit">Assign</button>
</form>
<div class="adm-card" style="padding:0;overflow:auto">
  <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
    <thead><tr><th>Code</th><th>Name</th><th>Org</th><th>Owner</th><th>Floor</th></tr></thead>
    <tbody>
      <?php foreach ($booths as $b): ?>
        <tr>
          <td><?= View::e($b['booth_code']) ?></td>
          <td><a href="/exhibitors/<?= (int)$b['id'] ?>"><?= View::e($b['name']) ?></a></td>
          <td><?= View::e($b['org_name']) ?></td>
          <td><?= View::e($b['owner_email']) ?></td>
          <td><?= View::e(($b['floor_x'] ?? '').','.($b['floor_y'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$booths): ?><tr><td colspan="5" class="muted">No booths assigned.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
