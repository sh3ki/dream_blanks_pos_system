<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Dream Blanks POS') ?></title>
  <meta name="app-base-path" content="<?= htmlspecialchars(app_base_path()) ?>">
  <link rel="icon" type="image/png" href="<?= htmlspecialchars(asset_url('/assets/images/no-image.png')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/assets/css/style.css')) ?>">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
  <script>
    (function () {
      window.APP_BASE_PATH = window.APP_BASE_PATH || (document.querySelector('meta[name="app-base-path"]')?.content || '');

      if (!window.__appFetchPatched && window.fetch) {
        const originalFetch = window.fetch.bind(window);
        window.fetch = function (resource, init) {
          if (typeof resource === 'string' && resource.startsWith('/') && !resource.startsWith('//')) {
            resource = (window.APP_BASE_PATH || '') + resource;
          }
          return originalFetch(resource, init);
        };
        window.__appFetchPatched = true;
      }

      if (!window.appPath) {
        window.appPath = function (path) {
          if (!path) return window.APP_BASE_PATH || '';
          if (!path.startsWith('/')) path = '/' + path;
          return (window.APP_BASE_PATH || '') + path;
        };
      }
    })();
  </script>
</head>
<body>
<?php
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
$basePath = app_base_path();
if ($basePath !== '' && str_starts_with($currentPath, $basePath)) {
  $currentPath = substr($currentPath, strlen($basePath)) ?: '/';
}
// strip query string
$currentPath = strtok($currentPath, '?');
?>
<div class="app-wrapper">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <span class="brand-icon"><?= icon('pos-display', 20) ?></span>
      <span>Dream Blanks</span>
    </div>
    <nav class="nav-menu">
      <div class="nav-section-label">Main</div>
      <?php if (can('dashboard', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/dashboard')) ?>" class="nav-link <?= str_starts_with($currentPath,'/dashboard') || $currentPath==='/' ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('dashboard') ?></span><span class="nav-label">Dashboard</span>
      </a>
      <?php endif; ?>
      <?php if (can('pos', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/pos')) ?>" class="nav-link <?= str_starts_with($currentPath,'/pos') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('pos') ?></span><span class="nav-label">Point of Sale</span>
      </a>
      <?php endif; ?>

      <div class="nav-section-label">Management</div>
      <?php if (can('invoices', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/invoices')) ?>" class="nav-link <?= str_starts_with($currentPath,'/invoices') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('invoice') ?></span><span class="nav-label">Invoices</span>
      </a>
      <?php endif; ?>
      <?php if (can('transactions', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/transactions')) ?>" class="nav-link <?= str_starts_with($currentPath,'/transactions') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('transactions') ?></span><span class="nav-label">Transactions</span>
      </a>
      <?php endif; ?>
      <?php if (can('project_lineup', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/project-lineup')) ?>" class="nav-link <?= str_starts_with($currentPath,'/project-lineup') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('lineup') ?></span><span class="nav-label">Project Lineup</span>
      </a>
      <?php endif; ?>
      <?php if (can('clients', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/clients')) ?>" class="nav-link <?= str_starts_with($currentPath,'/clients') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('clients') ?></span><span class="nav-label">Clients</span>
      </a>
      <?php endif; ?>
      <?php if (can('products', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/products')) ?>" class="nav-link <?= str_starts_with($currentPath,'/products') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('products') ?></span><span class="nav-label">Products</span>
      </a>
      <?php endif; ?>
      <?php if (can('stock_products', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/stock-products')) ?>" class="nav-link <?= str_starts_with($currentPath,'/stock-products') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('package') ?></span><span class="nav-label">Stock Products</span>
      </a>
      <?php endif; ?>
      <?php if (can('inventory', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/inventory')) ?>" class="nav-link <?= str_starts_with($currentPath,'/inventory') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('inventory') ?></span><span class="nav-label">Inventory</span>
      </a>
      <?php endif; ?>
      <?php if (can('variations', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/variations')) ?>" class="nav-link <?= str_starts_with($currentPath,'/variations') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('variations') ?></span><span class="nav-label">Variations</span>
      </a>
      <?php endif; ?>

      <?php if (can('reports_sales','view') || can('reports_inventory','view') || can('reports_financial','view')): ?>
      <div class="nav-section-label">Reports</div>
      <?php if (can('reports_sales', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/reports/sales')) ?>" class="nav-link <?= str_starts_with($currentPath,'/reports/sales') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('chart-bar') ?></span><span class="nav-label">Sales Report</span>
      </a>
      <?php endif; ?>
      <?php if (can('reports_inventory', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/reports/inventory')) ?>" class="nav-link <?= str_starts_with($currentPath,'/reports/inventory') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('clipboard') ?></span><span class="nav-label">Inventory Report</span>
      </a>
      <?php endif; ?>
      <?php if (can('reports_financial', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/reports/financial')) ?>" class="nav-link <?= str_starts_with($currentPath,'/reports/financial') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('money') ?></span><span class="nav-label">Financial Report</span>
      </a>
      <?php endif; ?>
      <?php endif; ?>

      <div class="nav-section-label">System</div>
      <?php if (can('audit_logs', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/audit-logs')) ?>" class="nav-link <?= str_starts_with($currentPath,'/audit-logs') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('audit') ?></span><span class="nav-label">Audit Logs</span>
      </a>
      <?php endif; ?>
      <?php if (can('users', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/users')) ?>" class="nav-link <?= str_starts_with($currentPath,'/users') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('users') ?></span><span class="nav-label">Users</span>
      </a>
      <?php endif; ?>
      <?php if (can('roles', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/roles')) ?>" class="nav-link <?= str_starts_with($currentPath,'/roles') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('roles') ?></span><span class="nav-label">Roles</span>
      </a>
      <?php endif; ?>
      <?php if (can('settings', 'view')): ?>
      <a href="<?= htmlspecialchars(app_url('/settings')) ?>" class="nav-link <?= str_starts_with($currentPath,'/settings') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('settings') ?></span><span class="nav-label">Settings</span>
      </a>
      <?php endif; ?>
    </nav>
  </aside>

  <!-- Topbar -->
  <header class="topbar" id="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" onclick="toggleSidebar()" title="Toggle sidebar"><?= icon('menu', 20) ?></button>
      <span class="page-title"><?= htmlspecialchars($pageTitle ?? ($title ?? '')) ?></span>
    </div>
    <div class="topbar-right">
      <?php
        if (!isset($unread_notifications) && !empty($_SESSION['user']['id'])) {
            $unread_notifications = \App\Models\Notification::unreadCount((int)$_SESSION['user']['id']);
        }
      ?>
      <button class="notification-btn" onclick="openNotifications()" title="Notifications">
        <?= icon('bell', 20) ?>
        <?php if (!empty($unread_notifications) && $unread_notifications > 0): ?>
          <span class="badge"><?= min($unread_notifications, 99) ?></span>
        <?php endif; ?>
      </button>
      <div class="user-menu" id="userMenuWrapper">
        <div class="user-avatar" id="topbarAvatar" onclick="toggleUserDropdown()" title="<?= htmlspecialchars($_SESSION['user']['first_name'] ?? 'User') ?>" style="cursor:pointer;overflow:hidden">
          <?php if (!empty($_SESSION['user']['profile_image'])): ?>
            <img src="<?= htmlspecialchars(app_url($_SESSION['user']['profile_image'])) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%" onerror="this.parentElement.innerHTML='<?= strtoupper(substr($_SESSION['user']['first_name'] ?? 'U', 0, 1)) ?>'">
          <?php else: ?>
            <?= strtoupper(substr($_SESSION['user']['first_name'] ?? 'U', 0, 1)) ?>
          <?php endif; ?>
        </div>
        <div class="user-dropdown" id="userDropdown">
          <div style="padding:12px 16px;border-bottom:1px solid var(--color-gray-100)">
            <div style="font-weight:600;font-size:.9rem"><?= htmlspecialchars(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? '')) ?></div>
            <div style="font-size:.8rem;color:var(--color-gray-500)"><?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?></div>
          </div>
          <a href="<?= htmlspecialchars(app_url('/profile')) ?>" style="display:flex;align-items:center;gap:8px"><?= icon('settings', 15) ?> Profile Settings</a>
          <hr style="margin:4px 0;border:none;border-top:1px solid var(--color-gray-100)">
          <a href="<?= htmlspecialchars(app_url('/logout')) ?>" style="color:var(--color-danger);display:flex;align-items:center;gap:8px"><?= icon('logout', 15) ?> Logout</a>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="main-content" id="mainContent">
    <?php if (!empty($flash_success)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($flash_success) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash_error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($flash_error) ?></div>
    <?php endif; ?>

    <?= $content ?? '' ?>
  </main>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Inactivity Warning Modal -->
<div id="inactivityModal" class="modal-overlay" style="z-index:9999;display:none">
  <div class="modal-content" style="max-width:400px;text-align:center">
    <div class="modal-body" style="padding:40px 36px">
      <div style="width:56px;height:56px;border-radius:50%;background:var(--color-warning-light);display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
        <?= icon('bell', 26) ?>
      </div>
      <h3 style="margin-bottom:8px;font-size:1.15rem">Still there?</h3>
      <p style="color:var(--color-gray-500);font-size:.875rem;margin-bottom:28px;line-height:1.6">
        You've been inactive for a while. Your session will automatically end in
        <strong id="inactivityCountdown" style="color:var(--color-danger)">0:15</strong>.
      </p>
      <div style="display:flex;gap:12px;justify-content:center">
        <a href="<?= htmlspecialchars(app_url('/logout')) ?>" class="btn btn-secondary">Logout Now</a>
        <button class="btn btn-primary" onclick="continueSession()">Continue Session</button>
      </div>
    </div>
  </div>
</div>

<script src="<?= htmlspecialchars(asset_url('/assets/js/app.js')) ?>"></script>
<script>
(function () {
  const INACTIVE_MS = 60 * 60 * 1000; // show warning after 60 minutes idle
  const COUNTDOWN_S = 15;             // 15-second countdown before auto-logout
  const LOGOUT_URL  = <?= json_encode(app_url('/logout')) ?>;

  let idleTimer     = null;
  let countdownInt  = null;
  let remaining     = COUNTDOWN_S;
  let warningActive = false;

  function fmtTime(s) {
    const m = Math.floor(s / 60);
    const sec = s % 60;
    return m + ':' + (sec < 10 ? '0' : '') + sec;
  }

  function resetIdle() {
    if (warningActive) return;
    clearTimeout(idleTimer);
    idleTimer = setTimeout(showWarning, INACTIVE_MS);
  }

  function showWarning() {
    warningActive = true;
    remaining = COUNTDOWN_S;
    const modal = document.getElementById('inactivityModal');
    modal.style.display = '';
    modal.classList.add('show');
    document.getElementById('inactivityCountdown').textContent = fmtTime(remaining);
    countdownInt = setInterval(function () {
      remaining--;
      const el = document.getElementById('inactivityCountdown');
      if (el) el.textContent = fmtTime(remaining);
      if (remaining <= 0) {
        clearInterval(countdownInt);
        window.location.href = LOGOUT_URL;
      }
    }, 1000);
  }

  window.continueSession = function () {
    clearInterval(countdownInt);
    warningActive = false;
    const modal = document.getElementById('inactivityModal');
    modal.classList.remove('show');
    modal.style.display = 'none';
    resetIdle();
  };

  ['mousemove', 'keydown', 'mousedown', 'scroll', 'touchstart', 'click'].forEach(function (evt) {
    document.addEventListener(evt, resetIdle, { passive: true });
  });

  resetIdle();
})();
</script>
</body>
</html>
