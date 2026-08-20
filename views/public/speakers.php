<?php use App\Support\View; ?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Programme</p>
    <h1>Speakers</h1>
    <p class="lead" style="margin:0;max-width:48ch">Industry and innovation voices across the two-day forum.</p>
  </div>
</section>
<section class="section container">
  <div class="grid grid-3">
    <?php foreach ($speakers as $s): ?>
      <article class="card">
        <h3><?= View::e($s['name']) ?></h3>
        <p class="muted"><?= View::e($s['title']) ?><?= $s['organization'] ? ' · ' . View::e($s['organization']) : '' ?></p>
        <p><?= View::e($s['bio'] ?? '') ?></p>
      </article>
    <?php endforeach; ?>
    <?php if (!$speakers): ?><p class="muted">Speakers will be announced as the programme locks.</p><?php endif; ?>
  </div>
</section>
