<?php use App\Support\Csrf; use App\Support\Url; use App\Support\View; ?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">About</p>
    <h1>Contact</h1>
    <p class="lead" style="margin:0;max-width:48ch">Talk to the ZBIF team about challenges, sponsorship, or participation.</p>
  </div>
</section>
<section class="section container" style="max-width:640px">
  <form method="post" action="<?= View::e(Url::to('/contact')) ?>" class="card">
    <?= Csrf::field() ?>
    <div class="form-group"><label for="c-name">Name</label><input id="c-name" name="name" required></div>
    <div class="form-group"><label for="c-email">Email</label><input id="c-email" type="email" name="email" required></div>
    <div class="form-group"><label for="c-org">Organization</label><input id="c-org" name="organization"></div>
    <div class="form-group"><label for="c-msg">Message</label><textarea id="c-msg" name="message" rows="5" required></textarea></div>
    <button class="btn btn-primary" type="submit">Send</button>
  </form>
</section>
