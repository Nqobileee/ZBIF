<?php
use App\Support\Csrf;
use App\Support\View;
$logicJson = json_encode(array_map(static function ($l) {
    return [
        'question_id' => (int) $l['question_id'],
        'condition' => json_decode($l['condition_json'] ?: '{}', true),
        'jump' => $l['jump_to_question_id'] ? (int) $l['jump_to_question_id'] : null,
    ];
}, $logic));
?>
<section class="page-hero"><div class="container"><h1><?= View::e($survey['title']) ?></h1><p class="muted" style="margin:0">ZBIF experience survey</p></div></section>
<section class="section container" style="max-width:760px;padding-top:1.5rem">
  <form method="post" action="/survey/<?= (int)$survey['id'] ?>" class="card" id="surveyForm" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <input type="hidden" name="t" value="<?= View::e($token ?? '') ?>">
    <?php foreach ($questions as $q):
      $opts = json_decode($q['options_json'] ?? 'null', true);
    ?>
      <div class="form-group survey-q" data-qid="<?= (int)$q['id'] ?>">
        <label><?= View::e($q['prompt']) ?><?= $q['is_required'] ? ' *' : '' ?></label>
        <?php if ($q['question_type'] === 'single' && is_array($opts)): ?>
          <?php foreach ($opts as $o): ?>
            <label style="font-weight:400;display:block"><input type="radio" name="q_<?= (int)$q['id'] ?>" value="<?= View::e($o) ?>" <?= $q['is_required']?'required':'' ?>> <?= View::e($o) ?></label>
          <?php endforeach; ?>
        <?php elseif ($q['question_type'] === 'multi' && is_array($opts)): ?>
          <?php foreach ($opts as $o): ?>
            <label style="font-weight:400;display:block"><input type="checkbox" name="q_<?= (int)$q['id'] ?>[]" value="<?= View::e($o) ?>"> <?= View::e($o) ?></label>
          <?php endforeach; ?>
        <?php elseif ($q['question_type'] === 'nps'): ?>
          <input type="number" min="0" max="10" name="q_<?= (int)$q['id'] ?>" <?= $q['is_required']?'required':'' ?>>
        <?php elseif (in_array($q['question_type'], ['likert','stars'], true)): ?>
          <input type="number" min="1" max="5" name="q_<?= (int)$q['id'] ?>" <?= $q['is_required']?'required':'' ?>>
        <?php elseif ($q['question_type'] === 'long_text'): ?>
          <textarea name="q_<?= (int)$q['id'] ?>" rows="4" <?= $q['is_required']?'required':'' ?>></textarea>
        <?php elseif ($q['question_type'] === 'file'): ?>
          <input type="file" name="q_<?= (int)$q['id'] ?>" <?= $q['is_required']?'required':'' ?>>
        <?php elseif ($q['question_type'] === 'matrix' && is_array($opts)): ?>
          <table class="table">
            <thead><tr><th></th><?php foreach (($opts['cols'] ?? []) as $c): ?><th><?= View::e($c) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
              <?php foreach (($opts['rows'] ?? []) as $r): ?>
                <tr>
                  <td><?= View::e($r) ?></td>
                  <?php foreach (($opts['cols'] ?? []) as $c): ?>
                    <td><input type="radio" name="q_<?= (int)$q['id'] ?>[<?= View::e($r) ?>]" value="<?= View::e($c) ?>"></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <input name="q_<?= (int)$q['id'] ?>" <?= $q['is_required']?'required':'' ?>>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <button class="btn btn-primary" type="submit">Submit</button>
  </form>
</section>
<script>
const logic = <?= $logicJson ?: '[]' ?>;
function valFor(qid) {
  const radios = document.querySelectorAll('[name="q_'+qid+'"]:checked');
  if (radios.length) return radios[0].value;
  const el = document.querySelector('[name="q_'+qid+'"]');
  return el ? el.value : '';
}
function applyLogic() {
  const blocks = [...document.querySelectorAll('.survey-q')];
  blocks.forEach(el => el.style.display = '');
  logic.forEach(rule => {
    const v = valFor(rule.question_id);
    const cond = rule.condition || {};
    let match = false;
    if (cond.op === 'equals') match = String(v) === String(cond.value);
    if (cond.op === 'contains') match = String(v).includes(String(cond.value));
    if (cond.op === 'gte') match = parseFloat(v) >= parseFloat(cond.value);
    if (!match) return;
    const startIdx = blocks.findIndex(b => Number(b.dataset.qid) === rule.question_id);
    const jumpIdx = rule.jump ? blocks.findIndex(b => Number(b.dataset.qid) === rule.jump) : blocks.length;
    for (let i = startIdx + 1; i < blocks.length; i++) {
      if (i < jumpIdx) blocks[i].style.display = 'none';
    }
  });
}
document.getElementById('surveyForm')?.addEventListener('change', applyLogic);
</script>
