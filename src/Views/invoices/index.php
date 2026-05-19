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
      <form id="filterForm" method="GET" class="d-flex gap-8" style="flex-wrap:wrap">
        <?php if (!empty($filters['sort'])):        ?><input type="hidden" name="sort"         value="<?= htmlspecialchars($filters['sort'])         ?>"><?php endif; ?>
        <?php if (!empty($filters['order'])):       ?><input type="hidden" name="order"        value="<?= htmlspecialchars($filters['order'])        ?>"><?php endif; ?>
        <select name="status" class="form-select" style="width:150px;height:38px" onchange="this.form.submit()">
          <option value="">All Status</option>
          <option value="fully_paid"     <?= ($filters['status']       ?? '') === 'fully_paid'     ? 'selected' : '' ?>>Fully Paid</option>
          <option value="partially_paid" <?= ($filters['status']       ?? '') === 'partially_paid' ? 'selected' : '' ?>>Partially Paid</option>
          <option value="unpaid"         <?= ($filters['status']       ?? '') === 'unpaid'         ? 'selected' : '' ?>>Unpaid</option>
        </select>
        <select name="method" class="form-select" style="width:135px;height:38px" onchange="this.form.submit()">
          <option value="">All Methods</option>
          <option value="cash"  <?= ($filters['method'] ?? '') === 'cash'  ? 'selected' : '' ?>>Cash</option>
          <option value="bdo"   <?= ($filters['method'] ?? '') === 'bdo'   ? 'selected' : '' ?>>Bank Transfer</option>
          <option value="gcash" <?= ($filters['method'] ?? '') === 'gcash' ? 'selected' : '' ?>>GCash</option>
        </select>
        <select name="invoice_sent" class="form-select" style="width:130px;height:38px" onchange="this.form.submit()">
          <option value="">All Sent</option>
          <option value="sent"     <?= ($filters['invoice_sent'] ?? '') === 'sent'     ? 'selected' : '' ?>>Sent</option>
          <option value="not_sent" <?= ($filters['invoice_sent'] ?? '') === 'not_sent' ? 'selected' : '' ?>>Not Sent</option>
        </select>
        <input type="date" name="date_from" class="form-input" style="height:38px;width:140px" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" onchange="this.form.submit()">
        <input type="date" name="date_to"   class="form-input" style="height:38px;width:140px" value="<?= htmlspecialchars($filters['date_to']   ?? '') ?>" onchange="this.form.submit()">
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
          <th style="padding:0"><?= invSortLink('i.primary_payment_mode','Method',$sort,$order,$filters) ?></th>
          <th style="padding:0"><?= invSortLink('i.invoice_sent','Invoice Sent',$sort,$order,$filters) ?></th>
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
        <tr style="cursor:pointer" onclick="window.open(appPath('/api/v1/invoices/<?= $inv['id'] ?>/print'), '_blank')">
          <td><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
          <td style="white-space:nowrap">
            <div style="font-size:.78rem;font-weight:600"><?= date('h:i A', strtotime($inv['invoice_date'])) ?></div>
            <div style="font-size:.75rem;color:var(--color-gray-500)"><?= date('M d, Y', strtotime($inv['invoice_date'])) ?></div>
          </td>
          <td>
            <div><?= htmlspecialchars($inv['client_name'] ?? 'Walk-in') ?></div>
            <?php if (!empty($inv['client_email'])): ?>
              <div style="font-size:.75rem;color:var(--color-gray-500)"><?= htmlspecialchars($inv['client_email']) ?></div>
            <?php endif; ?>
          </td>
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
            <a href="<?= app_url('/api/v1/invoices/' . $inv['id'] . '/print') ?>?download=1" target="_blank" class="icon-btn" title="Download PDF"><?= icon('download', 15) ?></a>
            <button class="icon-btn" onclick="viewPayHistory(<?= $inv['id'] ?>, '<?= htmlspecialchars($inv['invoice_number']) ?>')" title="Payment History"><?= icon('history', 15) ?></button>
            <?php if ($inv['payment_status'] !== 'fully_paid'): ?>
              <button class="icon-btn" onclick="addPayment(<?= $inv['id'] ?>, '<?= htmlspecialchars($inv['invoice_number']) ?>', <?= $balance ?>)" title="Add Payment"><?= icon('payment', 15) ?></button>
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

<!-- Payment History Modal -->
<div class="modal-overlay" id="payHistModal">
  <div class="modal-content" style="max-width:600px">
    <div class="modal-header">
      <h2 class="modal-title">Payment History — <span id="payHistInvNum"></span></h2>
      <button class="modal-close" onclick="document.getElementById('payHistModal').classList.remove('show')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body" style="padding:0">
      <div id="payHistLoading" style="padding:32px;text-align:center"><span class="spinner"></span></div>
      <table class="data-table" id="payHistTable" style="display:none">
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Mode</th>
            <th>Reference</th>
            <th style="text-align:right">Amount</th>
            <th>Recorded By</th>
          </tr>
        </thead>
        <tbody id="payHistBody"></tbody>
      </table>
      <p id="payHistEmpty" style="display:none;padding:32px;text-align:center;color:var(--color-gray-500)">No payment records found.</p>
    </div>
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

<?php
  $__bizName    = \App\Models\Setting::get('business_name', 'Dream Blanks');
  $__bizPhone   = \App\Models\Setting::get('business_phone', '');
  $__bizEmail   = \App\Models\Setting::get('business_email', '');
  $__bizAddress = \App\Models\Setting::get('business_address', '');
?>
<script>
const APP_BIZ = {
  name:    <?= json_encode($__bizName) ?>,
  phone:   <?= json_encode($__bizPhone) ?>,
  email:   <?= json_encode($__bizEmail) ?>,
  address: <?= json_encode($__bizAddress) ?>,
};
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

async function viewPayHistory(invoiceId, invoiceNum) {
  document.getElementById('payHistInvNum').textContent = invoiceNum;
  document.getElementById('payHistLoading').style.display = '';
  document.getElementById('payHistTable').style.display  = 'none';
  document.getElementById('payHistEmpty').style.display  = 'none';
  document.getElementById('payHistModal').classList.add('show');
  try {
    const res  = await fetch('/api/v1/invoices/' + invoiceId);
    const data = await res.json();
    const payments = data.data?.payments ?? [];
    document.getElementById('payHistLoading').style.display = 'none';
    if (!payments.length) {
      document.getElementById('payHistEmpty').style.display = '';
      return;
    }
    const modeLabel = { cash: 'Cash', bdo: 'Bank Transfer', gcash: 'GCash' };
    const modeBadge  = {
      cash:  '<span class="badge" style="background:#dcfce7;color:#166534">Cash</span>',
      bdo:   '<span class="badge" style="background:#fef9c3;color:#854d0e">Bank Transfer</span>',
      gcash: '<span class="badge" style="background:#dbeafe;color:#1e40af">GCash</span>',
    };
    document.getElementById('payHistBody').innerHTML = payments.map((p, i) => {
      const d = new Date(p.payment_date.replace(' ', 'T'));
      const time = d.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:true});
      const date = d.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
      const badge = modeBadge[p.payment_mode] ?? `<span class="badge badge-secondary">${modeLabel[p.payment_mode] ?? p.payment_mode ?? '—'}</span>`;
      return `
      <tr>
        <td>${i + 1}</td>
        <td style="white-space:nowrap">
          <div style="font-size:.78rem;font-weight:600">${time}</div>
          <div style="font-size:.75rem;color:var(--color-gray-500)">${date}</div>
        </td>
        <td>${badge}</td>
        <td style="font-size:.82rem">${p.reference_number ? p.reference_number : '<span style="color:var(--color-gray-400)">—</span>'}</td>
        <td style="text-align:right;font-weight:600">&#8369;${parseFloat(p.payment_amount).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
        <td style="font-size:.82rem">${p.recorded_by_name ?? '—'}</td>
      </tr>`;
    }).join('');
    document.getElementById('payHistTable').style.display = '';
  } catch (e) {
    document.getElementById('payHistLoading').style.display = 'none';
    document.getElementById('payHistEmpty').textContent = 'Failed to load payment history.';
    document.getElementById('payHistEmpty').style.display = '';
  }
}


</script>
<?php
$content = ob_get_clean();
$title   = 'Invoices | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
