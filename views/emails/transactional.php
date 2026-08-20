<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($emailTitle ?? 'ZBIF') ?></title>
</head>
<body style="margin:0;padding:0;background:#f4f7f5;font-family:Poppins,Arial,sans-serif;color:#101915">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f5;padding:24px 0">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden">
        <tr><td style="background:#0B3D2E;color:#fff;padding:18px 24px;font-size:18px;font-weight:600">ZBIF</td></tr>
        <tr><td style="padding:24px">
          <h1 style="margin:0 0 12px;font-size:20px;color:#0B3D2E"><?= htmlspecialchars($emailTitle ?? '') ?></h1>
          <p style="margin:0 0 16px;line-height:1.55;color:#33443c"><?= nl2br(htmlspecialchars($emailBody ?? '')) ?></p>
          <?php if (!empty($emailLink)): ?>
            <p style="margin:0"><a href="<?= htmlspecialchars($emailLink) ?>" style="display:inline-block;background:#C9A227;color:#0B3D2E;text-decoration:none;padding:10px 16px;border-radius:4px;font-weight:600">Open in ZBIF</a></p>
          <?php endif; ?>
        </td></tr>
        <tr><td style="padding:14px 24px;background:#f0f3f1;font-size:12px;color:#5B6B63">ZB Financial Holdings · Zimbabwe Business Innovation Forum</td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
