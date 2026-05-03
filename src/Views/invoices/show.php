<?php ob_start(); ?>
<?php
$balance = (float)$invoice['total_amount'] - (float)$invoice['total_paid'];
$statusCls = match($invoice['payment_status']) {
  'fully_paid' => 'badge-success',
  'partially_paid' => 'badge-warning',
  default => 'badge-danger',
};
?>
<div class="page-header">
  <div>
    <h1>Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></h1>
    <div class="text-muted" style="font-size:.85rem">Status: <span class="badge <?= $statusCls ?>"><?= str_replace('_', ' ', ucfirst($invoice['payment_status'])) ?></span></div>
  </div>
  <div class="d-flex gap-8">
    <a href="<?= app_url('/api/v1/invoices/' . $invoice['id'] . '/print') ?>" target="_blank" class="btn btn-secondary" style="display:flex;align-items:center;gap:6px"><?= icon('print', 15) ?> Print</a>
    <button class="btn btn-secondary" onclick="openEmailModal()" style="display:flex;align-items:center;gap:6px"><?= icon('email', 15) ?> Send Email</button>
    <?php if ($invoice['payment_status'] !== 'fully_paid'): ?>
      <button class="btn btn-primary" onclick="openPaymentModal()" style="display:flex;align-items:center;gap:6px"><?= icon('money', 15) ?> Add Payment</button>
    <?php endif; ?>
    <a href="<?= app_url('/invoices') ?>" class="btn btn-secondary">← Back</a>
  </div>
</div>

<div class="card" style="padding:16px;margin-bottom:16px">
  <div class="form-row">
    <div class="form-group">
      <label class="form-label">Invoice Date</label>
      <div><?= date('M d, Y', strtotime($invoice['invoice_date'])) ?></div>
    </div>
    <div class="form-group">
      <label class="form-label">Client</label>
      <div><?= htmlspecialchars($invoice['client_name'] ?? 'Walk-in') ?></div>
    </div>
    <div class="form-group">
      <label class="form-label">Processed By</label>
      <div><?= htmlspecialchars($invoice['created_by_name'] ?? '-') ?></div>
    </div>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-header"><h3 class="card-title">Items</h3></div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit Price</th><th>Line Total</th></tr>
      </thead>
      <tbody>
        <?php foreach (($invoice['items'] ?? []) as $item): ?>
        <tr>
          <td><?= htmlspecialchars($item['product_name'] ?? '') ?></td>
          <td><?= htmlspecialchars($item['sku'] ?? '') ?></td>
          <td><?= (int)$item['quantity'] ?></td>
          <td>₱<?= number_format($item['unit_price'], 2) ?></td>
          <td>₱<?= number_format($item['line_total'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($invoice['items'])): ?>
          <tr><td colspan="5" class="text-center text-muted" style="padding:24px">No items</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
  <div class="card" style="padding:16px">
    <h3 style="margin-bottom:12px">Totals</h3>
    <div class="summary-row"><span>Subtotal</span><span>₱<?= number_format($invoice['subtotal'], 2) ?></span></div>
    <div class="summary-row"><span>Discount</span><span>-₱<?= number_format($invoice['discount_amount'], 2) ?></span></div>
    <div class="summary-row"><span>Tax</span><span>+₱<?= number_format($invoice['tax_amount'], 2) ?></span></div>
    <div class="summary-row"><span>Additional Fee</span><span>+₱<?= number_format($invoice['additional_fee'], 2) ?></span></div>
    <div class="summary-row total"><span>Total</span><span>₱<?= number_format($invoice['total_amount'], 2) ?></span></div>
    <div class="summary-row"><span>Paid</span><span>₱<?= number_format($invoice['total_paid'], 2) ?></span></div>
    <div class="summary-row"><span>Balance</span><span>₱<?= number_format($balance, 2) ?></span></div>
  </div>

  <div class="card" style="padding:16px">
    <h3 style="margin-bottom:12px">Notes</h3>
    <div class="text-muted" style="min-height:120px">
      <?= !empty($invoice['notes']) ? nl2br(htmlspecialchars($invoice['notes'])) : 'No notes provided.' ?>
    </div>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-header"><h3 class="card-title">Payments</h3></div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr><th>#</th><th>Date</th><th>Amount</th><th>Mode</th><th>Reference</th><th>Recorded By</th></tr>
      </thead>
      <tbody>
        <?php foreach (($invoice['payments'] ?? []) as $pay): ?>
        <tr>
          <td><?= (int)$pay['payment_number'] ?></td>
          <td><?= date('M d, Y', strtotime($pay['payment_date'])) ?></td>
          <td>₱<?= number_format($pay['payment_amount'], 2) ?></td>
          <td><?= strtoupper(htmlspecialchars($pay['payment_mode'])) ?></td>
          <td><?= htmlspecialchars($pay['reference_number'] ?? '-') ?></td>
          <td><?= htmlspecialchars($pay['recorded_by_name'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($invoice['payments'])): ?>
          <tr><td colspan="6" class="text-center text-muted" style="padding:24px">No payments recorded</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Payment Modal -->
<div class="modal-overlay" id="paymentModal">
  <div class="modal-content" style="max-width:400px">
    <div class="modal-header">
      <h2 class="modal-title">Add Payment</h2>
      <button class="modal-close" onclick="closeModal('paymentModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <p style="margin-bottom:16px">Balance: <strong>₱<?= number_format($balance, 2) ?></strong></p>
      <div class="form-group">
        <label class="form-label">Payment Date</label>
        <input type="date" id="payDate" class="form-input" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Amount (₱) <span class="required">*</span></label>
        <input type="number" id="payAmount" class="form-input" min="0.01" step="0.01" value="<?= number_format($balance, 2, '.', '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Payment Mode</label>
        <select id="payMode" class="form-select">
          <option value="cash">💵 Cash</option>
          <option value="bdo">🏦 BDO</option>
          <option value="gcash">📱 GCash</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Reference Number</label>
        <input type="text" id="payRef" class="form-input" placeholder="e.g., CHK001">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('paymentModal')">Cancel</button>
      <button class="btn btn-primary" id="savePayBtn" onclick="savePayment()">Record Payment</button>
    </div>
  </div>
</div>

<!-- Email Modal -->
<div class="modal-overlay" id="emailModal">
  <div class="modal-content" style="max-width:420px">
    <div class="modal-header">
      <h2 class="modal-title">Send Invoice</h2>
      <button class="modal-close" onclick="closeModal('emailModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Recipient Email <span class="required">*</span></label>
        <input type="email" id="recipientEmail" class="form-input" placeholder="customer@email.com">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('emailModal')">Cancel</button>
      <button class="btn btn-primary" id="sendEmailBtn" onclick="sendInvoiceEmail()">Send Email</button>
    </div>
  </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const invoiceId = <?= (int)$invoice['id'] ?>;

function openPaymentModal() { openModal('paymentModal'); }
function openEmailModal() { openModal('emailModal'); }

async function savePayment() {
  const btn = document.getElementById('savePayBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';

  const payload = {
    payment_date: document.getElementById('payDate').value,
    payment_amount: parseFloat(document.getElementById('payAmount').value),
    payment_mode: document.getElementById('payMode').value,
    reference_number: document.getElementById('payRef').value,
  };

  try {
    const res  = await fetch('/api/v1/invoices/' + invoiceId + '/payments', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) { showToast('Payment recorded', 'success'); setTimeout(() => location.reload(), 800); }
    else showToast(data.message || 'Failed', 'error');
  } catch (e) {
    showToast('Network error', 'error');
  }

  btn.disabled = false; btn.innerHTML = 'Record Payment';
}

async function sendInvoiceEmail() {
  const btn = document.getElementById('sendEmailBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';

  const payload = { recipient_email: document.getElementById('recipientEmail').value };
  try {
    const res = await fetch('/api/v1/invoices/' + invoiceId + '/send-email', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) { showToast('Invoice sent', 'success'); closeModal('emailModal'); }
    else showToast(data.message || 'Failed', 'error');
  } catch (e) {
    showToast('Network error', 'error');
  }

  btn.disabled = false; btn.innerHTML = 'Send Email';
}
</script>

<?php
$content = ob_get_clean();
$title   = 'Invoice Details | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
