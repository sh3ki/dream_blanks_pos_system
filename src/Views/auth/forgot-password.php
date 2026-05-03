<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Forgot Password') ?></title>
  <meta name="app-base-path" content="<?= htmlspecialchars(app_base_path()) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/assets/css/style.css')) ?>">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <h1>🔐 Password Recovery</h1>
      <p>Enter your email to receive an OTP</p>
    </div>
    <?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?= implode('<br>', array_merge(...array_values($errors))) ?></div><?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars(app_url('/forgot-password')) ?>">
      <div class="form-group">
        <label class="form-label">Email Address <span class="required">*</span></label>
        <input type="email" name="email" class="form-input" placeholder="Enter your email" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block mt-16">Send OTP</button>
    </form>

    <div class="text-center mt-16"><a href="<?= htmlspecialchars(app_url('/login')) ?>" style="font-size:.875rem">← Back to Login</a></div>
  </div>
</div>
</body>
</html>
