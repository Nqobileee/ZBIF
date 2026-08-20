<?php
use App\Domain\EventPresentation;
use App\Support\Url;
use App\Support\View;

$event = $event ?? [];
$dateLabel = EventPresentation::dateRangeLabel($event);
$venueLine = EventPresentation::venueShort($event);
?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Attend</p>
    <h1>Venue</h1>
    <p class="lead" style="margin:0;max-width:48ch">Plan your visit to the forum venue in <?= View::e($event['city'] ?? 'Bulawayo') ?>.</p>
  </div>
</section>
<section class="section container reading">
  <h2><?= View::e($event['venue'] ?? 'Zimbabwe International Trade Fair (ZITF)') ?></h2>
  <p class="muted"><?= View::e($venueLine) ?> · <?= View::e($dateLabel) ?></p>
  <p style="margin-top:1.25rem"><a class="btn btn-outline" href="<?= View::e(Url::to('/contact')) ?>">Contact the organisers</a></p>
</section>
