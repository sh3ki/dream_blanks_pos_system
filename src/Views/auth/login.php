<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Login | Dream Blanks POS') ?></title>
  <meta name="app-base-path" content="<?= htmlspecialchars(app_base_path()) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/assets/css/style.css')) ?>">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <h1 style="display:flex;align-items:center;justify-content:center;gap:10px"><?= icon('store', 28) ?> Dream Blanks</h1>
      <p>Point of Sale System</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars(app_url('/login')) ?>" id="loginForm">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? bin2hex(random_bytes(16))) ?>">

      <div class="form-group">
        <label class="form-label" for="username_or_email">Email or Username <span class="required">*</span></label>
        <input type="text" id="username_or_email" name="username_or_email"
               class="form-input" placeholder="Enter email or username"
               value="<?= htmlspecialchars($_POST['username_or_email'] ?? '') ?>"
               required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password <span class="required">*</span></label>
        <input type="password" id="password" name="password"
               class="form-input" placeholder="Enter password" required>
      </div>

      <div class="form-group" style="margin-top:24px">
        <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
          Sign In
        </button>
      </div>
    </form>

    <div class="text-center mt-16">
      <a href="<?= htmlspecialchars(app_url('/forgot-password')) ?>" style="font-size:.875rem;color:var(--color-gray-500)">Forgot your password?</a>
    </div>
  </div>
</div>
<script>
document.getElementById('loginForm').addEventListener('submit', function() {
  document.getElementById('loginBtn').innerHTML = '<span class="spinner"></span> Signing in...';
  document.getElementById('loginBtn').disabled = true;
});
</script>
</body>
</html>
