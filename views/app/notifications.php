<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;

$notifications = $notifications ?? [];
?>
<div class="page-toolbar">
  <div>
    <p class="eyebrow">Inbox</p>
    <h1 class="page-title">Notifications</h1>
    <p class="page-lead">Deal Room requests, matches, exhibit status, and forum updates.</p>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap">
    <form method="post" action="<?= View::e(Url::to('/app/notifications/read')) ?>">
      <?= Csrf::field() ?>
      <button class="btn btn-ghost btn-sm" type="submit">Mark all read</button>
    </form>
    <a class="btn btn-outline btn-sm" href="<?= View::e(Url::to('/app/notifications/preferences')) ?>">Preferences</a>
  </div>
</div>

<div class="notif-list">
  <?php foreach ($notifications as $n): ?>
    <article class="notif-item <?= empty($n['is_read']) ? 'is-unread' : '' ?>">
      <div class="notif-item-main">
        <strong><?= View::e($n['title']) ?></strong>
        <p><?= View::e($n['body']) ?></p>
        <time class="muted"><?= View::e($n['created_at']) ?></time>
      </div>
      <div class="notif-item-actions">
        <?php if (!empty($n['link'])): ?>
          <a class="btn btn-primary btn-sm" href="<?= View::e(Url::to($n['link'])) ?>">Open</a>
        <?php endif; ?>
        <?php if (empty($n['is_read'])): ?>
          <form method="post" action="<?= View::e(Url::to('/app/notifications/read')) ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
            <button class="btn btn-ghost btn-sm" type="submit">Mark read</button>
          </form>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
  <?php if (!$notifications): ?>
    <div class="dash-card"><p class="muted" style="margin:0">No notifications yet. Match activity, Deal Room requests, and exhibit approvals will show here.</p></div>
  <?php endif; ?>
</div>
