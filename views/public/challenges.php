<?php
use App\Support\Url;
use App\Support\View;
?>
<section class="page-hero">
  <div class="container section-row" style="align-items:end">
    <div>
      <p class="eyebrow">Marketplace</p>
      <h1>Open challenges</h1>
      <p class="lead" style="margin:0;max-width:48ch">Screened industry problems waiting for solvers. Submit your own when you register.</p>
    </div>
    <a class="btn btn-primary" href="<?= View::e(Url::to('/register')) ?>">Submit a Challenge</a>
  </div>
</section>
<section class="section container">
  <form class="card filter-bar" method="get" style="margin:0 0 1.5rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:0.75rem;align-items:end">
    <div class="form-group" style="margin:0"><label for="f-sector">Sector</label><input id="f-sector" name="sector" value="<?= View::e($filters['sector'] ?? '') ?>"></div>
    <div class="form-group" style="margin:0"><label for="f-cat">Category</label><input id="f-cat" name="category" value="<?= View::e($filters['category'] ?? '') ?>"></div>
    <div class="form-group" style="margin:0"><label for="f-status">Status</label><input id="f-status" name="status" value="<?= View::e($filters['status'] ?? 'published') ?>"></div>
    <button class="btn btn-secondary" type="submit">Filter</button>
  </form>
  <?php if (!$challenges): ?>
    <div class="card card-static empty-state" style="text-align:center;padding:3rem 1.5rem">
      <p class="eyebrow">Empty</p>
      <h2>No challenges match yet</h2>
      <p class="muted">Adjust filters, or submit a challenge once your organisation is registered.</p>
      <a class="btn btn-primary" href="<?= View::e(Url::to('/register')) ?>">Register to submit</a>
    </div>
  <?php else: ?>
  <div class="grid grid-3">
    <?php foreach ($challenges as $c): ?>
      <article class="card">
        <div class="chip-row"><span class="badge"><?= View::e($c['category']) ?></span><span class="badge"><?= View::e($c['status']) ?></span></div>
        <h3><a href="<?= View::e(Url::to('/challenges/' . $c['id'])) ?>"><?= View::e($c['title']) ?></a></h3>
        <p class="muted"><?= View::e($c['org_name']) ?> · <?= View::e($c['engagement_type']) ?></p>
        <p><?= View::e(mb_substr($c['problem_statement'], 0, 160)) ?>…</p>
        <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/challenges/' . $c['id'])) ?>">View</a>
      </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
