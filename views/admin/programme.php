<?php
use App\Support\Csrf;
use App\Support\View;

function zbif_prog_dt(?string $v): string
{
    if (!$v) {
        return '';
    }
    $ts = strtotime($v);
    return $ts ? date('Y-m-d\TH:i', $ts) : '';
}
$types = ['keynote','panel','pitch','workshop','networking','deal_room','break','other'];
?>
<p class="muted" style="margin-top:0">Editing the programme for <strong><?= View::e(($event['name'] ?? 'event') . ' ' . ($event['edition'] ?? '')) ?></strong>. Changes appear on /programme and in the app schedule.</p>

<form method="post" action="/admin/programme" class="adm-card" style="max-width:900px">
  <?= Csrf::field() ?>
  <h2>Add session</h2>
  <div class="grid grid-2">
    <div class="form-group"><label>Title</label><input name="title" required></div>
    <div class="form-group"><label>Day number</label><input type="number" name="day_number" value="1" min="1"></div>
    <div class="form-group"><label>Type</label>
      <select name="session_type"><?php foreach ($types as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?></select>
    </div>
    <div class="form-group"><label>Track</label><input name="track"></div>
    <div class="form-group"><label>Room</label><input name="room"></div>
    <div class="form-group"><label>Capacity</label><input type="number" name="capacity"></div>
    <div class="form-group"><label>Starts</label><input type="datetime-local" name="starts_at" required></div>
    <div class="form-group"><label>Ends</label><input type="datetime-local" name="ends_at" required></div>
  </div>
  <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
  <button class="btn btn-primary" type="submit">Add to programme</button>
</form>

<?php foreach ($sessions as $s): ?>
  <article class="adm-card">
    <form method="post" action="/admin/programme/<?= (int)$s['id'] ?>">
      <?= Csrf::field() ?>
      <div class="grid grid-2">
        <div class="form-group"><label>Title</label><input name="title" value="<?= View::e($s['title']) ?>" required></div>
        <div class="form-group"><label>Day</label><input type="number" name="day_number" value="<?= (int)$s['day_number'] ?>" min="1"></div>
        <div class="form-group"><label>Type</label>
          <select name="session_type">
            <?php foreach ($types as $t): ?><option value="<?= $t ?>" <?= $s['session_type']===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Track</label><input name="track" value="<?= View::e($s['track'] ?? '') ?>"></div>
        <div class="form-group"><label>Room</label><input name="room" value="<?= View::e($s['room'] ?? '') ?>"></div>
        <div class="form-group"><label>Capacity</label><input type="number" name="capacity" value="<?= View::e((string)($s['capacity'] ?? '')) ?>"></div>
        <div class="form-group"><label>Starts</label><input type="datetime-local" name="starts_at" value="<?= View::e(zbif_prog_dt($s['starts_at'] ?? null)) ?>" required></div>
        <div class="form-group"><label>Ends</label><input type="datetime-local" name="ends_at" value="<?= View::e(zbif_prog_dt($s['ends_at'] ?? null)) ?>" required></div>
      </div>
      <div class="form-group"><label>Description</label><textarea name="description" rows="2"><?= View::e($s['description'] ?? '') ?></textarea></div>
      <div class="adm-actions">
        <button class="btn btn-secondary" type="submit">Save</button>
      </div>
    </form>
    <form method="post" action="/admin/programme/<?= (int)$s['id'] ?>/delete" style="margin-top:.75rem" onsubmit="return confirm('Delete this session?')">
      <?= Csrf::field() ?>
      <button class="btn btn-ghost btn-sm" type="submit">Delete</button>
    </form>
  </article>
<?php endforeach; ?>
<?php if (!$sessions): ?><div class="adm-empty">No sessions yet for this event.</div><?php endif; ?>
