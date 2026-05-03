<?php ob_start(); ?>
<?php
$search = $filters['search'] ?? '';
$status = $filters['status'] ?? '';
$lowCount = is_array($low_stock ?? null) ? count($low_stock) : 0;
?>
<div class="page-header">
  <h1>Inventory</h1>
  <button class="btn btn-primary" onclick="openRestock()">+ Create Restock</button>
</div>

<div class="stats-grid">
  <div class="stats-card">
    <div class="stats-header"><span class="stats-label">Low Stock Items</span><span class="stats-icon"><?= icon('alert', 18) ?></span></div>
    <div class="stats-value" style="color:var(--color-warning)"><?= $lowCount ?></div>
    <div class="stats-change text-muted">Needs attention</div>
  </div>
  <div class="stats-card">
    <div class="stats-header"><span class="stats-label">Inventory Count</span><span class="stats-icon"><?= icon('package', 18) ?></span></div>
    <div class="stats-value"><?= $pagination['total'] ?? 0 ?></div>
    <div class="stats-change text-muted">Total products</div>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:16px">
    <div class="filter-bar">
      <div class="search-bar" style="flex:1;max-width:280px">
        <?= icon('search', 16) ?> <input type="text" id="searchInput" placeholder="Search inventory..." value="<?= htmlspecialchars($search) ?>" oninput="debouncedSearch()" style="width:100%">
      </div>
      <select id="statusFilter" class="form-select" style="width:170px;height:38px" onchange="applyFilters()">
        <option value="">All Status</option>
        <option value="in_stock" <?= $status === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
        <option value="low_stock" <?= $status === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
        <option value="out_of_stock" <?= $status === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
      </select>
    </div>
  </div>

  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr><th>Product</th><th>Category</th><th>Qty</th><th>Status</th><th>Last Updated</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($inventory as $item): ?>
        <?php
          $statusCls = match($item['stock_status']) {
            'in_stock' => 'badge-success',
            'low_stock' => 'badge-warning',
            default => 'badge-danger',
          };
        ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars($item['name'] ?? '') ?></strong><br>
            <span class="text-muted" style="font-size:.8rem">SKU: <?= htmlspecialchars($item['sku'] ?? '-') ?></span>
          </td>
          <td><?= htmlspecialchars($item['category_name'] ?? '-') ?></td>
          <td><span class="badge <?= $statusCls ?>"><?= (int)$item['quantity_on_hand'] ?></span></td>
          <td><span class="badge <?= $statusCls ?>"><?= str_replace('_', ' ', ucfirst($item['stock_status'])) ?></span></td>
          <td><?= !empty($item['last_updated']) ? date('M d, Y H:i', strtotime($item['last_updated'])) : '-' ?></td>
          <td>
            <button class="icon-btn" onclick="openRestock(<?= (int)$item['product_id'] ?>, '<?= htmlspecialchars($item['name'] ?? '') ?>')" title="Restock">🔄</button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($inventory)): ?>
          <tr><td colspan="6" class="text-center text-muted" style="padding:48px">No inventory records found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($pagination) && $pagination['last_page'] > 1): ?>
  <?php
    $invQuery = array_filter(['search' => $search, 'status' => $status], fn($v) => $v !== '');
    $invBase  = $invQuery ? '?' . http_build_query($invQuery) . '&page=' : '?page=';
  ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
      <a href="<?= $invBase . $i ?>" class="page-link <?= $pagination['current_page'] == $i ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<div class="card" style="margin-top:20px">
  <div class="card-header"><h3 class="card-title">Low Stock Items</h3></div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr><th>Product</th><th>SKU</th><th>Qty</th><th>Alert Threshold</th></tr>
      </thead>
      <tbody>
        <?php foreach (($low_stock ?? []) as $ls): ?>
        <tr>
          <td><?= htmlspecialchars($ls['name'] ?? '') ?></td>
          <td><?= htmlspecialchars($ls['sku'] ?? '') ?></td>
          <td><span class="badge badge-danger"><?= (int)$ls['quantity_on_hand'] ?></span></td>
          <td><?= (int)($ls['low_stock_alert'] ?? 0) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($low_stock)): ?>
          <tr><td colspan="4" class="text-center text-muted" style="padding:36px">No low stock items</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Restock Modal -->
<div class="modal-overlay" id="restockModal">
  <div class="modal-content" style="max-width:520px">
    <div class="modal-header">
      <h2 class="modal-title">Create Restock Order</h2>
      <button class="modal-close" onclick="closeModal('restockModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Product <span class="required">*</span></label>
        <select id="restockProductSelect" class="form-select">
          <option value="">Select a product</option>
          <?php foreach ($inventory as $item): ?>
            <option value="<?= (int)$item['product_id'] ?>"><?= htmlspecialchars($item['name'] ?? '') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Quantity Requested <span class="required">*</span></label>
        <input type="number" id="restockQty" class="form-input" min="1" value="1">
      </div>
      <div class="form-group">
        <label class="form-label">Supplier Name</label>
        <input type="text" id="restockSupplier" class="form-input" placeholder="Supplier name">
      </div>
      <div class="form-group">
        <label class="form-label">Delivery Date</label>
        <input type="date" id="restockDate" class="form-input">
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea id="restockNotes" class="form-textarea" style="min-height:70px"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('restockModal')">Cancel</button>
      <button class="btn btn-primary" onclick="submitRestock()" id="restockBtn">Create Restock</button>
    </div>
  </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
let searchTimer = null;

function debouncedSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(applyFilters, 400);
}

function applyFilters() {
  const search = document.getElementById('searchInput').value;
  const status = document.getElementById('statusFilter').value;
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (status) params.set('status', status);
  window.location = '?' + params.toString();
}

function openRestock(productId = null, name = '') {
  const select = document.getElementById('restockProductSelect');
  if (productId) {
    select.value = String(productId);
    select.disabled = true;
  } else {
    select.value = '';
    select.disabled = false;
  }
  document.getElementById('restockQty').value = 1;
  document.getElementById('restockSupplier').value = '';
  document.getElementById('restockDate').value = '';
  document.getElementById('restockNotes').value = '';
  openModal('restockModal');
}

async function submitRestock() {
  const productId = document.getElementById('restockProductSelect').value;
  const qty = parseInt(document.getElementById('restockQty').value, 10);
  if (!productId || qty <= 0) return showToast('Select a product and quantity', 'error');

  const btn = document.getElementById('restockBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving...';

  const payload = {
    supplier_name: document.getElementById('restockSupplier').value,
    delivery_date: document.getElementById('restockDate').value,
    notes: document.getElementById('restockNotes').value,
    items: [
      { product_id: parseInt(productId, 10), quantity_requested: qty }
    ]
  };

  try {
    const res = await fetch('/api/v1/inventory/restock', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) { showToast('Restock created', 'success'); closeModal('restockModal'); setTimeout(() => location.reload(), 800); }
    else showToast(data.message || 'Failed', 'error');
  } catch (e) {
    showToast('Network error', 'error');
  }

  btn.disabled = false; btn.innerHTML = 'Create Restock';
}
</script>

<?php
$content = ob_get_clean();
$title   = 'Inventory | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
