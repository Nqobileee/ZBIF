<?php use App\Support\Url; use App\Support\View; ?>
<div class="grid">
  <?php foreach ($workspaces as $w): ?>
    <a class="card" href="<?= View::e(Url::to('/app/workspaces/' . $w['id'])) ?>">
      <span class="badge"><?= View::e($w['status']) ?></span>
      <h3><?= View::e($w['challenge_title']) ?></h3>
      <p class="muted"><?= View::e($w['solution_name']) ?></p>
    </a>
  <?php endforeach; ?>
</div>
