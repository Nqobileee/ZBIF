<?php use App\Support\Csrf; use App\Support\View; ?>
<div class="card" style="max-width:560px">
  <h2>Payment <?= View::e($tx['reference']) ?></h2>
  <p>Amount: <strong>USD <?= number_format((float)$tx['amount'], 2) ?></strong></p>
  <p>Status: <span class="badge"><?= View::e($tx['status']) ?></span></p>
  <p class="muted"><?= View::e($tx['gateway']) ?> · <?= View::e($tx['updated_at']) ?></p>
  <?php if ($tx['status'] === 'pending'): ?>
    <form method="post" action="/app/payments/<?= View::e($tx['reference']) ?>/simulate">
      <?= Csrf::field() ?>
      <p class="muted">In local / dormant mode you can mark this paid for demo. Production uses PayNow redirect when credentials are set.</p>
      <button class="btn btn-primary" type="submit">Mark paid (demo)</button>
    </form>
  <?php elseif ($tx['status'] === 'paid'): ?>
    <p class="flash flash-success">Payment complete. Thank you.</p>
    <a class="btn btn-primary" href="/app">Back to dashboard</a>
  <?php endif; ?>
</div>
