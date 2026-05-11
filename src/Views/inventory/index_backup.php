<?php ob_start(); ?>
<?php
$search   = $filters['search']   ?? '';
$status   = $filters['status']   ?? '';
$typeId   = $filters['type_id']  ?? '';
$colorId  = $filters['color_id'] ?? '';
$sizeId   = $filters['size_id']  ?? '';
$sort     = $filters['sort']     ?? 'i.stock_status';
$order    = strtoupper($filters['order'] ?? 'ASC');
$lowCount = is_array($low_stock ?? null) ? count($low_stock) : 0;

function invySortLink(string $col, string $label, string $currentSort, string $currentOrder, array $filters): string {
    $nextOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $params    = array_filter(array_merge($filters, ['sort' => $col, 'order' => $nextOrder]), fn($v) => $v !== '');
    $arrow = '';
    if ($currentSort === $col) $arrow = $currentOrder === 'ASC' ? ' <span style="font-size:.8em">â–²</span>' : ' <span style="font-size:.8em">â–¼</span>';
    else $arrow = ' <span style="font-size:.8em;opacity:.5">â‡…</span>';
    return '<a href="?' . http_build_query($params) . '" style="display:block;padding:12px 16px;color:inherit;text-decoration:none;white-space:nowrap">' . htmlspecialchars($label) . $arrow . '</a>';
}
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
    <div class="stats-change text-muted">Total stock products</div>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:16px">
    <div class="filter-bar" style="flex-wrap:wrap;gap:8px">
      <div class="search-bar" style="flex:1;max-width:280px">
        <?= icon('search', 16) ?> <input type="text" id="searchInput" placeholder="Search stock products..." value="<?= htmlspecialchars($search) ?>" oninput="debouncedSearch()" style="width:100%">
      </div>
      <select id="statusFilter" class="form-select" style="width:160px;height:38px" onchange="applyFilters()">
        <option value="">All Status</option>
        <option value="in_stock"     <?= $status === 'in_stock'     ? 'selected' : '' ?>>In Stock</option>
        <option value="low_stock"    <?= $status === 'low_stock'    ? 'selected' : '' ?>>Low Stock</option>
        <option value="out_of_stock" <?= $status === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
      </select>
      <button class="btn btn-secondary btn-sm" onclick="resetInvFilters()" style="height:38px">Reset</button>
    </div>
  </div>

  <div id="inventoryResultsContainer">
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr style="cursor:pointer">
          <th style="padding:0"><?= invySortLink('sp.code',  'Code',  $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= invySortLink('sp.name',  'Name',  $sort, $order, $filters) ?></th>
          <th>Type</th><th>Color</th><th>Size</th>
          <th style="padding:0"><?= invySortLink('i.quantity_on_hand', 'Qty', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= invySortLink('i.stock_status',     'Status', $sort, $order, $filters) ?></th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($inventory as $item): ?>
        <?php
          $statusCls = match($item['stock_status'] ?? '') {
            'in_stock'    => 'badge-success',
            'low_stock'   => 'badge-warning',
            default       => 'badge-danger',
          };
        ?>
        <tr>
          <td><code style="font-size:.8rem"><?= htmlspecialchars($item['code'] ?? '-') ?></code></td>
          <td><strong><?= htmlspecialchars($item['name'] ?? '') ?></strong></td>
          <td><?= htmlspecialchars($item['type_name']  ?? '-') ?></td>
          <td><?= htmlspecialchars($item['color_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($item['size_name']  ?? '-') ?></td>
          <td><span class="badge <?= $statusCls ?>"><?= (int)$item['quantity_on_hand'] ?></span></td>
          <td><span class="badge <?= $statusCls ?>"><?= str_replace('_', ' ', ucfirst($item['stock_status'] ?? '')) ?></span></td>
          <td>
            <button class="icon-btn" onclick="openRestock(<?= (int)($item['stock_product_id'] ?? 0) ?>, '<?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES) ?>')" title="Restock">ðŸ”„</button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($inventory)): ?>
          <tr><td colspan="8" class="text-center text-muted" style="padding:48px">No inventory records found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($pagination) && $pagination['last_page'] > 1): ?>
  <?php
    $invQuery = array_filter(['search' => $search, 'status' => $status, 'sort' => $sort, 'order' => $order], fn($v) => $v !== '');
    $invBase  = $invQuery ? '?' . http_build_query($invQuery) . '&page=' : '?page=';
  ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
      <a href="<?= $invBase . $i ?>" class="page-link <?= $pagination['current_page'] == $i ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  </div><!-- /inventoryResultsContainer -->
</div>

<div class="card" style="margin-top:20px">
  <div class="card-header"><h3 class="card-title">Low Stock Items</h3></div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr><th>Code</th><th>Name</th><th>Type</th><th>Color</th><th>Size</th><th>Qty</th><th>Alert Threshold</th></tr>
      </thead>
      <tbody>
        <?php foreach (($low_stock ?? []) as $ls): ?>
        <tr>
          <td><code style="font-size:.8rem"><?= htmlspecialchars($ls['code'] ?? '-') ?></code></td>
          <td><?= htmlspecialchars($ls['name'] ?? '') ?></td>
          <td><?= htmlspecialchars($ls['type_name']  ?? '-') ?></td>
          <td><?= htmlspecialchars($ls['color_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($ls['size_name']  ?? '-') ?></td>
          <td><span class="badge badge-danger"><?= (int)$ls['quantity_on_hand'] ?></span></td>
          <td><?= (int)($ls['low_stock_alert'] ?? 0) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($low_stock)): ?>
          <tr><td colspan="7" class="text-center text-muted" style="padding:36px">No low stock items</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Restock Modal â€” now keyed by stock product -->
<div class="modal-overlay" id="restockModal">
  <div class="modal-content" style="max-width:520px">
    <div class="modal-header">
      <h2 class="modal-title">Create Restock Order</h2>
      <button class="modal-close" onclick="closeModal('restockModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <div id="restockItemsContainer">
        <!-- rows injected by JS -->
      </div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="addRestockRow()" style="margin-top:6px">+ Add Item</button>
      <hr style="margin:14px 0">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Supplier Name</label>
          <input type="text" id="restockSupplier" class="form-input" placeholder="Supplier name">
        </div>
        <div class="form-group">
          <label class="form-label">Delivery Date</label>
          <input type="date" id="restockDate" class="form-input">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea id="restockNotes" class="form-textarea" style="min-height:60px"></textarea>
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
let _invStockProducts = <?= json_encode(array_map(fn($i) => ['id' => $i['stock_product_id'] ?? 0, 'code' => $i['code'] ?? '', 'name' => $i['name'] ?? ''], $inventory ?? [])) ?>;

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
  const url = new URL(window.location.href);
  if (url.searchParams.get('sort'))  params.set('sort',  url.searchParams.get('sort'));
  if (url.searchParams.get('order')) params.set('order', url.searchParams.get('order'));
  const qs      = params.toString();
  const invBase = '<?= htmlspecialchars(app_url('/inventory')) ?>';
  const pageUrl = window.location.origin + invBase + (qs ? '?' + qs : '');
  history.pushState({}, '', pageUrl);
  const container = document.getElementById('inventoryResultsContainer');
  if (container) container.style.opacity = '0.5';
  fetch(pageUrl).then(r => r.text()).then(html => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const el  = doc.getElementById('inventoryResultsContainer');
    if (el && container) { container.innerHTML = el.innerHTML; container.style.opacity = '1'; }
  }).catch(() => { if (container) container.style.opacity = '1'; });
}

function resetInvFilters() {
  document.getElementById('searchInput').value = '';
  document.getElementById('statusFilter').value = '';
  applyFilters();
}

// ---- Restock ----

function buildRestockItemOptions(selectedId) {
  return '<option value="">-- Select Stock Product --</option>'
    + _invStockProducts.map(sp => `<option value="${sp.id}" ${sp.id === selectedId ? 'selected' : ''}>[${sp.code}] ${sp.name.replace(/"/g,'&quot;')}</option>`).join('');
}

function addRestockRow(stockProductId = 0, qty = 1) {
  const container = document.getElementById('restockItemsContainer');
  const div       = document.createElement('div');
  div.className   = 'restock-row';
  div.style.cssText = 'display:grid;grid-template-columns:1fr 100px 32px;gap:6px;align-items:center;margin-bottom:6px';

  const sel = document.createElement('select');
  sel.className = 'form-select';
  sel.style.height = '34px';
  sel.innerHTML = buildRestockItemOptions(stockProductId);

  const qtyInput = document.createElement('input');
  qtyInput.type        = 'number';
  qtyInput.className   = 'form-input';
  qtyInput.style.height = '34px';
  qtyInput.placeholder = 'Qty';
  qtyInput.min         = '1';
  qtyInput.value       = qty;

  const del = document.createElement('button');
  del.type      = 'button';
  del.className = 'icon-btn danger';
  del.title     = 'Remove';
  del.innerHTML = 'âœ•';
  del.onclick   = () => div.remove();

  div.appendChild(sel);
  div.appendChild(qtyInput);
  div.appendChild(del);
  container.appendChild(div);
}

function openRestock(stockProductId = 0, name = '') {
  document.getElementById('restockItemsContainer').innerHTML = '';
  addRestockRow(stockProductId);
  document.getElementById('restockSupplier').value = '';
  document.getElementById('restockDate').value     = '';
  document.getElementById('restockNotes').value    = '';
  openModal('restockModal');
}

async function submitRestock() {
  const rows = document.querySelectorAll('.restock-row');
  const items = [];
  rows.forEach(row => {
    const spId = row.querySelector('select')?.value;
    const qty  = parseInt(row.querySelector('input')?.value, 10);
    if (spId && qty > 0) items.push({ stock_product_id: parseInt(spId, 10), quantity_requested: qty });
  });
  if (items.length === 0) return showToast('Add at least one item', 'error');

  const btn = document.getElementById('restockBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving...';

  const payload = {
    supplier_name: document.getElementById('restockSupplier').value,
    delivery_date: document.getElementById('restockDate').value,
    notes:         document.getElementById('restockNotes').value,
    items,
  };

  try {
    const res  = await fetch('<?= htmlspecialchars(app_url('/api/v1/inventory/restock')) ?>', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body:    JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) { showToast('Restock created', 'success'); closeModal('restockModal'); setTimeout(() => location.reload(), 800); }
    else showToast(data.message || 'Failed', 'error');
  } catch (e) { showToast('Network error', 'error'); }

  btn.disabled = false; btn.innerHTML = 'Create Restock';
}
</script>

<?php
$content = ob_get_clean();
$title   = 'Inventory | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
