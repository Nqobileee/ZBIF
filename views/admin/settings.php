<?php
use App\Support\Csrf;
use App\Support\Url;
use App\Support\View;

$tab = $tab ?? 'email';
$tabs = $tabs ?? [];
$smtp = $smtp ?? [];
$ai = $ai ?? [];
$payments = $payments ?? [];
$flags = $flags ?? [];
$failedJobs = $failedJobs ?? [];
$queue = (int) ($queue ?? 0);
$passSet = !empty($smtp['pass_set']);
?>
<nav class="adm-tabs" aria-label="Settings sections">
  <?php foreach ($tabs as $key => $label): ?>
    <a class="adm-tab <?= $tab === $key ? 'is-active' : '' ?>" href="<?= View::e(Url::to('/admin/settings?tab=' . $key)) ?>"><?= View::e($label) ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($tab === 'email'): ?>
  <?php
    $from = (string) ($smtp['from'] ?? '');
    $user = (string) ($smtp['user'] ?? '');
    $fromMismatch = $from !== '' && $user !== '' && strcasecmp($from, $user) !== 0;
  ?>
  <form method="post" action="/admin/settings" class="adm-card" style="max-width:760px">
    <?= Csrf::field() ?>
    <input type="hidden" name="tab" value="email">
    <h2>SMTP configuration</h2>
    <p class="muted">Used for verification, password reset, and admin test messages. Leave password blank to keep the current secret.</p>
    <div class="flash flash-success" style="margin-bottom:1rem">
      <strong>Inbox tip:</strong> Use a real mailbox on your domain as both SMTP username and From
      (for example <code>noreply@yourdomain.com</code>). Avoid <code>.local</code> addresses.
      On your DNS host, publish SPF, DKIM, and DMARC for that domain. Without those, providers often file mail in spam.
    </div>
    <?php if ($fromMismatch): ?>
      <div class="flash flash-error" style="margin-bottom:1rem">
        From address (<strong><?= View::e($from) ?></strong>) differs from SMTP username (<strong><?= View::e($user) ?></strong>).
        ZBIF will align the From header to the authenticated mailbox when domains differ, so mail is less likely to be rejected.
        Best practice: make them the same.
      </div>
    <?php endif; ?>
    <div class="grid grid-2">
      <div class="form-group"><label>Host</label><input name="smtp_host" value="<?= View::e($smtp['host'] ?? '') ?>" placeholder="smtp.example.com" required></div>
      <div class="form-group"><label>Port</label><input name="smtp_port" value="<?= View::e($smtp['port'] ?? '587') ?>" placeholder="587 or 465"></div>
      <div class="form-group"><label>Username</label><input name="smtp_user" value="<?= View::e($smtp['user'] ?? '') ?>" autocomplete="off"></div>
      <div class="form-group">
        <label>Password <?= $passSet ? '<span class="muted">(saved)</span>' : '' ?></label>
        <input type="password" name="smtp_pass" value="" placeholder="<?= $passSet ? '••••••••' : 'Enter SMTP password' ?>" autocomplete="new-password">
      </div>
      <div class="form-group"><label>Encryption</label>
        <select name="smtp_encryption">
          <?php foreach (['tls' => 'TLS (STARTTLS, usually port 587)', 'ssl' => 'SSL (implicit, usually port 465)', 'none' => 'None'] as $enc => $label): ?>
            <option value="<?= $enc ?>" <?= ($smtp['encryption'] ?? 'tls') === $enc ? 'selected' : '' ?>><?= View::e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>From email</label><input type="email" name="mail_from" value="<?= View::e($smtp['from'] ?? '') ?>" placeholder="noreply@yourdomain.com"></div>
      <div class="form-group"><label>From name</label><input name="mail_from_name" value="<?= View::e($smtp['from_name'] ?? 'ZBIF') ?>"></div>
      <div class="form-group"><label>Reply-To</label><input type="email" name="mail_reply_to" value="<?= View::e($smtp['reply_to'] ?? '') ?>" placeholder="support@yourdomain.com"></div>
    </div>
    <button class="btn btn-primary" type="submit">Save SMTP</button>
  </form>

  <form method="post" action="/admin/settings/test" class="adm-card" style="max-width:480px">
    <?= Csrf::field() ?>
    <input type="hidden" name="tab" value="email">
    <h2>Send test email</h2>
    <p class="muted">Sends through the saved SMTP settings and shows the real server error if it fails.</p>
    <div class="form-group"><label>To</label><input type="email" name="test_to" required placeholder="you@example.com" value="<?= View::e($smtp['user'] ?? '') ?>"></div>
    <button class="btn btn-secondary" type="submit">Send test</button>
  </form>

<?php elseif ($tab === 'ai'): ?>
  <div class="adm-stat-row">
    <?php foreach ($ai as $name => $ok): ?>
      <div class="adm-stat"><strong><?= $ok ? 'Ready' : 'Missing' ?></strong><span><?= View::e((string)$name) ?></span></div>
    <?php endforeach; ?>
    <?php if (!$ai): ?><div class="adm-empty">No AI providers configured.</div><?php endif; ?>
  </div>
  <div class="adm-card">
    <h2>AI providers</h2>
    <p class="muted">Keys are read from environment variables. Configure them on the server, then refresh this page.</p>
    <ul class="adm-feature-list">
      <?php foreach ($ai as $name => $ok): ?>
        <li><strong><?= View::e((string)$name) ?>:</strong> <?= $ok ? 'configured' : 'missing key' ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

<?php elseif ($tab === 'payments'): ?>
  <div class="adm-stat-row">
    <div class="adm-stat"><strong><?= !empty($payments['enabled']) ? 'On' : 'Off' ?></strong><span>PayNow</span></div>
    <div class="adm-stat"><strong><?= (int)($payments['pending'] ?? 0) ?></strong><span>Pending</span></div>
    <div class="adm-stat"><strong><?= (int)($payments['paid'] ?? 0) ?></strong><span>Paid</span></div>
  </div>
  <div class="adm-card">
    <h2>Payments (PayNow)</h2>
    <ul class="adm-feature-list">
      <li>Enabled: <?= !empty($payments['enabled']) ? 'yes' : 'no (dormant / demo waive)' ?></li>
      <li>Integration ID: <?= !empty($payments['integration_id_set']) ? 'set' : 'missing' ?></li>
      <li>Key: <?= !empty($payments['integration_key_set']) ? 'set' : 'missing' ?></li>
    </ul>
  </div>

<?php elseif ($tab === 'flags'): ?>
  <div class="adm-card" style="padding:0;overflow:auto">
    <div class="adm-panel-head" style="padding:1rem 1.25rem 0"><h2 style="margin:0">Feature flags</h2></div>
    <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
      <thead><tr><th>Flag</th><th>Enabled</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($flags as $f): ?>
          <tr>
            <td><?= View::e($f['flag_key']) ?><div class="muted"><?= View::e($f['description'] ?? '') ?></div></td>
            <td><?= (int)$f['is_enabled'] ? 'On' : 'Off' ?></td>
            <td>
              <form method="post" action="/admin/settings/flags">
                <?= Csrf::field() ?>
                <input type="hidden" name="flag_key" value="<?= View::e($f['flag_key']) ?>">
                <button class="btn btn-ghost btn-sm" type="submit">Toggle</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$flags): ?><tr><td colspan="3" class="muted">No feature flags.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

<?php else: ?>
  <div class="adm-stat-row">
    <div class="adm-stat"><strong><?= $queue ?></strong><span>Queue depth</span></div>
    <div class="adm-stat"><strong><?= count($failedJobs) ?></strong><span>Failed (recent)</span></div>
  </div>
  <div class="adm-card" style="padding:0;overflow:auto">
    <div class="adm-panel-head" style="padding:1rem 1.25rem 0"><h2 style="margin:0">Failed jobs</h2></div>
    <table class="table" style="margin:0;border:0;box-shadow:none;border-radius:0">
      <thead><tr><th>ID</th><th>Type</th><th>Error</th><th>When</th></tr></thead>
      <tbody>
        <?php foreach ($failedJobs as $j): ?>
          <tr>
            <td><?= (int)$j['id'] ?></td>
            <td><?= View::e($j['job_type']) ?></td>
            <td><?= View::e(mb_substr((string)$j['last_error'], 0, 140)) ?></td>
            <td><?= View::e($j['failed_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$failedJobs): ?><tr><td colspan="4" class="muted">None</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
