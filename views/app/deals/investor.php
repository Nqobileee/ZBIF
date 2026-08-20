<?php use App\Support\Csrf; use App\Support\View; $pipeline = $pipeline ?? []; $stages = $stages ?? []; ?>
<div class="home-hero" style="margin-bottom:1rem">
  <div>
    <p class="eyebrow">Capital</p>
    <h1>Investor flow</h1>
    <p>Watch investor-visible solutions, request intros, and move opportunities through diligence.</p>
  </div>
</div>
<div class="meet-layout">
  <section class="dash-card">
    <h2>Investor-visible opportunities</h2>
    <?php foreach ($solutions as $s): ?>
      <div style="padding:.75rem 0;border-bottom:1px solid var(--line)">
        <strong><?= View::e($s['name']) ?></strong>
        <div class="muted"><?= View::e($s['org_name']) ?> · <?= View::e($s['sector']) ?></div>
        <div style="margin-top:.4rem;display:flex;gap:.35rem;flex-wrap:wrap">
          <form method="post" action="/app/investor/watch" style="display:inline">
            <?= Csrf::field() ?>
            <input type="hidden" name="solution_id" value="<?= (int)$s['id'] ?>">
            <button class="btn btn-ghost btn-sm" type="submit"><?= in_array((int)$s['id'], array_map('intval', $watch), true) ? 'Unwatch' : 'Watch' ?></button>
          </form>
          <?php if (in_array((int)$s['id'], array_map('intval', $watch), true)): ?>
            <form method="post" action="/app/investor/intro" style="display:inline">
              <?= Csrf::field() ?>
              <input type="hidden" name="solution_id" value="<?= (int)$s['id'] ?>">
              <button class="btn btn-secondary btn-sm" type="submit">Request intro</button>
            </form>
            <a class="btn btn-ghost btn-sm" href="/app/investor/<?= (int)$s['id'] ?>/onepager.pdf">PDF</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$solutions): ?><p class="muted">No investor-visible solutions yet.</p><?php endif; ?>
  </section>
  <section class="dash-card">
    <h2>Pipeline</h2>
    <?php foreach ($stages as $st): ?>
      <h3 style="margin-top:.85rem;font-size:.95rem"><?= View::e(str_replace('_',' ',$st)) ?></h3>
      <?php foreach ($pipeline[$st] ?? [] as $w): ?>
        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-bottom:.45rem">
          <strong><?= View::e($w['solution_name']) ?></strong>
          <span class="muted"><?= View::e($w['org_name']) ?></span>
          <form method="post" action="/app/investor/stage" style="display:inline">
            <?= Csrf::field() ?>
            <input type="hidden" name="solution_id" value="<?= (int)$w['solution_id'] ?>">
            <select name="pipeline_stage" onchange="this.form.submit()">
              <?php foreach ($stages as $opt): ?>
                <option value="<?= $opt ?>" <?= ($w['pipeline_stage'] ?? '')===$opt?'selected':'' ?>><?= str_replace('_',' ',$opt) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>
      <?php endforeach; ?>
      <?php if (empty($pipeline[$st])): ?><p class="muted" style="font-size:0.85rem">Empty</p><?php endif; ?>
    <?php endforeach; ?>
  </section>
</div>
<section class="dash-card" style="margin-top:1rem">
  <h2>Diligence notes</h2>
  <?php foreach ($notes as $n): ?>
    <div style="margin-bottom:.65rem;padding-bottom:.65rem;border-bottom:1px solid var(--line)">
      <strong><?= View::e($n['solution_name'] ?: 'General') ?></strong>
      <div><?= View::e($n['note']) ?></div>
      <div class="muted" style="font-size:0.8rem"><?= View::e($n['updated_at']) ?></div>
    </div>
  <?php endforeach; ?>
  <form method="post" action="/app/investor/notes">
    <?= Csrf::field() ?>
    <div class="form-group"><label>Solution</label>
      <select name="solution_id" required>
        <?php foreach ($solutions as $s): ?>
          <option value="<?= (int)$s['id'] ?>"><?= View::e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><textarea name="note" rows="3" required></textarea></div>
    <button class="btn btn-primary" type="submit">Save note</button>
  </form>
</section>
