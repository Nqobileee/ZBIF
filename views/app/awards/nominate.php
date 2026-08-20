<?php use App\Support\Csrf; use App\Support\View; ?>
<form method="post" class="card" style="max-width:640px">
  <?= Csrf::field() ?>
  <div class="form-group"><label>Category</label>
    <select name="category_id"><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= View::e($c['name']) ?></option><?php endforeach; ?></select>
  </div>
  <div class="form-group"><label>Nominee name</label><input name="nominee_name" required></div>
  <div class="form-group"><label>Nominee organization</label><input name="nominee_org"></div>
  <div class="form-group"><label>Rationale</label><textarea name="rationale" rows="4" required></textarea></div>
  <button class="btn btn-primary" type="submit">Submit nomination</button>
</form>
