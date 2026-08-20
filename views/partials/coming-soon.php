<?php
use App\Support\View;
$eyebrow = $eyebrow ?? 'Coming soon';
$titleText = $titleText ?? ($title ?? 'Coming soon');
$lead = $lead ?? 'This area is being prepared for ZBIF. Check back closer to the forum.';
?>
<div class="coming-soon">
  <p class="eyebrow"><?= View::e($eyebrow) ?></p>
  <h1 class="page-title"><?= View::e($titleText) ?></h1>
  <p class="page-lead"><?= View::e($lead) ?></p>
  <div class="coming-soon-panel">
    <strong>Coming soon</strong>
    <p>We will open this feature when the programme and operations are ready.</p>
  </div>
</div>
