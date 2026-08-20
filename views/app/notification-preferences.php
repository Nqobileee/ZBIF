<?php use App\Support\Csrf; use App\Support\View; $p = $prefs ?: []; ?>
<form method="post" class="card" style="max-width:480px">
  <?= Csrf::field() ?>
  <label><input type="checkbox" name="email_enabled" <?= !isset($p['email_enabled']) || $p['email_enabled'] ? 'checked' : '' ?>> Email</label><br>
  <label><input type="checkbox" name="sms_enabled" <?= !empty($p['sms_enabled']) ? 'checked' : '' ?>> SMS</label><br>
  <label><input type="checkbox" name="whatsapp_enabled" <?= !empty($p['whatsapp_enabled']) ? 'checked' : '' ?>> WhatsApp</label><br>
  <label><input type="checkbox" name="in_app_enabled" <?= !isset($p['in_app_enabled']) || $p['in_app_enabled'] ? 'checked' : '' ?>> In-app</label><br>
  <label><input type="checkbox" name="digest_enabled" <?= !isset($p['digest_enabled']) || $p['digest_enabled'] ? 'checked' : '' ?>> Daily digest email</label><br>
  <button class="btn btn-primary" style="margin-top:1rem" type="submit">Save</button>
</form>
