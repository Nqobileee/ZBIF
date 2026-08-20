<?php use App\Support\View; ?>
<section class="section container" style="max-width:760px">
  <h1><?= View::e($post['title']) ?></h1>
  <p class="muted"><?= View::e($post['published_at']) ?></p>
  <div class="card"><?= nl2br(View::e($post['body'])) ?></div>
</section>
