<?php ob_start(); ?>

<div class="page-header">
  <h1>Product Variations</h1>
  <p class="text-muted" style="font-size:.875rem;margin-top:4px">Manage categories, colors, sizes, and types used in products.</p>
</div>

<!-- Four-column layout -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px">

  <!-- ── CATEGORIES ──────────────────────────────────── -->
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
      <h3 class="card-title" style="display:flex;align-items:center;gap:8px">
        <?= icon('tag', 18) ?> Categories
        <span class="badge badge-gray" id="catCount"><?= count($categories) ?></span>
      </h3>
      <?php if (can('variations', 'add')): ?>
      <button class="btn btn-primary btn-sm" onclick="openModal('catModal')" style="display:flex;align-items:center;gap:6px">
        <?= icon('plus', 14) ?> Add
      </button>
      <?php endif; ?>
    </div>
    <div class="card-body" style="padding:0">
      <div class="search-bar" style="padding:10px 16px;border-bottom:1px solid var(--color-gray-100)">
        <?= icon('search', 14) ?> <input type="text" placeholder="Search categories..." oninput="filterList('catList',this.value)" style="width:calc(100% - 26px)">
      </div>
      <ul class="variation-list" id="catList">
        <?php foreach ($categories as $cat): ?>
        <li class="variation-item" data-name="<?= htmlspecialchars(strtolower($cat['name'])) ?>">
          <div class="variation-info">
            <span class="variation-name"><?= htmlspecialchars($cat['name']) ?></span>
            <?php if (!empty($cat['code'])): ?>
              <span class="variation-meta">Code: <?= htmlspecialchars($cat['code']) ?></span>
            <?php endif; ?>
            <?php if (!empty($cat['description'])): ?>
              <span class="variation-meta"><?= htmlspecialchars($cat['description']) ?></span>
            <?php endif; ?>
          </div>
          <div class="d-flex align-center gap-4">
            <span class="badge <?= $cat['status'] === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= ucfirst($cat['status']) ?></span>
            <?php if (can('variations', 'edit')): ?>
            <button class="icon-btn" onclick="editVariation('cat',<?= $cat['id'] ?>,'<?= htmlspecialchars(addslashes($cat['name'])) ?>','<?= htmlspecialchars(addslashes($cat['code'] ?? '')) ?>','<?= $cat['status'] ?>','<?= htmlspecialchars(addslashes($cat['description'] ?? '')) ?>')" title="Edit"><?= icon('edit', 14) ?></button>
            <?php endif; ?>
            <?php if (can('variations', 'delete')): ?>
            <button class="icon-btn danger" onclick="deleteVariation('cat',<?= $cat['id'] ?>,'<?= htmlspecialchars(addslashes($cat['name'])) ?>')" title="Delete"><?= icon('delete', 14) ?></button>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?>
          <li class="empty-list-msg">No categories yet</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <!-- ── TYPES ──────────────────────────────────── -->
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
      <h3 class="card-title" style="display:flex;align-items:center;gap:8px">
        <?= icon('box', 18) ?> Types
        <span class="badge badge-gray" id="typeCount"><?= count($types ?? []) ?></span>
      </h3>
      <?php if (can('variations', 'add')): ?>
      <button class="btn btn-primary btn-sm" onclick="openModal('typeModal')" style="display:flex;align-items:center;gap:6px">
        <?= icon('plus', 14) ?> Add
      </button>
      <?php endif; ?>
    </div>
    <div class="card-body" style="padding:0">
      <div class="search-bar" style="padding:10px 16px;border-bottom:1px solid var(--color-gray-100)">
        <?= icon('search', 14) ?> <input type="text" placeholder="Search types..." oninput="filterList('typeList',this.value)" style="width:calc(100% - 26px)">
      </div>
      <ul class="variation-list" id="typeList">
        <?php foreach ($types ?? [] as $type): ?>
        <li class="variation-item" data-name="<?= htmlspecialchars(strtolower($type['name'])) ?>">
          <div class="variation-info">
            <span class="variation-name"><?= htmlspecialchars($type['name']) ?></span>
            <?php if (!empty($type['code'])): ?>
              <span class="variation-meta">Code: <?= htmlspecialchars($type['code']) ?></span>
            <?php endif; ?>
          </div>
          <div class="d-flex align-center gap-4">
            <span class="badge <?= $type['status'] === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= ucfirst($type['status']) ?></span>
            <?php if (can('variations', 'edit')): ?>
            <button class="icon-btn" onclick="editVariation('type',<?= $type['id'] ?>,'<?= htmlspecialchars(addslashes($type['name'])) ?>','<?= htmlspecialchars(addslashes($type['code'] ?? '')) ?>','<?= $type['status'] ?>')" title="Edit"><?= icon('edit', 14) ?></button>
            <?php endif; ?>
            <?php if (can('variations', 'delete')): ?>
            <button class="icon-btn danger" onclick="deleteVariation('type',<?= $type['id'] ?>,'<?= htmlspecialchars(addslashes($type['name'])) ?>')" title="Delete"><?= icon('delete', 14) ?></button>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
        <?php if (empty($types ?? [])): ?>
          <li class="empty-list-msg">No types yet</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <!-- ── COLORS ──────────────────────────────────── -->
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
      <h3 class="card-title" style="display:flex;align-items:center;gap:8px">
        <?= icon('color-swatch', 18) ?> Colors
        <span class="badge badge-gray" id="colorCount"><?= count($colors) ?></span>
      </h3>
      <?php if (can('variations', 'add')): ?>
      <button class="btn btn-primary btn-sm" onclick="openModal('colorModal')" style="display:flex;align-items:center;gap:6px">
        <?= icon('plus', 14) ?> Add
      </button>
      <?php endif; ?>
    </div>
    <div class="card-body" style="padding:0">
      <div class="search-bar" style="padding:10px 16px;border-bottom:1px solid var(--color-gray-100)">
        <?= icon('search', 14) ?> <input type="text" placeholder="Search colors..." oninput="filterList('colorList',this.value)" style="width:calc(100% - 26px)">
      </div>
      <ul class="variation-list" id="colorList">
        <?php foreach ($colors as $color): ?>
        <li class="variation-item" data-name="<?= htmlspecialchars(strtolower($color['name'])) ?>">
          <div class="variation-info" style="display:flex;flex-direction:row;align-items:center;gap:10px">
            <?php if (!empty($color['hex_code'])): ?>
              <span class="color-dot" style="background:<?= htmlspecialchars($color['hex_code']) ?>" title="<?= htmlspecialchars($color['hex_code']) ?>"></span>
            <?php endif; ?>
            <div>
              <span class="variation-name"><?= htmlspecialchars($color['name']) ?></span>
              <?php if (!empty($color['hex_code'])): ?>
                <span class="variation-meta"><?= htmlspecialchars($color['hex_code']) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div class="d-flex align-center gap-4">
            <span class="badge <?= $color['status'] === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= ucfirst($color['status']) ?></span>
            <?php if (can('variations', 'edit')): ?>
            <button class="icon-btn" onclick="editVariation('color',<?= $color['id'] ?>,'<?= htmlspecialchars(addslashes($color['name'])) ?>','<?= htmlspecialchars(addslashes($color['hex_code'] ?? '')) ?>','<?= $color['status'] ?>')" title="Edit"><?= icon('edit', 14) ?></button>
            <?php endif; ?>
            <?php if (can('variations', 'delete')): ?>
            <button class="icon-btn danger" onclick="deleteVariation('color',<?= $color['id'] ?>,'<?= htmlspecialchars(addslashes($color['name'])) ?>')" title="Delete"><?= icon('delete', 14) ?></button>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
        <?php if (empty($colors)): ?>
          <li class="empty-list-msg">No colors yet</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <!-- ── SIZES ──────────────────────────────────── -->
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
      <h3 class="card-title" style="display:flex;align-items:center;gap:8px">
        <?= icon('ruler', 18) ?> Sizes
        <span class="badge badge-gray" id="sizeCount"><?= count($sizes) ?></span>
      </h3>
      <?php if (can('variations', 'add')): ?>
      <button class="btn btn-primary btn-sm" onclick="openModal('sizeModal')" style="display:flex;align-items:center;gap:6px">
        <?= icon('plus', 14) ?> Add
      </button>
      <?php endif; ?>
    </div>
    <div class="card-body" style="padding:0">
      <div class="search-bar" style="padding:10px 16px;border-bottom:1px solid var(--color-gray-100)">
        <?= icon('search', 14) ?> <input type="text" placeholder="Search sizes..." oninput="filterList('sizeList',this.value)" style="width:calc(100% - 26px)">
      </div>
      <ul class="variation-list" id="sizeList">
        <?php foreach ($sizes as $size): ?>
        <li class="variation-item" data-name="<?= htmlspecialchars(strtolower($size['name'])) ?>">
          <div class="variation-info">
            <span class="variation-name"><?= htmlspecialchars($size['name']) ?></span>
            <?php if (!empty($size['code'])): ?>
              <span class="variation-meta">Code: <?= htmlspecialchars($size['code']) ?></span>
            <?php endif; ?>
          </div>
          <div class="d-flex align-center gap-4">
            <span class="badge <?= $size['status'] === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= ucfirst($size['status']) ?></span>
            <?php if (can('variations', 'edit')): ?>
            <button class="icon-btn" onclick="editVariation('size',<?= $size['id'] ?>,'<?= htmlspecialchars(addslashes($size['name'])) ?>','<?= htmlspecialchars(addslashes($size['code'] ?? '')) ?>','<?= $size['status'] ?>')" title="Edit"><?= icon('edit', 14) ?></button>
            <?php endif; ?>
            <?php if (can('variations', 'delete')): ?>
            <button class="icon-btn danger" onclick="deleteVariation('size',<?= $size['id'] ?>,'<?= htmlspecialchars(addslashes($size['name'])) ?>')" title="Delete"><?= icon('delete', 14) ?></button>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
        <?php if (empty($sizes)): ?>
          <li class="empty-list-msg">No sizes yet</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>

  

<!-- ── CATEGORY MODAL ── -->
<div class="modal-overlay" id="catModal">
  <div class="modal-content" style="max-width:420px">
    <div class="modal-header">
      <h2 class="modal-title" id="catModalTitle">Add Category</h2>
      <button class="modal-close" onclick="closeModal('catModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="catId">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Name <span class="required">*</span></label>
          <input type="text" id="catName" class="form-input" placeholder="e.g., Apparel" required>
        </div>
        <div class="form-group">
          <label class="form-label">Code <span class="required">*</span></label>
          <input type="text" id="catCode" class="form-input" placeholder="e.g., APP" maxlength="20" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <input type="text" id="catDesc" class="form-input" placeholder="Optional description">
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="catStatus" class="form-select">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('catModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveVariation('cat')" id="saveCatBtn">Save</button>
    </div>
  </div>
</div>

<!-- ── COLOR MODAL ── -->
<div class="modal-overlay" id="colorModal">
  <div class="modal-content" style="max-width:420px">
    <div class="modal-header">
      <h2 class="modal-title" id="colorModalTitle">Add Color</h2>
      <button class="modal-close" onclick="closeModal('colorModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="colorId">
      <div class="form-row">
        <div class="form-group" style="flex:1">
          <label class="form-label">Name <span class="required">*</span></label>
          <input type="text" id="colorName" class="form-input" placeholder="e.g., White" required>
        </div>
        <div class="form-group" style="width:full">
          <label class="form-label">Hex Code <span class="required">*</span></label>
          <div style="display:flex;gap:6px;align-items:center">
            <input type="color" id="colorPickerSwatch" value="#ffffff" oninput="document.getElementById('colorHex').value=this.value" style="width:36px;height:36px;padding:2px;border:1px solid var(--color-gray-300);border-radius:4px;cursor:pointer">
            <input type="text" id="colorHex" class="form-input" placeholder="#FFFFFF" maxlength="7" required oninput="syncColorPicker()" style="flex:1">
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="colorStatus" class="form-select">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('colorModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveVariation('color')" id="saveColorBtn">Save</button>
    </div>
  </div>
</div>

<!-- ── SIZE MODAL ── -->
<div class="modal-overlay" id="sizeModal">
  <div class="modal-content" style="max-width:420px">
    <div class="modal-header">
      <h2 class="modal-title" id="sizeModalTitle">Add Size</h2>
      <button class="modal-close" onclick="closeModal('sizeModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="sizeId">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Name <span class="required">*</span></label>
          <input type="text" id="sizeName" class="form-input" placeholder="e.g., Medium" required>
        </div>
        <div class="form-group">
          <label class="form-label">Code <span class="required">*</span></label>
          <input type="text" id="sizeCode" class="form-input" placeholder="e.g., M" maxlength="10" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="sizeStatus" class="form-select">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('sizeModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveVariation('size')" id="saveSizeBtn">Save</button>
    </div>
  </div>
</div>


<!-- ── TYPE MODAL ── -->
<div class="modal-overlay" id="typeModal">
  <div class="modal-content" style="max-width:420px">
    <div class="modal-header">
      <h2 class="modal-title" id="typeModalTitle">Add Type</h2>
      <button class="modal-close" onclick="closeModal('typeModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="typeId">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Name <span class="required">*</span></label>
          <input type="text" id="typeName" class="form-input" placeholder="e.g., T-Shirt" required>
        </div>
        <div class="form-group">
          <label class="form-label">Code <span class="required">*</span></label>
          <input type="text" id="typeCode" class="form-input" placeholder="e.g., TSH" maxlength="20" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="typeStatus" class="form-select">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('typeModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveVariation('type')" id="saveTypeBtn">Save</button>
    </div>
  </div>
</div>

<!-- ── CONFIRM DELETE ── -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal-content" style="max-width:400px">
    <div class="modal-header">
      <h2 class="modal-title">Confirm Delete</h2>
      <button class="modal-close" onclick="closeModal('deleteModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body"><p id="deleteMessage"></p></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
      <button class="btn btn-danger" id="deleteConfirmBtn">Delete</button>
    </div>
  </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

const endpoints = {
  cat:   { list: '/api/v1/variations/categories', single: '/api/v1/variations/categories/' },
  color: { list: '/api/v1/variations/colors',     single: '/api/v1/variations/colors/' },
  size:  { list: '/api/v1/variations/sizes',       single: '/api/v1/variations/sizes/' },
  type:  { list: '/api/v1/variations/types',       single: '/api/v1/variations/types/' },
};

const modals   = { cat: 'catModal',   color: 'colorModal',   size: 'sizeModal',   type: 'typeModal' };
const titles   = { cat: 'catModalTitle', color: 'colorModalTitle', size: 'sizeModalTitle', type: 'typeModalTitle' };
const saveBtns = { cat: 'saveCatBtn', color: 'saveColorBtn',  size: 'saveSizeBtn',  type: 'saveTypeBtn' };

function filterList(listId, query) {
  const items = document.querySelectorAll(`#${listId} .variation-item`);
  const q = query.toLowerCase();
  items.forEach(li => {
    li.style.display = li.dataset.name.includes(q) ? '' : 'none';
  });
}

function syncColorPicker() {
  const hex = document.getElementById('colorHex').value;
  if (/^#[0-9a-fA-F]{6}$/.test(hex)) {
    document.getElementById('colorPickerSwatch').value = hex;
  }
}

function openAddVariation(type) {
  document.getElementById(type === 'cat' ? 'catId' : type === 'color' ? 'colorId' : type === 'size' ? 'sizeId' : 'typeId').value = '';
  if (type === 'cat')   { document.getElementById('catName').value = ''; document.getElementById('catCode').value = ''; document.getElementById('catDesc').value = ''; document.getElementById('catStatus').value = 'active'; }
  if (type === 'color') { document.getElementById('colorName').value = ''; document.getElementById('colorHex').value = ''; document.getElementById('colorPickerSwatch').value = '#ffffff'; document.getElementById('colorStatus').value = 'active'; }
  if (type === 'size')  { document.getElementById('sizeName').value = ''; document.getElementById('sizeCode').value = ''; document.getElementById('sizeStatus').value = 'active'; }
  if (type === 'type')  { document.getElementById('typeName').value = ''; document.getElementById('typeCode').value = ''; document.getElementById('typeStatus').value = 'active'; }
  document.getElementById(titles[type]).textContent = `Add ${type === 'cat' ? 'Category' : type === 'color' ? 'Color' : type === 'size' ? 'Size' : 'Type'}`;
  openModal(modals[type]);
}

// Override the add buttons to call openAddVariation
document.querySelectorAll('[onclick="openModal(\'catModal\')"]').forEach(el => { el.onclick = () => openAddVariation('cat'); });
document.querySelectorAll('[onclick="openModal(\'colorModal\')"]').forEach(el => { el.onclick = () => openAddVariation('color'); });
document.querySelectorAll('[onclick="openModal(\'sizeModal\')"]').forEach(el => { el.onclick = () => openAddVariation('size'); });
document.querySelectorAll('[onclick="openModal(\'typeModal\')"]').forEach(el => { el.onclick = () => openAddVariation('type'); });

function editVariation(type, id, name, extra, status, extra2 = '') {
  document.getElementById(type === 'cat' ? 'catId' : type === 'color' ? 'colorId' : type === 'size' ? 'sizeId' : 'typeId').value = id;
  if (type === 'cat')   { document.getElementById('catName').value = name; document.getElementById('catCode').value = extra; document.getElementById('catDesc').value = extra2; document.getElementById('catStatus').value = status; }
  if (type === 'color') { document.getElementById('colorName').value = name; document.getElementById('colorHex').value = extra; if (/^#[0-9a-fA-F]{6}$/.test(extra)) document.getElementById('colorPickerSwatch').value = extra; document.getElementById('colorStatus').value = status; }
  if (type === 'size')  { document.getElementById('sizeName').value = name; document.getElementById('sizeCode').value = extra; document.getElementById('sizeStatus').value = status; }
  if (type === 'type')  { document.getElementById('typeName').value = name; document.getElementById('typeCode').value = extra; document.getElementById('typeStatus').value = status; }
  document.getElementById(titles[type]).textContent = `Edit ${type === 'cat' ? 'Category' : type === 'color' ? 'Color' : type === 'size' ? 'Size' : 'Type'}`;
  openModal(modals[type]);
}

async function saveVariation(type) {
  const idEl = document.getElementById(type === 'cat' ? 'catId' : type === 'color' ? 'colorId' : type === 'size' ? 'sizeId' : 'typeId');
  const id   = idEl.value;
  const btn  = document.getElementById(saveBtns[type]);
  btn.disabled = true;

  let payload = {};
  if (type === 'cat')   payload = { name: document.getElementById('catName').value,   code: document.getElementById('catCode').value, description: document.getElementById('catDesc').value,   status: document.getElementById('catStatus').value };
  if (type === 'color') payload = { name: document.getElementById('colorName').value,  hex_code:    document.getElementById('colorHex').value,    status: document.getElementById('colorStatus').value };
  if (type === 'size')  payload = { name: document.getElementById('sizeName').value,   code:        document.getElementById('sizeCode').value,     status: document.getElementById('sizeStatus').value };
  if (type === 'type')  payload = { name: document.getElementById('typeName').value,   code:        document.getElementById('typeCode').value,      status: document.getElementById('typeStatus').value };

  const url    = id ? endpoints[type].single + id : endpoints[type].list;

  const method = id ? 'PUT' : 'POST';

  try {
    const res  = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf }, body: JSON.stringify(payload) });
    const data = await res.json();
    if (data.success) { showToast(data.message, 'success'); closeModal(modals[type]); setTimeout(() => location.reload(), 600); }
    else showToast(data.message || 'Error', 'error');
  } catch (e) { showToast('Network error', 'error'); }

  btn.disabled = false;
}

function deleteVariation(type, id, name) {
  const label = type === 'cat' ? 'category' : type === 'color' ? 'color' : type === 'size' ? 'size' : 'type';
  document.getElementById('deleteMessage').textContent = `Delete ${label} "${name}"? Products using it will have this field cleared.`;
  document.getElementById('deleteConfirmBtn').onclick = async () => {
    const res  = await fetch(endpoints[type].single + id, { method: 'DELETE', headers: { 'X-CSRF-Token': csrf } });
    const data = await res.json();
    if (data.success) { showToast(data.message, 'success'); closeModal('deleteModal'); setTimeout(() => location.reload(), 600); }
    else showToast(data.message || 'Error', 'error');
  };
  openModal('deleteModal');
}
</script>

<?php
$content = ob_get_clean();
$title   = 'Product Variations | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
