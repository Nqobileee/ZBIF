<?php use App\Support\View; ?>
<div class="adm-toolbar">
  <p class="muted" style="margin:0"><strong><?= (int)$responses ?></strong> responses</p>
  <a class="btn btn-outline btn-sm" href="/admin/surveys/<?= (int)$survey['id'] ?>/export.csv">Export CSV</a>
</div>
<?php if (!$questions): ?>
  <div class="adm-empty">No questions on this survey.</div>
<?php endif; ?>
<?php foreach ($questions as $q): ?>
  <article class="adm-card">
    <h3 style="margin:0 0 .35rem"><?= View::e($q['prompt']) ?></h3>
    <p class="muted" style="margin:0">Type: <?= View::e($q['question_type']) ?>
      <?php if ($stats[$q['id']]['avg'] !== null): ?> · Avg <?= number_format((float)$stats[$q['id']]['avg'], 2) ?><?php endif; ?>
    </p>
  </article>
<?php endforeach; ?>
