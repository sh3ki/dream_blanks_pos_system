<?php ob_start(); ?>

<div class="page-header">
  <h1>Roles &amp; Permissions</h1>
  <?php if (can('roles', 'add')): ?>
  <button class="btn btn-primary" onclick="openAddRole()" style="display:flex;align-items:center;gap:6px">
    <?= icon('plus', 15) ?> Add Role
  </button>
  <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start">

  <!-- Roles List -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title" style="display:flex;align-items:center;gap:8px"><?= icon('roles', 18) ?> Roles</h3>
    </div>
    <div class="card-body" style="padding:0">
      <ul class="variation-list" id="roleList">
        <?php $loop_idx = -1; foreach ($roles as $role): $loop_idx++; ?>
        <li class="variation-item role-item <?= $loop_idx === 0 ? 'selected' : '' ?>"
            id="roleItem_<?= $role['id'] ?>"
            onclick="selectRole(<?= $role['id'] ?>,'<?= htmlspecialchars(addslashes($role['name'])) ?>','<?= htmlspecialchars(addslashes($role['description'] ?? '')) ?>','<?= $role['status'] ?>')"
            style="cursor:pointer">
          <div class="variation-info">
            <span class="variation-name"><?= htmlspecialchars($role['name']) ?></span>
            <span class="variation-meta"><?= count($role['permissions'] ?? []) ?> permissions</span>
          </div>
          <div class="d-flex align-center gap-4">
            <span class="badge <?= ($role['status'] ?? 'active') === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= ucfirst($role['status'] ?? 'active') ?></span>
            <?php if (can('roles', 'edit')): ?>
            <button class="icon-btn" onclick="event.stopPropagation();editRole(<?= $role['id'] ?>,'<?= htmlspecialchars(addslashes($role['name'])) ?>','<?= htmlspecialchars(addslashes($role['description'] ?? '')) ?>','<?= $role['status'] ?>')" title="Edit"><?= icon('edit', 14) ?></button>
            <?php endif; ?>
            <?php if (can('roles', 'delete')): ?>
            <button class="icon-btn danger" onclick="event.stopPropagation();deleteRole(<?= $role['id'] ?>,'<?= htmlspecialchars(addslashes($role['name'])) ?>')" title="Delete"><?= icon('delete', 14) ?></button>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
        <?php if (empty($roles)): ?>
          <li class="empty-list-msg">No roles yet. Add one!</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <!-- Permission Matrix -->
  <div class="card" id="permPanel">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
      <h3 class="card-title" id="permTitle" style="display:flex;align-items:center;gap:8px">
        <?= icon('roles', 18) ?> <span>Select a role to manage permissions</span>
      </h3>
      <button class="btn btn-primary btn-sm" id="savePermBtn" onclick="savePermissions()" style="display:none">
        Save Permissions
      </button>
    </div>
    <div class="card-body" id="permBody">
      <p class="text-muted" style="padding:24px;text-align:center">Click a role on the left to view and edit its permissions.</p>
    </div>
  </div>
</div>

<!-- Role Modal -->
<div class="modal-overlay" id="roleModal">
  <div class="modal-content" style="max-width:440px">
    <div class="modal-header">
      <h2 class="modal-title" id="roleModalTitle">Add Role</h2>
      <button class="modal-close" onclick="closeModal('roleModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="roleId">
      <div class="form-group">
        <label class="form-label">Role Name <span class="required">*</span></label>
        <input type="text" id="roleName" class="form-input" placeholder="e.g., Sales Staff" required>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <input type="text" id="roleDesc" class="form-input" placeholder="Optional description">
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="roleStatus" class="form-select">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('roleModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveRole()" id="saveRoleBtn">Save Role</button>
    </div>
  </div>
</div>

<!-- Confirm Delete -->
<div class="modal-overlay" id="deleteRoleModal">
  <div class="modal-content" style="max-width:400px">
    <div class="modal-header">
      <h2 class="modal-title">Confirm Delete</h2>
      <button class="modal-close" onclick="closeModal('deleteRoleModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body"><p id="deleteRoleMsg"></p></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('deleteRoleModal')">Cancel</button>
      <button class="btn btn-danger" id="deleteRoleConfirm">Delete</button>
    </div>
  </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
const allPermissions = <?= json_encode($permissions) ?>;
let currentRoleId = null;
let rolePermissions = <?= json_encode(
    array_column(
        array_map(fn($r) => ['id' => $r['id'], 'perms' => array_column($r['permissions'] ?? [], 'id')], $roles),
        'perms', 'id'
    )
) ?>;

function selectRole(id, name, desc, status) {
  currentRoleId = id;
  document.querySelectorAll('.role-item').forEach(el => el.classList.remove('selected'));
  document.getElementById('roleItem_' + id)?.classList.add('selected');

  const current = rolePermissions[id] || [];
  const titleEl = document.querySelector('#permTitle span');
  titleEl.textContent = name + ' — Permissions';
  document.getElementById('savePermBtn').style.display = '';

  const moduleLabels = {
    dashboard: 'Dashboard',
    users: 'Users', roles: 'Roles', clients: 'Clients', products: 'Products',
    stock_products: 'Stock Products', inventory: 'Inventory', pos: 'POS',
    invoices: 'Invoices', payments: 'Payments', transactions: 'Transactions',
    reports_sales: 'Reports — Sales', reports_inventory: 'Reports — Inventory', reports_financial: 'Reports — Financial',
    settings: 'Settings', audit_logs: 'Audit Logs',
    notifications: 'Notifications', variations: 'Variations',
  };
  let html = '<div class="perm-matrix">';
  for (const [module, perms] of Object.entries(allPermissions)) {
    const label = moduleLabels[module] || module.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    html += `<div class="perm-module">
      <div class="perm-module-name">${label}</div>
      <div class="perm-actions">`;
    perms.forEach(p => {
      const checked = current.includes(p.id) ? 'checked' : '';
      html += `<label class="perm-checkbox">
        <input type="checkbox" data-perm="${p.id}" ${checked}>
        <span>${p.action}</span>
      </label>`;
    });
    html += `</div></div>`;
  }
  html += '</div>';
  document.getElementById('permBody').innerHTML = html;
}

async function savePermissions() {
  if (!currentRoleId) return;
  const ids = [...document.querySelectorAll('#permBody input[type=checkbox]:checked')].map(el => parseInt(el.dataset.perm));
  const btn = document.getElementById('savePermBtn');
  btn.disabled = true;
  try {
    const res  = await fetch('/api/v1/roles/' + currentRoleId + '/permissions', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({ permission_ids: ids })
    });
    const data = await res.json();
    if (data.success) { showToast('Permissions saved', 'success'); rolePermissions[currentRoleId] = ids; }
    else showToast(data.message || 'Error', 'error');
  } catch(e) { showToast('Network error', 'error'); }
  btn.disabled = false;
}

function openAddRole() {
  document.getElementById('roleId').value = '';
  document.getElementById('roleName').value = '';
  document.getElementById('roleDesc').value = '';
  document.getElementById('roleStatus').value = 'active';
  document.getElementById('roleModalTitle').textContent = 'Add Role';
  openModal('roleModal');
}

function editRole(id, name, desc, status) {
  document.getElementById('roleId').value = id;
  document.getElementById('roleName').value = name;
  document.getElementById('roleDesc').value = desc;
  document.getElementById('roleStatus').value = status;
  document.getElementById('roleModalTitle').textContent = 'Edit Role';
  openModal('roleModal');
}

async function saveRole() {
  const id   = document.getElementById('roleId').value;
  const btn  = document.getElementById('saveRoleBtn');
  btn.disabled = true;
  const payload = {
    name:        document.getElementById('roleName').value,
    description: document.getElementById('roleDesc').value,
    status:      document.getElementById('roleStatus').value,
  };
  const url    = id ? '/api/v1/roles/' + id : '/api/v1/roles';
  const method = id ? 'PUT' : 'POST';
  try {
    const res  = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf }, body: JSON.stringify(payload) });
    const data = await res.json();
    if (data.success) { showToast(data.message, 'success'); closeModal('roleModal'); setTimeout(() => location.reload(), 600); }
    else showToast(data.message || 'Error', 'error');
  } catch(e) { showToast('Network error', 'error'); }
  btn.disabled = false;
}

function deleteRole(id, name) {
  document.getElementById('deleteRoleMsg').textContent = `Delete role "${name}"? This cannot be undone.`;
  document.getElementById('deleteRoleConfirm').onclick = async () => {
    const res  = await fetch('/api/v1/roles/' + id, { method: 'DELETE', headers: { 'X-CSRF-Token': csrf } });
    const data = await res.json();
    if (data.success) { showToast('Role deleted', 'success'); closeModal('deleteRoleModal'); setTimeout(() => location.reload(), 600); }
    else showToast(data.message || 'Error', 'error');
  };
  openModal('deleteRoleModal');
}

// Auto-select first role on load
document.addEventListener('DOMContentLoaded', () => {
  const first = document.querySelector('.role-item');
  if (first) first.click();
});
</script>

<?php
$content = ob_get_clean();
$title   = 'Roles & Permissions | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
