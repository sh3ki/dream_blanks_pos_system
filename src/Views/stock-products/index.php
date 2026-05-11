<?php ob_start(); ?>
<?php
$sort   = $filters['sort']   ?? 'sp.created_at';
$order  = strtolower($filters['order'] ?? 'desc');
function spSortLink(string $col, string $label, string $currentSort, string $currentOrder, array $filters): string {
    $nextOrder = ($currentSort === $col && $currentOrder === 'asc') ? 'desc' : 'asc';
    $arrow = $currentSort === $col
        ? ($currentOrder === 'asc' ? ' <span style="font-size:.8em">▲</span>' : ' <span style="font-size:.8em">▼</span>')
        : ' <span style="font-size:.8em;opacity:.5">⇅</span>';
    $q = http_build_query(array_merge($filters, ['sort' => $col, 'order' => $nextOrder]));
    return '<a href="?' . $q . '" style="display:block;padding:12px 16px;color:inherit;text-decoration:none;white-space:nowrap">' . htmlspecialchars($label) . $arrow . '</a>';
}
?>
<div class="page-header">
  <h1>Stock Products</h1>
  <div class="d-flex gap-8">
    <button class="btn btn-secondary btn-sm" onclick="showSpImportModal()"><?= icon('upload', 15) ?> Import CSV</button>
    <button class="btn btn-primary" onclick="showSpModal()">+ Add Stock Product</button>
  </div>
</div>

<!-- Floating multi-select adjust bar -->
<div id="spAdjustSelectionBar" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:var(--color-primary);color:#fff;padding:12px 24px;border-radius:30px;box-shadow:0 4px 16px rgba(0,0,0,.2);z-index:1000;align-items:center;gap:12px">
  <span id="spAdjustSelectionCount">0 selected</span>
  <button class="btn btn-sm" style="background:#fff;color:var(--color-primary);font-weight:600" onclick="openBulkAdjust()">Adjust Selected</button>
  <button style="background:none;border:none;color:#fff;cursor:pointer;font-size:1.1rem" onclick="clearSpSelection()">✕</button>
</div>

<div class="card">
  <div class="card-body" style="padding:16px">
    <div class="filter-bar" style="flex-wrap:wrap;gap:8px">
      <div class="search-bar" style="flex:1;min-width:200px;max-width:280px">
        <?= icon('search', 16) ?> <input type="text" id="searchInput" placeholder="Search by name or code..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" oninput="debouncedLoad()" style="width:100%">
      </div>
      <select id="typeFilter" class="form-select" style="width:130px;height:38px" onchange="loadSp()">
        <option value="">All Types</option>
        <?php foreach ($types ?? [] as $t): ?>
          <option value="<?= $t['id'] ?>" <?= ($filters['type_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="colorFilter" class="form-select" style="width:120px;height:38px" onchange="loadSp()">
        <option value="">All Colors</option>
        <?php foreach ($colors ?? [] as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($filters['color_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="sizeFilter" class="form-select" style="width:110px;height:38px" onchange="loadSp()">
        <option value="">All Sizes</option>
        <?php foreach ($sizes ?? [] as $s): ?>
          <option value="<?= $s['id'] ?>" <?= ($filters['size_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="statusFilter" class="form-select" style="width:120px;height:38px" onchange="loadSp()">
        <option value="">All Status</option>
        <option value="active"   <?= ($filters['status'] ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
      <select id="stockStatusFilter" class="form-select" style="width:150px;height:38px" onchange="loadSp()">
        <option value="">All Stock Status</option>
        <option value="in_stock"     <?= ($filters['stock_status'] ?? '') === 'in_stock'     ? 'selected' : '' ?>>In Stock</option>
        <option value="low_stock"    <?= ($filters['stock_status'] ?? '') === 'low_stock'    ? 'selected' : '' ?>>Low Stock</option>
        <option value="out_of_stock" <?= ($filters['stock_status'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
      </select>
      <button class="btn btn-secondary btn-sm" onclick="resetSpFilters()" style="height:38px">Reset</button>
    </div>
  </div>

  <div id="spResultsContainer">
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr style="cursor:pointer">
          <th style="width:38px"><input type="checkbox" id="spSelectAll" onchange="toggleSpSelectAll(this)" title="Select all"></th>
          <th style="width:52px">Image</th>
          <th style="padding:0"><?= spSortLink('sp.code',        'Code',    $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= spSortLink('sp.name',        'Name',    $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= spSortLink('t.name',         'Type',    $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= spSortLink('c.name',         'Color',   $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= spSortLink('s.name',         'Size',    $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= spSortLink('sp.current_qty', 'Qty',     $sort, $order, $filters) ?></th>
          <th>Stock Status</th>
          <th style="padding:0"><?= spSortLink('sp.status',      'Status',  $sort, $order, $filters) ?></th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="spBody">
        <?php foreach ($stock_products as $sp): ?>
        <tr style="cursor:pointer" onclick="viewSp(<?= $sp['id'] ?>)">
          <td onclick="event.stopPropagation()">
            <input type="checkbox" class="sp-select" value="<?= $sp['id'] ?>"
              data-name="<?= htmlspecialchars($sp['name'], ENT_QUOTES) ?>"
              onchange="onSpRowSelect()">
          </td>
          <td onclick="event.stopPropagation()">
            <img
              src="<?= htmlspecialchars(!empty($sp['image_path']) ? app_url($sp['image_path']) : asset_url('/assets/images/no-image.png')) ?>"
              alt="<?= htmlspecialchars($sp['name']) ?>"
              style="width:42px;height:42px;object-fit:cover;border-radius:8px;border:1px solid var(--color-gray-100)"
              onerror="this.src='<?= htmlspecialchars(asset_url('/assets/images/no-image.png')) ?>'">
          </td>
          <td onclick="event.stopPropagation()"><code style="font-size:.8rem"><?= htmlspecialchars($sp['code']) ?></code></td>
          <td><?= htmlspecialchars($sp['name']) ?></td>
          <td><?= htmlspecialchars($sp['type_name']  ?? '-') ?></td>
          <td><?= htmlspecialchars($sp['color_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($sp['size_name']  ?? '-') ?></td>
          <td>
            <?php
              $qty   = (int)$sp['current_qty'];
              $alert = (int)($sp['low_stock_alert'] ?? 10);
              $sCls  = $qty <= 0 ? 'badge-danger' : ($qty <= $alert ? 'badge-warning' : 'badge-success');
              $sLbl  = $qty <= 0 ? 'Out of Stock' : ($qty <= $alert ? 'Low Stock' : 'In Stock');
            ?>
            <span class="badge <?= $sCls ?>"><?= $qty ?></span>
          </td>
          <td><span class="badge <?= $sCls ?>"><?= $sLbl ?></span></td>
          <td><span class="badge <?= $sp['status'] === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= ucfirst($sp['status']) ?></span></td>
          <td onclick="event.stopPropagation()">
            <button class="icon-btn" onclick="adjustSp(<?= $sp['id'] ?>, '<?= htmlspecialchars($sp['name'], ENT_QUOTES) ?>', <?= $qty ?>)" title="Adjust Stock">±</button>
            <button class="icon-btn" onclick="editSp(<?= $sp['id'] ?>)" title="Edit"><?= icon('edit', 15) ?></button>
            <button class="icon-btn danger" onclick="deleteSp(<?= $sp['id'] ?>, '<?= htmlspecialchars($sp['name'], ENT_QUOTES) ?>')" title="Delete"><?= icon('delete', 15) ?></button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($stock_products)): ?>
          <tr><td colspan="11" class="text-center text-muted" style="padding:48px">No stock products found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($pagination) && $pagination['last_page'] > 1): ?>
  <?php $pq = http_build_query(array_filter(array_merge($filters, ['page' => null]), fn($v) => $v !== null && $v !== '')); ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
      <?php $href = '?' . ($pq ? $pq . '&' : '') . 'page=' . $i; ?>
      <a href="<?= $href ?>" class="page-link <?= $pagination['current_page'] == $i ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  </div><!-- /spResultsContainer -->
</div>

<!-- Create / Edit Modal -->
<div class="modal-overlay" id="spModal">
  <div class="modal-content" style="max-width:560px">
    <div class="modal-header">
      <h2 class="modal-title" id="spModalTitle">Add Stock Product</h2>
      <button class="modal-close" onclick="closeModal('spModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Code <span class="required">*</span></label>
          <input type="text" id="spCode" class="form-input" placeholder="e.g., SP-001" required>
        </div>
        <div class="form-group">
          <label class="form-label">Name <span class="required">*</span></label>
          <input type="text" id="spName" class="form-input" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea id="spDesc" class="form-textarea" style="min-height:60px"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Type</label>
          <select id="spType" class="form-select">
            <option value="">None</option>
            <?php foreach ($types ?? [] as $t): ?>
              <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Color</label>
          <select id="spColor" class="form-select">
            <option value="">None</option>
            <?php foreach ($colors ?? [] as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Size</label>
          <select id="spSize" class="form-select">
            <option value="">None</option>
            <?php foreach ($sizes ?? [] as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Low Stock Alert</label>
          <input type="number" id="spAlert" class="form-input" min="0" value="10">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="spStatus" class="form-select">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Image</label>
        <div style="display:flex;align-items:center;gap:12px">
          <img id="spImagePreview" src="" alt="" style="display:none;width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid var(--color-gray-200)">
          <div>
            <input type="file" id="spImageFile" accept="image/*" onchange="previewSpImage(this)" style="display:none">
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('spImageFile').click()">Choose Image</button>
            <div id="spImageFilename" style="font-size:.8rem;color:var(--color-gray-500);margin-top:4px">No file chosen</div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('spModal')">Cancel</button>
      <button class="btn btn-primary" onclick="confirmSaveSp()" id="saveSpBtn">Save</button>
    </div>
  </div>
</div>

<!-- View Modal -->
<div class="modal-overlay" id="viewSpModal">
  <div class="modal-content" style="max-width:620px">
    <div class="modal-header">
      <h2 class="modal-title">Stock Product Details</h2>
      <button class="modal-close" onclick="closeModal('viewSpModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body" id="viewSpBody"><div style="text-align:center;padding:48px"><span class="spinner"></span></div></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('viewSpModal')">Close</button>
      <button class="btn btn-primary" id="viewSpEditBtn">Edit</button>
    </div>
  </div>
</div>

<!-- Adjust Stock Modal - single item -->
<div class="modal-overlay" id="adjustSpModal">
  <div class="modal-content" style="max-width:400px">
    <div class="modal-header">
      <h2 class="modal-title">Adjust Stock</h2>
      <button class="modal-close" onclick="closeModal('adjustSpModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <p id="adjustSpLabel" style="margin:0 0 14px;font-weight:600"></p>
      <div class="form-group">
        <label class="form-label">Type</label>
        <div style="display:flex;gap:12px">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
            <input type="radio" name="adjustType" value="add" checked> Add
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
            <input type="radio" name="adjustType" value="deduct"> Deduct
          </label>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Quantity <span class="required">*</span></label>
        <input type="number" id="adjustQty" class="form-input" min="1" value="1">
      </div>
      <div class="form-group">
        <label class="form-label">Reason</label>
        <input type="text" id="adjustReason" class="form-input" placeholder="e.g., Manual correction" value="Manual adjustment">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('adjustSpModal')">Cancel</button>
      <button class="btn btn-primary" onclick="confirmAdjust()" id="adjustSpBtn">Adjust</button>
    </div>
  </div>
</div>

<!-- Bulk Adjust Modal - multiple items -->
<div class="modal-overlay" id="bulkAdjustModal">
  <div class="modal-content" style="max-width:520px">
    <div class="modal-header">
      <h2 class="modal-title">Adjust Stock — Multiple Items</h2>
      <button class="modal-close" onclick="closeModal('bulkAdjustModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <div style="max-height:220px;overflow-y:auto;border:1px solid var(--color-gray-100);border-radius:6px;margin-bottom:14px">
        <table class="data-table" style="margin:0">
          <thead>
            <tr><th>Stock Product</th><th style="width:100px">Qty <span class="required">*</span></th></tr>
          </thead>
          <tbody id="bulkAdjustBody"></tbody>
        </table>
      </div>
      <div class="form-group">
        <label class="form-label">Type</label>
        <div style="display:flex;gap:12px">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
            <input type="radio" name="bulkAdjustType" value="add" checked> Add
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
            <input type="radio" name="bulkAdjustType" value="deduct"> Deduct
          </label>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Reason</label>
        <input type="text" id="bulkAdjustReason" class="form-input" placeholder="e.g., Manual correction" value="Manual adjustment">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('bulkAdjustModal')">Cancel</button>
      <button class="btn btn-primary" onclick="confirmBulkAdjust()" id="bulkAdjustBtn">Adjust All</button>
    </div>
  </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal-overlay" id="confirmSpModal">
  <div class="modal-content" style="max-width:400px">
    <div class="modal-header"><h2 class="modal-title">Confirm Delete</h2><button class="modal-close" onclick="closeModal('confirmSpModal')">✕</button></div>
    <div class="modal-body"><p id="confirmSpMessage"></p></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('confirmSpModal')">Cancel</button>
      <button class="btn btn-danger" id="confirmSpBtn">Delete</button>
    </div>
  </div>
</div>

<!-- Action Confirm Modal (save/adjust) -->
<div class="modal-overlay" id="spActionConfirmModal">
  <div class="modal-content" style="max-width:420px">
    <div class="modal-header">
      <h2 class="modal-title" id="spActionConfirmTitle">Confirm</h2>
      <button class="modal-close" onclick="closeModal('spActionConfirmModal')">✕</button>
    </div>
    <div class="modal-body"><p id="spActionConfirmMessage" style="margin:0"></p></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('spActionConfirmModal')">Cancel</button>
      <button class="btn btn-primary" id="spActionConfirmBtn">Confirm</button>
    </div>
  </div>
</div>

<!-- Import Modal -->
<div class="modal-overlay" id="spImportModal">
  <div class="modal-content" style="max-width:540px">
    <div class="modal-header">
      <h2 class="modal-title">Import Stock Products</h2>
      <button class="modal-close" onclick="closeModal('spImportModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <div style="background:#f0f7ff;border:1px solid #b3d4f5;border-radius:8px;padding:14px 16px;margin-bottom:16px;font-size:.875rem;line-height:1.6">
        <strong style="display:block;margin-bottom:6px">How to Import Stock Products</strong>
        <ol style="margin:0 0 10px 18px;padding:0">
          <li>Download the CSV template below.</li>
          <li>Fill in your stock products starting from <strong>Row 2</strong> (Row 1 = headers).</li>
          <li>Required columns: <em>Code, Name</em>. Optional: Type, Color, Size, Low Stock Alert, Initial Qty, Status.</li>
          <li>Upload the filled CSV here and click <strong>Import</strong>.</li>
        </ol>
      </div>
      <a href="<?= app_url('/api/v1/stock-products/import-template') ?>" class="btn btn-secondary btn-sm" style="margin-bottom:14px" download>
        <?= icon('download', 13) ?> Download Template (.csv)
      </a>
      <div class="form-group">
        <label class="form-label">Choose File (.csv) <span class="required">*</span></label>
        <input type="file" id="spImportFile" class="form-input" accept=".csv,text/csv">
      </div>
      <div id="spImportResult" style="display:none"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('spImportModal')">Cancel</button>
      <button class="btn btn-primary" onclick="doSpImport()" id="spImportBtn"><?= icon('upload', 14) ?> Import</button>
    </div>
  </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
let _currentSpId = null;

function showSpModal(id = null) {
  _currentSpId = id;
  document.getElementById('spModalTitle').textContent = id ? 'Edit Stock Product' : 'Add Stock Product';
  if (!id) {
    ['spCode','spName','spDesc'].forEach(k => document.getElementById(k).value = '');
    document.getElementById('spType').value   = '';
    document.getElementById('spColor').value  = '';
    document.getElementById('spSize').value   = '';
    document.getElementById('spAlert').value  = 10;
    document.getElementById('spStatus').value = 'active';
    document.getElementById('spImageFile').value  = '';
    document.getElementById('spImagePreview').style.display = 'none';
    document.getElementById('spImageFilename').textContent  = 'No file chosen';
  }
  openModal('spModal');
}

function closeModal(id) { document.getElementById(id).classList.remove('show'); }
function openModal(id)  { document.getElementById(id).classList.add('show'); }

async function editSp(id) {
  const res  = await fetch('/api/v1/stock-products/' + id);
  const data = await res.json();
  if (!data.success) return showToast('Failed to load', 'error');
  const s = data.data;
  document.getElementById('spCode').value   = s.code;
  document.getElementById('spName').value   = s.name;
  document.getElementById('spDesc').value   = s.description || '';
  document.getElementById('spType').value   = s.type_id   || '';
  document.getElementById('spColor').value  = s.color_id  || '';
  document.getElementById('spSize').value   = s.size_id   || '';
  document.getElementById('spAlert').value  = s.low_stock_alert || 10;
  document.getElementById('spStatus').value = s.status;
  // Image preview
  document.getElementById('spImageFile').value = '';
  document.getElementById('spImageFilename').textContent = 'No file chosen (keep existing)';
  const preview = document.getElementById('spImagePreview');
  if (s.image_path) {
    preview.src = (window.APP_BASE_PATH || '') + s.image_path;
    preview.style.display = 'block';
  } else {
    preview.style.display = 'none';
  }
  showSpModal(id);
}

function previewSpImage(input) {
  const preview  = document.getElementById('spImagePreview');
  const filename = document.getElementById('spImageFilename');
  if (input.files && input.files[0]) {
    filename.textContent = input.files[0].name;
    const reader = new FileReader();
    reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
    reader.readAsDataURL(input.files[0]);
  }
}

function confirmSaveSp() {
  const id   = _currentSpId;
  const name = document.getElementById('spName').value.trim() || 'this stock product';
  const code = document.getElementById('spCode').value.trim();
  if (!code || !name) return saveSp(); // let saveSp show the validation error
  spConfirmAction(
    id ? 'Update Stock Product' : 'Add Stock Product',
    id ? `Update "${name}"?` : `Create new stock product "${name}"?`,
    saveSp
  );
}

async function saveSp() {
  const id  = _currentSpId;
  const btn = document.getElementById('saveSpBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving...';

  const code = document.getElementById('spCode').value.trim();
  const name = document.getElementById('spName').value.trim();
  if (!code || !name) {
    btn.disabled = false; btn.innerHTML = 'Save';
    return showToast('Code and name are required', 'error');
  }

  const formData = new FormData();
  formData.append('code',            code);
  formData.append('name',            name);
  formData.append('description',     document.getElementById('spDesc').value.trim());
  formData.append('type_id',         document.getElementById('spType').value  || '');
  formData.append('color_id',        document.getElementById('spColor').value || '');
  formData.append('size_id',         document.getElementById('spSize').value  || '');
  formData.append('low_stock_alert', document.getElementById('spAlert').value);
  formData.append('status',          document.getElementById('spStatus').value);
  const imgFile = document.getElementById('spImageFile').files[0];
  if (imgFile) formData.append('image', imgFile);
  if (id) formData.append('_method', 'PUT');

  const url = id ? '/api/v1/stock-products/' + id : '/api/v1/stock-products';

  try {
    const res  = await fetch(url, {
      method:  'POST',
      headers: { 'X-CSRF-Token': csrfToken },
      body:    formData,
    });
    const data = await res.json();
    if (data.success) { showToast(data.message, 'success'); closeModal('spModal'); setTimeout(loadSp, 700); }
    else showToast(data.message || 'Failed', 'error');
  } catch (e) { showToast('Network error', 'error'); }

  btn.disabled = false; btn.innerHTML = 'Save';
}

async function viewSp(id) {
  document.getElementById('viewSpBody').innerHTML = '<div style="text-align:center;padding:48px"><span class="spinner"></span></div>';
  document.getElementById('viewSpEditBtn').onclick = () => { closeModal('viewSpModal'); editSp(id); };
  openModal('viewSpModal');
  try {
    const res  = await fetch('/api/v1/stock-products/' + id);
    const data = await res.json();
    if (!data.success) { document.getElementById('viewSpBody').innerHTML = '<p class="text-danger">Failed to load</p>'; return; }
    const s    = data.data;
    const qty  = parseInt(s.current_qty);
    const alrt = parseInt(s.low_stock_alert || 10);
    const cls  = qty <= 0 ? 'badge-danger' : (qty <= alrt ? 'badge-warning' : 'badge-success');
    const used = s.used_by_products ?? [];

    let usedHtml = '';
    if (used.length > 0) {
      usedHtml = `<div style="margin-top:16px">
        <div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600;margin-bottom:6px">Used by Sellable Products</div>
        <table style="width:100%;border-collapse:collapse;font-size:.82rem">
          <thead><tr style="background:var(--color-gray-50)">
            <th style="padding:6px 10px;text-align:left;border-bottom:1px solid var(--color-gray-100)">SKU</th>
            <th style="padding:6px 10px;text-align:left;border-bottom:1px solid var(--color-gray-100)">Name</th>
            <th style="padding:6px 10px;text-align:right;border-bottom:1px solid var(--color-gray-100)">Qty/unit</th>
            <th style="padding:6px 10px;text-align:right;border-bottom:1px solid var(--color-gray-100)">Waste %</th>
          </tr></thead><tbody>
          ${used.map(p => `<tr>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50)"><code style="font-size:.78rem">${p.sku}</code></td>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50)">${p.name}</td>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50);text-align:right">${parseFloat(p.qty_required_per_unit)}</td>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50);text-align:right">${parseFloat(p.waste_percent || 0)}%</td>
          </tr>`).join('')}
          </tbody>
        </table>
      </div>`;
    }

    document.getElementById('viewSpBody').innerHTML = `
      ${s.image_path ? `<div style="text-align:center;margin-bottom:16px"><img src="${(window.APP_BASE_PATH||'')+s.image_path}" alt="${s.name}" style="max-height:160px;border-radius:10px;border:1px solid var(--color-gray-100);object-fit:contain"></div>` : ''}
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Code</div><code>${s.code}</code></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Name</div><strong>${s.name}</strong></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Status</div><span class="badge ${s.status === 'active' ? 'badge-success' : 'badge-gray'}">${s.status}</span></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Type</div>${s.type_name  || '-'}</div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Color</div>${s.color_name || '-'}</div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Size</div>${s.size_name  || '-'}</div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Current Qty</div><span class="badge ${cls}">${qty}</span></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Low Stock Alert</div>${alrt}</div>
      </div>
      ${s.description ? `<p style="margin:12px 0 0;font-size:.875rem;color:var(--color-gray-600)">${s.description}</p>` : ''}
      ${usedHtml}`;
  } catch (e) { document.getElementById('viewSpBody').innerHTML = '<p class="text-danger">Network error</p>'; }
}

function adjustSp(id, name, currentQty) {
  _currentSpId = id;
  document.getElementById('adjustSpLabel').textContent = `Adjusting: ${name} (current: ${currentQty})`;
  document.getElementById('adjustQty').value  = 1;
  document.getElementById('adjustReason').value = 'Manual adjustment';
  document.querySelector('input[name="adjustType"][value="add"]').checked = true;
  openModal('adjustSpModal');
}

function spConfirmAction(title, message, onConfirm) {
  document.getElementById('spActionConfirmTitle').textContent   = title;
  document.getElementById('spActionConfirmMessage').textContent = message;
  document.getElementById('spActionConfirmBtn').onclick = () => { closeModal('spActionConfirmModal'); onConfirm(); };
  openModal('spActionConfirmModal');
}

function confirmAdjust() {
  const type = document.querySelector('input[name="adjustType"]:checked')?.value;
  const qty  = parseInt(document.getElementById('adjustQty').value, 10);
  if (!qty || qty <= 0) return showToast('Enter a valid quantity', 'error');
  spConfirmAction(
    'Confirm Stock Adjustment',
    `Are you sure you want to ${type} ${qty} unit(s)?`,
    submitAdjust
  );
}

async function submitAdjust() {
  const id     = _currentSpId;
  const type   = document.querySelector('input[name="adjustType"]:checked')?.value;
  const qty    = parseInt(document.getElementById('adjustQty').value, 10);
  const reason = document.getElementById('adjustReason').value.trim() || 'Manual adjustment';
  if (!qty || qty <= 0) return showToast('Enter a valid quantity', 'error');

  const btn = document.getElementById('adjustSpBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';

  try {
    const res  = await fetch(`/api/v1/stock-products/${id}/adjust`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body:   JSON.stringify({ type, quantity: qty, reason }),
    });
    const data = await res.json();
    if (data.success) { showToast('Stock adjusted', 'success'); closeModal('adjustSpModal'); setTimeout(loadSp, 600); }
    else showToast(data.message || 'Failed', 'error');
  } catch (e) { showToast('Network error', 'error'); }

  btn.disabled = false; btn.innerHTML = 'Adjust';
}

// ---- Multi-select adjust ----
function onSpRowSelect() {
  const checked = document.querySelectorAll('.sp-select:checked');
  const bar = document.getElementById('spAdjustSelectionBar');
  if (checked.length > 0) {
    document.getElementById('spAdjustSelectionCount').textContent = checked.length + ' selected';
    bar.style.display = 'flex';
  } else {
    bar.style.display = 'none';
  }
  const all = document.querySelectorAll('.sp-select');
  const sa  = document.getElementById('spSelectAll');
  if (sa) { sa.indeterminate = checked.length > 0 && checked.length < all.length; sa.checked = checked.length === all.length; }
}

function toggleSpSelectAll(cb) {
  document.querySelectorAll('.sp-select').forEach(c => c.checked = cb.checked);
  onSpRowSelect();
}

function clearSpSelection() {
  document.querySelectorAll('.sp-select').forEach(c => c.checked = false);
  const sa = document.getElementById('spSelectAll');
  if (sa) { sa.checked = false; sa.indeterminate = false; }
  document.getElementById('spAdjustSelectionBar').style.display = 'none';
}

function openBulkAdjust() {
  const checked = document.querySelectorAll('.sp-select:checked');
  const tbody = document.getElementById('bulkAdjustBody');
  tbody.innerHTML = '';
  checked.forEach(cb => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${cb.dataset.name}</td>
      <td><input type="number" class="form-input bulk-adj-qty" data-id="${cb.value}" min="1" value="1" style="height:32px"></td>
    `;
    tbody.appendChild(tr);
  });
  document.getElementById('bulkAdjustReason').value = 'Manual adjustment';
  document.querySelector('input[name="bulkAdjustType"][value="add"]').checked = true;
  openModal('bulkAdjustModal');
}

function confirmBulkAdjust() {
  const type  = document.querySelector('input[name="bulkAdjustType"]:checked')?.value;
  const count = document.querySelectorAll('.bulk-adj-qty').length;
  spConfirmAction(
    'Confirm Bulk Adjustment',
    `Are you sure you want to ${type} stock for ${count} item(s)?`,
    submitBulkAdjust
  );
}

async function submitBulkAdjust() {
  const type   = document.querySelector('input[name="bulkAdjustType"]:checked')?.value;
  const reason = document.getElementById('bulkAdjustReason').value.trim() || 'Manual adjustment';
  const items  = [];
  document.querySelectorAll('.bulk-adj-qty').forEach(inp => {
    const id  = parseInt(inp.dataset.id, 10);
    const qty = parseInt(inp.value, 10);
    if (id > 0 && qty > 0) items.push({ id, quantity: qty });
  });
  if (items.length === 0) return showToast('Add at least one valid quantity', 'error');

  const btn = document.getElementById('bulkAdjustBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Adjusting...';

  try {
    const res  = await fetch('/api/v1/stock-products/bulk-adjust', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body:   JSON.stringify({ type, reason, items }),
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      closeModal('bulkAdjustModal');
      clearSpSelection();
      setTimeout(loadSp, 600);
    } else showToast(data.message || 'Failed', 'error');
  } catch (e) { showToast('Network error', 'error'); }

  btn.disabled = false; btn.innerHTML = 'Adjust All';
}

function deleteSp(id, name) {
  document.getElementById('confirmSpMessage').textContent = `Delete stock product "${name}"? Products using this stock product will need to be reassigned.`;
  document.getElementById('confirmSpBtn').onclick = async () => {
    const res  = await fetch('/api/v1/stock-products/' + id, {
      method:  'DELETE',
      headers: { 'X-CSRF-Token': csrfToken },
    });
    const data = await res.json();
    if (data.success) { showToast('Deleted', 'success'); closeModal('confirmSpModal'); setTimeout(loadSp, 600); }
    else showToast(data.message, 'error');
  };
  openModal('confirmSpModal');
}

const spBaseUrl = '<?= htmlspecialchars(app_url('/stock-products')) ?>';
let _spSearchTimer = null;

function debouncedLoad() {
  clearTimeout(_spSearchTimer);
  _spSearchTimer = setTimeout(loadSp, 400);
}

function loadSp() {
  const params = new URLSearchParams();
  const search      = document.getElementById('searchInput').value.trim();
  const type        = document.getElementById('typeFilter').value;
  const color       = document.getElementById('colorFilter').value;
  const size        = document.getElementById('sizeFilter').value;
  const status      = document.getElementById('statusFilter').value;
  const stockStatus = document.getElementById('stockStatusFilter').value;
  if (search)      params.set('search', search);
  if (type)        params.set('type_id', type);
  if (color)       params.set('color_id', color);
  if (size)        params.set('size_id', size);
  if (status)      params.set('status', status);
  if (stockStatus) params.set('stock_status', stockStatus);
  const cur = new URLSearchParams(window.location.search);
  if (cur.get('sort'))  params.set('sort',  cur.get('sort'));
  if (cur.get('order')) params.set('order', cur.get('order'));
  const qs      = params.toString();
  const pageUrl = window.location.origin + spBaseUrl + (qs ? '?' + qs : '');
  history.pushState({}, '', pageUrl);
  const container = document.getElementById('spResultsContainer');
  if (container) container.style.opacity = '0.5';
  fetch(pageUrl).then(r => r.text()).then(html => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const el  = doc.getElementById('spResultsContainer');
    if (el && container) { container.innerHTML = el.innerHTML; container.style.opacity = '1'; }
  }).catch(() => { if (container) container.style.opacity = '1'; });
}

function resetSpFilters() {
  ['searchInput','typeFilter','colorFilter','sizeFilter','statusFilter','stockStatusFilter'].forEach(id => {
    const el = document.getElementById(id); if (el) el.value = '';
  });
  loadSp();
}

function showSpImportModal() {
  document.getElementById('spImportFile').value = '';
  const res = document.getElementById('spImportResult');
  res.style.display = 'none'; res.innerHTML = '';
  openModal('spImportModal');
}

async function doSpImport() {
  const file = document.getElementById('spImportFile').files[0];
  if (!file) return showToast('Please select a CSV file', 'error');
  const btn = document.getElementById('spImportBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Importing...';
  const formData = new FormData();
  formData.append('file', file);
  try {
    const res  = await fetch('/api/v1/stock-products/bulk-import', { method: 'POST', headers: { 'X-CSRF-Token': csrfToken }, body: formData });
    const data = await res.json();
    const resultEl = document.getElementById('spImportResult');
    resultEl.style.display = 'block';
    if (data.success) {
      showToast(data.message, 'success');
      resultEl.innerHTML = `<div style="color:var(--color-success);padding:10px;background:#f0fff4;border-radius:6px;font-size:.875rem">${data.message}</div>`;
      setTimeout(() => { closeModal('spImportModal'); loadSp(); }, 1500);
    } else {
      let html = `<div style="color:var(--color-danger);padding:10px;background:#fff0f0;border-radius:6px;font-size:.875rem">${data.message || 'Import failed'}`;
      if (data.errors?.length) {
        html += '<ul style="margin:8px 0 0 16px">' + data.errors.map(e => `<li>Row ${e.row}: ${e.message}</li>`).join('') + '</ul>';
      }
      html += '</div>';
      resultEl.innerHTML = html;
    }
  } catch (e) { showToast('Network error', 'error'); }
  btn.disabled = false; btn.innerHTML = '<?= icon('upload', 14) ?> Import';
}
</script>
<?php
$content = ob_get_clean();
$title   = 'Stock Products | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
