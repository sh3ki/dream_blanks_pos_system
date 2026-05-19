<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Reset Password | Dream Blanks POS') ?></title>
  <?php $basePath = rtrim($_ENV['APP_BASE_PATH'] ?? '', '/'); ?>
  <meta name="app-base-path" content="<?= htmlspecialchars($basePath) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/assets/css/style.css')) ?>">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    html, body { height: 100%; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, Helvetica, sans-serif; }
    .login-wrap { min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr; }
    .login-brand {
      background: linear-gradient(155deg, #0056B3 0%, #003d7a 100%);
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      padding: 72px 60px; color: #fff; position: relative; overflow: hidden;
    }
    .login-brand::before {
      content: ''; position: absolute; inset: 0;
      background: radial-gradient(ellipse at 20% 30%, rgba(255,255,255,.10) 0%, transparent 55%),
                  radial-gradient(ellipse at 85% 80%, rgba(0,0,0,.10) 0%, transparent 50%);
      pointer-events: none;
    }
    .brand-icon-box {
      width: 88px; height: 88px; background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.20); border-radius: 20px;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 28px; position: relative; z-index: 1;
    }
    .brand-icon-box svg { width: 44px; height: 44px; stroke: #fff; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }
    .brand-name { font-size: 1.9rem; font-weight: 700; letter-spacing: -.02em; text-align: center; position: relative; z-index: 1; margin-bottom: 8px; }
    .brand-sub { font-size: .78rem; color: rgba(255,255,255,.60); letter-spacing: .1em; text-transform: uppercase; text-align: center; position: relative; z-index: 1; }
    .brand-rule { width: 36px; height: 2px; background: rgba(255,255,255,.25); border-radius: 2px; margin: 28px 0; position: relative; z-index: 1; }
    .brand-features { display: flex; flex-direction: column; gap: 11px; position: relative; z-index: 1; }
    .brand-feature { display: flex; align-items: center; gap: 10px; font-size: .82rem; color: rgba(255,255,255,.60); }
    .brand-dot { width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,.50); flex-shrink: 0; }
    .login-form-side { background: #F5F5F5; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 72px 60px; }
    .login-inner { width: 100%; max-width: 380px; }
    .login-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,.10); padding: 40px 36px; }
    .login-heading { margin-bottom: 28px; }
    .login-heading h2 { font-size: 1.45rem; font-weight: 700; color: #2D2D2D; margin: 0 0 5px; }
    .login-heading p { font-size: .875rem; color: #808080; margin: 0; }
    .login-card .form-group { margin-bottom: 16px; }
    .login-card .form-label { font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #505050; display: block; margin-bottom: 6px; }
    .login-card .form-input { height: 44px; font-size: .9rem; border: 1.5px solid #E8E8E8; border-radius: 6px; background: #F5F5F5; width: 100%; padding: 0 12px; transition: border-color .15s, box-shadow .15s, background .15s; }
    .login-card .form-input:focus { border-color: #0056B3; box-shadow: 0 0 0 3px rgba(0,86,179,.12); background: #fff; outline: none; }
    .login-card .alert { border-radius: 6px; font-size: .86rem; margin-bottom: 18px; }
    .login-btn { width: 100%; height: 44px; margin-top: 8px; background: #0056B3; color: #fff; border: none; border-radius: 6px; font-size: .92rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: background .15s, box-shadow .15s; box-shadow: 0 2px 6px rgba(0,86,179,.25); }
    .login-btn:hover { background: #003d7a; box-shadow: 0 4px 12px rgba(0,86,179,.30); }
    .login-btn:active { filter: brightness(.96); }
    .login-btn:disabled { opacity: .6; cursor: not-allowed; }
    .login-back { text-align: center; margin-top: 18px; }
    .login-back a { font-size: .82rem; color: #808080; text-decoration: none; transition: color .15s; }
    .login-back a:hover { color: #0056B3; }
    @media (max-width: 820px) { .login-wrap { grid-template-columns: 1fr; } .login-brand { display: none; } .login-form-side { padding: 40px 20px; min-height: 100vh; } }
  </style>
</head>
<body>

<div class="login-wrap">

  <!-- Brand panel -->
  <div class="login-brand">
    <div class="brand-icon-box">
      <svg viewBox="0 0 24 24">
        <rect x="4" y="3" width="16" height="12" rx="2" ry="2"/>
        <line x1="7" y1="7" x2="13" y2="7"/>
        <line x1="7" y1="11" x2="10" y2="11"/>
        <line x1="9" y1="15" x2="15" y2="15"/>
        <path d="M10 15v4"/>
        <path d="M7 21h10"/>
        <rect x="3" y="17" width="18" height="4" rx="1"/>
      </svg>
    </div>
    <div class="brand-name">Dream Blanks</div>
    <div class="brand-sub">Point of Sale System</div>
    <div class="brand-rule"></div>
    <div class="brand-features">
      <div class="brand-feature"><span class="brand-dot"></span>Sales &amp; invoice management</div>
      <div class="brand-feature"><span class="brand-dot"></span>Real-time inventory tracking</div>
      <div class="brand-feature"><span class="brand-dot"></span>Multi-role access control</div>
      <div class="brand-feature"><span class="brand-dot"></span>Comprehensive reporting</div>
    </div>
  </div>

  <!-- Form panel -->
  <div class="login-form-side">
    <div class="login-inner">
      <div class="login-card">

        <div class="login-heading">
          <h2>Set new password</h2>
          <p>Choose a strong password for your account.</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars(app_url('/reset-password')) ?>">
          <input type="hidden" name="reset_token" value="<?= htmlspecialchars($_SESSION['reset_token'] ?? $_GET['token'] ?? '') ?>">

          <div class="form-group">
            <label class="form-label" for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password"
                   class="form-input" placeholder="Min. 8 characters" required minlength="8" autofocus>
          </div>
          <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password"
                   class="form-input" placeholder="Repeat new password" required>
          </div>

          <button type="submit" class="login-btn">Reset Password</button>
        </form>

        <div class="login-back">
          <a href="<?= htmlspecialchars(app_url('/login')) ?>">← Back to Login</a>
        </div>

      </div>
    </div>
  </div>

</div>
</body>
</html>
