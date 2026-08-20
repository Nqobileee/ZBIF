<?php use App\Support\View; use App\Support\Url; ?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Innovators</p>
    <h1>Solutions and teams</h1>
    <p class="lead" style="margin:0;max-width:48ch">Published innovators building for industry adoption across ZBIF themes.</p>
  </div>
</section>
<section class="section container">
  <div class="grid grid-3">
    <?php foreach ($solutions as $s): ?>
      <article class="card">
        <span class="badge badge-gold"><?= View::e(str_replace('_', ' ', $s['stage'])) ?></span>
        <h3><a href="<?= View::e(Url::to('/innovators/' . $s['id'])) ?>"><?= View::e($s['name']) ?></a></h3>
        <p class="muted"><?= View::e($s['org_name']) ?> · <?= View::e($s['sector']) ?></p>
        <p><?= View::e(mb_substr($s['description'], 0, 140)) ?>…</p>
        <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/innovators/' . $s['id'])) ?>">View</a>
      </article>
    <?php endforeach; ?>
    <?php if (!$solutions): ?><p class="muted">Published solutions will appear here.</p><?php endif; ?>
  </div>
</section>
