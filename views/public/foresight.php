<?php
use App\Support\Csrf;
use App\Support\View;
$sectors = [];
foreach ($insights as $i) {
    $sectors[$i['sector']] = true;
}
$sectors = array_keys($sectors);
?>
<section class="page-hero">
  <div class="container">
    <h1>Foresight</h1>
    <p class="muted" style="margin:0">Emerging-tech pathways across Zimbabwe’s priority sectors. Filter by industry and time horizon.</p>
  </div>
</section>
<section class="section container">
  <form method="post" action="/foresight/download" class="card" style="margin-bottom:1.5rem;max-width:560px">
    <?= Csrf::field() ?>
    <h2 style="margin-top:0">Download foresight pack (PDF)</h2>
    <p class="muted">Leave your email to receive the branded sector foresight PDF, a free lead magnet from ZBIF.</p>
    <div class="form-group"><label>Name</label><input name="name"></div>
    <div class="form-group"><label>Work email</label><input type="email" name="email" required></div>
    <button class="btn btn-primary" type="submit">Email me the PDF</button>
  </form>
  <div class="chip-row" id="foresightFilters" style="margin-bottom:1.25rem">
    <button type="button" class="chip is-active" data-sector="all">All sectors</button>
    <?php foreach ($sectors as $s): ?>
      <button type="button" class="chip" data-sector="<?= View::e($s) ?>"><?= View::e($s) ?></button>
    <?php endforeach; ?>
  </div>
  <div class="chip-row" id="horizonFilters" style="margin-bottom:1.5rem">
    <button type="button" class="chip is-active" data-horizon="all">All horizons</button>
    <button type="button" class="chip" data-horizon="near">Near term</button>
    <button type="button" class="chip" data-horizon="mid">Mid term</button>
    <button type="button" class="chip" data-horizon="long">Long term</button>
  </div>
  <div class="grid grid-2" id="foresightGrid">
    <?php foreach ($insights as $i): ?>
      <article class="card foresight-card" data-sector="<?= View::e($i['sector']) ?>" data-horizon="<?= View::e($i['horizon']) ?>">
        <span class="badge"><?= View::e($i['sector']) ?></span>
        <span class="badge badge-gold"><?= View::e($i['horizon']) ?></span>
        <h3 style="margin-top:0.65rem"><?= View::e($i['title']) ?></h3>
        <p class="muted"><?= View::e($i['summary'] ?: '') ?></p>
        <details>
          <summary>Read insight</summary>
          <p><?= nl2br(View::e($i['body'])) ?></p>
        </details>
      </article>
    <?php endforeach; ?>
    <?php if (!$insights): ?><p class="muted">Foresight content will appear once published in CMS.</p><?php endif; ?>
  </div>
</section>
<script>
(function(){
  let sector = 'all', horizon = 'all';
  function apply() {
    document.querySelectorAll('.foresight-card').forEach(card => {
      const okS = sector === 'all' || card.dataset.sector === sector;
      const okH = horizon === 'all' || card.dataset.horizon === horizon;
      card.style.display = (okS && okH) ? '' : 'none';
    });
  }
  document.getElementById('foresightFilters')?.addEventListener('click', e => {
    const btn = e.target.closest('[data-sector]');
    if (!btn) return;
    sector = btn.dataset.sector;
    document.querySelectorAll('#foresightFilters .chip').forEach(c => c.classList.toggle('is-active', c === btn));
    apply();
  });
  document.getElementById('horizonFilters')?.addEventListener('click', e => {
    const btn = e.target.closest('[data-horizon]');
    if (!btn) return;
    horizon = btn.dataset.horizon;
    document.querySelectorAll('#horizonFilters .chip').forEach(c => c.classList.toggle('is-active', c === btn));
    apply();
  });
})();
</script>
<style>
.chip.is-active { background: var(--color-primary, #0B3D2E); color: #fff; }
</style>
