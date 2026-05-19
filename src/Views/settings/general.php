<?php ob_start(); ?>
<div class="page-header">
  <h1>Settings</h1>
</div>

<div class="card">
  <div class="card-header"><h3 class="card-title">Business Settings</h3></div>
  <div class="card-body">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Business Name</label>
        <input type="text" id="businessName" class="form-input" value="<?= htmlspecialchars($settings['business_name'] ?? 'Dream Blanks') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Currency Symbol</label>
        <input type="text" id="currencySymbol" class="form-input" value="<?= htmlspecialchars($settings['currency_symbol'] ?? '₱') ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Date Format</label>
        <select id="dateFormat" class="form-select">
          <?php $df = $settings['date_format'] ?? 'MM/DD/YYYY'; ?>
          <option value="MM/DD/YYYY" <?= $df === 'MM/DD/YYYY' ? 'selected' : '' ?>>MM/DD/YYYY</option>
          <option value="DD/MM/YYYY" <?= $df === 'DD/MM/YYYY' ? 'selected' : '' ?>>DD/MM/YYYY</option>
          <option value="YYYY-MM-DD" <?= $df === 'YYYY-MM-DD' ? 'selected' : '' ?>>YYYY-MM-DD</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Time Format</label>
        <?php $tf = $settings['time_format'] ?? '12-hour'; ?>
        <select id="timeFormat" class="form-select">
          <option value="12-hour" <?= $tf === '12-hour' ? 'selected' : '' ?>>12-hour</option>
          <option value="24-hour" <?= $tf === '24-hour' ? 'selected' : '' ?>>24-hour</option>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Low Stock Alert Default</label>
        <input type="number" id="lowStockAlert" class="form-input" min="0" value="<?= htmlspecialchars($settings['low_stock_alert_default'] ?? 10) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Invoice Prefix</label>
        <input type="text" id="invoicePrefix" class="form-input" value="<?= htmlspecialchars($settings['invoice_prefix'] ?? 'INV-') ?>">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Timezone</label>
      <input type="text" id="timezone" class="form-input" value="<?= htmlspecialchars($settings['timezone'] ?? 'Asia/Manila') ?>">
      <div class="text-muted" style="font-size:.8rem;margin-top:6px">Example: Asia/Manila, UTC, America/New_York</div>
    </div>

    <div style="display:flex;justify-content:flex-end">
      <?php if (can('settings', 'edit')): ?>
      <button class="btn btn-primary" id="saveSettingsBtn" onclick="saveSettings()">Save Settings</button>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

async function saveSettings() {
  const btn = document.getElementById('saveSettingsBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving...';

  const payload = {
    business_name: document.getElementById('businessName').value,
    currency_symbol: document.getElementById('currencySymbol').value,
    date_format: document.getElementById('dateFormat').value,
    time_format: document.getElementById('timeFormat').value,
    low_stock_alert_default: document.getElementById('lowStockAlert').value,
    invoice_prefix: document.getElementById('invoicePrefix').value,
    timezone: document.getElementById('timezone').value,
  };

  try {
    const res = await fetch('/api/v1/settings', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) showToast('Settings updated', 'success');
    else showToast(data.message || 'Update failed', 'error');
  } catch (e) {
    showToast('Network error', 'error');
  }

  btn.disabled = false; btn.innerHTML = 'Save Settings';
}
</script>

<?php
$content = ob_get_clean();
$title   = 'Settings | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
