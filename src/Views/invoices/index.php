<?php ob_start(); ?>
<?php
$sort  = $filters['sort']  ?? 'i.created_at';
$order = strtoupper($filters['order'] ?? 'DESC');
function invSortLink(string $col, string $label, string $currentSort, string $currentOrder, array $filters): string {
    $nextOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $params    = array_filter(array_merge($filters, ['sort' => $col, 'order' => $nextOrder]), fn($v) => $v !== '');
    $arrow     = '';
    if ($currentSort === $col) $arrow = $currentOrder === 'ASC' ? ' <span style="font-size:.8em">▲</span>' : ' <span style="font-size:.8em">▼</span>';
    else $arrow = ' <span style="font-size:.8em;opacity:.5">⇅</span>';
    return '<a href="?' . http_build_query($params) . '" style="display:block;padding:12px 16px;color:inherit;text-decoration:none;white-space:nowrap">' . htmlspecialchars($label) . $arrow . '</a>';
}
?>
<div class="page-header">
  <h1>Invoices</h1>
  <div class="d-flex gap-8">
    <a href="<?= app_url('/reports/financial') ?>" class="btn btn-secondary btn-sm" style="display:flex;align-items:center;gap:6px"><?= icon('money', 15) ?> Receivables</a>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:16px">
    <div class="filter-bar">
      <div class="search-bar" style="flex:1;max-width:280px">
        <?= icon('search', 16) ?> <input type="text" placeholder="Invoice # or client..." name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
          onchange="this.form.submit()" form="filterForm" style="width:100%">
      </div>
      <form id="filterForm" method="GET" class="d-flex gap-8">
        <?php if (!empty($filters['sort'])):  ?><input type="hidden" name="sort"  value="<?= htmlspecialchars($filters['sort'])  ?>"><?php endif; ?>
        <?php if (!empty($filters['order'])): ?><input type="hidden" name="order" value="<?= htmlspecialchars($filters['order']) ?>"><?php endif; ?>
        <select name="status" class="form-select" style="width:150px;height:38px" onchange="this.form.submit()">
          <option value="">All Status</option>
          <option value="fully_paid" <?= ($filters['status'] ?? '') === 'fully_paid' ? 'selected' : '' ?>>Fully Paid</option>
          <option value="partially_paid" <?= ($filters['status'] ?? '') === 'partially_paid' ? 'selected' : '' ?>>Partially Paid</option>
          <option value="unpaid" <?= ($filters['status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
        </select>
        <input type="date" name="date_from" class="form-input" style="height:38px;width:140px" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" onchange="this.form.submit()">
        <input type="date" name="date_to" class="form-input" style="height:38px;width:140px" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" onchange="this.form.submit()">
      </form>
    </div>
  </div>

  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr style="cursor:pointer">
          <th style="padding:0"><?= invSortLink('i.invoice_number','Invoice #',$sort,$order,$filters) ?></th>
          <th style="padding:0"><?= invSortLink('i.invoice_date','Date',$sort,$order,$filters) ?></th>
          <th style="padding:0"><?= invSortLink('c.full_name','Client',$sort,$order,$filters) ?></th>
          <th style="padding:0"><?= invSortLink('i.total_amount','Total',$sort,$order,$filters) ?></th>
          <th style="padding:0"><?= invSortLink('i.total_paid','Paid',$sort,$order,$filters) ?></th>
          <th style="padding:0"><?= invSortLink('i.balance','Balance',$sort,$order,$filters) ?></th>
          <th style="padding:0"><?= invSortLink('i.payment_status','Status',$sort,$order,$filters) ?></th>
          <th>Method</th>
          <th>Invoice Sent</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($invoices as $inv):
          $balance = (float)$inv['total_amount'] - (float)$inv['total_paid'];
          $statusCls = match($inv['payment_status']) {
            'fully_paid' => 'badge-success',
            'partially_paid' => 'badge-warning',
            default => 'badge-danger',
          };
        ?>
        <?php
          $methodBadge = match($inv['primary_payment_mode'] ?? '') {
            'cash'  => '<span class="badge" style="background:#dcfce7;color:#166534">Cash</span>',
            'bdo'   => '<span class="badge" style="background:#fef9c3;color:#854d0e">Bank Transfer</span>',
            'gcash' => '<span class="badge" style="background:#dbeafe;color:#1e40af">GCash</span>',
            default => '<span class="text-muted" style="font-size:.8rem">—</span>',
          };
          $sentBadge = $inv['invoice_sent'] === 'sent'
            ? '<span class="badge badge-success">Sent</span>'
            : '<span class="badge badge-secondary">Not Sent</span>';
        ?>
        <tr style="cursor:pointer" onclick="openInvoiceModal(<?= $inv['id'] ?>)">
          <td><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
          <td><?= date('M d, Y', strtotime($inv['invoice_date'])) ?></td>
          <td><?= htmlspecialchars($inv['client_name'] ?? 'Walk-in') ?></td>
          <td>₱<?= number_format($inv['total_amount'], 2) ?></td>
          <td>₱<?= number_format($inv['total_paid'], 2) ?></td>
          <td><?= $balance > 0 ? '<span style="color:var(--color-danger)">₱' . number_format($balance, 2) . '</span>' : '<span style="color:var(--color-success)">₱0.00</span>' ?></td>
          <td><span class="badge <?= $statusCls ?>"><?= str_replace('_', ' ', ucfirst($inv['payment_status'])) ?></span></td>
          <td><?= $methodBadge ?></td>
          <td onclick="event.stopPropagation()">
            <select onchange="toggleInvoiceSent(<?= $inv['id'] ?>, this.value)" style="padding:4px 8px;border-radius:6px;border:1px solid var(--color-border);font-size:.8rem">
              <option value="sent" <?= $inv['invoice_sent']==='sent'?'selected':'' ?>>Sent</option>
              <option value="not_sent" <?= $inv['invoice_sent']!=='sent'?'selected':'' ?>>Not Sent</option>
            </select>
          </td>
          <td onclick="event.stopPropagation()">
            <a href="<?= app_url('/api/v1/invoices/' . $inv['id'] . '/print') ?>" target="_blank" class="icon-btn" title="Print">🖨</a>
            <a href="<?= app_url('/api/v1/invoices/' . $inv['id'] . '/print') ?>?download=1" target="_blank" class="icon-btn" title="Download PDF"><?= icon('download', 15) ?></a>
            <?php if ($inv['payment_status'] !== 'fully_paid'): ?>
              <button class="icon-btn" onclick="addPayment(<?= $inv['id'] ?>, '<?= htmlspecialchars($inv['invoice_number']) ?>', <?= $balance ?>)" title="Add Payment">💳</button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($invoices)): ?>
          <tr><td colspan="10" class="text-center text-muted" style="padding:48px">No invoices found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
    $iPqFilters = array_filter($filters ?? [], fn($v) => $v !== '');
    unset($iPqFilters['page'], $iPqFilters['per_page']);
    echo renderPagination($pagination, $iPqFilters);
  ?>
</div>

<!-- Invoice Preview Modal -->
<div class="modal-overlay" id="invPreviewModal">
  <div class="modal-content" style="max-width:780px;max-height:90vh;overflow-y:auto">
    <div class="modal-header">
      <h2 class="modal-title">Invoice</h2>
      <button class="modal-close" onclick="closeModal('invPreviewModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body" id="invModalBody" style="padding:24px"></div>
  </div>
</div>

<!-- Add Payment Modal -->
<div class="modal-overlay" id="paymentModal">
  <div class="modal-content" style="max-width:400px">
    <div class="modal-header">
      <h2 class="modal-title">Add Payment</h2>
      <button class="modal-close" onclick="document.getElementById('paymentModal').classList.remove('show')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <p style="margin-bottom:16px">Invoice: <strong id="payInvoiceNum"></strong> | Balance: <strong id="payBalance"></strong></p>
      <div class="form-group">
        <label class="form-label">Payment Date</label>
        <input type="date" id="payDate" class="form-input" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Amount (₱) <span class="required">*</span></label>
        <input type="number" id="payAmount" class="form-input" min="0.01" step="0.01">
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
        <input type="text" id="payRef" class="form-input" placeholder="e.g., CHK001 or transaction ID">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="document.getElementById('paymentModal').classList.remove('show')">Cancel</button>
      <button class="btn btn-primary" id="savePayBtn" onclick="savePayment()">Record Payment</button>
    </div>
  </div>
</div>

<script>
let currentInvoiceId = null;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function addPayment(invoiceId, number, balance) {
  currentInvoiceId = invoiceId;
  document.getElementById('payInvoiceNum').textContent = number;
  document.getElementById('payBalance').textContent = '₱' + balance.toFixed(2);
  document.getElementById('payAmount').value = balance.toFixed(2);
  document.getElementById('paymentModal').classList.add('show');
}

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
    const res  = await fetch('/api/v1/invoices/' + currentInvoiceId + '/payments', {
      method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) { showToast('Payment recorded!', 'success'); setTimeout(() => location.reload(), 800); }
    else showToast(data.message, 'error');
  } catch (e) { showToast('Error', 'error'); }
  btn.disabled = false; btn.innerHTML = 'Record Payment';
}

async function toggleInvoiceSent(id, value) {
  try {
    const res = await fetch('/api/v1/invoices/' + id + '/toggle-sent', {
      method: 'PUT', headers: {'Content-Type':'application/json','X-CSRF-Token': csrfToken},
      body: JSON.stringify({invoice_sent: value}),
    });
    const data = await res.json();
    if (data.success) showToast('Invoice status updated', 'success');
    else showToast(data.message || 'Failed to update', 'error');
  } catch (e) { showToast('Network error', 'error'); }
}

// ===== Invoice Preview Modal =====
function esc(s) { const d=document.createElement('div');d.textContent=s||'';return d.innerHTML; }

async function openInvoiceModal(id) {
  document.getElementById('invModalBody').innerHTML = '<div style="padding:40px;text-align:center"><span class="spinner"></span></div>';
  openModal('invPreviewModal');
  try {
    const res  = await fetch('/api/v1/invoices/' + id);
    const data = await res.json();
    if (!data.success) { document.getElementById('invModalBody').innerHTML = '<p class="text-danger">Failed to load invoice</p>'; return; }
    const inv = data.data;
    renderInvoiceModal(inv);
  } catch (e) { document.getElementById('invModalBody').innerHTML = '<p class="text-danger">Network error</p>'; }
}

function renderInvoiceModal(inv) {
  const methodLabel = {cash:'Cash', bdo:'Bank Transfer', gcash:'GCash'};
  const statusCls = {fully_paid:'badge-success', partially_paid:'badge-warning', unpaid:'badge-danger'};
  const balance = (parseFloat(inv.total_amount||0) - parseFloat(inv.total_paid||0));
  let itemRows = '';
  let totalQty = 0;
  (inv.items||[]).forEach(it => {
    const qty  = parseInt(it.quantity||0);
    const up   = parseFloat(it.unit_price||0);
    const disc = parseFloat(it.discount||0);
    const net  = up * qty - disc;
    totalQty  += qty;
    itemRows  += `<tr>
      <td style="padding:8px 12px">${esc(it.product_name||'')} ${it.variation_name?`<span style="font-size:.8rem;color:#6b7280">(${esc(it.variation_name)})</span>`:''}${it.sku?`<div style="font-size:.75rem;color:#9ca3af">${esc(it.sku)}</div>`:''}</td>
      <td style="padding:8px 12px;text-align:center">${qty}</td>
      <td style="padding:8px 12px;text-align:right">₱${up.toFixed(2)}${disc>0?`<div style="font-size:.75rem;color:#e74c3c">-₱${disc.toFixed(2)}</div>`:''}</td>
      <td style="padding:8px 12px;text-align:right">₱${net.toFixed(2)}</td>
    </tr>`;
  });

  let payRows = '';
  (inv.payments||[]).forEach((p,i) => {
    payRows += `<tr>
      <td style="padding:6px 10px">${i+1}</td>
      <td style="padding:6px 10px">${p.payment_date ? new Date(p.payment_date).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'}) : '—'}</td>
      <td style="padding:6px 10px">${esc(methodLabel[p.payment_mode]||p.payment_mode||'—')}</td>
      <td style="padding:6px 10px;text-align:right">₱${parseFloat(p.payment_amount||0).toFixed(2)}</td>
    </tr>`;
  });

  const discount = parseFloat(inv.discount_amount||0);
  const tax      = parseFloat(inv.tax_amount||0);
  const fee      = parseFloat(inv.additional_fee||0);
  const subtotal = parseFloat(inv.total_amount||0) + discount - tax - fee;

  document.getElementById('invModalBody').innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
      <div>
        <div style="font-size:1.5rem;font-weight:800;letter-spacing:.5px">DREAM BLANKS</div>
        <div style="font-size:.8rem;color:#6b7280">Customized Apparel & Merchandise</div>
      </div>
      <div style="text-align:right;font-size:.8rem;color:#374151">
        <div style="font-weight:700;margin-bottom:2px">Dream Blanks</div>
        <div>Philippines</div>
      </div>
    </div>
    <div style="text-align:center;border-top:2px solid #111;border-bottom:2px solid #111;padding:8px 0;margin-bottom:16px">
      <span style="font-size:1.25rem;font-weight:800;letter-spacing:2px">INVOICE</span>
    </div>
    <div style="display:flex;justify-content:space-between;margin-bottom:16px">
      <div>
        <div style="font-size:.75rem;color:#6b7280;font-weight:700;margin-bottom:4px">BILL TO</div>
        <div style="font-weight:600">${esc(inv.client_name||'Walk-in Customer')}</div>
        ${inv.client_email?`<div style="font-size:.85rem;color:#374151">${esc(inv.client_email)}</div>`:''}
        ${inv.client_phone?`<div style="font-size:.85rem;color:#374151">${esc(inv.client_phone)}</div>`:''}
      </div>
      <div style="text-align:right">
        <div style="margin-bottom:4px"><span style="font-size:.75rem;color:#6b7280">INVOICE #</span><br><strong>${esc(inv.invoice_number)}</strong></div>
        <div><span style="font-size:.75rem;color:#6b7280">DATE</span><br><strong>${inv.invoice_date ? new Date(inv.invoice_date+'T00:00:00').toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'}) : '—'}</strong></div>
        <div style="margin-top:4px"><span class="badge ${statusCls[inv.payment_status]||'badge-secondary'}">${(inv.payment_status||'').replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</span></div>
      </div>
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;font-size:.875rem">
      <thead><tr style="background:#f9fafb;border-bottom:2px solid #e5e7eb">
        <th style="padding:8px 12px;text-align:left">Description</th>
        <th style="padding:8px 12px;text-align:center">QTY</th>
        <th style="padding:8px 12px;text-align:right">Unit Price</th>
        <th style="padding:8px 12px;text-align:right">Total</th>
      </tr></thead>
      <tbody style="border-bottom:1px solid #e5e7eb">${itemRows}</tbody>
      <tfoot><tr style="background:#f9fafb">
        <td style="padding:8px 12px;font-weight:600">Total QTY</td>
        <td style="padding:8px 12px;text-align:center;font-weight:700">${totalQty}</td>
        <td colspan="2"></td>
      </tr></tfoot>
    </table>
    <div style="display:flex;justify-content:flex-end;margin-bottom:16px">
      <table style="font-size:.875rem;min-width:250px">
        <tr><td style="padding:4px 12px;color:#6b7280">Subtotal</td><td style="padding:4px 12px;text-align:right">₱${subtotal.toFixed(2)}</td></tr>
        ${discount>0?`<tr><td style="padding:4px 12px;color:#6b7280">Discount</td><td style="padding:4px 12px;text-align:right;color:#e74c3c">-₱${discount.toFixed(2)}</td></tr>`:''}
        ${tax>0?`<tr><td style="padding:4px 12px;color:#6b7280">Tax</td><td style="padding:4px 12px;text-align:right">₱${tax.toFixed(2)}</td></tr>`:''}
        ${fee>0?`<tr><td style="padding:4px 12px;color:#6b7280">Additional Fee</td><td style="padding:4px 12px;text-align:right">₱${fee.toFixed(2)}</td></tr>`:''}
        <tr style="border-top:2px solid #111;font-weight:800;font-size:1rem"><td style="padding:8px 12px">TOTAL</td><td style="padding:8px 12px;text-align:right">₱${parseFloat(inv.total_amount||0).toFixed(2)}</td></tr>
        <tr style="font-weight:700;color:#166534"><td style="padding:4px 12px">TOTAL PAID</td><td style="padding:4px 12px;text-align:right">₱${parseFloat(inv.total_paid||0).toFixed(2)}</td></tr>
        ${balance>0?`<tr style="font-weight:700;color:#dc2626"><td style="padding:4px 12px">BALANCE</td><td style="padding:4px 12px;text-align:right">₱${balance.toFixed(2)}</td></tr>`:''}
      </table>
    </div>
    ${payRows ? `
    <div style="margin-bottom:16px">
      <div style="font-weight:700;margin-bottom:8px;font-size:.875rem">Payment History</div>
      <table style="width:100%;border-collapse:collapse;font-size:.8rem">
        <thead><tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
          <th style="padding:6px 10px;text-align:left">#</th>
          <th style="padding:6px 10px;text-align:left">Date</th>
          <th style="padding:6px 10px;text-align:left">Mode</th>
          <th style="padding:6px 10px;text-align:right">Amount</th>
        </tr></thead>
        <tbody>${payRows}</tbody>
      </table>
    </div>` : ''}
    <div style="display:flex;justify-content:space-between;border-top:1px solid #e5e7eb;padding-top:16px;font-size:.8rem;color:#374151">
      <div>Sales Staff: <strong>${esc(inv.created_by_name||'—')}</strong></div>
      <div style="text-align:center">Authorized Signature: _______________</div>
      <div style="font-style:italic;color:#6b7280">Thank you for your Business!</div>
    </div>
  `;
}
</script>
<?php
$content = ob_get_clean();
$title   = 'Invoices | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
