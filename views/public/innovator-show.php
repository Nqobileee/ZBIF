<?php use App\Support\Url; use App\Support\View; $s = $solution; ?>
<section class="page-hero">
  <div class="container">
    <p class="hero-kicker"><?= View::e($s['org_name']) ?> · <?= View::e($s['sector']) ?></p>
    <h1><?= View::e($s['name']) ?></h1>
    <p class="muted" style="margin:0"><?= View::e(str_replace('_', ' ', $s['stage'])) ?><?= !empty($s['investor_visible']) ? ' · Investor visible' : '' ?></p>
  </div>
</section>
<section class="section container" style="max-width:820px">
  <p><?= nl2br(View::e($s['description'])) ?></p>
  <?php if (!empty($s['problem_solved'])): ?>
    <h2>Problem solved</h2>
    <p><?= nl2br(View::e($s['problem_solved'])) ?></p>
  <?php endif; ?>
  <?php if (!empty($s['traction'])): ?>
    <h2>Traction</h2>
    <p><?= nl2br(View::e($s['traction'])) ?></p>
  <?php endif; ?>
  <p style="margin-top:1.5rem">
    <a class="btn btn-primary" href="<?= View::e(Url::to('/register')) ?>">Register to connect</a>
    <a class="btn btn-ghost" href="<?= View::e(Url::to('/innovators')) ?>">All innovators</a>
  </p>
</section>
<script type="application/ld+json">
<?= json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'Organization',
  'name' => $s['org_name'],
  'description' => mb_substr(strip_tags((string)$s['description']), 0, 300),
  'url' => Url::to('/innovators/' . $s['id']),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>
