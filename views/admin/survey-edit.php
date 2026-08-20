<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;
use App\Domain\SurveyEngine;
?>
<div class="adm-card">
  <p style="margin:0"><strong>Status:</strong> <span class="badge"><?= View::e($survey['status']) ?></span> · Audience: <?= View::e($survey['audience']) ?></p>
  <p class="muted">Public / venue QR link:</p>
  <code><?= View::e($publicUrl) ?></code>
  <div style="margin-top:0.75rem">
    <img src="<?= View::e(\App\Support\QrCode::svgDataUri($publicUrl, 140)) ?>" width="140" height="140" alt="Survey QR">
  </div>
  <form method="post" action="/admin/surveys/<?= (int)$survey['id'] ?>/publish" class="adm-actions" style="margin-top:1rem">
    <?= Csrf::field() ?>
    <select name="status"><option value="open">Open</option><option value="closed">Closed</option><option value="draft">Draft</option></select>
    <button class="btn btn-secondary" type="submit">Update status</button>
    <a class="btn btn-ghost btn-sm" href="/admin/surveys/<?= (int)$survey['id'] ?>/analytics">Analytics</a>
    <a class="btn btn-ghost btn-sm" href="/admin/surveys/<?= (int)$survey['id'] ?>/export.csv">CSV</a>
  </form>
</div>

<form method="post" action="/admin/surveys/<?= (int)$survey['id'] ?>/deliver" class="adm-card">
  <?= Csrf::field() ?>
  <h2>Multi-channel delivery</h2>
  <div class="grid grid-2">
    <div class="form-group"><label>Channel</label>
      <select name="channel"><option value="in_app">In-app</option><option value="email">Email</option><option value="sms">SMS</option></select>
    </div>
    <div class="form-group"><label>Role filter (optional)</label><input name="role" placeholder="innovator"></div>
  </div>
  <button class="btn btn-primary" type="submit">Send invitations</button>
</form>

<form method="post" action="/admin/surveys/<?= (int)$survey['id'] ?>/questions" class="adm-card">
  <?= Csrf::field() ?>
  <h2>Add question</h2>
  <div class="form-group"><label>Prompt</label><input name="prompt" required></div>
  <div class="form-group"><label>Type</label>
    <select name="question_type" id="qType">
      <?php foreach ($types as $k => $label): ?><option value="<?= $k ?>"><?= View::e($label) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="form-group" id="optsBox"><label>Options (one per line for single/multi)</label><textarea name="options" rows="4"></textarea></div>
  <div class="grid grid-2" id="matrixBox" style="display:none">
    <div class="form-group"><label>Matrix rows</label><textarea name="matrix_rows" rows="3"></textarea></div>
    <div class="form-group"><label>Matrix columns</label><textarea name="matrix_cols" rows="3"></textarea></div>
  </div>
  <label style="display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1rem"><input type="checkbox" name="is_required" value="1" checked> Required</label>
  <div><button class="btn btn-primary" type="submit">Add question</button></div>
</form>

<h2 style="margin:1.25rem 0 .75rem;font-size:1.1rem">Questions</h2>
<?php if (!$questions): ?><div class="adm-empty">No questions yet.</div><?php endif; ?>
<?php foreach ($questions as $q): ?>
  <article class="adm-card" style="padding:1rem 1.15rem">
    <span class="badge"><?= View::e($q['question_type']) ?></span>
    <strong style="margin-left:.35rem">#<?= (int)$q['id'] ?></strong>
    <span style="margin-left:.35rem"><?= View::e($q['prompt']) ?></span>
  </article>
<?php endforeach; ?>

<form method="post" action="/admin/surveys/<?= (int)$survey['id'] ?>/logic" class="adm-card">
  <?= Csrf::field() ?>
  <h2>Conditional branching</h2>
  <div class="grid grid-2">
    <div class="form-group"><label>If question ID</label><input name="question_id" required></div>
    <div class="form-group"><label>Operator</label>
      <select name="op"><option value="equals">equals</option><option value="contains">contains</option><option value="gte">gte</option></select>
    </div>
    <div class="form-group"><label>Value</label><input name="value" required></div>
    <div class="form-group"><label>Jump to question ID (blank = end)</label><input name="jump_to_question_id"></div>
  </div>
  <button class="btn btn-secondary" type="submit">Add logic rule</button>
</form>
<?php if ($logic): ?>
  <div class="adm-card">
    <h2>Logic rules</h2>
    <ul class="adm-list">
      <?php foreach ($logic as $l): ?>
        <li>Q<?= (int)$l['question_id'] ?> <?= View::e($l['condition_json']) ?> → <?= View::e((string)($l['jump_to_question_id'] ?? 'end')) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
<script>
document.getElementById('qType')?.addEventListener('change', (e) => {
  const v = e.target.value;
  document.getElementById('matrixBox').style.display = v === 'matrix' ? '' : 'none';
  document.getElementById('optsBox').style.display = ['single','multi'].includes(v) ? '' : (v === 'matrix' ? 'none' : '');
});
</script>
