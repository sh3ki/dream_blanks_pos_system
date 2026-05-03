<?php
use App\Models\Client;

ob_start();
$clientName = trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? ''));
$invoices = Client::getInvoiceHistory($client['id']);
?>
<div class="page-header">
  <div>
    <h1>Client: <?= htmlspecialchars($clientName) ?></h1>
    <div class="text-muted" style="font-size:.85rem">Status: <?= ucfirst($client['status'] ?? 'active') ?></div>
  </div>
  <a href="/clients" class="btn btn-secondary">← Back to Clients</a>
</div>

<div class="card" style="padding:16px;margin-bottom:16px">
  <h3 style="margin-bottom:12px">Profile</h3>
  <div class="form-row">
    <div class="form-group">
      <label class="form-label">Email</label>
      <div><?= htmlspecialchars($client['email'] ?? '-') ?></div>
    </div>
    <div class="form-group">
      <label class="form-label">Created</label>
      <div><?= !empty($client['created_at']) ? date('M d, Y', strtotime($client['created_at'])) : '-' ?></div>
    </div>
    <div class="form-group">
      <label class="form-label">Last Updated</label>
      <div><?= !empty($client['updated_at']) ? date('M d, Y', strtotime($client['updated_at'])) : '-' ?></div>
    </div>
  </div>
</div>

<div class="card" style="padding:16px;margin-bottom:16px">
  <h3 style="margin-bottom:12px">Addresses</h3>
  <?php if (!empty($client['addresses'])): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px">
      <?php foreach ($client['addresses'] as $addr): ?>
        <div class="card" style="padding:12px">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <strong><?= htmlspecialchars(ucfirst($addr['address_type'] ?? '')) ?></strong>
            <?php if (!empty($addr['is_primary'])): ?>
              <span class="badge badge-success">Primary</span>
            <?php endif; ?>
          </div>
          <div style="margin-top:8px;font-size:.9rem">
            <?= htmlspecialchars($addr['street_address'] ?? '') ?><br>
            <?= htmlspecialchars($addr['barangay'] ?? '') ?>
            <?= htmlspecialchars($addr['city'] ?? '') ?>
            <?= htmlspecialchars($addr['province'] ?? '') ?><br>
            <?= htmlspecialchars($addr['postal_code'] ?? '') ?>
            <?= htmlspecialchars($addr['country'] ?? 'Philippines') ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-muted">No addresses recorded.</div>
  <?php endif; ?>
</div>

<div class="card" style="padding:16px;margin-bottom:16px">
  <h3 style="margin-bottom:12px">Contacts</h3>
  <?php if (!empty($client['contacts'])): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px">
      <?php foreach ($client['contacts'] as $contact): ?>
        <div class="card" style="padding:12px">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <strong><?= htmlspecialchars(ucfirst($contact['contact_type'] ?? '')) ?></strong>
            <?php if (!empty($contact['is_primary'])): ?>
              <span class="badge badge-success">Primary</span>
            <?php endif; ?>
          </div>
          <div style="margin-top:8px;font-size:1rem">
            <?= htmlspecialchars($contact['contact_number'] ?? '') ?>
          </div>
          <?php if (!empty($contact['is_verified'])): ?>
            <div class="text-muted" style="font-size:.75rem;margin-top:6px">Verified</div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-muted">No contacts recorded.</div>
  <?php endif; ?>
</div>

<div class="card" style="padding:16px">
  <h3 style="margin-bottom:12px">Invoice History</h3>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr><th>Invoice #</th><th>Date</th><th>Total</th><th>Paid</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($invoices as $inv): ?>
        <?php
          $statusCls = match($inv['payment_status']) {
            'fully_paid' => 'badge-success',
            'partially_paid' => 'badge-warning',
            default => 'badge-danger',
          };
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
          <td><?= date('M d, Y', strtotime($inv['invoice_date'])) ?></td>
          <td>₱<?= number_format($inv['total_amount'], 2) ?></td>
          <td>₱<?= number_format($inv['total_paid'], 2) ?></td>
          <td><span class="badge <?= $statusCls ?>"><?= str_replace('_', ' ', ucfirst($inv['payment_status'])) ?></span></td>
          <td>
            <a href="/invoices/<?= $inv['id'] ?>" class="icon-btn" title="View"><?= icon('eye', 15) ?></a>
            <a href="<?= app_url('/api/v1/invoices/' . $inv['id'] . '/print') ?>" target="_blank" class="icon-btn" title="Print"><?= icon('print', 15) ?></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($invoices)): ?>
          <tr><td colspan="6" class="text-center text-muted" style="padding:36px">No invoices yet</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$content = ob_get_clean();
$title   = 'Client Details | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
