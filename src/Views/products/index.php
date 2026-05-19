<?php ob_start(); ?>
<div class="page-header">
  <h1>Products</h1>
  <div class="d-flex gap-8">
    <?php if (can('products', 'import')): ?>
    <button class="btn btn-secondary btn-sm" onclick="showImportModal()"><?= icon('upload', 15) ?> Import CSV</button>
    <?php endif; ?>
    <?php if (can('products', 'add')): ?>
    <button class="btn btn-primary" onclick="showProductModal()">+ Add Product</button>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:16px">
    <div class="filter-bar" style="flex-wrap:wrap;gap:8px">
      <div class="search-bar" style="flex:1;min-width:200px;max-width:280px">
        <?= icon('search', 16) ?> <input type="text" id="searchInput" placeholder="Search products..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" oninput="debouncedLoad()" style="width:100%">
      </div>
      <select id="categoryFilter" class="form-select" style="width:140px;height:38px" onchange="loadProducts()">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="typeFilter" class="form-select" style="width:130px;height:38px" onchange="loadProducts()">
        <option value="">All Types</option>
        <?php foreach ($types ?? [] as $t): ?>
          <option value="<?= $t['id'] ?>" <?= ($filters['type_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="colorFilter" class="form-select" style="width:120px;height:38px" onchange="loadProducts()">
        <option value="">All Colors</option>
        <?php foreach ($colors as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($filters['color_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="sizeFilter" class="form-select" style="width:110px;height:38px" onchange="loadProducts()">
        <option value="">All Sizes</option>
        <?php foreach ($sizes as $s): ?>
          <option value="<?= $s['id'] ?>" <?= ($filters['size_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="statusFilter" class="form-select" style="width:120px;height:38px" onchange="loadProducts()">
        <option value="">All Status</option>
        <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
      <select id="stockStatusFilter" class="form-select" style="width:150px;height:38px" onchange="filterByStockStatus()">
        <option value="">All Stock Status</option>
        <option value="in_stock">In Stock</option>
        <option value="low_stock">Low Stock</option>
        <option value="out_of_stock">Out of Stock</option>
      </select>
      <button class="btn btn-secondary btn-sm" onclick="resetFilters()" style="height:38px">Reset</button>
    </div>
  </div>

<?php
  $sort  = $filters['sort']  ?? 'p.created_at';
  $order = strtolower($filters['order'] ?? 'desc');
  function sortLink(string $col, string $label, string $currentSort, string $currentOrder, array $filters): string {
    $newOrder = ($currentSort === $col && $currentOrder === 'asc') ? 'desc' : 'asc';
    $q = http_build_query(array_merge($filters, ['sort' => $col, 'order' => $newOrder]));
    $arrow = $currentSort === $col ? ($currentOrder === 'asc' ? ' <span style="font-size:.8em">▲</span>' : ' <span style="font-size:.8em">▼</span>') : ' <span style="font-size:.8em;opacity:.5">⇅</span>';
    return '<a href="?'.$q.'" style="display:block;padding:12px 16px;color:inherit;text-decoration:none;white-space:nowrap">'.htmlspecialchars($label).$arrow.'</a>';
  }
?>
  <div id="productsResultsContainer">
  <div class="table-wrapper">
    <table class="data-table" id="productsTable">
      <thead>
        <tr style="cursor:pointer">
          <th style="width:52px">Image</th>
          <th style="padding:0"><?= sortLink('p.name', 'Product', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('c.name', 'Category', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('t.name', 'Type', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('cl.name', 'Color', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('s.name', 'Size', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('p.selling_price', 'Price', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('p.computed_stock', 'QTY', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('stock_status', 'Stock Status', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('p.status', 'Status', $sort, $order, $filters) ?></th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="productsBody">
        <?php foreach ($products as $p): ?>
        <tr style="cursor:pointer" onclick="viewProduct(<?= $p['id'] ?>)" data-stock-status="<?= $p['stock_status'] ?? 'out_of_stock' ?>">
          <td onclick="event.stopPropagation()">
            <img
              src="<?= htmlspecialchars(!empty($p['image_path']) ? app_url($p['image_path']) : asset_url('/assets/images/no-image.png')) ?>"
              alt="<?= htmlspecialchars($p['name']) ?>"
              style="width:42px;height:42px;object-fit:cover;border-radius:8px;border:1px solid var(--color-gray-100)"
              onerror="this.src='<?= htmlspecialchars(asset_url('/assets/images/no-image.png')) ?>'"
            >
          </td>
          <td onclick="event.stopPropagation()">
            <div style="font-size:.75rem;color:var(--color-gray-500)"><?= htmlspecialchars($p['sku']) ?></div>
            <div style="font-weight:700"><?= htmlspecialchars($p['name']) ?></div>
          </td>
          <td><?= htmlspecialchars($p['category_code'] ?? $p['category_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($p['type_code'] ?? $p['type_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($p['color_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($p['size_code'] ?? $p['size_name'] ?? '-') ?></td>
          <td><strong>₱<?= number_format($p['selling_price'], 2) ?></strong></td>
          <td>
            <?php
              $qty = (int)($p['computed_stock'] ?? 0);
              $ss  = $p['stock_status'] ?? 'out_of_stock';
              $sCls = $ss === 'in_stock' ? 'badge-success' : ($ss === 'low_stock' ? 'badge-warning' : 'badge-danger');
              $sLbl = $ss === 'in_stock' ? 'In Stock' : ($ss === 'low_stock' ? 'Low Stock' : 'Out of Stock');
            ?>
            <span class="badge <?= $sCls ?>"><?= $qty ?></span>
          </td>
          <td><span class="badge <?= $sCls ?>"><?= $sLbl ?></span></td>
          <td><span class="badge <?= $p['status']==='active' ? 'badge-success' : 'badge-gray' ?>"><?= ucfirst($p['status']) ?></span></td>
          <td onclick="event.stopPropagation()">
            <?php if (can('products', 'edit')): ?>
            <button class="icon-btn" onclick="editProduct(<?= $p['id'] ?>)" title="Edit"><?= icon('edit', 15) ?></button>
            <?php endif; ?>
            <?php if (can('products', 'delete')): ?>
            <button class="icon-btn danger" onclick="deleteProduct(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>')" title="Delete"><?= icon('delete', 15) ?></button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
          <tr><td colspan="11" class="text-center text-muted" style="padding:48px">No products found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
    $pqFilters = array_filter(array_merge($filters, ['page' => null, 'per_page' => null]), fn($v) => $v !== null && $v !== '');
    echo renderPagination($pagination, $pqFilters);
  ?>
  </div><!-- /productsResultsContainer -->
</div>

<!-- Product Modal -->
<div class="modal-overlay" id="productModal">
  <div class="modal-content" style="max-width:640px">
    <div class="modal-header">
      <h2 class="modal-title" id="productModalTitle">Add Product</h2>
      <button class="modal-close" onclick="closeModal('productModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <form id="productForm" enctype="multipart/form-data">
        <input type="hidden" id="productId">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">SKU <span class="required">*</span></label>
            <input type="text" id="pSku" name="sku" class="form-input" required placeholder="e.g., PROD001">
          </div>
          <div class="form-group">
            <label class="form-label">Product Name <span class="required">*</span></label>
            <input type="text" id="pName" name="name" class="form-input" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea id="pDesc" name="description" class="form-textarea" style="min-height:70px"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select id="pCategory" name="category_id" class="form-select">
              <option value="">None</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Type</label>
            <select id="pType" name="type_id" class="form-select">
              <option value="">None</option>
              <?php foreach ($types ?? [] as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div> 
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Color</label>
            <select id="pColor" name="color_id" class="form-select">
              <option value="">None</option>
              <?php foreach ($colors as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Size</label>
            <select id="pSize" name="size_id" class="form-select">
              <option value="">None</option>
              <?php foreach ($sizes as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Cost Price (₱) <span class="required">*</span></label>
            <input type="number" id="pCost" name="cost_price" class="form-input" min="0" step="0.01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Selling Price (₱) <span class="required">*</span></label>
            <input type="number" id="pPrice" name="selling_price" class="form-input" min="0" step="0.01" required>
          </div>
        </div>

        <!-- Stock Requirements Section -->
        <div class="form-group" style="margin-top:8px">
          <label class="form-label">
            Stock Requirements <span style="font-size:.75rem;color:var(--color-gray-400);font-weight:400">(which stock products are consumed per unit sold)</span>
          </label>

          <!-- Search + Filters -->
          <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">
            <input type="text" id="sreqSearch" class="form-input" placeholder="Search code or name..." style="flex:1;min-width:140px;height:34px" oninput="filterSreqPicker()">
            <select id="sreqTypeFilter" class="form-select" style="width:120px;height:34px" onchange="filterSreqPicker()">
              <option value="">All Types</option>
              <?php foreach ($types ?? [] as $t): ?>
              <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <select id="sreqColorFilter" class="form-select" style="width:110px;height:34px" onchange="filterSreqPicker()">
              <option value="">All Colors</option>
              <?php foreach ($colors as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <select id="sreqSizeFilter" class="form-select" style="width:100px;height:34px" onchange="filterSreqPicker()">
              <option value="">All Sizes</option>
              <?php foreach ($sizes as $s): ?>
              <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Picker Table -->
          <div style="max-height:260px;overflow-y:auto;border:1px solid var(--color-gray-100);border-radius:6px">
            <table class="data-table" style="margin:0;font-size:.82rem">
              <thead style="position:sticky;top:0;z-index:1">
                <tr>
                  <th style="width:32px"><input type="checkbox" id="sreqSelectAll" onchange="toggleSreqSelectAll(this)" title="Select all"></th>
                  <th>Code</th>
                  <th>Name</th>
                  <th>Type</th>
                  <th>Color</th>
                  <th>Size</th>
                  <th style="width:90px">Qty/unit</th>
                </tr>
              </thead>
              <tbody id="sreqPickerBody">
                <tr><td colspan="7" class="text-center text-muted" style="padding:20px">Loading stock products...</td></tr>
              </tbody>
            </table>
          </div>
          <div id="sreqSelectedCount" style="margin-top:5px;font-size:.8rem;color:var(--color-gray-400)">0 item(s) selected</div>
        </div>
        <div class="form-group">
          <label class="form-label">Product Image</label>
          <input type="file" id="pImage" name="image" class="form-input" accept="image/*">
          <div style="margin-top:10px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <img
              id="pImagePreview"
              src="<?= htmlspecialchars(asset_url('/assets/images/no-image.png')) ?>"
              alt="Product image preview"
              style="width:96px;height:96px;object-fit:cover;border-radius:10px;border:1px solid var(--color-gray-100);background:#fff"
            >
            <div style="font-size:.8rem;color:var(--color-gray-500)">Preview updates locally when you choose an image. It is saved only after clicking Save Product.</div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('productModal')">Cancel</button>
      <button class="btn btn-primary" onclick="confirmSaveProduct()" id="saveProductBtn">Save Product</button>
    </div>
  </div>
</div>

<!-- View Product Modal -->
<div class="modal-overlay" id="viewProductModal">
  <div class="modal-content" style="max-width:640px">
    <div class="modal-header">
      <h2 class="modal-title">Product Details</h2>
      <button class="modal-close" onclick="closeModal('viewProductModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body" id="viewProductBody"><div style="text-align:center;padding:48px"><span class="spinner"></span></div></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('viewProductModal')">Close</button>
      <button class="btn btn-primary" id="viewEditBtn" onclick="">Edit Product</button>
    </div>
  </div>
</div>

<!-- Import CSV Modal -->
<div class="modal-overlay" id="importModal">
  <div class="modal-content" style="max-width:640px">
    <div class="modal-header">
      <h2 class="modal-title"><?= icon('upload', 16) ?> Import Products</h2>
      <button class="modal-close" onclick="closeModal('importModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <div style="background:#f0f7ff;border:1px solid #b3d4f5;border-radius:8px;padding:14px 16px;margin-bottom:16px;font-size:.875rem;line-height:1.6">
        <strong style="display:block;margin-bottom:6px;font-size:.95rem">How to Import Products</strong>
        <ol style="margin:0 0 10px 18px;padding:0">
          <li>Click <strong>Download Template (.xlsx)</strong> below to get the pre-filled spreadsheet.</li>
          <li>Fill in your products starting from <strong>Row 2</strong> (Row 1 = headers). Row 2 is a sample — you can edit or replace it.</li>
          <li>Columns marked with <strong>*</strong> are required: <em>SKU, Name, Cost Price, Selling Price</em>.</li>
          <li><strong>Category, Type, Color, Size &amp; Status</strong> columns have dropdown menus — select from the list.</li>
          <li><strong>Low Stock Alert</strong> sets the threshold for low-stock warnings (defaults to 10 if left blank).</li>
          <li>Save the file, then upload it here and click <strong>Import</strong>.</li>
        </ol>
        <div style="font-size:.82rem;color:#555">
          <strong>Notes:</strong>
          <ul style="margin:4px 0 0 18px;padding:0">
            <li>Duplicate SKUs will be skipped with an error message.</li>
            <li>Leave <em>Initial Stock</em> blank or 0 if product has no stock yet.</li>
            <li>Status must be <em>active</em> or <em>inactive</em> (defaults to <em>active</em>).</li>
          </ul>
        </div>
      </div>
      <a href="<?= app_url('/api/v1/products/import-template') ?>" class="btn btn-secondary btn-sm" style="margin-bottom:14px" download>
        <?= icon('download', 13) ?> Download Template (.xlsx)
      </a>
      <div class="form-group">
        <label class="form-label">Choose File (.xlsx or .csv) <span class="required">*</span></label>
        <input type="file" id="importFile" class="form-input" accept=".xlsx,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
      </div>
      <div id="importResult" style="display:none"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('importModal')">Cancel</button>
      <button class="btn btn-primary" onclick="doImport()" id="importBtn"><?= icon('upload', 14) ?> Import</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="confirmModal">
  <div class="modal-content" style="max-width:400px">
    <div class="modal-header"><h2 class="modal-title">Confirm Delete</h2><button class="modal-close" onclick="closeModal('confirmModal')">✕</button></div>
    <div class="modal-body"><p id="confirmMessage"></p></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('confirmModal')">Cancel</button>
      <button class="btn btn-danger" id="confirmBtn">Delete</button>
    </div>
  </div>
</div>

<!-- Action Confirm Modal (save) -->
<div class="modal-overlay" id="prodActionConfirmModal">
  <div class="modal-content" style="max-width:420px">
    <div class="modal-header">
      <h2 class="modal-title" id="prodActionConfirmTitle">Confirm</h2>
      <button class="modal-close" onclick="closeModal('prodActionConfirmModal')">✕</button>
    </div>
    <div class="modal-body"><p id="prodActionConfirmMessage" style="margin:0"></p></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('prodActionConfirmModal')">Cancel</button>
      <button class="btn btn-primary" id="prodActionConfirmBtn">Confirm</button>
    </div>
  </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const noImagePreview = '<?= htmlspecialchars(asset_url('/assets/images/no-image.png')) ?>';

function setImagePreview(src) {
  document.getElementById('pImagePreview').src = src || noImagePreview;
}

document.getElementById('pImage').addEventListener('change', (event) => {
  const file = event.target.files?.[0];
  if (!file) {
    setImagePreview(noImagePreview);
    return;
  }

  const objectUrl = URL.createObjectURL(file);
  setImagePreview(objectUrl);
});

function showProductModal(id = null) {
  document.getElementById('productId').value = id || '';
  document.getElementById('productModalTitle').textContent = id ? 'Edit Product' : 'Add Product';
  document.getElementById('pSku').disabled = false;
  if (!id) {
    document.getElementById('productForm').reset();
    setImagePreview(noImagePreview);
    _sreqSelections = new Map();
  }
  document.getElementById('productModal').classList.add('show');
  // Load picker and render after cache is ready
  loadSreqCache().then(() => filterSreqPicker());
}

// Stock requirements data cache for the open modal
let _allSPsReq     = [];
let _sreqLoaded    = false;
let _sreqSelections = new Map(); // id → { qty }

async function loadSreqCache() {
  if (_sreqLoaded) return;
  try {
    const res  = await fetch('/api/v1/stock-products/all');
    const data = await res.json();
    if (data.success) { _allSPsReq = data.data?.stock_products || []; _sreqLoaded = true; }
  } catch (e) { /* ignore */ }
}

function filterSreqPicker() {
  const search = (document.getElementById('sreqSearch')?.value || '').toLowerCase();
  const typeId = parseInt(document.getElementById('sreqTypeFilter')?.value || '0', 10) || 0;
  const colId  = parseInt(document.getElementById('sreqColorFilter')?.value || '0', 10) || 0;
  const sizId  = parseInt(document.getElementById('sreqSizeFilter')?.value || '0', 10) || 0;
  const filtered = _allSPsReq.filter(sp => {
    if (typeId && parseInt(sp.type_id)  !== typeId) return false;
    if (colId  && parseInt(sp.color_id) !== colId)  return false;
    if (sizId  && parseInt(sp.size_id)  !== sizId)  return false;
    if (search && !sp.code.toLowerCase().includes(search) && !sp.name.toLowerCase().includes(search)) return false;
    return true;
  });
  renderSreqPicker(filtered);
}

function _escHtml(str) {
  const d = document.createElement('span');
  d.textContent = str || '';
  return d.innerHTML;
}

function renderSreqPicker(filtered) {
  const tbody = document.getElementById('sreqPickerBody');
  if (!tbody) return;
  if (!filtered.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted" style="padding:20px">No stock products found</td></tr>';
    updateSreqSelectedCount();
    return;
  }
  tbody.innerHTML = filtered.map(sp => {
    const sel   = _sreqSelections.has(sp.id);
    const entry = sel ? _sreqSelections.get(sp.id) : { qty: 1 };
    return `<tr>
      <td><input type="checkbox" class="sreq-cb" data-id="${sp.id}" ${sel ? 'checked' : ''} onchange="onSreqCheck(this, ${sp.id})"></td>
      <td><code style="font-size:.78rem">${_escHtml(sp.code)}</code></td>
      <td>${_escHtml(sp.name)}</td>
      <td style="font-size:.8rem">${_escHtml(sp.type_name || '-')}</td>
      <td style="font-size:.8rem">${_escHtml(sp.color_name || '-')}</td>
      <td style="font-size:.8rem">${_escHtml(sp.size_name || '-')}</td>
      <td><input type="number" class="form-input sreq-qty" data-id="${sp.id}" min="0.001" step="0.001" value="${entry.qty}" style="height:28px;width:78px" oninput="onSreqQtyChange(this,${sp.id})" ${!sel ? 'disabled' : ''}></td>
    </tr>`;
  }).join('');
  updateSreqSelectedCount();
}

function onSreqCheck(cb, id) {
  if (cb.checked) {
    const row   = cb.closest('tr');
    const qty   = parseFloat(row?.querySelector('.sreq-qty')?.value) || 1;
    _sreqSelections.set(id, { qty: qty > 0 ? qty : 1 });
    row?.querySelector('.sreq-qty')?.removeAttribute('disabled');
  } else {
    _sreqSelections.delete(id);
    const row = cb.closest('tr');
    row?.querySelector('.sreq-qty')?.setAttribute('disabled', 'disabled');
  }
  updateSreqSelectedCount();
}

function onSreqQtyChange(input, id) {
  const qty = parseFloat(input.value);
  if (_sreqSelections.has(id) && qty > 0) {
    const e = _sreqSelections.get(id);
    _sreqSelections.set(id, { ...e, qty });
  }
}

function toggleSreqSelectAll(cb) {
  const search = (document.getElementById('sreqSearch')?.value || '').toLowerCase();
  const typeId = parseInt(document.getElementById('sreqTypeFilter')?.value || '0', 10) || 0;
  const colId  = parseInt(document.getElementById('sreqColorFilter')?.value || '0', 10) || 0;
  const sizId  = parseInt(document.getElementById('sreqSizeFilter')?.value || '0', 10) || 0;
  const filtered = _allSPsReq.filter(sp => {
    if (typeId && parseInt(sp.type_id)  !== typeId) return false;
    if (colId  && parseInt(sp.color_id) !== colId)  return false;
    if (sizId  && parseInt(sp.size_id)  !== sizId)  return false;
    if (search && !sp.code.toLowerCase().includes(search) && !sp.name.toLowerCase().includes(search)) return false;
    return true;
  });
  if (cb.checked) {
    filtered.forEach(sp => { if (!_sreqSelections.has(sp.id)) _sreqSelections.set(sp.id, { qty: 1 }); });
  } else {
    filtered.forEach(sp => _sreqSelections.delete(sp.id));
  }
  renderSreqPicker(filtered);
}

function updateSreqSelectedCount() {
  const el = document.getElementById('sreqSelectedCount');
  if (el) el.textContent = `${_sreqSelections.size} item(s) selected`;
}

function collectStockReqs() {
  const result = [];
  _sreqSelections.forEach((entry, id) => {
    if (entry.qty > 0) {
      result.push({ stock_product_id: id, qty_required_per_unit: entry.qty, waste_percent: 0 });
    }
  });
  return result;
}

function closeModal(id) { document.getElementById(id).classList.remove('show'); }

async function editProduct(id) {
  await loadSreqCache();
  const res  = await fetch('/api/v1/products/' + id);
  const data = await res.json();
  if (!data.success) return;
  const p = data.data;
  document.getElementById('pSku').value      = p.sku;
  document.getElementById('pName').value     = p.name;
  document.getElementById('pDesc').value     = p.description || '';
  document.getElementById('pCategory').value = p.category_id || '';
  document.getElementById('pColor').value    = p.color_id || '';
  document.getElementById('pSize').value     = p.size_id || '';
  document.getElementById('pCost').value     = p.cost_price;
  document.getElementById('pType').value     = p.type_id || '';
  document.getElementById('pPrice').value    = p.selling_price;
  setImagePreview(p.image_path ? appPath(p.image_path) : noImagePreview);

  // Load stock requirements into picker
  _sreqSelections = new Map();
  try {
    const rRes  = await fetch('/api/v1/products/' + id + '/stock-requirements');
    const rData = await rRes.json();
    if (rData.success) {
      (rData.data?.requirements ?? []).forEach(r => {
        _sreqSelections.set(parseInt(r.stock_product_id), {
          qty:   parseFloat(r.qty_required_per_unit) || 1,
        });
      });
    }
  } catch (e) { /* ignore */ }

  showProductModal(id);
}

function confirmSaveProduct() {
  const id   = document.getElementById('productId')?.value;
  const name = document.getElementById('productName')?.value.trim() || 'this product';
  const code = document.getElementById('productCode')?.value.trim();
  if (!code || !name) return saveProduct(); // let saveProduct show validation errors
  const title = id ? 'Update Product' : 'Add Product';
  const msg   = id ? `Update "${name}"?` : `Create new product "${name}"?`;
  document.getElementById('prodActionConfirmTitle').textContent   = title;
  document.getElementById('prodActionConfirmMessage').textContent = msg;
  document.getElementById('prodActionConfirmBtn').onclick = () => { closeModal('prodActionConfirmModal'); saveProduct(); };
  openModal('prodActionConfirmModal');
}

async function saveProduct() {
  const id   = document.getElementById('productId').value;
  const form = document.getElementById('productForm');
  const btn  = document.getElementById('saveProductBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving...';

  const formData = new FormData(form);

  // Append stock requirements as JSON array
  const reqs = collectStockReqs();
  formData.append('stock_requirements', JSON.stringify(reqs));

  const method = 'POST';
  const url    = id ? '/api/v1/products/' + id : '/api/v1/products';
  if (id) formData.append('_method', 'PUT');

  try {
    const res  = await fetch(url, { method, headers: { 'X-CSRF-Token': csrfToken }, body: formData });
    const data = await res.json();
    if (data.success) { showToast(data.message, 'success'); closeModal('productModal'); setTimeout(() => loadProducts(), 800); }
    else showToast(data.message || 'Failed', 'error');
  } catch (e) { showToast('Network error', 'error'); }

  btn.disabled = false; btn.innerHTML = 'Save Product';
}

function deleteProduct(id, name) {
  document.getElementById('confirmMessage').textContent = `Delete product "${name}"? This cannot be undone.`;
  document.getElementById('confirmBtn').onclick = async () => {
    const res  = await fetch('/api/v1/products/' + id, { method: 'DELETE', headers: { 'X-CSRF-Token': csrfToken } });
    const data = await res.json();
    if (data.success) { showToast('Product deleted', 'success'); closeModal('confirmModal'); setTimeout(() => loadProducts(), 600); }
    else showToast(data.message, 'error');
  };
  document.getElementById('confirmModal').classList.add('show');
}

function showImportModal() {
  document.getElementById('importFile').value = '';
  document.getElementById('importResult').style.display = 'none';
  document.getElementById('importResult').innerHTML = '';
  openModal('importModal');
}

async function doImport() {
  const file = document.getElementById('importFile').files?.[0];
  if (!file) { showToast('Please choose a file (.xlsx or .csv)', 'error'); return; }
  const btn = document.getElementById('importBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Importing...';
  const formData = new FormData();
  formData.append('file', file);
  try {
    const res  = await fetch('/api/v1/products/bulk-import', { method: 'POST', headers: { 'X-CSRF-Token': csrfToken }, body: formData });
    const data = await res.json();
    const resultEl = document.getElementById('importResult');
    resultEl.style.display = '';
    if (data.success) {
      const created = data.data?.created ?? 0;
      const skipped = data.data?.skipped ?? 0;
      const errs    = data.data?.errors  ?? [];
      let html = '';
      if (created > 0) {
        html += `<div class="alert alert-success" style="margin-bottom:10px">
          <strong>${created} product(s) imported successfully.</strong>${skipped ? ` ${skipped} row(s) were skipped — see below.` : ''}
        </div>`;
      } else {
        html += `<div class="alert alert-warning" style="margin-bottom:10px">
          <strong>No products were imported.</strong> ${skipped ? `${skipped} row(s) had errors — see below.` : ''}
        </div>`;
      }
      if (errs.length) {
        html += `<div style="margin-bottom:6px;font-size:.82rem;font-weight:600;color:#c0392b">${errs.length} issue(s) found — fix these in your file and re-import the affected rows:</div>`;
        html += `<div style="overflow-x:auto;max-height:280px;overflow-y:auto;border:1px solid #f5c6cb;border-radius:6px">`;
        html += `<table style="width:100%;border-collapse:collapse;font-size:.8rem">`;
        html += `<thead><tr style="background:#f8d7da;position:sticky;top:0">`;
        html += `<th style="padding:6px 10px;text-align:left;border-bottom:1px solid #f5c6cb;white-space:nowrap">Row #</th>`;
        html += `<th style="padding:6px 10px;text-align:left;border-bottom:1px solid #f5c6cb;white-space:nowrap">Column</th>`;
        html += `<th style="padding:6px 10px;text-align:left;border-bottom:1px solid #f5c6cb;white-space:nowrap">Your Value</th>`;
        html += `<th style="padding:6px 10px;text-align:left;border-bottom:1px solid #f5c6cb">Issue</th>`;
        html += `</tr></thead><tbody>`;
        errs.forEach((e, i) => {
          const bg = i % 2 === 0 ? '#fff' : '#fff8f8';
          const val = e.value !== '' && e.value != null ? `<code style="font-size:.78rem">${String(e.value).replace(/</g,'&lt;')}</code>` : '<span style="color:#aaa">empty</span>';
          html += `<tr style="background:${bg}">`;
          html += `<td style="padding:5px 10px;border-bottom:1px solid #fce8e8;font-weight:700;color:#c0392b;white-space:nowrap">Row ${e.row}</td>`;
          html += `<td style="padding:5px 10px;border-bottom:1px solid #fce8e8;white-space:nowrap">${e.col}</td>`;
          html += `<td style="padding:5px 10px;border-bottom:1px solid #fce8e8;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${val}</td>`;
          html += `<td style="padding:5px 10px;border-bottom:1px solid #fce8e8">${String(e.message).replace(/</g,'&lt;')}</td>`;
          html += `</tr>`;
        });
        html += `</tbody></table></div>`;
      }
      resultEl.innerHTML = html;
      if (created > 0) setTimeout(() => location.reload(), 3000);
    } else {
      resultEl.innerHTML = `<div class="alert alert-danger">${data.message || 'Import failed'}</div>`;
    }
  } catch (e) { showToast('Network error', 'error'); }
  btn.disabled = false; btn.innerHTML = '<?= icon('upload', 14) ?> Import';
}

async function viewProduct(id) {
  document.getElementById('viewProductBody').innerHTML = '<div style="text-align:center;padding:48px"><span class="spinner"></span></div>';
  document.getElementById('viewEditBtn').onclick = () => { closeModal('viewProductModal'); editProduct(id); };
  openModal('viewProductModal');
  try {
    const [pRes, rRes] = await Promise.all([
      fetch('/api/v1/products/' + id),
      fetch('/api/v1/products/' + id + '/stock-requirements'),
    ]);
    const [pData, rData] = await Promise.all([pRes.json(), rRes.json()]);
    if (!pData.success) { document.getElementById('viewProductBody').innerHTML = '<p class="text-danger">Failed to load product</p>'; return; }
    const p    = pData.data;
    const reqs = rData.success ? (rData.data?.requirements ?? []) : [];
    const max  = rData.success ? (rData.data?.max_sellable ?? '?') : '?';
    const img  = p.image_path ? appPath(p.image_path) : appPath('/assets/images/no-image.png');
    const maxBadge = max === 0 ? 'badge-danger' : (max <= 10 ? 'badge-warning' : 'badge-success');

    let reqsHtml = '';
    if (reqs.length > 0) {
      reqsHtml = `<div style="margin-top:16px">
        <div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600;margin-bottom:6px">Stock Requirements</div>
        <table style="width:100%;border-collapse:collapse;font-size:.82rem">
          <thead><tr style="background:var(--color-gray-50)">
            <th style="padding:6px 10px;text-align:left;border-bottom:1px solid var(--color-gray-100)">Stock Product</th>
            <th style="padding:6px 10px;text-align:right;border-bottom:1px solid var(--color-gray-100)">Qty/unit</th>
            <th style="padding:6px 10px;text-align:right;border-bottom:1px solid var(--color-gray-100)">In Stock</th>
          </tr></thead><tbody>
          ${reqs.map(r => `<tr>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50)">[${r.stock_product_code}] ${r.stock_product_name}</td>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50);text-align:right">${parseFloat(r.qty_required_per_unit)}</td>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50);text-align:right">${parseInt(r.current_qty)}</td>
          </tr>`).join('')}
          </tbody>
        </table>
      </div>`;
    } else {
      reqsHtml = `<div style="margin-top:16px;padding:10px;background:var(--color-gray-50);border-radius:6px;font-size:.82rem;color:var(--color-gray-400)">No stock requirements assigned yet.</div>`;
    }

    document.getElementById('viewProductBody').innerHTML = `
      <div style="display:grid;grid-template-columns:140px 1fr;gap:20px;align-items:start">
        <img src="${img}" onerror="this.src='${appPath('/assets/images/no-image.png')}'"
          style="width:140px;height:140px;object-fit:cover;border-radius:10px;border:1px solid var(--color-gray-100)">
        <div>
          <h3 style="margin:0 0 4px">${p.name}</h3>
          <code style="font-size:.8rem;color:var(--color-gray-500)">${p.sku}</code>
          ${p.description ? `<p style="margin:10px 0 0;font-size:.875rem;color:var(--color-gray-600)">${p.description}</p>` : ''}
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:20px">
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Category</div><div>${p.category_name || '-'}</div></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Type</div><div>${p.type_name || '-'}</div></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Color</div><div>${p.color_name || '-'}</div></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Size</div><div>${p.size_name || '-'}</div></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Cost Price</div><div>₱${parseFloat(p.cost_price).toFixed(2)}</div></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Selling Price</div><div style="font-weight:700;color:var(--color-primary)">₱${parseFloat(p.selling_price).toFixed(2)}</div></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Max Sellable</div><div><span class="badge ${maxBadge}">${max}</span></div></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Status</div><div><span class="badge ${p.status === 'active' ? 'badge-success' : 'badge-gray'}">${p.status}</span></div></div>
      </div>
      ${reqsHtml}`;
  } catch (e) { document.getElementById('viewProductBody').innerHTML = '<p class="text-danger">Network error</p>'; }
}

const productsBaseUrl = '<?= htmlspecialchars(app_url('/products')) ?>';

let searchTimeout;
function debouncedLoad() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(loadProducts, 400);
}

function loadProducts() {
  const params = new URLSearchParams();
  const search   = document.getElementById('searchInput').value.trim();
  const category = document.getElementById('categoryFilter').value;
  const type     = document.getElementById('typeFilter').value;
  const color    = document.getElementById('colorFilter').value;
  const size     = document.getElementById('sizeFilter').value;
  const status   = document.getElementById('statusFilter').value;
  if (search)   params.set('search', search);
  if (category) params.set('category_id', category);
  if (type)     params.set('type_id', type);
  if (color)    params.set('color_id', color);
  if (size)     params.set('size_id', size);
  if (status)   params.set('status', status);
  // Preserve current sort/order from URL
  const cur = new URLSearchParams(window.location.search);
  if (cur.get('sort'))  params.set('sort',  cur.get('sort'));
  if (cur.get('order')) params.set('order', cur.get('order'));
  const qs = params.toString();
  const pageUrl = window.location.origin + productsBaseUrl + (qs ? '?' + qs : '');
  history.pushState({}, '', pageUrl);
  const container = document.getElementById('productsResultsContainer');
  if (container) container.style.opacity = '0.5';
  fetch(pageUrl)
    .then(r => r.text())
    .then(html => {
      const doc = new DOMParser().parseFromString(html, 'text/html');
      const el = doc.getElementById('productsResultsContainer');
      if (el && container) { container.innerHTML = el.innerHTML; container.style.opacity = '1'; filterByStockStatus(); }
    })
    .catch(() => { if (container) container.style.opacity = '1'; });
}

function filterByStockStatus() {
  const val = document.getElementById('stockStatusFilter')?.value || '';
  document.querySelectorAll('#productsBody tr[data-stock-status]').forEach(tr => {
    tr.style.display = (!val || tr.dataset.stockStatus === val) ? '' : 'none';
  });
}

function resetFilters() {
  ['searchInput','categoryFilter','typeFilter','colorFilter','sizeFilter','statusFilter','stockStatusFilter'].forEach(id => {
    const el = document.getElementById(id); if (el) el.value = '';
  });
  filterByStockStatus();
  loadProducts();
}
</script>
<?php
$content = ob_get_clean();
$title   = 'Products | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
