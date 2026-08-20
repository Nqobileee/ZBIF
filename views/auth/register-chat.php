<?php use App\Support\Url; use App\Support\View; ?>
<section class="section container" style="max-width:720px">
  <h1>Register with Nova</h1>
  <p class="muted">Conversational registration. Prefer the form? <a href="<?= View::e(Url::to('/register')) ?>">Switch anytime</a>.</p>
  <div class="card" style="height:520px;display:flex;flex-direction:column;padding:0;overflow:hidden">
    <div id="novaLog" class="chat-log" style="flex:1"></div>
    <form id="novaForm" class="chat-input">
      <input id="novaInput" type="text" placeholder="Type your reply…" required>
      <button class="btn btn-primary" type="submit">Send</button>
    </form>
  </div>
</section>
<script>
localStorage.removeItem('zbif_nova_token');
</script>
