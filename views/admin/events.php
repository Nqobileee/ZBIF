<?php
use App\Support\Csrf;
use App\Support\View;

function zbif_dt_local(?string $v): string
{
    if (!$v) {
        return '';
    }
    $ts = strtotime($v);
    return $ts ? date('Y-m-d\TH:i', $ts) : '';
}
?>
<p class="muted" style="margin-top:0">Changing dates here updates the homepage countdown and every place that reads the active event.</p>
<form method="post" action="/admin/events/switch" class="adm-card" style="display:flex;gap:0.75rem;align-items:end;flex-wrap:wrap">
  <?= Csrf::field() ?>
  <input type="hidden" name="redirect" value="/admin/events">
  <div class="form-group" style="margin:0;flex:1;min-width:220px"><label>Active event context</label>
    <select name="event_id">
      <?php foreach ($events as $e): ?>
        <option value="<?= (int)$e['id'] ?>" <?= (int)$e['id']===(int)$activeId?'selected':'' ?>><?= View::e($e['name'].' ('.$e['edition'].')') ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn-secondary" type="submit">Switch</button>
</form>

<form method="post" class="adm-card" style="max-width:820px">
  <?= Csrf::field() ?>
  <h2>Create edition</h2>
  <div class="grid grid-2">
    <div class="form-group"><label>Name</label><input name="name" value="ZBIF" required></div>
    <div class="form-group"><label>Edition</label><input name="edition" value="<?= date('Y') ?>"></div>
    <div class="form-group"><label>Slug</label><input name="slug" placeholder="zbif-2027"></div>
    <div class="form-group"><label>Status</label>
      <select name="status"><option value="draft">draft</option><option value="published">published</option><option value="live">live</option><option value="archived">archived</option></select>
    </div>
    <div class="form-group"><label>Starts</label><input type="datetime-local" name="starts_at" required></div>
    <div class="form-group"><label>Ends</label><input type="datetime-local" name="ends_at" required></div>
    <div class="form-group"><label>Venue</label><input name="venue" value="ZITF grounds"></div>
    <div class="form-group"><label>Registration fee USD</label><input type="number" step="0.01" name="registration_fee" value="0"></div>
  </div>
  <div class="form-group"><label>Theme</label><input name="theme"></div>
  <div class="form-group"><label>Clone catalogue from</label>
    <select name="clone_from">
      <option value="">(none)</option>
      <?php foreach ($events as $e): ?><option value="<?= (int)$e['id'] ?>"><?= View::e($e['name'].' '.$e['edition']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn-primary" type="submit">Create event</button>
</form>

<?php foreach ($events as $e): ?>
  <article class="adm-card">
    <div class="adm-panel-head">
      <h2 style="margin:0"><?= View::e($e['name'].' '.$e['edition']) ?></h2>
      <?php if ((int)$e['id'] === (int)$activeId): ?><span class="badge badge-gold">Active · drives countdown</span><?php endif; ?>
    </div>
    <form method="post" action="/admin/events/<?= (int)$e['id'] ?>">
      <?= Csrf::field() ?>
      <div class="grid grid-2">
        <div class="form-group"><label>Name</label><input name="name" value="<?= View::e($e['name']) ?>"></div>
        <div class="form-group"><label>Edition</label><input name="edition" value="<?= View::e($e['edition']) ?>"></div>
        <div class="form-group"><label>Status</label>
          <select name="status">
            <?php foreach (['draft','published','live','archived'] as $st): ?>
              <option value="<?= $st ?>" <?= $e['status']===$st?'selected':'' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Fee USD</label><input type="number" step="0.01" name="registration_fee" value="<?= View::e((string)($e['registration_fee'] ?? 0)) ?>"></div>
        <div class="form-group"><label>Starts</label><input type="datetime-local" name="starts_at" value="<?= View::e(zbif_dt_local($e['starts_at'] ?? null)) ?>"></div>
        <div class="form-group"><label>Ends</label><input type="datetime-local" name="ends_at" value="<?= View::e(zbif_dt_local($e['ends_at'] ?? null)) ?>"></div>
        <div class="form-group"><label>Venue</label><input name="venue" value="<?= View::e($e['venue']) ?>"></div>
        <div class="form-group"><label>City</label><input name="city" value="<?= View::e($e['city']) ?>"></div>
      </div>
      <div class="form-group"><label>Theme</label><input name="theme" value="<?= View::e($e['theme'] ?? '') ?>"></div>
      <button class="btn btn-secondary" type="submit">Save dates &amp; details</button>
    </form>
  </article>
<?php endforeach; ?>
