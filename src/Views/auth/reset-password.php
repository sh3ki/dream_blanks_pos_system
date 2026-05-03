<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Reset Password') ?></title>
  <?php $basePath = rtrim($_ENV['APP_BASE_PATH'] ?? '', '/'); ?>
  <meta name="app-base-path" content="<?= htmlspecialchars($basePath) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(($basePath ?: '') . '/assets/css/style.css') ?>">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo"><h1>🔑 Reset Password</h1><p>Enter your new password</p></div>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" action="<?= htmlspecialchars(($basePath ?: '') . '/reset-password') ?>">
      <input type="hidden" name="reset_token" value="<?= htmlspecialchars($_SESSION['reset_token'] ?? $_GET['token'] ?? '') ?>">
      <div class="form-group">
        <label class="form-label">New Password <span class="required">*</span></label>
        <input type="password" name="new_password" class="form-input" placeholder="Min. 8 characters" required minlength="8">
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password <span class="required">*</span></label>
        <input type="password" name="confirm_password" class="form-input" placeholder="Repeat new password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block mt-16">Reset Password</button>
    </form>
    <div class="text-center mt-16"><a href="<?= htmlspecialchars(($basePath ?: '') . '/login') ?>" style="font-size:.875rem">← Back to Login</a></div>
  </div>
</div>
</body>
</html>
