<?php
use App\Rbac\Gate;
use App\Support\Url;
use App\Support\View;

$focus = $focus ?? [];
$totalPublished = (int) ($totalPublished ?? count($published));
?>
<div class="home-hero" style="margin-bottom:1rem">
  <div>
    <p class="eyebrow">Marketplace</p>
    <h1>Challenges for your lane</h1>
    <p>
      <?php if ($focus): ?>
        Showing challenges aligned with <?= View::e(implode(', ', array_map('ucwords', array_slice($focus, 0, 4)))) ?>.
      <?php else: ?>
        Complete your organisation focus areas to tighten this list.
      <?php endif; ?>
      <?php if ($totalPublished > count($published)): ?>
        <span class="muted">Filtered from <?= $totalPublished ?> published challenges.</span>
      <?php endif; ?>
    </p>
  </div>
  <?php if (Gate::allows('challenges.submit')): ?>
    <a class="btn btn-primary" href="<?= View::e(Url::to('/app/challenges/create')) ?>">Submit challenge</a>
  <?php endif; ?>
</div>

<?php if ($mine): ?>
  <h2 style="margin:0 0 .75rem;font-size:1.05rem">My submissions</h2>
  <div class="chal-list" style="margin-bottom:1.35rem">
    <?php foreach ($mine as $c): ?>
      <article class="chal-card">
        <div class="chal-card-top">
          <span class="badge"><?= View::e($c['status']) ?></span>
          <span class="muted"><?= View::e($c['sector'] ?? $c['category'] ?? '') ?></span>
        </div>
        <h3><a href="<?= View::e(Url::to('/app/challenges/' . $c['id'])) ?>"><?= View::e($c['title']) ?></a></h3>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="chal-list">
  <?php foreach ($published as $c): ?>
    <?php
      $problem = (string) ($c['problem_statement'] ?? '');
      $excerpt = mb_strlen($problem) > 160 ? mb_substr($problem, 0, 160) . '…' : $problem;
    ?>
    <article class="chal-card">
      <div class="chal-card-top">
        <span class="badge"><?= View::e($c['category'] ?? $c['sector'] ?? 'Challenge') ?></span>
        <span class="muted"><?= View::e($c['org_name'] ?? '') ?></span>
      </div>
      <h3><a href="<?= View::e(Url::to('/app/challenges/' . $c['id'])) ?>"><?= View::e($c['title']) ?></a></h3>
      <?php if ($excerpt !== ''): ?>
        <p class="chal-excerpt"><?= View::e($excerpt) ?></p>
        <div class="chal-full"><?= nl2br(View::e($problem)) ?></div>
      <?php endif; ?>
      <div class="chal-actions">
        <?php if ($problem !== ''): ?>
          <button class="btn btn-ghost btn-sm" type="button" data-chal-toggle>Read full brief</button>
        <?php endif; ?>
        <a class="btn btn-primary btn-sm" href="<?= View::e(Url::to('/app/solutions/create?challenge_id=' . (int) $c['id'])) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Submit solution</a>
        <a class="btn btn-outline btn-sm" href="<?= View::e(Url::to('/app/challenges/' . $c['id'])) ?>">Open</a>
      </div>
    </article>
  <?php endforeach; ?>
  <?php if (!$published): ?>
    <p class="muted">No published challenges match your profile yet.</p>
  <?php endif; ?>
</div>
