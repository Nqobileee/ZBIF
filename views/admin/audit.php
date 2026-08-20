<?php use App\Support\View; ?>
<?php if (!$logs): ?>
  <div class="adm-empty">No audit events yet.</div>
<?php else: ?>
<div class="adm-card" style="padding:0;overflow:auto">
  <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
    <thead><tr><th>When</th><th>Action</th><th>Entity</th><th>Actor</th></tr></thead>
    <tbody>
      <?php foreach ($logs as $l): ?>
        <tr>
          <td><?= View::e($l['created_at']) ?></td>
          <td><?= View::e($l['action']) ?></td>
          <td><?= View::e(($l['entity_type'] ?? '') . ' #' . ($l['entity_id'] ?? '')) ?></td>
          <td><?= View::e((string)($l['actor_id'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
