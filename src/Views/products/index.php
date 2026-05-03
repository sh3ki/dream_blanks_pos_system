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
    <div class="filter-bar">
      <div class="search-bar" style="flex:1;max-width:300px">
        <?= icon('search', 16) ?> <input type="text" id="searchInput" placeholder="Search products..." oninput="loadProducts()" style="width:100%">
      </div>
      <select id="categoryFilter" class="form-select" style="width:150px;height:38px" onchange="loadProducts()">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="statusFilter" class="form-select" style="width:130px;height:38px" onchange="loadProducts()">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>
  </div>

  <div class="table-wrapper">
    <table class="data-table" id="productsTable">
      <thead>
        <tr>
          <th>Image</th><th>SKU</th><th>Name</th><th>Category</th><th>Color</th><th>Size</th>
          <th>Cost</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody id="productsBody">
        <?php foreach ($products as $p): ?>
        <tr>
          <td>
            <img
              src="<?= htmlspecialchars(!empty($p['image_path']) ? app_url($p['image_path']) : asset_url('/assets/images/no-image.png')) ?>"
              alt="<?= htmlspecialchars($p['name']) ?>"
              style="width:42px;height:42px;object-fit:cover;border-radius:8px;border:1px solid var(--color-gray-100)"
              onerror="this.src='<?= htmlspecialchars(asset_url('/assets/images/no-image.png')) ?>'"
            >
          </td>
          <td><code style="font-size:.8rem"><?= htmlspecialchars($p['sku']) ?></code></td>
          <td><?= htmlspecialchars($p['name']) ?></td>
          <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($p['color_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($p['size_name'] ?? '-') ?></td>
          <td>₱<?= number_format($p['cost_price'], 2) ?></td>
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
          <td>
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
  <div class="pagination">
    <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
      <button class="page-link <?= $pagination['current_page'] == $i ? 'active' : '' ?>" onclick="goPage(<?= $i ?>)"><?= $i ?></button>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
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
    if (data.success) { showToast(data.message, 'success'); closeModal('productModal'); setTimeout(() => location.reload(), 800); }
    else showToast(data.message || 'Failed', 'error');
  } catch (e) { showToast('Network error', 'error'); }

  btn.disabled = false; btn.innerHTML = 'Save Product';
}

function deleteProduct(id, name) {
  document.getElementById('confirmMessage').textContent = `Delete product "${name}"? This cannot be undone.`;
  document.getElementById('confirmBtn').onclick = async () => {
    const res  = await fetch('/api/v1/products/' + id, { method: 'DELETE', headers: { 'X-CSRF-Token': csrfToken } });
    const data = await res.json();
    if (data.success) { showToast('Product deleted', 'success'); closeModal('confirmModal'); setTimeout(() => location.reload(), 600); }
    else showToast(data.message, 'error');
  };
  document.getElementById('confirmModal').classList.add('show');
}

function showImportModal() { alert('CSV import: Create a file with columns: sku,name,description,cost_price,selling_price,initial_stock'); }

let searchTimeout;
function loadProducts() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    const search   = document.getElementById('searchInput').value;
    const category = document.getElementById('categoryFilter').value;
    const status   = document.getElementById('statusFilter').value;
    let url = '?';
    if (search) url += 'search=' + encodeURIComponent(search) + '&';
    if (category) url += 'category_id=' + category + '&';
    if (status) url += 'status=' + status;
    window.history.replaceState(null, '', '/products' + url);
    location.reload();
  }, 500);
}

function goPage(page) { window.location.href = '/products?page=' + page; }
</script>
<?php
$content = ob_get_clean();
$title   = 'Products | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
