<?php
use App\Domain\EventPresentation;
use App\Support\Url;
use App\Support\View;

$event = $event ?? \App\Domain\EventContext::current();
$edition = (string) ($event['edition'] ?? '2026');
$dateLabel = EventPresentation::dateRangeLabel($event);
$venueLine = EventPresentation::venueShort($event);
$untilLine = EventPresentation::untilLine($event);
$starts = EventPresentation::countdownIso($event);
$tab = in_array(($tab ?? ''), ['agenda', 'deal-rooms'], true) ? $tab : 'agenda';
$days = $days ?? [];
$timetable = $timetable ?? [];
$stats = $stats ?? ['forum_days' => 0, 'deal_rooms' => 3, 'sessions_booked' => 0, 'pitches_confirmed' => 0];
$statusLabel = [
  'live' => 'Live now',
  'next' => 'Up next',
  'scheduled' => 'Scheduled',
  'confirmed' => 'Confirmed',
  'available' => 'Available',
];
$typeLabel = [
  'networking' => 'Networking',
  'keynote' => 'Keynote',
  'panel' => 'Panel',
  'pitch' => 'Pitch',
  'workshop' => 'Workshop',
  'demo' => 'Demo',
  'showcase' => 'Showcase',
  'awards' => 'Awards',
  'roundtable' => 'Roundtable',
];
$firstDay = $days[0]['day'] ?? null;
?>
<section class="sched-hero">
  <div class="container sched-hero-inner">
    <a class="sched-back" href="<?= View::e(Url::to('/')) ?>">← Back to home</a>
    <div class="sched-hero-grid">
      <div>
        <p class="eyebrow" style="color:var(--gold-500)">ZBIF <?= View::e($edition) ?></p>
        <h1>Event schedule</h1>
        <p class="lead"><?= (int) $stats['forum_days'] ?> days of keynotes, sector pitches, deal rooms, and networking at Zimbabwe's flagship innovation forum.</p>
        <ul class="sched-meta">
          <li><span class="sched-meta-ico" aria-hidden="true">◷</span><?= View::e($dateLabel) ?></li>
          <li><span class="sched-meta-ico" aria-hidden="true">⌖</span><?= View::e($venueLine) ?></li>
        </ul>
        <div class="sched-stats" aria-label="Forum snapshot">
          <div><strong><?= (int) $stats['forum_days'] ?></strong><span>Forum days</span></div>
          <div><strong><?= (int) $stats['deal_rooms'] ?></strong><span>Deal rooms</span></div>
          <div><strong><?= (int) $stats['sessions_booked'] ?></strong><span>Sessions booked</span></div>
          <div><strong><?= (int) $stats['pitches_confirmed'] ?></strong><span>Pitches confirmed</span></div>
        </div>
      </div>
      <div class="sched-countdown-wrap">
        <p class="sched-count-label">Countdown to forum</p>
        <div class="countdown countdown-plain" id="scheduleCountdown" data-target="<?= View::e($starts) ?>" aria-live="polite"></div>
        <p class="sched-until"><?= View::e($untilLine) ?></p>
      </div>
    </div>
  </div>
</section>

<section class="section container sched-body">
  <div class="sched-tabs" role="tablist" aria-label="Schedule views">
    <a class="sched-tab <?= $tab === 'agenda' ? 'is-active' : '' ?>" role="tab" aria-selected="<?= $tab === 'agenda' ? 'true' : 'false' ?>" href="<?= View::e(Url::to('/schedule?tab=agenda')) ?>">
      <span class="sched-tab-ico" aria-hidden="true">▦</span> Event agenda
    </a>
    <a class="sched-tab <?= $tab === 'deal-rooms' ? 'is-active' : '' ?>" role="tab" aria-selected="<?= $tab === 'deal-rooms' ? 'true' : 'false' ?>" href="<?= View::e(Url::to('/schedule?tab=deal-rooms')) ?>">
      <span class="sched-tab-ico" aria-hidden="true">▣</span> Deal rooms
    </a>
  </div>

  <?php if ($tab === 'agenda'): ?>
    <div class="sched-panel">
      <?php
        $soonDays = $days;
        if (!$soonDays) {
          $soonDays = [
            ['day' => 1, 'label' => 'Day 1', 'weekday' => '', 'theme' => ''],
            ['day' => 2, 'label' => 'Day 2', 'weekday' => '', 'theme' => ''],
            ['day' => 3, 'label' => 'Day 3', 'weekday' => '', 'theme' => ''],
            ['day' => 4, 'label' => 'Day 4', 'weekday' => '', 'theme' => ''],
          ];
          $firstDay = 1;
        }
      ?>
      <div class="agenda-layout">
        <aside class="agenda-days" aria-label="Forum days">
          <?php foreach ($soonDays as $d): ?>
            <button type="button" class="agenda-day <?= (int)$d['day'] === (int)$firstDay ? 'is-active' : '' ?>" data-agenda-day="<?= (int) $d['day'] ?>">
              <small>Day <?= (int) $d['day'] ?></small>
              <strong><?= View::e($d['label']) ?></strong>
            </button>
          <?php endforeach; ?>
        </aside>
        <div class="agenda-main">
          <?php foreach ($soonDays as $d): ?>
            <div data-agenda-panel="<?= (int) $d['day'] ?>" <?= (int)$d['day'] === (int)$firstDay ? '' : 'hidden' ?>>
              <h2><?= View::e(trim(($d['weekday'] ? $d['weekday'] . ' ' : '') . $d['label'])) ?></h2>
              <div class="coming-soon-panel" style="margin-top:1rem">
                <strong>Coming soon</strong>
                <p>The detailed programme for this day will be published closer to the forum.</p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="sched-panel">
      <div class="tt-head">
        <div>
          <h2>Deal room timetable</h2>
          <p>30-minute private sessions between innovators and decision-makers.</p>
        </div>
        <div class="tt-legend" aria-label="Status legend">
          <span><i class="tt-dot live"></i> Live now</span>
          <span><i class="tt-dot next"></i> Up next</span>
          <span><i class="tt-dot sched"></i> Scheduled</span>
          <span><i class="tt-dot conf"></i> Confirmed</span>
          <span><i class="tt-dot open"></i> Available</span>
        </div>
      </div>

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
              <span><?= View::e(strtoupper($room['focus'])) ?></span>
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
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="section container">
  <div class="sched-cta">
    <div>
      <p class="eyebrow">Get involved</p>
      <h2>Join ZBIF <?= View::e($edition) ?> as an innovator or organisation.</h2>
    </div>
    <a class="btn btn-primary" href="<?= View::e(Url::to('/register')) ?>">Register now</a>
  </div>
</section>
