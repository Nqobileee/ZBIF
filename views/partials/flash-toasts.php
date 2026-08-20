<?php
use App\Support\View;
/** @var list<array{type:string,message:string}> $flash */
$flash = $flash ?? [];
if (!$flash) {
    return;
}
?>
<div class="toast-stack" id="toastStack" aria-live="polite" aria-relevant="additions">
  <?php foreach ($flash as $f): ?>
    <?php
      $type = preg_replace('/[^a-z]/', '', strtolower((string) ($f['type'] ?? 'info'))) ?: 'info';
      if (!in_array($type, ['success', 'error', 'info', 'warning'], true)) {
          $type = 'info';
      }
    ?>
    <div class="flash flash-<?= View::e($type) ?> toast" role="status" data-toast data-toast-type="<?= View::e($type) ?>">
      <p class="toast-msg"><?= View::e((string) ($f['message'] ?? '')) ?></p>
      <button type="button" class="toast-close" aria-label="Dismiss notification">&times;</button>
    </div>
  <?php endforeach; ?>
</div>
