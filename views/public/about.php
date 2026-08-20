<?php use App\Support\Url; use App\Support\View; ?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">About</p>
    <h1>About ZBIF</h1>
    <p class="lead" style="margin:0;max-width:52ch">Zimbabwe Business Innovation Forum. A marketplace where industry challenges meet local innovation.</p>
  </div>
</section>
<section class="section container reading">
  <h2>Not a conference site. A marketplace.</h2>
  <p>ZBIF connects companies that have real operational problems with innovators, universities, and startups that can solve them. Matched teams develop solutions and close deals at the flagship forum.</p>
  <p class="muted">During the forum, solvers present commercially viable solutions to industry leaders and investors. Private Deal Rooms turn conversations into MOUs, pilots, procurement, and investment.</p>
</section>
<section class="section section-wash">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow is-navy">Outcomes</p>
      <h2>How value is measured</h2>
      <p class="lead">Attendance is not the scoreboard.</p>
    </div>
    <div class="grid grid-3">
      <article class="card card-static"><h3>Innovations adopted</h3><p class="muted">Solutions that move into operations.</p></article>
      <article class="card card-static"><h3>Partnerships formed</h3><p class="muted">MOUs and commercial agreements.</p></article>
      <article class="card card-static"><h3>Capital unlocked</h3><p class="muted">Pilots, procurement, and investment.</p></article>
    </div>
    <p style="margin-top:1.5rem"><a class="btn btn-primary" href="<?= View::e(Url::to('/how-it-works')) ?>">See how it works</a></p>
  </div>
</section>
