<?php ob_start(); ?>
<?php
$search    = $filters['search']   ?? '';
$status    = $filters['status']   ?? '';
$sort      = $filters['sort']     ?? 'i.stock_status';
$order     = strtoupper($filters['order'] ?? 'ASC');
$lowCount  = is_array($low_stock ?? null) ? count($low_stock) : 0;
$activeTab = $active_tab ?? 'inventory';

function invySortLink2(string $col, string $label, string $currentSort, string $currentOrder, array $filters): string {
    $nextOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $params    = array_filter(array_merge($filters, ['sort' => $col, 'order' => $nextOrder, 'tab' => 'inventory']), fn($v) => $v !== '');
    $arrow = '';
    if ($currentSort === $col) $arrow = $currentOrder === 'ASC' ? ' <span style="font-size:.8em">▲</span>' : ' <span style="font-size:.8em">▼</span>';
    else $arrow = ' <span style="font-size:.8em;opacity:.5">⇅</span>';
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

<!-- Tabs -->
<div style="display:flex;gap:0;border-bottom:2px solid var(--color-gray-100);margin-bottom:16px">
  <button id="tab-inv-btn" onclick="switchTab('inventory')"
    style="padding:10px 20px;border:none;background:none;cursor:pointer;font-weight:600;font-size:.9rem;border-bottom:2px solid <?= $activeTab === 'inventory' ? 'var(--color-primary)' : 'transparent' ?>;color:<?= $activeTab === 'inventory' ? 'var(--color-primary)' : 'var(--color-gray-500)' ?>;margin-bottom:-2px">
    Inventory
  </button>
  <button id="tab-hist-btn" onclick="switchTab('history')"
    style="padding:10px 20px;border:none;background:none;cursor:pointer;font-weight:600;font-size:.9rem;border-bottom:2px solid <?= $activeTab === 'history' ? 'var(--color-primary)' : 'transparent' ?>;color:<?= $activeTab === 'history' ? 'var(--color-primary)' : 'var(--color-gray-500)' ?>;margin-bottom:-2px">
    Inventory History
  </button>
</div>

<!-- ===== INVENTORY TAB ===== -->
<div id="tab-inventory" style="display:<?= $activeTab === 'inventory' ? 'block' : 'none' ?>">
  <!-- Floating Restock Selected bar -->
  <div id="restockSelectionBar" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:var(--color-primary);color:#fff;padding:12px 24px;border-radius:30px;box-shadow:0 4px 16px rgba(0,0,0,.2);z-index:1000;display:none;align-items:center;gap:12px">
    <span id="restockSelectionCount">0 selected</span>
    <button class="btn btn-sm" style="background:#fff;color:var(--color-primary);font-weight:600" onclick="openRestockFromSelection()">Restock Selected</button>
    <button style="background:none;border:none;color:#fff;cursor:pointer;font-size:1.1rem" onclick="clearSelection()">✕</button>
  </div>

  <div class="card">
    <div class="card-body" style="padding:16px">
      <div class="filter-bar" style="flex-wrap:wrap;gap:8px">
        <div class="search-bar" style="flex:1;max-width:280px">
          <?= icon('search', 16) ?> <input type="text" id="invSearchInput" placeholder="Search stock products..." value="<?= htmlspecialchars($search) ?>" oninput="debouncedSearch()" style="width:100%">
        </div>
        <select id="invStatusFilter" class="form-select" style="width:160px;height:38px" onchange="applyInvFilters()">
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
            <th style="width:38px"><input type="checkbox" id="selectAllInv" onchange="toggleSelectAll(this)" title="Select all"></th>
            <th style="padding:0"><?= invySortLink2('sp.code',  'Code',   $sort, $order, $filters) ?></th>
            <th style="padding:0"><?= invySortLink2('sp.name',  'Name',   $sort, $order, $filters) ?></th>
            <th>Type</th><th>Color</th><th>Size</th>
            <th style="padding:0"><?= invySortLink2('i.quantity_on_hand', 'Qty',    $sort, $order, $filters) ?></th>
            <th style="padding:0"><?= invySortLink2('i.stock_status',     'Status', $sort, $order, $filters) ?></th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="invTableBody">
          <?php foreach ($inventory as $item): ?>
          <?php
            $statusCls = match($item['stock_status'] ?? '') {
              'in_stock'    => 'badge-success',
              'low_stock'   => 'badge-warning',
              default       => 'badge-danger',
            };
            $statusLbl = str_replace('_', ' ', ucfirst($item['stock_status'] ?? 'out of stock'));
            $spId = (int)($item['stock_product_id'] ?? 0);
          ?>
          <tr>
            <td><input type="checkbox" class="inv-select" value="<?= $spId ?>"
              data-name="<?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES) ?>"
              onchange="onRowSelect()"></td>
            <td><code style="font-size:.8rem"><?= htmlspecialchars($item['code'] ?? '-') ?></code></td>
            <td><strong><?= htmlspecialchars($item['name'] ?? '') ?></strong></td>
            <td><?= htmlspecialchars($item['type_name']  ?? '-') ?></td>
            <td><?= htmlspecialchars($item['color_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($item['size_name']  ?? '-') ?></td>
            <td><span class="badge <?= $statusCls ?>"><?= (int)$item['quantity_on_hand'] ?></span></td>
            <td><span class="badge <?= $statusCls ?>"><?= $statusLbl ?></span></td>
            <td>
              <button class="icon-btn" onclick="openRestock(<?= $spId ?>, '<?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES) ?>')" title="Restock">🔄</button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($inventory)): ?>
            <tr><td colspan="9" class="text-center text-muted" style="padding:48px">No inventory records found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if (!empty($pagination) && $pagination['last_page'] > 1): ?>
    <?php
      $invQuery = array_filter(['search' => $search, 'status' => $status, 'sort' => $sort, 'order' => $order, 'tab' => 'inventory'], fn($v) => $v !== '');
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

  <!-- Restock Orders Table -->
  <div class="card" style="margin-top:20px">
    <div class="card-header"><h3 class="card-title">Recent Restock Orders</h3></div>
    <?php if (!empty($restock_orders)): ?>
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Date</th>
            <th>Supplier</th>
            <th>Items</th>
            <th>Delivery Status</th>
            <th>Created By</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($restock_orders as $ro): ?>
          <?php
            $dsCls = match($ro['delivery_status'] ?? '') {
              'delivered'   => 'badge-success',
              'ordered'     => 'badge-primary',
              'incomplete'  => 'badge-warning',
              'problematic' => 'badge-danger',
              default       => 'badge-gray',
            };
          ?>
          <tr>
            <td><code style="font-size:.8rem"><?= htmlspecialchars($ro['order_number'] ?? '-') ?></code></td>
            <td style="font-size:.85rem"><?= !empty($ro['order_date']) ? date('M d, Y', strtotime($ro['order_date'])) : '-' ?></td>
            <td><?= htmlspecialchars($ro['supplier_name'] ?? '-') ?></td>
            <td><span class="badge badge-gray"><?= (int)($ro['items_count'] ?? 0) ?></span></td>
            <td><span class="badge <?= $dsCls ?>"><?= ucfirst($ro['delivery_status'] ?? '-') ?></span></td>
            <td style="font-size:.85rem"><?= htmlspecialchars($ro['created_by_name'] ?? '-') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="card-body"><p class="text-muted text-center" style="padding:24px 0">No restock orders yet.</p></div>
    <?php endif; ?>
  </div>
</div><!-- /tab-inventory -->

<!-- ===== INVENTORY HISTORY TAB ===== -->
<div id="tab-history" style="display:<?= $activeTab === 'history' ? 'block' : 'none' ?>">
  <div class="card">
    <div class="card-body" style="padding:16px">
      <div class="filter-bar" style="flex-wrap:wrap;gap:8px">
        <div class="search-bar" style="flex:1;max-width:280px">
          <?= icon('search', 16) ?> <input type="text" id="histSearchInput" placeholder="Search by code or name..." value="<?= htmlspecialchars($hist_filters['search'] ?? '') ?>" oninput="debouncedHistSearch()" style="width:100%">
        </div>
        <select id="histTypeFilter" class="form-select" style="width:160px;height:38px" onchange="applyHistFilters()">
          <option value="">All Movement Types</option>
          <option value="sale"        <?= ($hist_filters['movement_type'] ?? '') === 'sale'        ? 'selected' : '' ?>>Sale</option>
          <option value="restock"     <?= ($hist_filters['movement_type'] ?? '') === 'restock'     ? 'selected' : '' ?>>Restock</option>
          <option value="adjustment"  <?= ($hist_filters['movement_type'] ?? '') === 'adjustment'  ? 'selected' : '' ?>>Adjustment</option>
          <option value="return"      <?= ($hist_filters['movement_type'] ?? '') === 'return'      ? 'selected' : '' ?>>Return</option>
        </select>
        <button class="btn btn-secondary btn-sm" onclick="resetHistFilters()" style="height:38px">Reset</button>
      </div>
    </div>
    <div id="historyResultsContainer">
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Stock Product</th>
            <th>Type</th><th>Color</th><th>Size</th>
            <th>Movement Type</th>
            <th>Change</th>
            <th>Notes</th>
            <th>By</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history ?? [] as $h): ?>
          <?php
            $qty = (int)$h['quantity_change'];
            $mvCls = $qty > 0 ? 'badge-success' : 'badge-danger';
          ?>
          <tr>
            <td style="white-space:nowrap;font-size:.82rem"><?= !empty($h['created_at']) ? date('M d, Y H:i', strtotime($h['created_at'])) : '-' ?></td>
            <td>
              <code style="font-size:.78rem"><?= htmlspecialchars($h['sp_code'] ?? '-') ?></code>
              <?php if (!empty($h['sp_name'])): ?>
                <br><span style="font-size:.8rem"><?= htmlspecialchars($h['sp_name']) ?></span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($h['type_name']  ?? '-') ?></td>
            <td><?= htmlspecialchars($h['color_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($h['size_name']  ?? '-') ?></td>
            <td><span class="badge badge-gray" style="font-size:.75rem"><?= ucfirst($h['movement_type'] ?? '-') ?></span></td>
            <td><span class="badge <?= $mvCls ?>"><?= $qty > 0 ? '+' . $qty : $qty ?></span></td>
            <td style="font-size:.82rem;max-width:180px;word-break:break-word"><?= htmlspecialchars($h['reason'] ?? '-') ?></td>
            <td style="font-size:.82rem"><?= htmlspecialchars($h['created_by_name'] ?? '-') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($history)): ?>
            <tr><td colspan="9" class="text-center text-muted" style="padding:48px">No inventory history found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if (!empty($hist_pagination) && $hist_pagination['last_page'] > 1): ?>
    <?php
      $hq = array_filter(['history_search' => $hist_filters['search'] ?? '', 'movement_type' => $hist_filters['movement_type'] ?? '', 'tab' => 'history'], fn($v) => $v !== '');
      $hb = $hq ? '?' . http_build_query($hq) . '&history_page=' : '?tab=history&history_page=';
    ?>
    <div class="pagination">
      <?php for ($i = 1; $i <= $hist_pagination['last_page']; $i++): ?>
        <a href="<?= $hb . $i ?>" class="page-link <?= $hist_pagination['current_page'] == $i ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
    </div><!-- /historyResultsContainer -->
  </div>
</div><!-- /tab-history -->

<!-- ===== RESTOCK MODAL (redesigned) ===== -->
<div class="modal-overlay" id="restockModal">
  <div class="modal-content" style="max-width:760px;max-height:90vh;overflow-y:auto">
    <div class="modal-header" style="position:sticky;top:0;background:#fff;z-index:1">
      <h2 class="modal-title">Create Restock Order</h2>
      <button class="modal-close" onclick="closeModal('restockModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">

      <!-- SP Picker Section -->
      <div style="margin-bottom:16px">
        <div style="font-weight:600;font-size:.875rem;margin-bottom:8px">Select Stock Products to Restock</div>

        <!-- Search + Filters -->
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">
          <input type="text" id="restockSpSearch" class="form-input" placeholder="Search code or name..." style="flex:1;min-width:160px;height:34px" oninput="filterRestockPicker()">
          <select id="restockSpTypeFilter" class="form-select" style="width:130px;height:34px" onchange="filterRestockPicker()">
            <option value="">All Types</option>
            <?php foreach ($types ?? [] as $t): ?>
            <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <select id="restockSpColorFilter" class="form-select" style="width:120px;height:34px" onchange="filterRestockPicker()">
            <option value="">All Colors</option>
            <?php foreach ($colors ?? [] as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <select id="restockSpSizeFilter" class="form-select" style="width:110px;height:34px" onchange="filterRestockPicker()">
            <option value="">All Sizes</option>
            <?php foreach ($sizes ?? [] as $sz): ?>
            <option value="<?= (int)$sz['id'] ?>"><?= htmlspecialchars($sz['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- SP Picker Table -->
        <div style="max-height:280px;overflow-y:auto;border:1px solid var(--color-gray-100);border-radius:6px">
          <table class="data-table" style="margin:0;font-size:.82rem">
            <thead style="position:sticky;top:0;z-index:1">
              <tr>
                <th style="width:34px"><input type="checkbox" id="restockSelectAllSps" onchange="toggleRestockSelectAll(this)" title="Select all"></th>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th>Color</th>
                <th>Size</th>
                <th style="text-align:right">Stock Qty</th>
                <th style="width:90px">Restock Qty</th>
              </tr>
            </thead>
            <tbody id="restockSpPickerBody">
              <tr><td colspan="8" class="text-center text-muted" style="padding:24px"><span class="spinner"></span> Loading...</td></tr>
            </tbody>
          </table>
        </div>
        <div id="restockSelectedCount" style="margin-top:6px;font-size:.8rem;color:var(--color-gray-400)">0 item(s) selected</div>
      </div>

      <hr style="margin:14px 0">

      <!-- Order Details Section -->
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
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Delivery Status</label>
          <select id="restockDeliveryStatus" class="form-select">
            <option value="ordered">Ordered</option>
            <option value="delivered">Delivered</option>
            <option value="incomplete">Incomplete</option>
            <option value="problematic">Problematic</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea id="restockNotes" class="form-textarea" style="min-height:38px"></textarea>
        </div>
      </div>
    </div>
    <div class="modal-footer" style="position:sticky;bottom:0;background:#fff;z-index:1">
      <button class="btn btn-secondary" onclick="closeModal('restockModal')">Cancel</button>
      <button class="btn btn-primary" onclick="confirmRestock()" id="restockBtn">Create Restock</button>
    </div>
  </div>
</div>

<!-- Action Confirm Modal -->
<div class="modal-overlay" id="invActionConfirmModal">
  <div class="modal-content" style="max-width:420px">
    <div class="modal-header">
      <h2 class="modal-title" id="invActionConfirmTitle">Confirm</h2>
      <button class="modal-close" onclick="closeModal('invActionConfirmModal')">✕</button>
    </div>
    <div class="modal-body"><p id="invActionConfirmMessage" style="margin:0"></p></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('invActionConfirmModal')">Cancel</button>
      <button class="btn btn-primary" id="invActionConfirmBtn">Confirm</button>
    </div>
  </div>
</div>

<script>
const csrfToken     = document.querySelector('meta[name="csrf-token"]')?.content || '';
const invBaseUrl    = '<?= htmlspecialchars(app_url('/inventory')) ?>';
let searchTimer     = null;
let histSearchTimer = null;

// All stock products loaded via AJAX for restock picker
let _allSPs       = [];
let _spSelections = new Map(); // id → qty
let _spLoaded     = false;

// ---- Tab switching ----
function switchTab(tab) {
  document.getElementById('tab-inventory').style.display = tab === 'inventory' ? 'block' : 'none';
  document.getElementById('tab-history').style.display   = tab === 'history'   ? 'block' : 'none';
  const btnInv  = document.getElementById('tab-inv-btn');
  const btnHist = document.getElementById('tab-hist-btn');
  const active  = 'var(--color-primary)';
  const inactive= 'var(--color-gray-500)';
  btnInv.style.borderBottomColor  = tab === 'inventory' ? active : 'transparent';
  btnInv.style.color              = tab === 'inventory' ? active : inactive;
  btnHist.style.borderBottomColor = tab === 'history'   ? active : 'transparent';
  btnHist.style.color             = tab === 'history'   ? active : inactive;
  history.replaceState({}, '', invBaseUrl + '?tab=' + tab);
}

// ---- Inventory filters ----
function debouncedSearch() { clearTimeout(searchTimer); searchTimer = setTimeout(applyInvFilters, 400); }

function applyInvFilters() {
  const search = document.getElementById('invSearchInput').value;
  const status = document.getElementById('invStatusFilter').value;
  const params = new URLSearchParams({ tab: 'inventory' });
  if (search) params.set('search', search);
  if (status) params.set('status', status);
  const url = new URL(window.location.href);
  if (url.searchParams.get('sort'))  params.set('sort',  url.searchParams.get('sort'));
  if (url.searchParams.get('order')) params.set('order', url.searchParams.get('order'));
  const pageUrl = window.location.origin + invBaseUrl + '?' + params.toString();
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
  document.getElementById('invSearchInput').value = '';
  document.getElementById('invStatusFilter').value = '';
  applyInvFilters();
}

// ---- History filters ----
function debouncedHistSearch() { clearTimeout(histSearchTimer); histSearchTimer = setTimeout(applyHistFilters, 400); }

function applyHistFilters() {
  const search = document.getElementById('histSearchInput').value;
  const type   = document.getElementById('histTypeFilter').value;
  const params = new URLSearchParams({ tab: 'history' });
  if (search) params.set('history_search', search);
  if (type)   params.set('movement_type', type);
  const pageUrl = window.location.origin + invBaseUrl + '?' + params.toString();
  history.pushState({}, '', pageUrl);
  const container = document.getElementById('historyResultsContainer');
  if (container) container.style.opacity = '0.5';
  fetch(pageUrl).then(r => r.text()).then(html => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const el  = doc.getElementById('historyResultsContainer');
    if (el && container) { container.innerHTML = el.innerHTML; container.style.opacity = '1'; }
  }).catch(() => { if (container) container.style.opacity = '1'; });
}

function resetHistFilters() {
  document.getElementById('histSearchInput').value = '';
  document.getElementById('histTypeFilter').value  = '';
  applyHistFilters();
}

// ---- Multi-select (inventory table) ----
function onRowSelect() {
  const checked = document.querySelectorAll('.inv-select:checked');
  const bar     = document.getElementById('restockSelectionBar');
  if (checked.length > 0) {
    document.getElementById('restockSelectionCount').textContent = checked.length + ' selected';
    bar.style.display = 'flex';
  } else {
    bar.style.display = 'none';
  }
  const all  = document.querySelectorAll('.inv-select');
  const sa   = document.getElementById('selectAllInv');
  if (sa) { sa.indeterminate = checked.length > 0 && checked.length < all.length; sa.checked = checked.length === all.length; }
}

function toggleSelectAll(cb) {
  document.querySelectorAll('.inv-select').forEach(c => c.checked = cb.checked);
  onRowSelect();
}

function clearSelection() {
  document.querySelectorAll('.inv-select').forEach(c => c.checked = false);
  const sa = document.getElementById('selectAllInv');
  if (sa) { sa.checked = false; sa.indeterminate = false; }
  document.getElementById('restockSelectionBar').style.display = 'none';
}

function openRestockFromSelection() {
  const checked = document.querySelectorAll('.inv-select:checked');
  const preIds  = Array.from(checked).map(cb => parseInt(cb.value, 10));
  openRestock(0, '', preIds);
}

// ---- Restock SP Picker ----
async function loadAllSPs() {
  if (_spLoaded) return;
  try {
    const res  = await fetch('/api/v1/stock-products/all');
    const data = await res.json();
    if (data.success) { _allSPs = data.data || []; _spLoaded = true; }
  } catch (e) { /* silently fail */ }
}

function filterRestockPicker() {
  const search = (document.getElementById('restockSpSearch')?.value || '').toLowerCase();
  const typeId = parseInt(document.getElementById('restockSpTypeFilter')?.value || '0', 10) || 0;
  const colId  = parseInt(document.getElementById('restockSpColorFilter')?.value || '0', 10) || 0;
  const sizId  = parseInt(document.getElementById('restockSpSizeFilter')?.value || '0', 10) || 0;

  return _allSPs.filter(sp => {
    if (typeId && sp.type_id !== typeId)   return false;
    if (colId  && sp.color_id !== colId)   return false;
    if (sizId  && sp.size_id !== sizId)    return false;
    if (search && !sp.code.toLowerCase().includes(search) && !sp.name.toLowerCase().includes(search)) return false;
    return true;
  });
}

function esc(str) {
  const d = document.createElement('span');
  d.textContent = str || '';
  return d.innerHTML;
}

function renderRestockPicker(filtered) {
  const tbody = document.getElementById('restockSpPickerBody');
  if (!tbody) return;
  if (!filtered.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted" style="padding:24px">No stock products found</td></tr>';
    updateRestockSelectedCount();
    return;
  }
  tbody.innerHTML = filtered.map(sp => {
    const sel = _spSelections.has(sp.id);
    const qty = sel ? _spSelections.get(sp.id) : 1;
    const qtyLow = sp.current_qty <= (sp.low_stock_alert || 10);
    return `<tr>
      <td><input type="checkbox" class="restock-sp-cb" data-id="${sp.id}" ${sel ? 'checked' : ''} onchange="onRestockSpCheck(this, ${sp.id})"></td>
      <td><code style="font-size:.78rem">${esc(sp.code)}</code></td>
      <td>${esc(sp.name)}</td>
      <td style="font-size:.8rem">${esc(sp.type_name || '-')}</td>
      <td style="font-size:.8rem">${esc(sp.color_name || '-')}</td>
      <td style="font-size:.8rem">${esc(sp.size_name || '-')}</td>
      <td style="text-align:right"><span class="badge ${qtyLow ? 'badge-warning' : 'badge-gray'}">${sp.current_qty}</span></td>
      <td><input type="number" class="form-input restock-sp-qty" data-id="${sp.id}" min="1" value="${qty}" style="height:30px;width:72px" oninput="onRestockQtyChange(this, ${sp.id})" ${!sel ? 'disabled' : ''}></td>
    </tr>`;
  }).join('');
  updateRestockSelectedCount();
}

function onRestockSpCheck(cb, id) {
  if (cb.checked) {
    const qtyInput = cb.closest('tr')?.querySelector('.restock-sp-qty');
    const qty = qtyInput ? parseInt(qtyInput.value, 10) : 1;
    _spSelections.set(id, qty > 0 ? qty : 1);
    if (qtyInput) qtyInput.disabled = false;
  } else {
    _spSelections.delete(id);
    const qtyInput = cb.closest('tr')?.querySelector('.restock-sp-qty');
    if (qtyInput) qtyInput.disabled = true;
  }
  updateRestockSelectedCount();
  updateRestockSelectAllState();
}

function onRestockQtyChange(input, id) {
  const qty = parseInt(input.value, 10);
  if (_spSelections.has(id) && qty > 0) _spSelections.set(id, qty);
}

function toggleRestockSelectAll(cb) {
  const filtered = filterRestockPicker();
  if (cb.checked) {
    filtered.forEach(sp => { if (!_spSelections.has(sp.id)) _spSelections.set(sp.id, 1); });
  } else {
    filtered.forEach(sp => _spSelections.delete(sp.id));
  }
  renderRestockPicker(filtered);
}

function updateRestockSelectAllState() {
  const filtered = filterRestockPicker();
  const allChecked  = filtered.length > 0 && filtered.every(sp => _spSelections.has(sp.id));
  const someChecked = filtered.some(sp => _spSelections.has(sp.id));
  const cb = document.getElementById('restockSelectAllSps');
  if (cb) { cb.checked = allChecked; cb.indeterminate = !allChecked && someChecked; }
}

function updateRestockSelectedCount() {
  const el = document.getElementById('restockSelectedCount');
  if (el) el.textContent = `${_spSelections.size} item(s) selected`;
  updateRestockSelectAllState();
}

async function openRestock(stockProductId = 0, name = '', preIds = []) {
  _spSelections = new Map();
  preIds.forEach(id => _spSelections.set(id, 1));
  if (stockProductId > 0) _spSelections.set(stockProductId, 1);

  document.getElementById('restockSupplier').value = '';
  document.getElementById('restockDate').value     = '';
  document.getElementById('restockDeliveryStatus').value = 'ordered';
  document.getElementById('restockNotes').value    = '';
  document.getElementById('restockSpSearch').value  = '';
  document.getElementById('restockSpTypeFilter').value  = '';
  document.getElementById('restockSpColorFilter').value = '';
  document.getElementById('restockSpSizeFilter').value  = '';

  openModal('restockModal');

  if (!_spLoaded) {
    document.getElementById('restockSpPickerBody').innerHTML =
      '<tr><td colspan="8" class="text-center text-muted" style="padding:24px"><span class="spinner"></span> Loading...</td></tr>';
    await loadAllSPs();
  }
  renderRestockPicker(filterRestockPicker());
}

// ---- Confirm action helper ----
function invConfirmAction(title, message, onConfirm) {
  document.getElementById('invActionConfirmTitle').textContent   = title;
  document.getElementById('invActionConfirmMessage').textContent = message;
  document.getElementById('invActionConfirmBtn').onclick = () => { closeModal('invActionConfirmModal'); onConfirm(); };
  openModal('invActionConfirmModal');
}

function confirmRestock() {
  const count = _spSelections.size;
  if (count === 0) return showToast('Select at least one stock product', 'error');
  invConfirmAction('Create Restock Order', `Create a restock order with ${count} item(s)?`, submitRestock);
}

async function submitRestock() {
  if (_spSelections.size === 0) return showToast('Select at least one stock product', 'error');

  const items = [];
  _spSelections.forEach((qty, id) => {
    if (qty > 0) items.push({ stock_product_id: id, quantity_requested: qty });
  });
  if (items.length === 0) return showToast('Add at least one item', 'error');

  const btn = document.getElementById('restockBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving...';

  const payload = {
    supplier_name:    document.getElementById('restockSupplier').value,
    delivery_date:    document.getElementById('restockDate').value,
    delivery_status:  document.getElementById('restockDeliveryStatus').value,
    notes:            document.getElementById('restockNotes').value,
    items,
  };

  try {
    const res  = await fetch('/api/v1/inventory/restock', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body:    JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) {
      showToast('Restock order created', 'success');
      closeModal('restockModal');
      clearSelection();
      setTimeout(() => location.reload(), 800);
    } else showToast(data.message || 'Failed', 'error');
  } catch (e) { showToast('Network error', 'error'); }

  btn.disabled = false; btn.innerHTML = 'Create Restock';
}
</script>

<?php
$content = ob_get_clean();
$title   = 'Inventory | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
