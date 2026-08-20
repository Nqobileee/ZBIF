<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;
?>
<form method="post" class="adm-card" style="max-width:720px">
  <?= Csrf::field() ?>
  <h2>Create survey</h2>
  <div class="form-group"><label>Title</label><input name="title" required></div>
  <div class="form-group"><label>Audience</label><input name="audience" value="all"></div>
  <div class="form-group"><label>Default question type</label>
    <select name="default_type"><option>short_text</option><option>nps</option><option>likert</option><option>long_text</option></select>
  </div>
  <div class="form-group"><label>Questions (one per line, optional quick start)</label><textarea name="questions" rows="5"></textarea></div>
  <label style="display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1rem"><input type="checkbox" name="is_anonymous" value="1"> Anonymous</label>
  <div><button class="btn btn-primary" type="submit">Create survey</button></div>
</form>
<div class="adm-card" style="padding:0;overflow:auto">
  <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
    <thead><tr><th>Title</th><th>Status</th><th>Builder</th><th>Analytics</th></tr></thead>
    <tbody>
      <?php foreach ($surveys as $s): ?>
        <tr>
          <td><?= View::e($s['title']) ?></td>
          <td><span class="badge"><?= View::e($s['status']) ?></span></td>
          <td><a href="<?= View::e(Url::to('/admin/surveys/' . $s['id'] . '/edit')) ?>">Edit</a></td>
          <td><a href="<?= View::e(Url::to('/admin/surveys/' . $s['id'] . '/analytics')) ?>">Analytics</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$surveys): ?><tr><td colspan="4" class="muted">No surveys yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
