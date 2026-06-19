<?php ob_start(); ?>

<style>
  .variation-item {
    cursor: grab;
    transition: opacity 0.2s ease, border 0.2s ease, background-color 0.2s ease;
    position: relative;
    user-select: none;
  }

  .variation-item.dragging {
    opacity: 0.4;
    background-color: var(--color-gray-50);
    border: 2px dashed var(--color-primary);
  }

  .variation-item.drag-over {
    border-top: 3px solid var(--color-primary);
    background-color: rgba(var(--color-primary-rgb), 0.08);
    padding-top: calc(12px - 3px) !important;
  }

  .variation-list.drag-over {
    background-color: rgba(var(--color-primary-rgb), 0.04);
    border-radius: 6px;
  }

  .drag-handle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    cursor: grab;
    color: var(--color-gray-400);
    margin-right: 8px;
    flex-shrink: 0;
    transition: color 0.2s ease, cursor 0.2s ease;
  }

  .drag-handle:hover {
    color: var(--color-primary);
    cursor: grab;
  }

  .variation-item.dragging .drag-handle {
    cursor: grabbing;
    color: var(--color-primary);
  }

  .variation-item[style*="display: none"] {
    pointer-events: none;
  }
</style>

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
      <ul class="variation-list" id="catList" data-type="cat">
        <?php foreach ($categories as $cat): ?>
        <li class="variation-item" draggable="true" data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars(strtolower($cat['name'])) ?>">
          <div class="drag-handle" title="Drag to reorder"><?= icon('drag-handle', 16) ?></div>
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
      <ul class="variation-list" id="typeList" data-type="type">
        <?php foreach ($types ?? [] as $type): ?>
        <li class="variation-item" draggable="true" data-id="<?= $type['id'] ?>" data-name="<?= htmlspecialchars(strtolower($type['name'])) ?>">
          <div class="drag-handle" title="Drag to reorder"><?= icon('drag-handle', 16) ?></div>
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
      <ul class="variation-list" id="colorList" data-type="color">
        <?php foreach ($colors as $color): ?>
        <li class="variation-item" draggable="true" data-id="<?= $color['id'] ?>" data-name="<?= htmlspecialchars(strtolower($color['name'])) ?>">
          <div class="drag-handle" title="Drag to reorder"><?= icon('drag-handle', 16) ?></div>
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
      <ul class="variation-list" id="sizeList" data-type="size">
        <?php foreach ($sizes as $size): ?>
        <li class="variation-item" draggable="true" data-id="<?= $size['id'] ?>" data-name="<?= htmlspecialchars(strtolower($size['name'])) ?>">
          <div class="drag-handle" title="Drag to reorder"><?= icon('drag-handle', 16) ?></div>
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

// ── DRAG & DROP FUNCTIONALITY ───────────────────────────────────────

let draggedItem = null;
let dragSource = null;


function buildReorderUrl(type) {
  const paths = {
    cat:   '/api/v1/variations/categories/reorder',
    type:  '/api/v1/variations/types/reorder',
    color: '/api/v1/variations/colors/reorder',
    size:  '/api/v1/variations/sizes/reorder',
  };
  
  if (!paths[type]) {
    console.error(`Unknown variation type: ${type}`);
    return null;
  }
  
  return paths[type];
}

function initDragAndDrop() {
  const lists = document.querySelectorAll('.variation-list');
  console.log(`Initializing drag-drop for ${lists.length} lists`);
  
  lists.forEach(list => {
    const items = list.querySelectorAll('.variation-item:not(.empty-list-msg)');
    console.log(`  List "${list.getAttribute('data-type')}" has ${items.length} items`);
    
    // Add listeners to each item for drag start/end
    items.forEach(item => {
      item.addEventListener('dragstart', handleDragStart, false);
      item.addEventListener('dragend', handleDragEnd, false);
    });
    
    // Add listeners to the list itself for drop zone
    list.addEventListener('dragover', handleListDragOver, false);
    list.addEventListener('drop', handleListDrop, false);
    list.addEventListener('dragenter', handleListDragEnter, false);
    list.addEventListener('dragleave', handleListDragLeave, false);
  });
}

function handleDragStart(e) {
  draggedItem = this;
  dragSource = this.parentNode;
  this.classList.add('dragging');
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/html', this.innerHTML);
  console.log('Drag started on item:', this.getAttribute('data-id'));
}

function handleDragEnd(e) {
  if (this === draggedItem) {
    this.classList.remove('dragging');
  }
  // Clear all drop indicators
  document.querySelectorAll('.variation-item.drag-over').forEach(item => {
    item.classList.remove('drag-over');
  });
  document.querySelectorAll('.variation-list.drag-over').forEach(list => {
    list.classList.remove('drag-over');
  });
  console.log('Drag ended');
}

function handleListDragOver(e) {
  if (e.preventDefault) {
    e.preventDefault();
  }
  if (e.stopPropagation) {
    e.stopPropagation();
  }
  e.dataTransfer.dropEffect = 'move';
  
  if (draggedItem && draggedItem.parentNode === this) {
    // Find the item under the cursor
    const items = Array.from(this.querySelectorAll('.variation-item:not(.empty-list-msg)'))
      .filter(item => item.style.display !== 'none');
    
    for (let item of items) {
      const rect = item.getBoundingClientRect();
      if (e.clientY < rect.top + rect.height / 2) {
        if (item !== draggedItem) {
          item.classList.add('drag-over');
        }
      } else {
        item.classList.remove('drag-over');
      }
    }
  }
  
  return false;
}

function handleListDragEnter(e) {
  if (draggedItem && draggedItem.parentNode === this) {
    this.classList.add('drag-over');
  }
}

function handleListDragLeave(e) {
  // Only remove class if leaving the list entirely
  if (e.target === this) {
    this.classList.remove('drag-over');
    document.querySelectorAll('.variation-item.drag-over').forEach(item => {
      item.classList.remove('drag-over');
    });
  }
}

function handleListDrop(e) {
  if (e.stopPropagation) {
    e.stopPropagation();
  }
  
  if (e.preventDefault) {
    e.preventDefault();
  }
  
  console.log('Drop detected on list');
  
  if (!draggedItem || draggedItem.parentNode !== this) {
    console.log('Drop rejected: item not from this list');
    return false;
  }
  
  // Skip if hidden
  if (draggedItem.style.display === 'none') {
    console.warn('Cannot reorder hidden items');
    return false;
  }
  
  // Find visible items only
  const items = Array.from(this.querySelectorAll('.variation-item:not(.empty-list-msg)'))
    .filter(item => item.style.display !== 'none');
  
  // Find which item is under the cursor
  const targetItem = Array.from(items).find(item => {
    if (item === draggedItem) return false;
    const rect = item.getBoundingClientRect();
    return e.clientY >= rect.top && e.clientY <= rect.bottom;
  });
  
  if (targetItem) {
    const rect = targetItem.getBoundingClientRect();
    const isBottom = e.clientY > rect.top + rect.height / 2;
    
    if (isBottom) {
      targetItem.parentNode.insertBefore(draggedItem, targetItem.nextSibling);
    } else {
      targetItem.parentNode.insertBefore(draggedItem, targetItem);
    }
    
    console.log('Item reordered');
  } else {
    console.log('No target item found under cursor');
  }
  
  // Clear visual indicators
  this.classList.remove('drag-over');
  document.querySelectorAll('.variation-item.drag-over').forEach(item => {
    item.classList.remove('drag-over');
  });
  
  // Save the new order
  const listType = this.getAttribute('data-type');
  if (listType) {
    console.log(`Saving reorder for type: ${listType}`);
    saveReorder(listType);
  } else {
    console.error('Could not determine list type from data-type attribute');
  }
  
  return false;
}

async function saveReorder(type) {
  const list = document.querySelector(`[data-type="${type}"]`);
  if (!list) {
    console.error('List not found for type:', type);
    return;
  }
  
  // Get only visible items (not filtered out)
  const items = list.querySelectorAll('.variation-item:not(.empty-list-msg)');
  const orderedIds = Array.from(items)
    .filter(item => {
      // Skip hidden items from search filter
      return item.style.display !== 'none';
    })
    .map(item => {
      const id = item.getAttribute('data-id');
      return parseInt(id, 10);
    });
  
  if (orderedIds.length === 0) {
    console.warn('No visible items found to reorder');
    return;
  }
  
  console.log(`Reordering ${type}:`, orderedIds);
  
  const url = buildReorderUrl(type);
  if (!url) {
    console.error('Could not build reorder URL for type:', type);
    showToast('Configuration error', 'error');
    return;
  }
  
  console.log(`Sending to URL: ${url}`);
  console.log(`CSRF Token: ${csrf ? csrf.substring(0, 20) + '...' : 'MISSING'}`);
  
  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrf
      },
      body: JSON.stringify({ order: orderedIds })
    });
    
    console.log(`Response status: ${res.status}`);
    
    if (!res.ok) {
      const errorText = await res.text();
      console.error(`HTTP Error: ${res.status} ${res.statusText}`, errorText);
      throw new Error(`HTTP Error: ${res.status} ${res.statusText}`);
    }
    
    const data = await res.json();
    console.log('Reorder response:', data);
    
    if (data.success) {
      showToast(data.message || 'Order updated', 'success');
    } else {
      console.error('Reorder failed:', data);
      showToast(data.message || 'Error reordering', 'error');
      setTimeout(() => location.reload(), 1500);
    }
  } catch (e) {
    console.error('Network error during reorder:', e);
    showToast('Network error while reordering: ' + e.message, 'error');
    // Don't reload - let user retry
  }
}

// Initialize drag and drop on page load
document.addEventListener('DOMContentLoaded', function() {
  console.log('Page loaded - initializing drag and drop');
  initDragAndDrop();
});

// Re-init drag and drop after modal close to catch any updates
window.addEventListener('closeModal', function() {
  console.log('Modal closed - re-initializing drag and drop');
  setTimeout(initDragAndDrop, 100);
});
</script>

<?php
$content = ob_get_clean();
$title   = 'Product Variations | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
