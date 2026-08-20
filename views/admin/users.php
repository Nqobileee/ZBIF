<?php use App\Support\Csrf; use App\Support\View; ?>
<div class="adm-card">
  <h2>Create admin / user</h2>
  <p class="muted">Use role <strong>super_admin</strong> or <strong>organizer</strong> for full admin access.</p>
  <form method="post" action="/admin/users/create">
    <?= Csrf::field() ?>
    <div class="grid grid-2">
      <div class="form-group"><label>First name</label><input name="first_name" required></div>
      <div class="form-group"><label>Last name</label><input name="last_name" required></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
      <div class="form-group"><label>Password</label><input type="password" name="password" required minlength="10"></div>
      <div class="form-group"><label>Role</label>
        <select name="role">
          <?php foreach ($roles as $r): ?>
            <option value="<?= View::e($r['slug']) ?>" <?= $r['slug']==='super_admin'?'selected':'' ?>><?= View::e($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <button class="btn btn-primary" type="submit">Create user</button>
  </form>
</div>

<div class="adm-card" style="padding:0;overflow:auto">
  <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
    <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Roles</th><th>Active</th><th>Joined</th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= (int)$u['id'] ?></td>
          <td><?= View::e($u['first_name'].' '.$u['last_name']) ?></td>
          <td><?= View::e($u['email']) ?></td>
          <td><?= View::e($u['roles'] ?? '') ?></td>
          <td><?= (int)$u['is_active'] ? 'Yes' : 'No' ?></td>
          <td><?= View::e($u['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="adm-card" style="max-width:560px">
  <h2>Assign role / activate</h2>
  <form method="post" action="/admin/users/role">
    <?= Csrf::field() ?>
    <div class="form-group"><label>User ID</label><input name="user_id" required></div>
    <div class="form-group"><label>Role</label>
      <select name="role"><?php foreach ($roles as $r): ?><option value="<?= View::e($r['slug']) ?>"><?= View::e($r['name']) ?></option><?php endforeach; ?></select>
    </div>
    <div class="form-group"><label>Active</label>
      <select name="is_active"><option value="1">Yes</option><option value="0">No</option></select>
    </div>
    <button class="btn btn-primary" type="submit">Save</button>
  </form>
</div>
