<?php $message = $message ?? 'You do not have permission to access this page.'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 Forbidden</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/assets/css/style.css')) ?>">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg-secondary)">
  <div class="card" style="padding:32px;max-width:460px;text-align:center">
    <h1 style="margin-bottom:8px">403</h1>
    <p style="font-weight:600;margin-bottom:8px">Access denied</p>
    <p class="text-muted" style="margin-bottom:20px"><?= htmlspecialchars($message) ?></p>
    <a href="/dashboard" class="btn btn-primary">Go to Dashboard</a>
  </div>
</body>
</html>
