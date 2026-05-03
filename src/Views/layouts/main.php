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
      <span class="brand-icon"><?= icon('store', 20) ?></span>
      <span>Dream Blanks</span>
    </div>
    <nav class="nav-menu">
      <div class="nav-section-label">Main</div>
      <a href="<?= htmlspecialchars(app_url('/dashboard')) ?>" class="nav-link <?= str_starts_with($currentPath,'/dashboard') || $currentPath==='/' ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('dashboard') ?></span><span class="nav-label">Dashboard</span>
      </a>
      <a href="<?= htmlspecialchars(app_url('/pos')) ?>" class="nav-link <?= str_starts_with($currentPath,'/pos') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('pos') ?></span><span class="nav-label">Point of Sale</span>
      </a>

      <div class="nav-section-label">Management</div>
      <a href="<?= htmlspecialchars(app_url('/invoices')) ?>" class="nav-link <?= str_starts_with($currentPath,'/invoices') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('invoice') ?></span><span class="nav-label">Invoices</span>
      </a>
      <a href="<?= htmlspecialchars(app_url('/clients')) ?>" class="nav-link <?= str_starts_with($currentPath,'/clients') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('clients') ?></span><span class="nav-label">Clients</span>
      </a>
      <a href="<?= htmlspecialchars(app_url('/products')) ?>" class="nav-link <?= str_starts_with($currentPath,'/products') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('products') ?></span><span class="nav-label">Products</span>
      </a>
      <a href="<?= htmlspecialchars(app_url('/inventory')) ?>" class="nav-link <?= str_starts_with($currentPath,'/inventory') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('inventory') ?></span><span class="nav-label">Inventory</span>
      </a>
      <a href="<?= htmlspecialchars(app_url('/variations')) ?>" class="nav-link <?= str_starts_with($currentPath,'/variations') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('variations') ?></span><span class="nav-label">Variations</span>
      </a>

      <div class="nav-section-label">Reports</div>
      <a href="<?= htmlspecialchars(app_url('/reports/sales')) ?>" class="nav-link <?= str_starts_with($currentPath,'/reports/sales') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('chart-bar') ?></span><span class="nav-label">Sales Report</span>
      </a>
      <a href="<?= htmlspecialchars(app_url('/reports/inventory')) ?>" class="nav-link <?= str_starts_with($currentPath,'/reports/inventory') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('clipboard') ?></span><span class="nav-label">Inventory Report</span>
      </a>
      <a href="<?= htmlspecialchars(app_url('/reports/financial')) ?>" class="nav-link <?= str_starts_with($currentPath,'/reports/financial') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('money') ?></span><span class="nav-label">Financial Report</span>
      </a>

      <div class="nav-section-label">System</div>
      <a href="<?= htmlspecialchars(app_url('/users')) ?>" class="nav-link <?= str_starts_with($currentPath,'/users') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('users') ?></span><span class="nav-label">Users</span>
      </a>
      <a href="<?= htmlspecialchars(app_url('/roles')) ?>" class="nav-link <?= str_starts_with($currentPath,'/roles') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('roles') ?></span><span class="nav-label">Roles</span>
      </a>
      <a href="<?= htmlspecialchars(app_url('/settings')) ?>" class="nav-link <?= str_starts_with($currentPath,'/settings') ? 'active':'' ?>">
        <span class="nav-icon"><?= icon('settings') ?></span><span class="nav-label">Settings</span>
      </a>
    </nav>
  </aside>

  <!-- Topbar -->
  <header class="topbar" id="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" onclick="toggleSidebar()" title="Toggle sidebar"><?= icon('menu', 20) ?></button>
      <span class="page-title"><?= htmlspecialchars($pageTitle ?? ($title ?? '')) ?></span>
    </div>
    <div class="topbar-right">
      <button class="notification-btn" onclick="openNotifications()" title="Notifications">
        <?= icon('bell', 20) ?>
        <?php if (!empty($unread_notifications) && $unread_notifications > 0): ?>
          <span class="badge"><?= min($unread_notifications, 99) ?></span>
        <?php endif; ?>
      </button>
      <div class="user-menu" id="userMenuWrapper">
        <div class="user-avatar" onclick="toggleUserDropdown()" title="<?= htmlspecialchars($_SESSION['user']['first_name'] ?? 'User') ?>" style="cursor:pointer">
          <?= strtoupper(substr($_SESSION['user']['first_name'] ?? 'U', 0, 1)) ?>
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

<script src="<?= htmlspecialchars(asset_url('/assets/js/app.js')) ?>"></script>
</body>
</html>
