<?php ob_start(); ?>
<div class="page-header">
  <h1>Products</h1>
  <div class="d-flex gap-8">
    <button class="btn btn-secondary btn-sm" onclick="showImportModal()"><?= icon('upload', 15) ?> Import CSV</button>
    <button class="btn btn-primary" onclick="showProductModal()">+ Add Product</button>
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
          <th style="padding:0"><?= sortLink('p.sku', 'SKU', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('p.name', 'Name', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('c.name', 'Category', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('t.name', 'Type', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('cl.name', 'Color', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('s.name', 'Size', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('p.selling_price', 'Price', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('p.current_stock', 'Stock', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= sortLink('p.status', 'Status', $sort, $order, $filters) ?></th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="productsBody">
        <?php foreach ($products as $p): ?>
        <tr style="cursor:pointer" onclick="viewProduct(<?= $p['id'] ?>)">
          <td onclick="event.stopPropagation()">
            <img
              src="<?= htmlspecialchars(!empty($p['image_path']) ? app_url($p['image_path']) : asset_url('/assets/images/no-image.png')) ?>"
              alt="<?= htmlspecialchars($p['name']) ?>"
              style="width:42px;height:42px;object-fit:cover;border-radius:8px;border:1px solid var(--color-gray-100)"
              onerror="this.src='<?= htmlspecialchars(asset_url('/assets/images/no-image.png')) ?>'"
            >
          </td>
          <td onclick="event.stopPropagation()"><code style="font-size:.8rem"><?= htmlspecialchars($p['sku']) ?></code></td>
          <td><?= htmlspecialchars($p['name']) ?></td>
          <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($p['type_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($p['color_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($p['size_name'] ?? '-') ?></td>
          <td><strong>₱<?= number_format($p['selling_price'], 2) ?></strong></td>
          <td>
            <?php
              $stock = (int)$p['current_stock'];
              $alert = (int)($p['low_stock_alert'] ?? 10);
              $cls = $stock <= 0 ? 'badge-danger' : ($stock <= $alert ? 'badge-warning' : 'badge-success');
            ?>
            <span class="badge <?= $cls ?>"><?= $stock ?></span>
          </td>
          <td><span class="badge <?= $p['status']==='active' ? 'badge-success' : 'badge-gray' ?>"><?= ucfirst($p['status']) ?></span></td>
          <td onclick="event.stopPropagation()">
            <button class="icon-btn" onclick="editProduct(<?= $p['id'] ?>)" title="Edit"><?= icon('edit', 15) ?></button>
            <button class="icon-btn danger" onclick="deleteProduct(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>')" title="Delete"><?= icon('delete', 15) ?></button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
          <tr><td colspan="11" class="text-center text-muted" style="padding:48px">No products found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if (!empty($pagination) && $pagination['last_page'] > 1): ?>
  <?php $pq = http_build_query(array_filter(array_merge($filters, ['page' => null]), fn($v) => $v !== null && $v !== '')); ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
      <?php $href = '?' . ($pq ? $pq . '&' : '') . 'page=' . $i; ?>
      <a href="<?= $href ?>" class="page-link <?= $pagination['current_page'] == $i ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
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
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Initial Stock</label>
            <input type="number" id="pStock" name="initial_stock" class="form-input" min="0" value="0">
          </div>
          <div class="form-group">
            <label class="form-label">Low Stock Alert</label>
            <input type="number" id="pAlert" name="low_stock_alert" class="form-input" min="0" value="10">
          </div>
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
      <button class="btn btn-primary" onclick="saveProduct()" id="saveProductBtn">Save Product</button>
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
  <div class="modal-content" style="max-width:480px">
    <div class="modal-header">
      <h2 class="modal-title"><?= icon('upload', 16) ?> Import Products</h2>
      <button class="modal-close" onclick="closeModal('importModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <div style="background:#f0f7ff;border:1px solid #b3d4f5;border-radius:8px;padding:14px 16px;margin-bottom:16px;font-size:.875rem;line-height:1.6">
        <strong style="display:block;margin-bottom:6px;font-size:.95rem">How to Import Products</strong>
        <ol style="margin:0 0 10px 18px;padding:0">
          <li>Click <strong>Download Template (.xlsx)</strong> below to get the pre-filled spreadsheet.</li>
          <li>Fill in your products starting from <strong>Row 3</strong> (Row 1 = headers, Row 2 = example).</li>
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
  }
  document.getElementById('productModal').classList.add('show');
}

function closeModal(id) { document.getElementById(id).classList.remove('show'); }

async function editProduct(id) {
  const res = await fetch('/api/v1/products/' + id);
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
  document.getElementById('pAlert').value    = p.low_stock_alert || 10;
  setImagePreview(p.image_path ? appPath(p.image_path) : noImagePreview);
  showProductModal(id);
}

async function saveProduct() {
  const id   = document.getElementById('productId').value;
  const form = document.getElementById('productForm');
  const btn  = document.getElementById('saveProductBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving...';

  const formData = new FormData(form);

  const method = 'POST';
  const url    = id ? '/api/v1/products/' + id : '/api/v1/products';
  if (id) {
    formData.append('_method', 'PUT');
  }

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
      let html = `<div class="alert alert-success"><strong>${data.message}</strong></div>`;
      if (data.data?.errors?.length) {
        html += `<div class="alert alert-warning"><strong>Warnings:</strong><ul style="margin:6px 0 0 16px">${data.data.errors.map(e => `<li style="font-size:.82rem">${e}</li>`).join('')}</ul></div>`;
      }
      resultEl.innerHTML = html;
      setTimeout(() => location.reload(), 2000);
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
    const res  = await fetch('/api/v1/products/' + id);
    const data = await res.json();
    if (!data.success) { document.getElementById('viewProductBody').innerHTML = '<p class="text-danger">Failed to load product</p>'; return; }
    const p = data.data;
    const img = p.image_path ? appPath(p.image_path) : appPath('/assets/images/no-image.png');
    const stock = parseInt(p.current_stock);
    const alert = parseInt(p.low_stock_alert || 10);
    const stockCls = stock <= 0 ? 'badge-danger' : (stock <= alert ? 'badge-warning' : 'badge-success');
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
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Stock</div><div><span class="badge ${stockCls}">${stock}</span></div></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Low Stock Alert</div><div>${p.low_stock_alert || 10}</div></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Status</div><div><span class="badge ${p.status === 'active' ? 'badge-success' : 'badge-gray'}">${p.status}</span></div></div>
      </div>`;
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
      if (el && container) { container.innerHTML = el.innerHTML; container.style.opacity = '1'; }
    })
    .catch(() => { if (container) container.style.opacity = '1'; });
}

function resetFilters() {
  ['searchInput','categoryFilter','typeFilter','colorFilter','sizeFilter','statusFilter'].forEach(id => {
    const el = document.getElementById(id); if (el) el.value = '';
  });
  loadProducts();
}
</script>
<?php
$content = ob_get_clean();
$title   = 'Products | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
