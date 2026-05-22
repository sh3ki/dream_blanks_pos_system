<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Forgot Password | Dream Blanks POS') ?></title>
  <meta name="app-base-path" content="<?= htmlspecialchars(app_base_path()) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/assets/css/style.css')) ?>">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    html, body { height: 100%; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, Helvetica, sans-serif; }
    .login-wrap { min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr; }
    .login-brand {
      background: linear-gradient(155deg, #2C2C2C 0%, #1A1A1A 100%);
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      padding: 72px 60px; color: #fff; position: relative; overflow: hidden;
    }
    .login-brand::before {
      content: ''; position: absolute; inset: 0;
      background: radial-gradient(ellipse at 20% 30%, rgba(255,255,255,.06) 0%, transparent 55%),
                  radial-gradient(ellipse at 85% 80%, rgba(255,255,255,.03) 0%, transparent 50%);
      pointer-events: none;
    }
    .brand-icon-box {
      width: 88px; height: 88px; background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.14); border-radius: 20px;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 28px; position: relative; z-index: 1;
    }
    .brand-icon-box svg { width: 44px; height: 44px; stroke: rgba(255,255,255,.85); fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }
    .brand-name { font-size: 1.9rem; font-weight: 700; letter-spacing: -.02em; text-align: center; position: relative; z-index: 1; margin-bottom: 8px; color: #F0F0F0; }
    .brand-sub { font-size: .78rem; color: rgba(255,255,255,.45); letter-spacing: .1em; text-transform: uppercase; text-align: center; position: relative; z-index: 1; }
    .brand-rule { width: 36px; height: 2px; background: rgba(255,255,255,.18); border-radius: 2px; margin: 28px 0; position: relative; z-index: 1; }
    .brand-features { display: flex; flex-direction: column; gap: 11px; position: relative; z-index: 1; }
    .brand-feature { display: flex; align-items: center; gap: 10px; font-size: .82rem; color: rgba(255,255,255,.45); }
    .brand-dot { width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,.35); flex-shrink: 0; }
    .login-form-side { background: #F2F2F2; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 72px 60px; }
    .login-inner { width: 100%; max-width: 380px; }
    .login-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 14px rgba(0,0,0,.08); padding: 40px 36px; }
    .login-heading { margin-bottom: 28px; }
    .login-heading h2 { font-size: 1.45rem; font-weight: 700; color: #1A1A1A; margin: 0 0 5px; }
    .login-heading p { font-size: .875rem; color: #888; margin: 0; }
    .login-card .form-group { margin-bottom: 16px; }
    .login-card .form-label { font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #555; display: block; margin-bottom: 6px; }
    .login-card .form-input { height: 44px; font-size: .9rem; border: 1.5px solid #E2E2E2; border-radius: 6px; background: #F7F7F7; width: 100%; padding: 0 12px; transition: border-color .15s, box-shadow .15s, background .15s; }
    .login-card .form-input:focus { border-color: #555; box-shadow: 0 0 0 3px rgba(0,0,0,.07); background: #fff; outline: none; }
    .login-card .alert { border-radius: 6px; font-size: .86rem; margin-bottom: 18px; }
    .login-btn { width: 100%; height: 44px; margin-top: 8px; background: #2C2C2C; color: #fff; border: none; border-radius: 6px; font-size: .92rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: background .15s, box-shadow .15s; box-shadow: 0 2px 6px rgba(0,0,0,.18); }
    .login-btn:hover { background: #1A1A1A; box-shadow: 0 4px 12px rgba(0,0,0,.22); }
    .login-btn:active { filter: brightness(.94); }
    .login-btn:disabled { opacity: .55; cursor: not-allowed; }
    .login-back { text-align: center; margin-top: 18px; }
    .login-back a { font-size: .82rem; color: #888; text-decoration: none; transition: color .15s; }
    .login-back a:hover { color: #2C2C2C; }
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
          <h2>Password recovery</h2>
          <p>Enter your email address and we'll send you an OTP.</p>
        </div>

        <?php if (!empty($success)): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger"><?= implode('<br>', array_merge(...array_values($errors))) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars(app_url('/forgot-password')) ?>">
          <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   class="form-input" placeholder="your@email.com" required autofocus>
          </div>

          <button type="submit" class="login-btn">Send OTP</button>
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
