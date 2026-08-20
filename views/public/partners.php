<?php
use App\Domain\EventPresentation;
use App\Support\Url;
use App\Support\View;

$partners = EventPresentation::publicPartners($partners ?? []);
?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Our partners</p>
    <h1>Powered by industry leaders</h1>
    <p class="lead" style="margin:0;max-width:52ch">ZBIF is made possible through the support of leading organisations across banking, government, academia, and innovation.</p>
  </div>
</section>
<section class="section container">
  <div class="partners-grid" style="margin-bottom:1.5rem">
    <?php foreach ($partners as $p): ?>
      <?php if (empty($p['logo_path'])) continue; ?>
      <div class="partner-cell">
        <img src="<?= View::e(Url::asset(ltrim((string)$p['logo_path'], '/'))) ?>" alt="<?= View::e($p['name']) ?>" class="partner-logo" loading="lazy">
        <strong><?= View::e($p['name']) ?></strong>
      </div>
    <?php endforeach; ?>
    <?php if (!$partners): ?>
      <p class="muted" style="grid-column:1/-1;text-align:center">Partner confirmations will appear here.</p>
    <?php endif; ?>
  </div>
  <p class="partners-cta">
    <button class="btn btn-outline" type="button" data-partner-soon>Become a partner</button>
  </p>
</section>
