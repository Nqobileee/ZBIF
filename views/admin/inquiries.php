<?php use App\Support\Csrf; use App\Support\View; $magnets = $magnets ?? []; ?>
<div class="adm-card" style="padding:0;overflow:auto">
  <div class="adm-panel-head" style="padding:1rem 1.25rem 0">
    <h2 style="margin:0">Partner / contact inquiries</h2>
  </div>
  <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
    <thead><tr><th>When</th><th>Name</th><th>Type</th><th>Message</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= View::e($r['created_at']) ?></td>
          <td><?= View::e($r['name']) ?><br><span class="muted"><?= View::e($r['email']) ?></span></td>
          <td><?= View::e($r['interest_type']) ?></td>
          <td><?= View::e(mb_substr($r['message'], 0, 120)) ?></td>
          <td>
            <form method="post" action="/admin/inquiries">
              <?= Csrf::field() ?>
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <select name="status" onchange="this.form.submit()">
                <?php foreach (['new','contacted','closed'] as $st): ?>
                  <option value="<?= $st ?>" <?= $r['status']===$st?'selected':'' ?>><?= $st ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="muted">No inquiries.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<div class="adm-card" style="padding:0;overflow:auto">
  <div class="adm-panel-head" style="padding:1rem 1.25rem 0">
    <h2 style="margin:0">Lead magnet captures</h2>
  </div>
  <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
    <thead><tr><th>When</th><th>Email</th><th>Name</th><th>Magnet</th></tr></thead>
    <tbody>
      <?php foreach ($magnets as $m): ?>
        <tr>
          <td><?= View::e($m['created_at']) ?></td>
          <td><?= View::e($m['email']) ?></td>
          <td><?= View::e($m['name'] ?? '') ?></td>
          <td><?= View::e($m['magnet_type']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$magnets): ?><tr><td colspan="4" class="muted">No lead captures.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
