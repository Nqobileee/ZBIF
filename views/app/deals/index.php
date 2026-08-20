<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;

$rooms = $rooms ?? [];
$pipeline = $pipeline ?? [];
$timetable = $timetable ?? [];
$users = $users ?? [];
$statusLabel = [
  'live' => 'Live now',
  'next' => 'Up next',
  'scheduled' => 'Scheduled',
  'confirmed' => 'Confirmed',
  'available' => 'Available',
];
?>
<div class="tt-head">
  <div>
    <h1>Deal room timetable</h1>
    <p>30-minute private sessions between innovators and decision-makers. Book in person at ZBIF or arrange an online call outside the platform.</p>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
    <div class="tt-legend" aria-label="Status legend">
      <span><i class="tt-dot live"></i> Live now</span>
      <span><i class="tt-dot next"></i> Up next</span>
      <span><i class="tt-dot sched"></i> Scheduled</span>
      <span><i class="tt-dot conf"></i> Confirmed</span>
      <span><i class="tt-dot open"></i> Available</span>
    </div>
    <button type="button" class="btn btn-primary btn-sm" id="openBookDealModal">Book a deal meeting</button>
  </div>
</div>

<?php if (!empty($requests)): ?>
  <section class="dash-card" style="margin-bottom:1rem">
    <h2 style="margin:0 0 .35rem;font-size:1.05rem">Pending connection requests</h2>
    <p class="muted" style="margin:0 0 .75rem">Accept to open a confidential Deal Room conversation.</p>
    <?php foreach ($requests as $r): ?>
      <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:.55rem 0;border-top:1px solid var(--line)">
        <div>
          <strong><?= View::e($r['first_name'].' '.$r['last_name']) ?></strong>
          <div class="muted"><?= View::e($r['message'] ?? 'Would like to connect on a challenge.') ?></div>
        </div>
        <form method="post" action="<?= View::e(Url::to('/app/connections/' . $r['id'] . '/accept')) ?>">
          <?= Csrf::field() ?>
          <button class="btn btn-primary btn-sm" type="submit">Accept &amp; open room</button>
        </form>
      </div>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<div class="tt-filters" role="tablist" aria-label="Filter rooms">
  <button type="button" class="is-active" data-tt-filter="all">All rooms</button>
  <?php foreach ($timetable as $room): ?>
    <button type="button" data-tt-filter="<?= View::e($room['key']) ?>"><?= View::e($room['name']) ?></button>
  <?php endforeach; ?>
</div>

<?php foreach ($timetable as $room): ?>
  <section class="tt-room" data-tt-room="<?= View::e($room['key']) ?>">
    <div class="tt-room-head">
      <div class="tt-room-ico" aria-hidden="true"><?= View::e(strtoupper($room['key'])) ?></div>
      <div>
        <strong><?= View::e($room['name']) ?></strong>
        <span><?= View::e($room['focus']) ?></span>
      </div>
    </div>
    <div class="tt-slots">
      <?php foreach ($room['slots'] as $slot): ?>
        <?php $st = $slot['status']; ?>
        <div class="tt-slot">
          <div class="tt-slot-top">
            <span><?= View::e($slot['start'] . ' – ' . $slot['end']) ?></span>
            <span class="badge"><?= View::e($statusLabel[$st] ?? $st) ?></span>
          </div>
          <strong class="<?= $st === 'available' ? 'open' : '' ?>"><?= View::e($slot['label']) ?></strong>
          <?php if ($st === 'available'): ?>
            <div style="margin-top:.45rem">
              <button type="button" class="btn btn-ghost btn-sm" data-open-book
                data-prefill-room="<?= View::e($room['name']) ?>"
                data-prefill-start="<?= View::e(date('Y-m-d') . 'T' . $slot['start']) ?>">Book</button>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>

<section class="dash-card" style="margin-top:1.25rem">
  <h2 style="margin:0 0 .75rem;font-size:1.05rem">Your active Deal Rooms</h2>
  <?php if ($rooms): ?>
    <?php foreach ($rooms as $r): ?>
      <a class="chal-card" style="display:block;margin-bottom:.55rem;text-decoration:none" href="<?= View::e(Url::to('/app/deals/' . $r['id'])) ?>">
        <strong style="color:var(--navy-900)"><?= View::e($r['title']) ?></strong>
        <div class="muted"><?= View::e(str_replace('_', ' ', $r['stage'])) ?> · Updated <?= View::e(substr((string)($r['updated_at'] ?? ''), 0, 16)) ?></div>
      </a>
    <?php endforeach; ?>
  <?php else: ?>
    <p class="muted" style="margin:0">No Deal Rooms yet. Accept a connection or get matched on a challenge to start one.</p>
  <?php endif; ?>
</section>

<div class="zbif-modal" id="bookDealModal" hidden>
  <div class="zbif-modal-backdrop" data-close-book></div>
  <div class="zbif-modal-panel zbif-modal-wide" role="dialog" aria-modal="true" aria-labelledby="bookDealTitle">
    <button type="button" class="zbif-modal-close" data-close-book aria-label="Close">×</button>
    <p class="eyebrow" style="margin:0">Deal rooms</p>
    <h2 id="bookDealTitle">Book a deal meeting</h2>
    <p>In-person at the forum, or online (you arrange the video link separately).</p>
    <form method="post" action="<?= View::e(Url::to('/app/meetings')) ?>" style="margin-top:1rem">
      <?= Csrf::field() ?>
      <input type="hidden" name="redirect" value="/app/deals">
      <div class="form-group"><label>Title</label><input name="title" value="Deal discussion" required></div>
      <div class="form-group"><label>Format</label>
        <select name="meeting_format" id="meetingFormat">
          <option value="in_person">In person at ZBIF</option>
          <option value="online">Online (outside platform)</option>
        </select>
      </div>
      <div class="form-group"><label>Type</label>
        <select name="meeting_type">
          <option value="one_to_one">1:1</option>
          <option value="small_group">Small group</option>
          <option value="b2g">Business to government</option>
          <option value="pitch_investor">Pitch to investor</option>
        </select>
      </div>
      <div class="form-group"><label>With</label>
        <select name="participant_id" required>
          <?php foreach ($users as $u): if ((int)$u['id'] === (int)\App\Auth\Auth::id()) continue; ?>
            <option value="<?= (int)$u['id'] ?>"><?= View::e($u['first_name'].' '.$u['last_name'].' ('.$u['email'].')') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="grid grid-2">
        <div class="form-group"><label>Starts</label><input type="datetime-local" name="starts_at" id="dealStarts" required></div>
        <div class="form-group"><label>Ends</label><input type="datetime-local" name="ends_at" id="dealEnds" required></div>
      </div>
      <div class="form-group"><label>Location / room</label>
        <input name="location" id="dealLocation" value="Room A" placeholder="Room A, Room B, or Online call">
      </div>
      <div class="form-group"><label>Notes</label><textarea name="notes" rows="2" placeholder="Agenda, preferred video link, or booth number"></textarea></div>
      <div class="zbif-modal-actions">
        <button class="btn btn-primary" type="submit">Request meeting</button>
        <button class="btn btn-ghost" type="button" data-close-book>Cancel</button>
      </div>
    </form>
  </div>
</div>
<script>
(function(){
  var modal = document.getElementById('bookDealModal');
  function openBook(){ if (modal) modal.hidden = false; }
  function closeBook(){ if (modal) modal.hidden = true; }
  var openBtn = document.getElementById('openBookDealModal');
  if (openBtn) openBtn.addEventListener('click', openBook);
  document.querySelectorAll('[data-open-book]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var loc = document.getElementById('dealLocation');
      var starts = document.getElementById('dealStarts');
      var ends = document.getElementById('dealEnds');
      if (loc) loc.value = btn.getAttribute('data-prefill-room') || 'Room A';
      var s = btn.getAttribute('data-prefill-start');
      if (starts && s) {
        starts.value = s;
        var d = new Date(s);
        if (!isNaN(d.getTime()) && ends) {
          d.setMinutes(d.getMinutes() + 30);
          var pad = function(n){ return String(n).padStart(2,'0'); };
          ends.value = d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+'T'+pad(d.getHours())+':'+pad(d.getMinutes());
        }
      }
      openBook();
    });
  });
  document.querySelectorAll('[data-close-book]').forEach(function(el){
    el.addEventListener('click', closeBook);
  });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeBook(); });
  var fmt = document.getElementById('meetingFormat');
  var loc = document.getElementById('dealLocation');
  if (fmt && loc) {
    fmt.addEventListener('change', function(){
      if (fmt.value === 'online') loc.value = 'Online call (arrange separately)';
      else if (loc.value.indexOf('Online') === 0) loc.value = 'Room A';
    });
  }
})();
</script>
