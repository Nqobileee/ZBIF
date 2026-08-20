<?php use App\Support\Csrf; use App\Support\View; $counts = $counts ?? []; $statuses = $statuses ?? []; $filter = $filter ?? ''; ?>
<div class="home-hero" style="margin-bottom:1rem">
  <div>
    <p class="eyebrow">Exhibition floor</p>
    <h1>Booth CRM</h1>
    <p>Manage your booth profile and capture visitor leads during the forum.</p>
  </div>
</div>
<?php if ($booth): ?>
  <div class="meet-layout">
    <form method="post" action="/app/exhibition" class="dash-card">
      <?= Csrf::field() ?>
      <h2>Booth profile</h2>
      <div class="form-group"><label>Name</label><input name="name" value="<?= View::e($booth['name']) ?>"></div>
      <div class="form-group"><label>Description</label><textarea name="description" rows="3"><?= View::e($booth['description'] ?? '') ?></textarea></div>
      <div class="form-group"><label>Brochure URL</label><input name="brochure_url" value="<?= View::e(json_decode($booth['media_json'] ?? '{}', true)['brochure'] ?? '') ?>"></div>
      <div class="form-group"><label>Video URL</label><input name="video_url" value="<?= View::e(json_decode($booth['media_json'] ?? '{}', true)['video'] ?? '') ?>"></div>
      <div class="grid grid-2">
        <div class="form-group"><label>Floor X</label><input name="floor_x" value="<?= View::e((string)($booth['floor_x'] ?? '')) ?>"></div>
        <div class="form-group"><label>Floor Y</label><input name="floor_y" value="<?= View::e((string)($booth['floor_y'] ?? '')) ?>"></div>
      </div>
      <label><input type="checkbox" name="launching_at_forum" value="1" <?= $booth['launching_at_forum'] ? 'checked' : '' ?>> Launching at forum</label>
      <button class="btn btn-primary" style="display:block;margin-top:0.75rem" type="submit">Save booth</button>
    </form>
    <form method="post" action="/app/exhibition/leads" class="dash-card">
      <?= Csrf::field() ?>
      <h2>Capture a lead</h2>
      <p class="muted">Enter the visitor badge token provided at check-in.</p>
      <div class="form-group"><label>Attendee badge token</label><input name="qr_token" required></div>
      <div class="form-group"><label>Notes</label><input name="notes"></div>
      <div class="form-group"><label>Tags</label><input name="tags" placeholder="procurement, pilot"></div>
      <button class="btn btn-primary" type="submit">Capture lead</button>
    </form>
  </div>
  <div class="chip-row" style="margin:1rem 0">
    <a class="chip <?= $filter===''?'is-active':'' ?>" href="/app/exhibition">All</a>
    <?php foreach ($statuses as $st): ?>
      <a class="chip <?= $filter===$st?'is-active':'' ?>" href="/app/exhibition?status=<?= urlencode($st) ?>"><?= View::e($st) ?> (<?= (int)($counts[$st] ?? 0) ?>)</a>
    <?php endforeach; ?>
  </div>
  <p><a class="btn btn-ghost btn-sm" href="/app/exhibition/leads.csv">Export CSV</a></p>
  <div class="chal-list">
  <?php foreach ($leads as $l): ?>
    <article class="chal-card">
      <strong><?= View::e($l['first_name'].' '.$l['last_name']) ?></strong>
      <div class="muted"><?= View::e($l['email']) ?> · <?= View::e($l['created_at']) ?></div>
      <form method="post" action="/app/exhibition/leads/update" style="margin-top:0.65rem">
        <?= Csrf::field() ?>
        <input type="hidden" name="lead_id" value="<?= (int)$l['id'] ?>">
        <div class="grid grid-2">
          <div class="form-group"><label>Status</label>
            <select name="status">
              <?php foreach ($statuses as $st): ?><option value="<?= $st ?>" <?= ($l['status'] ?? 'new')===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Follow-up</label><input type="datetime-local" name="follow_up_at" value="<?= View::e($l['follow_up_at'] ? date('Y-m-d\TH:i', strtotime($l['follow_up_at'])) : '') ?>"></div>
        </div>
        <div class="form-group"><label>Tags</label><input name="tags" value="<?= View::e($l['tags'] ?? '') ?>"></div>
        <div class="form-group"><label>Notes</label><textarea name="notes" rows="2"><?= View::e($l['notes'] ?? '') ?></textarea></div>
        <button class="btn btn-secondary btn-sm" type="submit">Update lead</button>
      </form>
    </article>
  <?php endforeach; ?>
  <?php if (!$leads): ?><p class="muted">No leads captured yet.</p><?php endif; ?>
  </div>
<?php else: ?>
  <div class="dash-card">
    <p class="muted" style="margin:0">No booth assigned to your account yet. Ask an organizer to assign one under Admin → Booths.</p>
  </div>
<?php endif; ?>
<style>.chip.is-active{background:var(--navy-800);color:#fff;border-color:var(--navy-800)}</style>
