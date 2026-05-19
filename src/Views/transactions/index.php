<?php ob_start(); ?>
<?php
$sort  = $filters['sort']  ?? 'i.invoice_date';
$order = strtoupper($filters['order'] ?? 'DESC');
function txSortLink(string $col, string $label, string $currentSort, string $currentOrder, array $filters): string {
    $nextOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $params    = array_filter(array_merge($filters, ['sort' => $col, 'order' => $nextOrder]), fn($v) => $v !== '');
    $arrow     = '';
    if ($currentSort === $col) $arrow = $currentOrder === 'ASC' ? ' <span style="font-size:.8em">▲</span>' : ' <span style="font-size:.8em">▼</span>';
    else $arrow = ' <span style="font-size:.8em;opacity:.5">⇅</span>';
    return '<a href="?' . http_build_query($params) . '" style="display:block;padding:12px 16px;color:inherit;text-decoration:none;white-space:nowrap">' . htmlspecialchars($label) . $arrow . '</a>';
}
?>
<div class="page-header">
  <h1>Transactions</h1>
  <span style="font-size:.85rem;color:var(--color-gray-500);align-self:center">1 row per invoice — all purchased products shown per row</span>
</div>

<div class="card">
  <div class="card-body" style="padding:16px">
    <div class="filter-bar">
      <div class="search-bar" style="flex:1;max-width:300px">
        <?= icon('search', 16) ?> <input type="text" placeholder="Product, SKU, invoice #, client..." name="search"
          value="<?= htmlspecialchars($filters['search'] ?? '') ?>" onchange="this.form.submit()" form="txFilterForm" style="width:100%">
      </div>
      <form id="txFilterForm" method="GET" class="d-flex gap-8">
        <?php if (!empty($filters['sort'])):  ?><input type="hidden" name="sort"  value="<?= htmlspecialchars($filters['sort'])  ?>"><?php endif; ?>
        <?php if (!empty($filters['order'])): ?><input type="hidden" name="order" value="<?= htmlspecialchars($filters['order']) ?>"><?php endif; ?>
        <input type="date" name="date_from" class="form-input" style="height:38px;width:140px"
          value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" onchange="this.form.submit()">
        <input type="date" name="date_to" class="form-input" style="height:38px;width:140px"
          value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" onchange="this.form.submit()">
        <?php if (!empty(array_filter($filters))): ?>
          <a href="<?= app_url('/transactions') ?>" class="btn btn-secondary btn-sm" style="height:38px;display:flex;align-items:center">Clear</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th style="padding:0"><?= txSortLink('i.invoice_number', 'Invoice #', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= txSortLink('i.invoice_date',   'Date',      $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= txSortLink('c.full_name',      'Client',    $sort, $order, $filters) ?></th>
          <th>Products</th>
          <th style="padding:0"><?= txSortLink('i.total_amount',   'Total',     $sort, $order, $filters) ?></th>
          <th>Payment</th>
          <th style="padding:0"><?= txSortLink('i.payment_status', 'Status',    $sort, $order, $filters) ?></th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transactions as $tx):
          $statusCls = match($tx['payment_status']) {
            'fully_paid'     => 'badge-success',
            'partially_paid' => 'badge-warning',
            default          => 'badge-danger',
          };
          $methodBadge = match($tx['payment_mode'] ?? '') {
            'cash'  => '<span class="badge" style="background:#dcfce7;color:#166534">Cash</span>',
            'bdo'   => '<span class="badge" style="background:#fef9c3;color:#854d0e">BDO</span>',
            'gcash' => '<span class="badge" style="background:#dbeafe;color:#1e40af">GCash</span>',
            default => '<span class="text-muted" style="font-size:.8rem">—</span>',
          };
          $productLines = array_filter(array_map('trim', explode(',', $tx['products_list'] ?? '')));
        ?>
        <tr style="cursor:pointer" onclick="window.open(appPath('/api/v1/invoices/<?= $tx['id'] ?>/print'), '_blank')">
          <td><strong><?= htmlspecialchars($tx['invoice_number']) ?></strong></td>
          <td style="white-space:nowrap">
            <div style="font-size:.78rem;font-weight:600"><?= date('h:i A', strtotime($tx['invoice_date'])) ?></div>
            <div style="font-size:.75rem;color:var(--color-gray-500)"><?= date('M d, Y', strtotime($tx['invoice_date'])) ?></div>
          </td>
          <td>
            <div><?= htmlspecialchars($tx['client_name'] ?? 'Walk-in') ?></div>
            <?php if (!empty($tx['client_email'])): ?>
              <div style="font-size:.75rem;color:var(--color-gray-500)"><?= htmlspecialchars($tx['client_email']) ?></div>
            <?php endif; ?>
          </td>
          <td style="max-width:260px">
            <?php if (!empty($productLines)): ?>
              <?php foreach ($productLines as $line): ?>
                <div style="font-size:.82rem;line-height:1.6"><?= htmlspecialchars($line) ?></div>
              <?php endforeach; ?>
            <?php else: ?>
              <span class="text-muted" style="font-size:.8rem">—</span>
            <?php endif; ?>
            <?php if ((int)($tx['item_count'] ?? 0) > 1): ?>
              <div style="font-size:.72rem;color:var(--color-gray-400);margin-top:2px"><?= (int)$tx['item_count'] ?> items · <?= (int)$tx['total_qty'] ?> units</div>
            <?php endif; ?>
          </td>
          <td><strong>₱<?= number_format((float)$tx['total_amount'], 2) ?></strong></td>
          <td><?= $methodBadge ?></td>
          <td><span class="badge <?= $statusCls ?>"><?= str_replace('_', ' ', ucfirst($tx['payment_status'])) ?></span></td>
          <td onclick="event.stopPropagation()" style="white-space:nowrap">
            <a href="<?= app_url('/api/v1/invoices/' . $tx['id'] . '/print') ?>?download=1" target="_blank" class="icon-btn" title="Download PDF"><?= icon('download', 15) ?></a>
            <button class="icon-btn" onclick="viewTxPayHistory(<?= $tx['id'] ?>, '<?= htmlspecialchars($tx['invoice_number']) ?>')" title="Payment History"><?= icon('history', 15) ?></button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($transactions)): ?>
          <tr><td colspan="8" class="text-center text-muted" style="padding:48px">No transactions found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
    $txPqFilters = array_filter($filters ?? [], fn($v) => $v !== '');
    unset($txPqFilters['page'], $txPqFilters['per_page']);
    echo renderPagination($pagination, $txPqFilters);
  ?>
</div>

<!-- TX Payment History Modal -->
<div class="modal-overlay" id="txPayHistModal">
  <div class="modal-content" style="max-width:600px">
    <div class="modal-header">
      <h2 class="modal-title">Payment History &mdash; <span id="txPayHistInvNum"></span></h2>
      <button class="modal-close" onclick="document.getElementById('txPayHistModal').classList.remove('show')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body" style="padding:0">
      <div id="txPayHistLoading" style="padding:32px;text-align:center"><span class="spinner"></span></div>
      <table class="data-table" id="txPayHistTable" style="display:none">
        <thead>
          <tr>
            <th>#</th><th>Date</th><th>Mode</th><th>Reference</th>
            <th style="text-align:right">Amount</th><th>Recorded By</th>
          </tr>
        </thead>
        <tbody id="txPayHistBody"></tbody>
      </table>
      <p id="txPayHistEmpty" style="display:none;padding:32px;text-align:center;color:var(--color-gray-500)">No payment records found.</p>
    </div>
  </div>
</div>
<script>
async function viewTxPayHistory(invoiceId, invoiceNum) {
  document.getElementById('txPayHistInvNum').textContent    = invoiceNum;
  document.getElementById('txPayHistLoading').style.display = '';
  document.getElementById('txPayHistTable').style.display   = 'none';
  document.getElementById('txPayHistEmpty').style.display   = 'none';
  document.getElementById('txPayHistModal').classList.add('show');
  try {
    const res      = await fetch('/api/v1/invoices/' + invoiceId);
    const data     = await res.json();
    const payments = data.data?.payments ?? [];
    document.getElementById('txPayHistLoading').style.display = 'none';
    if (!payments.length) { document.getElementById('txPayHistEmpty').style.display = ''; return; }
    const modeLabel = { cash: 'Cash', bdo: 'Bank Transfer', gcash: 'GCash' };
    const modeBadge  = {
      cash:  '<span class="badge" style="background:#dcfce7;color:#166534">Cash</span>',
      bdo:   '<span class="badge" style="background:#fef9c3;color:#854d0e">Bank Transfer</span>',
      gcash: '<span class="badge" style="background:#dbeafe;color:#1e40af">GCash</span>',
    };
    document.getElementById('txPayHistBody').innerHTML = payments.map((p, i) => {
      const d = new Date(p.payment_date.replace(' ', 'T'));
      const time = d.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:true});
      const date = d.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
      const badge = modeBadge[p.payment_mode] ?? `<span class="badge badge-secondary">${modeLabel[p.payment_mode] ?? p.payment_mode ?? '&mdash;'}</span>`;
      return `
      <tr>
        <td>${i + 1}</td>
        <td style="white-space:nowrap">
          <div style="font-size:.78rem;font-weight:600">${time}</div>
          <div style="font-size:.75rem;color:var(--color-gray-500)">${date}</div>
        </td>
        <td>${badge}</td>
        <td style="font-size:.82rem">${p.reference_number || '<span style="color:var(--color-gray-400)">&mdash;</span>'}</td>
        <td style="text-align:right;font-weight:600">&#8369;${parseFloat(p.payment_amount).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
        <td style="font-size:.82rem">${p.recorded_by_name ?? '&mdash;'}</td>
      </tr>`;
    }).join('');
    document.getElementById('txPayHistTable').style.display = '';
  } catch (e) {
    document.getElementById('txPayHistLoading').style.display = 'none';
    document.getElementById('txPayHistEmpty').textContent = 'Failed to load payment history.';
    document.getElementById('txPayHistEmpty').style.display = '';
  }
}
</script>

<?php
$content = ob_get_clean();
$title   = 'Transactions | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
