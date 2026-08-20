<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;
$files = $files ?? [];
$timeline = $timeline ?? [];
$checklist = $checklist ?? [];
$stages = $stages ?? [];
$stageLabel = str_replace('_', ' ', (string) ($room['stage'] ?? ''));
?>
<div class="deal-page">
  <section class="deal-hero">
    <div class="deal-hero-top">
      <div>
        <p class="eyebrow">Confidential Deal Room</p>
        <h2><?= View::e($room['title']) ?></h2>
        <p>
          Stage: <strong><?= View::e($stageLabel) ?></strong>
          <?php if (!empty($challenge)): ?> · Challenge: <?= View::e($challenge['title']) ?><?php endif; ?>
          <?php if (!empty($solution)): ?> · Solution: <?= View::e($solution['name']) ?><?php endif; ?>
        </p>
      </div>
      <a class="btn btn-on-dark btn-sm" href="<?= View::e(Url::to('/app/deals')) ?>">← Pipeline</a>
    </div>
    <div class="deal-pipe" aria-label="Pipeline stage">
      <?php
        $hit = false;
        foreach ($stages as $st):
          $isCurrent = $st === ($room['stage'] ?? '');
          if ($isCurrent) { $hit = true; }
          $cls = $isCurrent ? 'is-live' : (!$hit ? 'is-done' : '');
      ?>
        <span class="<?= $cls ?>"><?= View::e(str_replace('_', ' ', $st)) ?></span>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if ($sponsor): ?>
    <div class="deal-sponsor-banner">Deal Room presented by <strong><?= View::e($sponsor['name']) ?></strong></div>
  <?php endif; ?>

  <?php if (empty($ndaAccepted)): ?>
    <div class="deal-nda">
      <h3 style="margin:0 0 .5rem">Confidentiality (NDA)</h3>
      <p class="muted" style="margin:0 0 1rem">By continuing you agree that Deal Room materials and discussions are confidential to ZBIF participants and may not be shared outside this room without written consent.</p>
      <form method="post" action="/app/deals/<?= (int)$room['id'] ?>/nda">
        <?= Csrf::field() ?>
        <button class="btn btn-primary" type="submit">I acknowledge the NDA</button>
      </form>
    </div>
  <?php else: ?>
    <p class="muted" style="margin:0">NDA acknowledged. This room is confidential.</p>
  <?php endif; ?>

  <?php if (!empty($room['agreement_summary'])): ?>
    <div class="deal-panel">
      <h3>Agreement summary</h3>
      <p style="margin:0"><?= nl2br(View::e($room['agreement_summary'])) ?></p>
    </div>
  <?php endif; ?>

  <div class="deal-room-grid">
    <section class="deal-panel" style="margin:0">
      <h2>Messages</h2>
      <div class="deal-msg-log" id="dealMessages" data-room-id="<?= (int)$room['id'] ?>" data-after="<?= $messages ? (int)end($messages)['id'] : 0 ?>">
        <?php if (!$messages): ?><p class="muted">No messages yet. Start the discussion.</p><?php endif; ?>
        <?php foreach ($messages as $m): ?>
          <div class="deal-msg">
            <strong><?= View::e($m['first_name'].' '.$m['last_name']) ?></strong>
            <div class="body"><?= View::e($m['body']) ?></div>
            <div class="when"><?= View::e($m['created_at']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <form method="post" action="/app/deals/<?= (int)$room['id'] ?>/messages" id="dealMsgForm" class="deal-compose" onsubmit="return postDealMsg(event)">
        <?= Csrf::field() ?>
        <textarea name="body" id="dealBody" rows="2" required placeholder="Write a confidential message…"></textarea>
        <button class="btn btn-primary" type="submit">Send message</button>
      </form>
    </section>

    <aside class="deal-side-stack">
      <div class="deal-panel">
        <h2>Closing checklist</h2>
        <?php foreach ($checklist as $item): ?>
          <form method="post" action="/app/deals/<?= (int)$room['id'] ?>/checklist" class="deal-check-item">
            <?= Csrf::field() ?>
            <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
            <button class="btn btn-ghost" type="submit" aria-label="Toggle"><?= (int)$item['is_done'] ? '✓' : '○' ?></button>
            <span style="<?= (int)$item['is_done'] ? 'text-decoration:line-through;opacity:0.65' : '' ?>"><?= View::e($item['label']) ?></span>
          </form>
        <?php endforeach; ?>
        <?php if (!$checklist): ?><p class="muted">Checklist will appear here.</p><?php endif; ?>
      </div>

      <div class="deal-panel">
        <h2>Stage timeline</h2>
        <?php if (!$timeline): ?><p class="muted">No stage changes yet.</p><?php endif; ?>
        <ul class="deal-timeline">
          <?php foreach ($timeline as $ev): ?>
            <li>
              <strong><?= View::e(str_replace('_',' ', (string)$ev['from_stage'])) ?></strong>
              → <?= View::e(str_replace('_',' ', (string)$ev['to_stage'])) ?>
              <div class="muted" style="font-size:0.8rem"><?= View::e($ev['created_at']) ?><?= $ev['first_name'] ? ' · '.$ev['first_name'].' '.$ev['last_name'] : '' ?></div>
              <?php if ($ev['notes']): ?><div class="muted"><?= View::e($ev['notes']) ?></div><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="deal-panel">
        <h2>Shared files</h2>
        <ul class="deal-file-list">
          <?php foreach ($files as $f): ?>
            <li>
              <a href="/app/deals/<?= (int)$room['id'] ?>/files/<?= (int)$f['id'] ?>/download"><?= View::e($f['original_name']) ?></a>
              <span class="muted"><?= View::e($f['created_at']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php if (!$files): ?><p class="muted">No files shared yet.</p><?php endif; ?>
        <form method="post" action="/app/deals/<?= (int)$room['id'] ?>/files" enctype="multipart/form-data" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
          <?= Csrf::field() ?>
          <input type="file" name="file" required style="flex:1;min-width:140px">
          <button class="btn btn-outline btn-sm" type="submit">Upload</button>
        </form>
        <p class="muted" style="font-size:0.8rem;margin-top:0.5rem">PDF, Office, images, CSV. Max 5MB.</p>
      </div>

      <div class="deal-panel">
        <h2>Book meeting</h2>
        <form method="post" action="/app/deals/<?= (int)$room['id'] ?>/meeting">
          <?= Csrf::field() ?>
          <div class="form-group"><label>Starts</label><input type="datetime-local" name="starts_at" required></div>
          <div class="form-group"><label>Ends</label><input type="datetime-local" name="ends_at" required></div>
          <label class="reg-inline-check" style="margin-bottom:.75rem"><input type="checkbox" name="advance_stage" value="1" checked> Move stage to meeting booked</label>
          <button class="btn btn-secondary" type="submit">Propose meeting</button>
        </form>
      </div>

      <div class="deal-panel">
        <h2>Move stage</h2>
        <form method="post" action="/app/deals/<?= (int)$room['id'] ?>/stage">
          <?= Csrf::field() ?>
          <div class="form-group">
            <select name="stage">
              <?php foreach ($stages as $st): ?><option value="<?= $st ?>" <?= $room['stage']===$st?'selected':'' ?>><?= str_replace('_',' ',$st) ?></option><?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-outline" type="submit">Update stage</button>
        </form>
      </div>

      <div class="deal-panel">
        <h2>Tag outcome</h2>
        <form method="post" action="/app/deals/<?= (int)$room['id'] ?>/outcome">
          <?= Csrf::field() ?>
          <div class="form-group">
            <select name="outcome_type">
              <?php foreach (['mou','pilot','investment','procurement','adoption','partnership'] as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Amount USD</label><input name="amount_usd"></div>
          <div class="form-group"><label>Agreement summary</label><textarea name="agreement_summary" rows="2"><?= View::e($room['agreement_summary'] ?? '') ?></textarea></div>
          <div class="form-group"><label>Notes</label><textarea name="notes" rows="2"></textarea></div>
          <div class="form-group"><label>Public title</label><input name="public_title"></div>
          <div class="form-group"><label>Public summary</label><textarea name="public_summary" rows="2"></textarea></div>
          <label class="reg-inline-check" style="margin-bottom:.75rem"><input type="checkbox" name="announced_publicly" value="1"> Announce on /deals</label>
          <button class="btn btn-primary" type="submit">Save outcome</button>
        </form>
      </div>

      <div class="deal-panel">
        <h2>Participants</h2>
        <?php foreach ($participants as $p): ?>
          <div class="deal-part">
            <strong><?= View::e($p['first_name'].' '.$p['last_name']) ?></strong>
            <div class="muted"><?= View::e($p['email']) ?><?= !empty($p['nda_accepted_at']) ? ' · NDA ✓' : '' ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </aside>
  </div>
</div>
<script>
async function postDealMsg(e) {
  e.preventDefault();
  const form = e.target;
  const body = new URLSearchParams(new FormData(form));
  await fetch(form.action, { method:'POST', body });
  document.getElementById('dealBody').value = '';
  return false;
}
</script>
