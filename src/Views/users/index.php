<?php ob_start(); ?>
<?php
$sort  = $sort  ?? 'created_at';
$order = $order ?? 'DESC';
function userSortLink(string $col, string $label, string $currentSort, string $currentOrder, string $search, string $status): string {
    $nextOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $params    = array_filter(['search' => $search, 'status' => $status, 'sort' => $col, 'order' => $nextOrder], fn($v) => $v !== '');
    $arrow     = '';
    if ($currentSort === $col) $arrow = $currentOrder === 'ASC' ? ' <span style="font-size:.8em">▲</span>' : ' <span style="font-size:.8em">▼</span>';
    else $arrow = ' <span style="font-size:.8em;opacity:.5">⇅</span>';
    return '<a href="?' . http_build_query($params) . '" style="display:block;padding:12px 16px;color:inherit;text-decoration:none;white-space:nowrap">' . htmlspecialchars($label) . $arrow . '</a>';
}
?>
<div class="page-header">
  <h1>Users</h1>
  <button class="btn btn-primary" onclick="openAddUser()">+ Add User</button>
</div>

<div class="card">
  <div class="card-body" style="padding:16px">
    <div class="filter-bar">
      <div class="search-bar" style="flex:1;max-width:280px">
        <?= icon('search', 16) ?> <input type="text" placeholder="Search users..." id="searchInput" value="<?= htmlspecialchars($search ?? '') ?>" oninput="debouncedSearch()" style="width:100%">
      </div>
      <select id="statusFilter" class="form-select" style="width:150px;height:38px" onchange="applyFilters()">
        <option value="">All Status</option>
        <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= ($status ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>
  </div>

  <div id="usersResultsContainer">
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr style="cursor:pointer">
          <th style="width:52px"></th>
          <th style="padding:0"><?= userSortLink('first_name','Name',$sort,$order,$search??'',$status??'') ?></th>
          <th style="padding:0"><?= userSortLink('username','Username',$sort,$order,$search??'',$status??'') ?></th>
          <th style="padding:0"><?= userSortLink('email','Email',$sort,$order,$search??'',$status??'') ?></th>
          <th>Role</th>
          <th style="padding:0"><?= userSortLink('status','Status',$sort,$order,$search??'',$status??'') ?></th>
          <th style="padding:0"><?= userSortLink('last_login','Last Login',$sort,$order,$search??'',$status??'') ?></th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="usersBody">
        <?php foreach ($users as $u): ?>
        <tr style="cursor:pointer" onclick="viewUser(<?= $u['id'] ?>)">
          <td style="width:52px">
            <span style="display:inline-flex;width:36px;height:36px;border-radius:50%;overflow:hidden;flex-shrink:0;border:1.5px solid var(--color-gray-200)">
              <img src="<?= htmlspecialchars(!empty($u['profile_image']) ? app_url($u['profile_image']) : asset_url('/assets/images/no-image.png')) ?>"
                alt="" style="width:100%;height:100%;object-fit:cover;display:block"
                onerror="this.src='<?= htmlspecialchars(asset_url('/assets/images/no-image.png')) ?>'">
            </span>
          </td>
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
          <td onclick="event.stopPropagation()">
            <button class="icon-btn" onclick="editUser(<?= $u['id'] ?>)" title="Edit"><?= icon('edit', 15) ?></button>
            <?php if ($u['id'] !== ($_SESSION['user']['id'] ?? 0)): ?>
              <button class="icon-btn danger" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')" title="Delete"><?= icon('delete', 15) ?></button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
          <tr><td colspan="8" class="text-center text-muted" style="padding:48px">No users found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
    $uPqFilters = array_filter(['search' => $search ?? '', 'status' => $status ?? '', 'sort' => $sort ?? '', 'order' => $order ?? ''], fn($v) => $v !== '');
    echo renderPagination($pagination, $uPqFilters);
  ?>
  </div><!-- /usersResultsContainer -->
</div>

<!-- View User Modal -->
<div class="modal-overlay" id="viewUserModal">
  <div class="modal-content" style="max-width:520px">
    <div class="modal-header">
      <h2 class="modal-title" id="viewUserTitle">User Details</h2>
      <button class="modal-close" onclick="closeModal('viewUserModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body" id="viewUserBody" style="min-height:160px">
      <div class="text-center text-muted" style="padding:48px">Loading...</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('viewUserModal')">Close</button>
      <button class="btn btn-primary" id="viewUserEditBtn">Edit</button>
    </div>
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
      <div class="form-group">
        <label class="form-label">Profile Image</label>
        <input type="file" id="uImage" class="form-input" accept="image/*" onchange="previewUserImage(event)">
        <div style="margin-top:10px;display:flex;align-items:center;gap:12px">
          <img id="uImagePreview" src="<?= htmlspecialchars(asset_url('/assets/images/no-image.png')) ?>" alt="Preview"
            style="width:64px;height:64px;object-fit:cover;border-radius:50%;border:1px solid var(--color-gray-100)"
            onerror="this.src='<?= htmlspecialchars(asset_url('/assets/images/no-image.png')) ?>'">
          <span style="font-size:.8rem;color:var(--color-gray-500)">Optional. JPG, PNG, GIF, WEBP</span>
        </div>
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
const noUserImg = '<?= htmlspecialchars(asset_url('/assets/images/no-image.png')) ?>';

function previewUserImage(event) {
  const file = event.target.files?.[0];
  document.getElementById('uImagePreview').src = file ? URL.createObjectURL(file) : noUserImg;
}

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
    document.getElementById('uImage').value     = '';
    document.getElementById('uImagePreview').src = u.profile_image ? appPath(u.profile_image) : noUserImg;
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
  document.getElementById('uImage').value = '';
  document.getElementById('uImagePreview').src = noUserImg;
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

  const formData = new FormData();
  formData.append('first_name', document.getElementById('uFirst').value);
  formData.append('last_name',  document.getElementById('uLast').value);
  formData.append('username',   document.getElementById('uUsername').value);
  formData.append('email',      document.getElementById('uEmail').value);
  formData.append('status',     document.getElementById('uStatus').value);
  const roleVal = document.getElementById('uRole').value;
  if (roleVal) formData.append('roles[]', roleVal);
  const pw = document.getElementById('uPassword').value;
  if (pw) formData.append('password', pw);
  const imgFile = document.getElementById('uImage').files?.[0];
  if (imgFile) formData.append('profile_image', imgFile);
  if (id) formData.append('_method', 'PUT');

  const url    = id ? '/api/v1/users/' + id : '/api/v1/users';
  try {
    const res  = await fetch(url, { method: 'POST', headers: { 'X-CSRF-Token': csrfToken }, body: formData });
    const data = await res.json();
    if (data.success) { showToast(data.message, 'success'); closeModal('userModal'); setTimeout(() => applyFilters(), 600); }
    else showToast(data.message || 'Failed', 'error');
  } catch (e) { showToast('Network error', 'error'); }
  btn.disabled = false; btn.innerHTML = 'Save User';
}

function deleteUser(id, username) {
  document.getElementById('confirmMessage').textContent = `Delete user "${username}"? This cannot be undone.`;
  document.getElementById('confirmBtn').onclick = async () => {
    const res  = await fetch('/api/v1/users/' + id, { method: 'DELETE', headers: { 'X-CSRF-Token': csrfToken } });
    const data = await res.json();
    if (data.success) { showToast('User deleted', 'success'); closeModal('confirmModal'); setTimeout(() => applyFilters(), 500); }
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

const usersBase = '<?= htmlspecialchars(app_url('/users')) ?>';
let searchTimer;
function debouncedSearch() { clearTimeout(searchTimer); searchTimer = setTimeout(applyFilters, 350); }

function applyFilters() {
  const search = document.getElementById('searchInput').value.trim();
  const status = document.getElementById('statusFilter').value;
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (status) params.set('status', status);
  const url = new URL(window.location.href);
  if (url.searchParams.get('sort'))  params.set('sort',  url.searchParams.get('sort'));
  if (url.searchParams.get('order')) params.set('order', url.searchParams.get('order'));
  const qs = params.toString();
  const pageUrl = window.location.origin + usersBase + (qs ? '?' + qs : '');
  history.pushState({}, '', pageUrl);
  const container = document.getElementById('usersResultsContainer');
  if (container) container.style.opacity = '0.5';
  fetch(pageUrl).then(r => r.text()).then(html => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const el = doc.getElementById('usersResultsContainer');
    if (el && container) { container.innerHTML = el.innerHTML; container.style.opacity = '1'; }
  }).catch(() => { if (container) container.style.opacity = '1'; });
}

async function viewUser(id) {
  document.getElementById('viewUserBody').innerHTML = '<div class="text-center text-muted" style="padding:48px"><span class="spinner"></span></div>';
  document.getElementById('viewUserTitle').textContent = 'User Details';
  document.getElementById('viewUserEditBtn').onclick = () => { closeModal('viewUserModal'); editUser(id); };
  openModal('viewUserModal');
  try {
    const res  = await fetch('/api/v1/users/' + id);
    const data = await res.json();
    if (!data.success) { document.getElementById('viewUserBody').innerHTML = '<p class="text-danger">Failed to load</p>'; return; }
    const u = data.data;
    const name = [u.first_name, u.middle_name, u.last_name].filter(Boolean).join(' ');
    document.getElementById('viewUserTitle').textContent = name;
    const rolesList = (u.roles || []).map(r => `<span class="badge badge-gray">${typeof r === 'object' ? r.name : r}</span>`).join(' ') || '<span class="text-muted">No roles</span>';
    const img = u.profile_image ? appPath(u.profile_image) : appPath('/assets/images/no-image.png');
    document.getElementById('viewUserBody').innerHTML = `
      <div style="display:flex;align-items:center;gap:20px;margin-bottom:20px">
        <img src="${img}" onerror="this.src='${appPath('/assets/images/no-image.png')}'" style="width:80px;height:80px;object-fit:cover;border-radius:50%;border:2px solid var(--color-gray-200)">
        <div>
          <div style="font-size:1.1rem;font-weight:700">${name}</div>
          <div style="color:var(--color-gray-500);font-size:.875rem">@${u.username}</div>
          <div style="margin-top:6px">${rolesList}</div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div><div style="font-size:.72rem;text-transform:uppercase;font-weight:600;color:var(--color-gray-400)">Email</div><div>${u.email || '-'}</div></div>
        <div><div style="font-size:.72rem;text-transform:uppercase;font-weight:600;color:var(--color-gray-400)">Status</div><div><span class="badge ${u.status === 'active' ? 'badge-success' : 'badge-danger'}">${u.status}</span></div></div>
        <div><div style="font-size:.72rem;text-transform:uppercase;font-weight:600;color:var(--color-gray-400)">Last Login</div><div>${u.last_login ? new Date(u.last_login).toLocaleString() : 'Never'}</div></div>
        <div><div style="font-size:.72rem;text-transform:uppercase;font-weight:600;color:var(--color-gray-400)">Created</div><div>${u.created_at ? new Date(u.created_at).toLocaleDateString() : '-'}</div></div>
      </div>`;
  } catch (e) { document.getElementById('viewUserBody').innerHTML = '<p class="text-danger">Network error</p>'; }
}
</script>
<?php
$content = ob_get_clean();
$title   = 'Users | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
