<?php use App\Support\Url; use App\Support\View; ?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Deals</p>
    <h1>Deal Rooms and outcomes</h1>
    <p class="lead" style="margin:0;max-width:52ch">Private rooms move matches to MOUs, pilots, investment, or procurement, with public announcements only by consent.</p>
  </div>
</section>
<section class="section container">
  <div class="deal-band">
    <div class="grid grid-2" style="align-items:center;gap:var(--s-6)">
      <div>
        <p class="eyebrow" style="color:var(--gold-500)">How it pays off</p>
        <h2 style="margin:0 0 .5rem">From chat to signed outcome</h2>
        <p style="margin:0">Files, meetings, checklist, and a pipeline stage that ends when both sides agree.</p>
      </div>
      <div class="deal-mock" aria-hidden="true">
        <div class="deal-mock-pipe">
          <span>Introduced</span><span>In discussion</span><span class="is-live">MOU signed</span><span>Pilot</span>
        </div>
      </div>
    </div>
  </div>
</section>
<section class="section container">
  <div class="section-row">
    <div class="section-head">
      <p class="eyebrow">Live</p>
      <h2>Open challenges</h2>
    </div>
    <a class="btn btn-outline" href="<?= View::e(Url::to('/challenges')) ?>">Browse all</a>
  </div>
  <div class="grid grid-3">
    <?php foreach ($open as $c): ?>
      <article class="card card-static"><h3><a href="<?= View::e(Url::to('/challenges/' . $c['id'])) ?>"><?= View::e($c['title']) ?></a></h3><p class="muted"><?= View::e($c['category']) ?></p></article>
    <?php endforeach; ?>
    <?php if (!$open): ?><p class="muted">Published challenges will appear here.</p><?php endif; ?>
  </div>
</section>
<section class="section section-wash">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow">Success</p>
      <h2>Announced MOUs and pilots</h2>
      <p class="lead">Shared publicly only when both parties consent.</p>
    </div>
    <div class="grid grid-2">
      <?php foreach ($announced as $d): ?>
        <article class="card card-static">
          <span class="badge badge-gold"><?= View::e($d['outcome_type']) ?></span>
          <h3><?= View::e($d['public_title'] ?: 'Deal announced') ?></h3>
          <p><?= View::e($d['public_summary'] ?: $d['notes']) ?></p>
        </article>
      <?php endforeach; ?>
      <?php if (!$announced): ?><p class="muted">Announcements will appear as deals close.</p><?php endif; ?>
    </div>
    <p style="margin-top:1.5rem"><a class="btn btn-primary" href="<?= View::e(Url::to('/register')) ?>">Register to participate</a></p>
  </div>
</section>
