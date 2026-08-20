<?php
use App\Support\View;

$personal = $personal ?? [];
$forumDays = $forumDays ?? [
  ['day' => 1, 'label' => 'Day 1', 'weekday' => ''],
  ['day' => 2, 'label' => 'Day 2', 'weekday' => ''],
  ['day' => 3, 'label' => 'Day 3', 'weekday' => ''],
  ['day' => 4, 'label' => 'Day 4', 'weekday' => ''],
];
$firstDay = $forumDays[0]['day'] ?? 1;
?>
<div class="page-toolbar">
  <div>
    <p class="eyebrow">Forum</p>
    <h1 class="page-title">My agenda</h1>
    <p class="page-lead">Your saved events and anything your organisation is doing on the day. The full programme schedule is still being finalised.</p>
  </div>
</div>

<section class="dash-card" style="margin-bottom:1rem">
  <h2 class="section-title">Your events</h2>
  <?php if ($personal): ?>
    <div class="agenda-timeline" style="padding-left:4.5rem;margin-top:.75rem">
      <?php foreach ($personal as $s): ?>
        <?php $start = date('H:i', strtotime((string) $s['starts_at'])); ?>
        <article class="agenda-item">
          <div class="agenda-time"><?= View::e($start) ?></div>
          <div class="agenda-card">
            <div class="agenda-card-top">
              <span class="badge">Saved</span>
              <span class="muted"><?= View::e(substr((string) $s['starts_at'], 0, 16)) ?></span>
            </div>
            <h3><?= View::e($s['title']) ?></h3>
            <?php if (!empty($s['room'])): ?>
              <div class="agenda-meta"><span><?= View::e($s['room']) ?></span></div>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="muted" style="margin:0">No personal events yet. When organisers publish sessions you can pin them here. Company-day activities will also appear in this list.</p>
  <?php endif; ?>
</section>

<section class="dash-card">
  <h2 class="section-title">Programme schedule</h2>
  <p class="muted" style="margin:0 0 1rem">Official day-by-day sessions will appear here once confirmed.</p>
  <div class="agenda-layout">
    <aside class="agenda-days" aria-label="Forum days">
      <?php foreach ($forumDays as $d): ?>
        <button type="button" class="agenda-day <?= (int)$d['day'] === (int)$firstDay ? 'is-active' : '' ?>" data-agenda-day="<?= (int)$d['day'] ?>">
          <small>Day <?= (int)$d['day'] ?></small>
          <strong><?= View::e($d['label']) ?></strong>
        </button>
      <?php endforeach; ?>
    </aside>
    <div class="agenda-main">
      <?php foreach ($forumDays as $d): ?>
        <div data-agenda-panel="<?= (int)$d['day'] ?>" <?= (int)$d['day'] === (int)$firstDay ? '' : 'hidden' ?>>
          <h2><?= View::e(trim(($d['weekday'] ? $d['weekday'] . ' ' : '') . $d['label'])) ?></h2>
          <div class="coming-soon-panel" style="margin-top:1rem">
            <strong>Coming soon</strong>
            <p>The detailed programme for this day is not published yet.</p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
