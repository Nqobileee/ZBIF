<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;
?>
<section class="section container">
  <span class="badge"><?= View::e($challenge['category']) ?></span>
  <h1><?= View::e($challenge['title']) ?></h1>
  <p class="muted"><?= View::e($challenge['org_name']) ?> · <?= View::e($challenge['sector']) ?> · <?= View::e($challenge['status']) ?></p>
  <article class="card" style="margin-top:1rem">
    <h3>Problem statement</h3>
    <p><?= nl2br(View::e($challenge['problem_statement'])) ?></p>
    <?php if ($challenge['desired_outcome']): ?><h3>Desired outcome</h3><p><?= View::e($challenge['desired_outcome']) ?></p><?php endif; ?>
  </article>
  <p style="margin-top:1rem"><a class="btn btn-primary" href="<?= View::e(Url::to('/register')) ?>">Register to respond</a></p>
</section>
