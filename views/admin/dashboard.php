<?php use App\Support\Url; use App\Support\View; ?>
<div class="adm-stat-row">
  <div class="adm-stat"><strong><?= (int)$pendingChallenges ?></strong><span>Challenges awaiting screening</span></div>
  <div class="adm-stat"><strong><?= (int)$pendingRegs ?></strong><span>Registrations pending</span></div>
  <div class="adm-stat"><strong><?= (int)$queue ?></strong><span>Background queue depth</span></div>
  <?php
    $metricKeys = array_slice($metrics ?? [], 0, 3, true);
    foreach ($metricKeys as $k => $v):
  ?>
    <div class="adm-stat"><strong><?= View::e(is_float($v) ? number_format($v) : (string)$v) ?></strong><span><?= View::e(str_replace('_', ' ', (string)$k)) ?></span></div>
  <?php endforeach; ?>
</div>

<div class="dash-grid">
  <section class="adm-card">
    <div class="adm-panel-head">
      <h2>Organiser workspace</h2>
      <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/admin/reports')) ?>">Open reports</a>
    </div>
    <p class="muted" style="margin-top:0">Configure the live forum, keep Deal Rooms moving, and publish programme changes that appear on the public site and countdown.</p>
    <div class="dash-quick">
      <a class="btn btn-primary" href="<?= View::e(Url::to('/admin/events')) ?>">Set event dates</a>
      <a class="btn btn-secondary" href="<?= View::e(Url::to('/admin/programme')) ?>">Edit programme</a>
      <a class="btn btn-outline" href="<?= View::e(Url::to('/admin/deals')) ?>">Deal room status</a>
      <a class="btn btn-outline" href="<?= View::e(Url::to('/admin/users')) ?>">Create admin user</a>
    </div>
    <h3 style="margin-top:1.5rem">Impact snapshot</h3>
    <ul class="adm-feature-list">
      <?php foreach (($metrics ?? []) as $k => $v): ?>
        <li><strong><?= View::e(str_replace('_', ' ', (string)$k)) ?>:</strong> <?= View::e(is_scalar($v) ? (string)$v : json_encode($v)) ?></li>
      <?php endforeach; ?>
      <?php if (empty($metrics)): ?><li>No metrics yet for the active event.</li><?php endif; ?>
    </ul>
  </section>
  <div>
    <section class="adm-card">
      <h3>What you can configure</h3>
      <ul class="adm-feature-list">
        <li>Forum dates (updates countdown everywhere)</li>
        <li>Settings (email, AI, payments, flags)</li>
        <li>Day programme and schedule</li>
        <li>Users, companies, registrations</li>
        <li>Deal room stages and reports</li>
      </ul>
    </section>
    <section class="adm-card">
      <h3>Quick links</h3>
      <div class="dash-quick">
        <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/admin/settings')) ?>">Settings</a>
        <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/admin/reports')) ?>">Reports</a>
        <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/admin/organizations')) ?>">Companies</a>
        <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/admin/registrations')) ?>">Registrations</a>
        <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/admin/cms')) ?>">CMS</a>
        <a class="btn btn-ghost btn-sm" href="<?= View::e(Url::to('/admin/settings?tab=jobs')) ?>">Failed jobs</a>
      </div>
    </section>
  </div>
</div>
