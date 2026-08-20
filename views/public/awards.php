<?php use App\Support\View; ?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Recognition</p>
    <h1>Awards</h1>
    <p class="lead" style="margin:0;max-width:48ch">Categories, judging, and winners for commercially ready innovation.</p>
  </div>
</section>
<section class="section container">
  <div class="grid grid-2">
    <?php foreach ($categories as $c): ?>
      <article class="card"><h3><?= View::e($c['name']) ?></h3><p class="muted"><?= View::e($c['description'] ?? '') ?></p></article>
    <?php endforeach; ?>
  </div>
  <?php if ($winners): ?>
    <h2 style="margin-top:2rem">Winners</h2>
    <div class="grid grid-2">
      <?php foreach ($winners as $w): ?>
        <article class="card"><span class="badge badge-gold"><?= View::e($w['category_name']) ?></span><h3><?= View::e($w['nominee_name']) ?></h3></article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
