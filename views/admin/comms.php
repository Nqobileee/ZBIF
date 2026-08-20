<?php use App\Support\Csrf; use App\Support\View; ?>
<div class="adm-card" style="max-width:720px">
  <h2>Broadcast message</h2>
  <p class="muted">Send an in-app, email, or SMS notice to active users.</p>
  <form method="post">
    <?= Csrf::field() ?>
    <div class="form-group"><label>Audience role (blank = all active)</label>
      <select name="role">
        <option value="">All active users</option>
        <?php foreach ($roles as $r): ?><option value="<?= View::e($r['slug']) ?>"><?= View::e($r['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Channel</label>
      <select name="channel"><option value="in_app">In-app</option><option value="email">Email</option><option value="sms">SMS</option></select>
    </div>
    <div class="form-group"><label>Title</label><input name="title" required></div>
    <div class="form-group"><label>Message</label><textarea name="body" rows="5" required></textarea></div>
    <button class="btn btn-primary" type="submit">Send</button>
  </form>
</div>
