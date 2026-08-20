<?php use App\Support\Csrf; use App\Support\View; $stages = $stages ?? []; $byStage = $byStage ?? []; ?>
<div class="adm-stat-row">
  <?php foreach ($stages as $st): ?>
    <div class="adm-stat">
      <strong><?= (int)($byStage[$st] ?? 0) ?></strong>
      <span><?= View::e(str_replace('_',' ',$st)) ?></span>
    </div>
  <?php endforeach; ?>
</div>
<div class="adm-toolbar">
  <p class="muted" style="margin:0">Force stage only when organisers need to intervene.</p>
  <a class="btn btn-outline btn-sm" href="/admin/deals/export.csv">Export outcomes CSV</a>
</div>
<div class="adm-card" style="padding:0;overflow:auto">
  <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
    <thead><tr><th>Title</th><th>Stage</th><th>People</th><th>Updated</th><th>Intervene</th></tr></thead>
    <tbody>
      <?php foreach ($rooms as $r): ?>
        <tr>
          <td><a href="/app/deals/<?= (int)$r['id'] ?>"><?= View::e($r['title']) ?></a></td>
          <td><span class="badge badge-gold"><?= View::e(str_replace('_',' ',$r['stage'])) ?></span></td>
          <td><?= (int)($r['participant_count'] ?? 0) ?></td>
          <td><?= View::e($r['updated_at']) ?></td>
          <td>
            <form method="post" action="/admin/deals/stage" style="display:flex;gap:0.35rem;flex-wrap:wrap">
              <?= Csrf::field() ?>
              <input type="hidden" name="deal_room_id" value="<?= (int)$r['id'] ?>">
              <select name="stage">
                <?php foreach ($stages as $st): ?><option value="<?= $st ?>" <?= $r['stage']===$st?'selected':'' ?>><?= str_replace('_',' ',$st) ?></option><?php endforeach; ?>
              </select>
              <input name="notes" placeholder="Admin note" style="min-width:120px">
              <button class="btn btn-ghost btn-sm" type="submit">Update</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rooms): ?><tr><td colspan="5" class="muted">No deal rooms yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<div class="adm-card">
  <h2>Outcomes</h2>
  <table class="table">
    <thead><tr><th>Type</th><th>Amount</th><th>Public</th></tr></thead>
    <tbody>
      <?php foreach ($outcomes as $o): ?>
        <tr><td><?= View::e($o['outcome_type']) ?></td><td><?= View::e((string)($o['amount_usd'] ?? '')) ?></td><td><?= (int)$o['announced_publicly'] ? 'Yes' : 'No' ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$outcomes): ?><tr><td colspan="3" class="muted">No outcomes recorded.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
