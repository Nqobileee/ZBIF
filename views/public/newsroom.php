<?php use App\Support\Url; use App\Support\View; ?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">About</p>
    <h1>Newsroom</h1>
    <p class="lead" style="margin:0;max-width:48ch">Announcements, MOUs, and programme updates.</p>
  </div>
</section>
<section class="section container">
  <div class="grid grid-3">
    <?php foreach ($posts as $p): ?>
      <article class="card">
        <h3><a href="<?= View::e(Url::to('/newsroom/' . $p['slug'])) ?>"><?= View::e($p['title']) ?></a></h3>
        <p class="muted"><?= View::e($p['published_at']) ?></p>
        <p><?= View::e($p['excerpt'] ?? '') ?></p>
      </article>
    <?php endforeach; ?>
    <?php if (!$posts): ?><p class="muted">Posts will appear when published.</p><?php endif; ?>
  </div>
</section>
