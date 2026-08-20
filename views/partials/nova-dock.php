<?php
use App\Support\Url;
use App\Support\View;
$novaMode = $novaMode ?? 'assist'; // assist | registration
$novaOpen = !empty($novaOpen);
$novaTitle = $novaTitle ?? 'Nova · ZBIF assist';
$novaHint = $novaHint ?? 'Ask about registration, the forum, or how matching works.';
?>
<button class="nova-fab" id="novaOpen" type="button" aria-controls="novaDock" aria-expanded="<?= $novaOpen ? 'true' : 'false' ?>">
  <span class="nova-fab-dot" aria-hidden="true"></span>
  Ask Nova
</button>
<aside class="nova-dock<?= $novaOpen ? ' is-open' : '' ?>" id="novaDock" role="complementary" aria-label="Ask Nova" data-mode="<?= View::e($novaMode) ?>">
  <header class="nova-dock-head">
    <div>
      <strong><?= View::e($novaTitle) ?></strong>
      <p><?= View::e($novaHint) ?></p>
    </div>
    <button class="nova-dock-close" type="button" id="novaClose" aria-label="Close assistant">&times;</button>
  </header>
  <div class="nova-dock-log chat-log" id="novaLog"></div>
  <div class="nova-dock-chips" id="novaChips">
    <button type="button" data-prompt="How do I register for ZBIF?">How to register</button>
    <button type="button" data-prompt="How does challenge matching work?">Matching</button>
    <button type="button" data-prompt="When and where is the forum?">Forum dates</button>
  </div>
  <form class="nova-dock-input chat-input" id="novaForm">
    <input type="text" id="novaInput" placeholder="Ask Nova…" autocomplete="off" required maxlength="500">
    <button class="btn btn-primary btn-sm" type="submit">Send</button>
  </form>
</aside>
