<?php use App\Support\Csrf; use App\Support\Url; use App\Support\View; ?>
<?php if (!$sponsor): ?>
  <div class="card">
    <h2>Sponsor portal</h2>
    <p class="muted">No active sponsorship is linked to your account yet. Apply or wait for organizer approval.</p>
    <a class="btn btn-primary" href="/app/sponsorship">Apply for sponsorship</a>
  </div>
<?php else: ?>
  <div class="deal-sponsor-banner" style="margin-bottom:1rem">Portal for <strong><?= View::e($sponsor['name']) ?></strong> · <?= View::e(str_replace('_',' ',$sponsor['tier'])) ?></div>
  <div class="grid grid-2">
    <section class="card">
      <h2>Brand assets</h2>
      <?php if (!empty($sponsor['logo_path'])): ?>
        <p><img src="<?= View::e(Url::asset(ltrim($sponsor['logo_path'], '/'))) ?>" alt="Logo" style="max-height:64px" onerror="this.style.display='none'"></p>
        <p class="muted"><?= View::e($sponsor['logo_path']) ?></p>
      <?php endif; ?>
      <form method="post" action="/app/sponsor-portal/assets" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <div class="form-group"><label>Website</label><input name="website" value="<?= View::e($sponsor['website'] ?? '') ?>"></div>
        <div class="form-group"><label>Replace logo</label><input type="file" name="logo" accept="image/*"></div>
        <button class="btn btn-primary" type="submit">Save assets</button>
      </form>
    </section>
    <section class="card">
      <h2>Entitlements & ROI</h2>
      <ul>
        <?php foreach ($entitlements as $e): ?>
          <li><strong><?= View::e(str_replace('_',' ',$e['entitlement_key'])) ?></strong>: <?= View::e($e['entitlement_value'] ?? '') ?></li>
        <?php endforeach; ?>
      </ul>
      <div class="stat-grid" style="margin-top:1rem">
        <div class="stat"><strong><?= (int)$dealRooms ?></strong>Branded deal rooms</div>
        <div class="stat"><strong><?= View::e(number_format((float)$sponsor['contribution_usd'])) ?></strong>Contribution USD</div>
      </div>
      <p style="margin-top:1rem"><a class="btn btn-ghost" href="/admin/impact">Impact report</a></p>
    </section>
  </div>
<?php endif; ?>
