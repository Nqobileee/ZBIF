<?php use App\Support\Csrf; use App\Support\View; ?>
<?php if (!$orgs): ?>
  <div class="adm-empty">No organizations yet.</div>
<?php else: ?>
<div class="adm-card" style="padding:0;overflow:auto">
  <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
    <thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Industry</th><th>City</th><th>Members</th><th>Website</th></tr></thead>
    <tbody>
      <?php foreach ($orgs as $o): ?>
        <tr>
          <td><?= (int)$o['id'] ?></td>
          <td><?= View::e($o['name']) ?></td>
          <td><span class="badge"><?= View::e($o['type']) ?></span></td>
          <td><?= View::e($o['industry'] ?? '') ?></td>
          <td><?= View::e(($o['city'] ?? '') . (($o['country'] ?? '') ? ', '.$o['country'] : '')) ?></td>
          <td><?= (int)($o['member_count'] ?? 0) ?></td>
          <td><?php if (!empty($o['website'])): ?><a href="<?= View::e($o['website']) ?>" target="_blank" rel="noopener"><?= View::e($o['website']) ?></a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
