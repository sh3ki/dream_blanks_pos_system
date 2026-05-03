<?php ob_start(); ?>
<div class="page-header">
  <h1>Users</h1>
  <button class="btn btn-primary" onclick="openModal('userModal')">+ Add User</button>
</div>

<div class="card">
  <div class="card-body" style="padding:16px">
    <div class="filter-bar">
      <div class="search-bar" style="flex:1;max-width:280px">
        <?= icon('search', 16) ?> <input type="text" placeholder="Search users..." id="searchInput" oninput="filterUsers()" style="width:100%">
      </div>
    </div>
  </div>

  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
      </thead>
      <tbody id="usersBody">
        <?php foreach ($users as $u): ?>
        <tr>
          <td><strong><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></strong></td>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td>
            <?php foreach (($u['roles'] ?? []) as $role): ?>
              <span class="badge badge-gray"><?= htmlspecialchars(is_array($role) ? ($role['name'] ?? '') : $role) ?></span>
            <?php endforeach; ?>
          </td>
          <td><span class="badge <?= $u['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>"><?= ucfirst($u['status']) ?></span></td>
          <td style="font-size:.8rem;color:#808080"><?= $u['last_login'] ? date('M d, Y H:i', strtotime($u['last_login'])) : 'Never' ?></td>
          <td>
            <button class="icon-btn" onclick="editUser(<?= $u['id'] ?>)" title="Edit"><?= icon('edit', 15) ?></button>
            <?php if ($u['id'] !== ($_SESSION['user']['id'] ?? 0)): ?>
              <button class="icon-btn danger" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')" title="Delete"><?= icon('delete', 15) ?></button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
          <tr><td colspan="7" class="text-center text-muted" style="padding:48px">No users found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- User Modal -->
<div class="modal-overlay" id="userModal">
  <div class="modal-content" style="max-width:560px">
    <div class="modal-header">
      <h2 class="modal-title" id="userModalTitle">Add User</h2>
      <button class="modal-close" onclick="closeModal('userModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="userId">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">First Name <span class="required">*</span></label>
          <input type="text" id="uFirst" class="form-input" required>
        </div>
        <div class="form-group">
          <label class="form-label">Last Name <span class="required">*</span></label>
          <input type="text" id="uLast" class="form-input" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Username <span class="required">*</span></label>
          <input type="text" id="uUsername" class="form-input" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email <span class="required">*</span></label>
          <input type="email" id="uEmail" class="form-input" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Password <span id="passwordRequired" class="required">*</span></label>
          <input type="password" id="uPassword" class="form-input" placeholder="Leave blank to keep current">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select id="uStatus" class="form-select">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Role</label>
        <select id="uRole" class="form-select">
          <?php foreach ($roles as $role): ?>
            <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('userModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveUser()" id="saveUserBtn">Save User</button>
    </div>
  </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal-content" style="max-width:400px">
    <div class="modal-header"><h2 class="modal-title">Confirm Delete</h2><button class="modal-close" onclick="closeModal('confirmModal')"><?= icon('close', 16) ?></button></div>
    <div class="modal-body"><p id="confirmMessage"></p></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('confirmModal')">Cancel</button>
      <button class="btn btn-danger" id="confirmBtn">Delete</button>
    </div>
  </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function editUser(id) {
  fetch('/api/v1/users/' + id).then(r => r.json()).then(res => {
    if (!res.success) return;
    const u = res.data;
    document.getElementById('userId').value     = u.id;
    document.getElementById('uFirst').value     = u.first_name;
    document.getElementById('uLast').value      = u.last_name;
    document.getElementById('uUsername').value  = u.username;
    document.getElementById('uEmail').value     = u.email;
    document.getElementById('uStatus').value    = u.status;
    document.getElementById('uPassword').value  = '';
    const firstRole = Array.isArray(u.roles) ? u.roles[0] : null;
    document.getElementById('uRole').value      = (firstRole && typeof firstRole === 'object') ? firstRole.id : '';
    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('passwordRequired').style.display = 'none';
    openModal('userModal');
  });
}

function openAddUser() {
  document.getElementById('userId').value = '';
  document.getElementById('uFirst').value = '';
  document.getElementById('uLast').value = '';
  document.getElementById('uUsername').value = '';
  document.getElementById('uEmail').value = '';
  document.getElementById('uPassword').value = '';
  document.getElementById('uStatus').value = 'active';
  document.getElementById('userModalTitle').textContent = 'Add User';
  document.getElementById('passwordRequired').style.display = '';
  openModal('userModal');
}

document.querySelector('[onclick="openModal(\'userModal\')"]')?.addEventListener('click', openAddUser);
document.querySelector('[onclick="openModal(\'userModal\')"]').onclick = openAddUser;

async function saveUser() {
  const id  = document.getElementById('userId').value;
  const btn = document.getElementById('saveUserBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving...';

  const payload = {
    first_name: document.getElementById('uFirst').value,
    last_name:  document.getElementById('uLast').value,
    username:   document.getElementById('uUsername').value,
    email:      document.getElementById('uEmail').value,
    status:     document.getElementById('uStatus').value,
    roles:      document.getElementById('uRole').value ? [parseInt(document.getElementById('uRole').value, 10)] : [],
  };
  const pw = document.getElementById('uPassword').value;
  if (pw) payload.password = pw;

  const url    = id ? '/api/v1/users/' + id : '/api/v1/users';
  const method = id ? 'PUT' : 'POST';
  try {
    const res  = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify(payload) });
    const data = await res.json();
    if (data.success) { showToast(data.message, 'success'); closeModal('userModal'); setTimeout(() => location.reload(), 800); }
    else showToast(data.message || 'Failed', 'error');
  } catch (e) { showToast('Network error', 'error'); }
  btn.disabled = false; btn.innerHTML = 'Save User';
}

function deleteUser(id, username) {
  document.getElementById('confirmMessage').textContent = `Delete user "${username}"? This cannot be undone.`;
  document.getElementById('confirmBtn').onclick = async () => {
    const res  = await fetch('/api/v1/users/' + id, { method: 'DELETE', headers: { 'X-CSRF-Token': csrfToken } });
    const data = await res.json();
    if (data.success) { showToast('User deleted', 'success'); closeModal('confirmModal'); setTimeout(() => location.reload(), 600); }
    else showToast(data.message, 'error');
  };
  openModal('confirmModal');
}

function filterUsers() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('#usersBody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
<?php
$content = ob_get_clean();
$title   = 'Users | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
