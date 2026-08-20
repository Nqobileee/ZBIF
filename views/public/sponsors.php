<?php
use App\Support\Url;
use App\Support\View;
?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Support ZBIF</p>
    <h1>Sponsorship</h1>
    <p class="lead" style="margin:0;max-width:52ch">Partner packages that power Deal Rooms, exhibition presence, and the innovation marketplace. Public partner logos appear without tier badges.</p>
  </div>
</section>
<section class="section container">
  <div class="chal-list" style="margin-bottom:2rem;max-width:820px">
    <?php foreach ($sponsors as $s): ?>
      <?php if (stripos((string)$s['name'], 'rilpix') !== false) continue; ?>
      <article class="card">
        <h3 style="margin:0 0 .35rem"><?= View::e($s['name']) ?></h3>
        <?php if (!empty($s['logo_path'])): ?>
          <p><img src="<?= View::e(Url::asset(ltrim($s['logo_path'], '/'))) ?>" alt="" style="max-height:48px" onerror="this.style.display='none'"></p>
        <?php endif; ?>
        <?php if (!empty($s['contribution_usd'])): ?>
          <p class="muted">Indicative contribution: USD <?= number_format((float) $s['contribution_usd']) ?></p>
        <?php endif; ?>
        <?php $benefits = json_decode($s['benefits_json'] ?? '[]', true) ?: []; ?>
        <?php if ($benefits): ?>
          <ul><?php foreach ($benefits as $b): ?><li><?= View::e(is_string($b) ? $b : json_encode($b)) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
    <?php if (!$sponsors): ?>
      <p class="muted">Sponsorship packages will be listed here. In the meantime, use the partner inquiry form.</p>
    <?php endif; ?>
  </div>
  <div class="awards-band">
    <div>
      <h2 style="color:#fff;margin-bottom:0.4rem">Become a sponsor</h2>
      <p>Packages with Deal Room branding, exhibition presence, and impact reporting.</p>
    </div>
    <a class="btn btn-primary" href="<?= View::e(Url::to('/app/sponsorship')) ?>">Apply now</a>
  </div>
</section>
