<?php use App\Support\View; $b = $booth; $media = json_decode($b['media_json'] ?? '{}', true) ?: []; ?>
<section class="page-hero"><div class="container">
  <p class="hero-kicker">Booth <?= View::e($b['booth_code']) ?><?= ($b['floor_x'] !== null) ? ' · Floor ' . View::e($b['floor_x'] . ',' . $b['floor_y']) : '' ?></p>
  <h1><?= View::e($b['name']) ?></h1>
  <p class="muted" style="margin:0"><?= View::e($b['org_name']) ?></p>
</div></section>
<section class="section container" style="max-width:760px">
  <p><?= nl2br(View::e($b['description'] ?? '')) ?></p>
  <?php if (!empty($media['brochure'])): ?><p><a href="<?= View::e($media['brochure']) ?>">Brochure</a></p><?php endif; ?>
  <?php if (!empty($media['video'])): ?><p><a href="<?= View::e($media['video']) ?>">Video</a></p><?php endif; ?>
  <?php if ($b['launching_at_forum']): ?><p class="badge badge-gold">Launching at forum</p><?php endif; ?>
</section>
