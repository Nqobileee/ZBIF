<?php use App\Support\View; ?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Support</p>
    <h1>FAQ</h1>
    <p class="lead" style="margin:0;max-width:48ch">Short answers for executives, innovators, and partners.</p>
  </div>
</section>
<section class="section container reading">
  <?php foreach ($faqs as $f): ?>
    <details class="card" style="margin-bottom:0.75rem">
      <summary style="cursor:pointer;min-height:44px;display:flex;align-items:center"><strong><?= View::e($f['question']) ?></strong></summary>
      <p><?= View::e($f['answer']) ?></p>
    </details>
  <?php endforeach; ?>
  <?php if (!$faqs): ?><p class="muted">FAQ entries will appear here.</p><?php endif; ?>
</section>
