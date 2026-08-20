<?php use App\Support\Csrf; use App\Support\View; ?>
<div class="adm-grid adm-grid-2">
  <div class="adm-card">
    <h2>Pages</h2>
    <?php if ($pages): ?>
      <ul class="adm-list">
        <?php foreach ($pages as $p): ?>
          <li><strong><?= View::e($p['title']) ?></strong> · <?= View::e($p['slug']) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted">No CMS pages yet.</p>
    <?php endif; ?>
  </div>
  <div class="adm-card">
    <h2>News</h2>
    <?php if ($news): ?>
      <ul class="adm-list">
        <?php foreach ($news as $n): ?><li><?= View::e($n['title']) ?></li><?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted">No news posts yet.</p>
    <?php endif; ?>
  </div>
</div>

<form method="post" action="/admin/cms/page" class="adm-card" style="max-width:720px">
  <?= Csrf::field() ?>
  <h2>Create / update page</h2>
  <div class="form-group"><label>Slug</label><input name="slug" required></div>
  <div class="form-group"><label>Title</label><input name="title" required></div>
  <div class="form-group"><label>Meta description</label><input name="meta_description"></div>
  <div class="form-group"><label>Body</label><textarea name="body" rows="5"></textarea></div>
  <button class="btn btn-primary" type="submit">Save page</button>
</form>

<form method="post" action="/admin/cms/news" class="adm-card" style="max-width:720px">
  <?= Csrf::field() ?>
  <h2>Add news post</h2>
  <div class="form-group"><label>Title</label><input name="title" required></div>
  <div class="form-group"><label>Excerpt</label><input name="excerpt"></div>
  <div class="form-group"><label>Body</label><textarea name="body" rows="5" required></textarea></div>
  <button class="btn btn-secondary" type="submit">Publish news</button>
</form>

<form method="post" action="/admin/cms/testimonial" class="adm-card" style="max-width:720px">
  <?= Csrf::field() ?>
  <h2>Social proof testimonial</h2>
  <div class="form-group"><label>Quote</label><textarea name="quote" rows="3" required></textarea></div>
  <div class="grid grid-2">
    <div class="form-group"><label>Author</label><input name="author_name" required></div>
    <div class="form-group"><label>Role</label><input name="author_role"></div>
  </div>
  <div class="form-group"><label>Organisation</label><input name="org_name"></div>
  <button class="btn btn-primary" type="submit">Publish testimonial</button>
</form>
<?php if (!empty($testimonials)): ?>
  <div class="adm-card">
    <h2>Testimonials</h2>
    <ul class="adm-list">
      <?php foreach ($testimonials as $t): ?>
        <li>“<?= View::e(mb_substr($t['quote'], 0, 80)) ?>…” · <?= View::e($t['author_name']) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" action="/admin/cms/foresight" class="adm-card" style="max-width:720px">
  <?= Csrf::field() ?>
  <h2>Foresight insight</h2>
  <div class="grid grid-2">
    <div class="form-group"><label>Sector</label><input name="sector" required placeholder="Manufacturing"></div>
    <div class="form-group"><label>Horizon</label>
      <select name="horizon"><option value="near">Near</option><option value="mid">Mid</option><option value="long">Long</option></select>
    </div>
  </div>
  <div class="form-group"><label>Title</label><input name="title" required></div>
  <div class="form-group"><label>Summary</label><input name="summary"></div>
  <div class="form-group"><label>Body</label><textarea name="body" rows="4" required></textarea></div>
  <button class="btn btn-secondary" type="submit">Add foresight item</button>
</form>
<?php if (!empty($foresight)): ?>
  <div class="adm-card">
    <h2>Foresight items</h2>
    <ul class="adm-list">
      <?php foreach ($foresight as $f): ?><li><?= View::e($f['sector']) ?> · <?= View::e($f['title']) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" action="/admin/cms/faq" class="adm-card" style="max-width:640px">
  <?= Csrf::field() ?>
  <h2>Add FAQ</h2>
  <div class="form-group"><label>Question</label><input name="question" required></div>
  <div class="form-group"><label>Answer</label><textarea name="answer" rows="3" required></textarea></div>
  <button class="btn btn-primary" type="submit">Add FAQ</button>
</form>
