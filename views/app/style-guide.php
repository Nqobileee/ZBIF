<section class="card">
  <h2>Design tokens</h2>
  <div class="grid grid-4" style="margin-top:1rem">
    <?php
    $swatches = [
      'Primary' => '#0B3D2E', 'Primary-600' => '#0F4F3B', 'Primary-50' => '#EEF4F1',
      'Accent' => '#C9A227', 'Ink' => '#101915', 'Muted' => '#5B6B63',
      'Success' => '#1E7F51', 'Warning' => '#C9821F', 'Danger' => '#B23A3A', 'Info' => '#2A6F97',
    ];
    foreach ($swatches as $name => $hex):
    ?>
      <div>
        <div style="height:64px;border-radius:0.75rem;background:<?= $hex ?>;border:1px solid rgba(0,0,0,0.05)"></div>
        <strong><?= $name ?></strong><div class="muted"><?= $hex ?></div>
      </div>
    <?php endforeach; ?>
  </div>
  <h3 style="margin-top:1.5rem">Components</h3>
  <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
    <button class="btn btn-primary">Primary</button>
    <button class="btn btn-secondary">Secondary</button>
    <button class="btn btn-ghost">Ghost</button>
    <span class="badge">Badge</span>
    <span class="badge badge-gold">Gold</span>
  </div>
  <p style="margin-top:1rem;font-family:Poppins">Typography: Poppins 400/500/600/700 · base 16px · measure ~72ch</p>
</section>
