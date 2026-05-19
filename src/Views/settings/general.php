<?php ob_start(); ?>
<div class="page-header">
  <h1>Settings</h1>
</div>

<!-- Business Information -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><h3 class="card-title">Business Information</h3></div>
  <div class="card-body">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Business Name</label>
        <input type="text" id="business_name" class="form-input" value="<?= htmlspecialchars($settings['business_name'] ?? 'Dream Blanks') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Business Phone</label>
        <input type="text" id="business_phone" class="form-input" value="<?= htmlspecialchars($settings['business_phone'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Business Email</label>
        <input type="email" id="business_email" class="form-input" value="<?= htmlspecialchars($settings['business_email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Business Address</label>
        <input type="text" id="business_address" class="form-input" value="<?= htmlspecialchars($settings['business_address'] ?? '') ?>">
      </div>
    </div>
  </div>
</div>

<!-- Currency & Localization -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><h3 class="card-title">Currency &amp; Localization</h3></div>
  <div class="card-body">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Currency Symbol</label>
        <input type="text" id="currency_symbol" class="form-input" value="<?= htmlspecialchars($settings['currency_symbol'] ?? 'â‚±') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Currency Code</label>
        <input type="text" id="currency_code" class="form-input" value="<?= htmlspecialchars($settings['currency_code'] ?? 'PHP') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Date Format</label>
        <?php $df = $settings['date_format'] ?? 'MM/DD/YYYY'; ?>
        <select id="date_format" class="form-select">
          <option value="MM/DD/YYYY" <?= $df === 'MM/DD/YYYY' ? 'selected' : '' ?>>MM/DD/YYYY</option>
          <option value="DD/MM/YYYY" <?= $df === 'DD/MM/YYYY' ? 'selected' : '' ?>>DD/MM/YYYY</option>
          <option value="YYYY-MM-DD" <?= $df === 'YYYY-MM-DD' ? 'selected' : '' ?>>YYYY-MM-DD</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Time Format</label>
        <?php $tf = $settings['time_format'] ?? '12-hour'; ?>
        <select id="time_format" class="form-select">
          <option value="12-hour" <?= $tf === '12-hour' ? 'selected' : '' ?>>12-hour</option>
          <option value="24-hour" <?= $tf === '24-hour' ? 'selected' : '' ?>>24-hour</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Timezone</label>
      <input type="text" id="timezone" class="form-input" value="<?= htmlspecialchars($settings['timezone'] ?? 'Asia/Manila') ?>">
      <div class="text-muted" style="font-size:.8rem;margin-top:4px">Example: Asia/Manila, UTC, America/New_York</div>
    </div>
  </div>
</div>

<!-- Invoice Settings -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><h3 class="card-title">Invoice Settings</h3></div>
  <div class="card-body">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Invoice Prefix</label>
        <input type="text" id="invoice_prefix" class="form-input" value="<?= htmlspecialchars($settings['invoice_prefix'] ?? 'INV-') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Next Invoice Number</label>
        <input type="number" id="invoice_next_number" class="form-input" min="1" value="<?= htmlspecialchars($settings['invoice_next_number'] ?? '1') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Invoice Number Reset</label>
        <?php $rf = $settings['invoice_reset_frequency'] ?? 'yearly'; ?>
        <select id="invoice_reset_frequency" class="form-select">
          <option value="daily"   <?= $rf === 'daily'   ? 'selected' : '' ?>>Daily</option>
          <option value="monthly" <?= $rf === 'monthly' ? 'selected' : '' ?>>Monthly</option>
          <option value="yearly"  <?= $rf === 'yearly'  ? 'selected' : '' ?>>Yearly</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Tax Rate (%)</label>
        <input type="number" id="tax_rate" class="form-input" min="0" max="100" step="0.01" value="<?= htmlspecialchars($settings['tax_rate'] ?? '0') ?>">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Receipt Footer Message</label>
      <input type="text" id="receipt_footer" class="form-input" value="<?= htmlspecialchars($settings['receipt_footer'] ?? 'Thank you for your business!') ?>">
    </div>
  </div>
</div>

<!-- System Settings -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><h3 class="card-title">System Settings</h3></div>
  <div class="card-body">
    <div class="form-group">
      <label class="form-label">Low Stock Alert Default</label>
      <input type="number" id="low_stock_alert_default" class="form-input" min="0" value="<?= htmlspecialchars($settings['low_stock_alert_default'] ?? 10) ?>">
      <div class="text-muted" style="font-size:.8rem;margin-top:4px">Default threshold for low stock notifications</div>
    </div>
  </div>
</div>

<?php if (can('settings', 'edit')): ?>
<div style="display:flex;justify-content:flex-end;margin-bottom:24px">
  <button class="btn btn-primary" id="saveSettingsBtn" onclick="saveSettings()">Save Settings</button>
</div>
<?php endif; ?>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

async function saveSettings() {
  const btn = document.getElementById('saveSettingsBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving...';

  const fields = [
    'business_name','business_phone','business_email','business_address',
    'currency_symbol','currency_code','date_format','time_format','timezone',
    'invoice_prefix','invoice_next_number','invoice_reset_frequency','tax_rate','receipt_footer',
    'low_stock_alert_default',
  ];
  const payload = {};
  fields.forEach(id => {
    const el = document.getElementById(id);
    if (el) payload[id] = el.value;
  });

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
