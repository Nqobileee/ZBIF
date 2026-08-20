<?php use App\Support\View; use App\Support\Url; ?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Participate</p>
    <h1>Exhibitors</h1>
    <p class="lead" style="margin:0;max-width:48ch">Directory of booths on the InnovaMatch floor.</p>
  </div>
</section>
<section class="section container">
  <p class="muted">Click a booth on the floor map to open its public profile.</p>
  <svg class="floor-map" viewBox="0 0 800 420" role="img" aria-label="Exhibition floor map">
    <rect width="800" height="420" fill="#f7f9f8"/>
    <?php foreach ($booths as $i => $b):
      $x = $b['floor_x'] !== null ? (float)$b['floor_x'] : (40 + ($i % 6) * 120);
      $y = $b['floor_y'] !== null ? (float)$b['floor_y'] : (40 + intdiv($i, 6) * 100);
    ?>
      <a href="<?= View::e(Url::to('/exhibitors/' . $b['id'])) ?>">
        <rect x="<?= $x ?>" y="<?= $y ?>" width="100" height="70" rx="12" fill="#0B3D2E" opacity="0.85"></rect>
        <text x="<?= $x + 50 ?>" y="<?= $y + 40 ?>" text-anchor="middle" fill="#fff" font-size="14" font-family="Poppins"><?= View::e($b['booth_code']) ?></text>
      </a>
    <?php endforeach; ?>
  </svg>
  <div class="grid grid-2" style="margin-top:1.5rem">
    <?php foreach ($booths as $b): ?>
      <article class="card" id="booth-<?= (int) $b['id'] ?>">
        <span class="badge"><?= View::e($b['booth_code']) ?></span>
        <h3><a href="<?= View::e(Url::to('/exhibitors/' . $b['id'])) ?>"><?= View::e($b['name']) ?></a></h3>
        <p><?= View::e($b['description'] ?? '') ?></p>
        <?php if ($b['launching_at_forum']): ?><span class="badge badge-gold">Launching at forum</span><?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</section>
