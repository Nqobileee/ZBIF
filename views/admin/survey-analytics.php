<?php use App\Support\View; $a = $analytics; ?>
<div class="adm-stat-row">
  <div class="adm-stat"><strong><?= (int)$a['responses'] ?></strong><span>Responses</span></div>
  <?php
  $npsQ = null;
  foreach ($a['questions'] as $q) { if ($q['type']==='nps' && $q['nps'] !== null) { $npsQ = $q; break; } }
  ?>
  <?php if ($npsQ): ?><div class="adm-stat"><strong><?= View::e((string)$npsQ['nps']) ?></strong><span>NPS</span></div><?php endif; ?>
</div>
<?php if (!empty($a['by_role'])): ?>
  <div class="adm-card" style="padding:0;overflow:auto">
    <div class="adm-panel-head" style="padding:1rem 1.25rem 0"><h2 style="margin:0">By role</h2></div>
    <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
      <thead><tr><th>Role</th><th>Responses</th></tr></thead>
      <tbody>
        <?php foreach ($a['by_role'] as $r): ?>
          <tr><td><?= View::e($r['role'] ?: 'anonymous/unknown') ?></td><td><?= (int)$r['c'] ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php foreach ($a['questions'] as $q): ?>
  <article class="adm-card">
    <h3 style="margin:0 0 .35rem"><?= View::e($q['prompt']) ?></h3>
    <p class="muted" style="margin:0"><?= View::e($q['type']) ?><?= $q['avg'] !== null ? ' · avg ' . number_format((float)$q['avg'], 2) : '' ?><?= $q['nps'] !== null ? ' · NPS ' . $q['nps'] : '' ?></p>
    <?php if ($q['distribution']): ?>
      <div style="display:grid;gap:0.45rem;margin-top:0.9rem">
        <?php $max = max($q['distribution']) ?: 1; foreach ($q['distribution'] as $label => $count): ?>
          <div>
            <div style="display:flex;justify-content:space-between;font-size:0.85rem"><span><?= View::e((string)$label) ?></span><span><?= (int)$count ?></span></div>
            <div style="height:8px;background:rgba(15,28,48,.06);border-radius:99px;overflow:hidden">
              <div style="height:100%;width:<?= round(($count/$max)*100) ?>%;background:var(--adm-gold,#D4AF37)"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($q['word_frequency']): ?>
      <div class="chip-row" style="margin-top:0.85rem">
        <?php foreach ($q['word_frequency'] as $w => $c): ?><span class="chip"><?= View::e($w) ?> (<?= (int)$c ?>)</span><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>
<?php endforeach; ?>
<p><a class="btn btn-primary" href="/admin/surveys/<?= (int)$survey['id'] ?>/export.pdf">Export PDF report</a></p>
