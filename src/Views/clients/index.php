<?php ob_start(); ?>
<div class="page-header">
  <h1>Clients</h1>
  <button class="btn btn-primary" onclick="openClientModal()">+ Add Client</button>
</div>

<div class="card">
  <div class="card-body" style="padding:16px">
    <div class="filter-bar">
      <div class="search-bar" style="flex:1;max-width:280px">
        <?= icon('search', 16) ?> <input type="text" id="searchInput" placeholder="Search clients..." value="<?= htmlspecialchars($search ?? '') ?>" oninput="debouncedSearch()" style="width:100%">
      </div>
      <select id="statusFilter" class="form-select" style="width:150px;height:38px" onchange="applyFilters()">
        <option value="">All Status</option>
        <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= ($status ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>
  </div>

  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr><th>Name</th><th>Email</th><th>Status</th><th>Created</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($clients as $c): ?>
        <tr>
          <td><strong><?= htmlspecialchars(trim($c['first_name'] . ' ' . $c['last_name'])) ?></strong></td>
          <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
          <td><span class="badge <?= ($c['status'] ?? '') === 'active' ? 'badge-success' : 'badge-danger' ?>"><?= ucfirst($c['status'] ?? 'active') ?></span></td>
          <td><?= !empty($c['created_at']) ? date('M d, Y', strtotime($c['created_at'])) : '-' ?></td>
          <td>
            <a href="/clients/<?= $c['id'] ?>" class="icon-btn" title="View"><?= icon('eye', 15) ?></a>
            <button class="icon-btn" onclick="editClient(<?= $c['id'] ?>)" title="Edit"><?= icon('edit', 15) ?></button>
            <button class="icon-btn danger" onclick="deleteClient(<?= $c['id'] ?>, '<?= htmlspecialchars(trim($c['first_name'] . ' ' . $c['last_name'])) ?>')" title="Delete"><?= icon('delete', 15) ?></button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($clients)): ?>
          <tr><td colspan="5" class="text-center text-muted" style="padding:48px">No clients found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($pagination) && $pagination['last_page'] > 1): ?>
  <?php
    $query = array_filter(['search' => $search ?? '', 'status' => $status ?? '']);
    $queryString = http_build_query($query);
    $pagePrefix = $queryString ? $queryString . '&' : '';
  ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
      <button class="page-link <?= $pagination['current_page'] == $i ? 'active' : '' ?>" onclick="window.location='?<?= $pagePrefix ?>page=<?= $i ?>'">
        <?= $i ?>
      </button>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Client Modal -->
<div class="modal-overlay" id="clientModal">
  <div class="modal-content" style="max-width:720px">
    <div class="modal-header">
      <h2 class="modal-title" id="clientModalTitle">Add Client</h2>
      <button class="modal-close" onclick="closeModal('clientModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="clientId">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">First Name <span class="required">*</span></label>
          <input type="text" id="cFirst" class="form-input" required>
        </div>
        <div class="form-group">
          <label class="form-label">Middle Name</label>
          <input type="text" id="cMiddle" class="form-input">
        </div>
        <div class="form-group">
          <label class="form-label">Last Name <span class="required">*</span></label>
          <input type="text" id="cLast" class="form-input" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" id="cEmail" class="form-input" placeholder="client@email.com">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select id="cStatus" class="form-select">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div id="clientExtraSection">
        <div style="display:flex;justify-content:space-between;align-items:center;margin:16px 0 8px">
          <h4 style="margin:0">Addresses</h4>
          <button class="btn btn-secondary btn-sm" onclick="addAddress()">+ Add Address</button>
        </div>
        <div id="addressesContainer"></div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin:16px 0 8px">
          <h4 style="margin:0">Contacts</h4>
          <button class="btn btn-secondary btn-sm" onclick="addContact()">+ Add Contact</button>
        </div>
        <div id="contactsContainer"></div>
      </div>
      <div id="clientEditNote" class="alert alert-info" style="display:none">
        Addresses and contacts can be viewed in the client details page.
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('clientModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveClient()" id="saveClientBtn">Save Client</button>
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
let searchTimer = null;
let addressCount = 0;
let contactCount = 0;

function debouncedSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(applyFilters, 400);
}

function applyFilters() {
  const search = document.getElementById('searchInput').value;
  const status = document.getElementById('statusFilter').value;
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (status) params.set('status', status);
  window.location = '?' + params.toString();
}

function openClientModal() {
  resetClientForm();
  document.getElementById('clientModalTitle').textContent = 'Add Client';
  document.getElementById('clientExtraSection').style.display = '';
  document.getElementById('clientEditNote').style.display = 'none';
  openModal('clientModal');
}

function resetClientForm() {
  document.getElementById('clientId').value = '';
  document.getElementById('cFirst').value = '';
  document.getElementById('cMiddle').value = '';
  document.getElementById('cLast').value = '';
  document.getElementById('cEmail').value = '';
  document.getElementById('cStatus').value = 'active';
  document.getElementById('addressesContainer').innerHTML = '';
  document.getElementById('contactsContainer').innerHTML = '';
  addressCount = 0;
  contactCount = 0;
  addAddress();
  addContact();
}

function addressTemplate(data = {}) {
  return `
    <div class="card address-item" style="padding:12px;margin-bottom:10px">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Type</label>
          <select class="form-select" data-field="address_type">
            <option value="billing">Billing</option>
            <option value="shipping">Shipping</option>
            <option value="home">Home</option>
            <option value="work">Work</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group" style="flex:1">
          <label class="form-label">Street Address</label>
          <input type="text" class="form-input" data-field="street_address" placeholder="Street, building, unit">
        </div>
        <div class="form-group" style="width:120px">
          <label class="form-label">Primary</label>
          <label style="display:flex;align-items:center;gap:6px">
            <input type="checkbox" data-field="is_primary"> Yes
          </label>
        </div>
      </div>
      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">Barangay</label>
          <input type="text" class="form-input" data-field="barangay">
        </div>
        <div class="form-group">
          <label class="form-label">City <span class="required">*</span></label>
          <input type="text" class="form-input" data-field="city" required>
        </div>
        <div class="form-group">
          <label class="form-label">Province</label>
          <input type="text" class="form-input" data-field="province">
        </div>
      </div>
      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">Postal Code</label>
          <input type="text" class="form-input" data-field="postal_code">
        </div>
        <div class="form-group">
          <label class="form-label">Country</label>
          <input type="text" class="form-input" data-field="country" value="Philippines">
        </div>
        <div class="form-group" style="display:flex;align-items:end;justify-content:flex-end">
          <button class="btn btn-danger btn-sm" onclick="removeAddress(this)" type="button">Remove</button>
        </div>
      </div>
    </div>
  `;
}

function contactTemplate() {
  return `
    <div class="card contact-item" style="padding:12px;margin-bottom:10px">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Type</label>
          <select class="form-select" data-field="contact_type">
            <option value="mobile">Mobile</option>
            <option value="landline">Landline</option>
            <option value="work">Work</option>
            <option value="home">Home</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group" style="flex:1">
          <label class="form-label">Contact Number <span class="required">*</span></label>
          <input type="text" class="form-input" data-field="contact_number" required>
        </div>
        <div class="form-group" style="width:120px">
          <label class="form-label">Primary</label>
          <label style="display:flex;align-items:center;gap:6px">
            <input type="checkbox" data-field="is_primary"> Yes
          </label>
        </div>
        <div class="form-group" style="width:120px">
          <label class="form-label">Verified</label>
          <label style="display:flex;align-items:center;gap:6px">
            <input type="checkbox" data-field="is_verified"> Yes
          </label>
        </div>
        <div class="form-group" style="display:flex;align-items:end;justify-content:flex-end">
          <button class="btn btn-danger btn-sm" onclick="removeContact(this)" type="button">Remove</button>
        </div>
      </div>
    </div>
  `;
}

function addAddress() {
  if (addressCount >= 3) return showToast('Maximum of 3 addresses', 'warning');
  document.getElementById('addressesContainer').insertAdjacentHTML('beforeend', addressTemplate());
  addressCount++;
}

function removeAddress(btn) {
  btn.closest('.address-item')?.remove();
  addressCount = Math.max(0, addressCount - 1);
}

function addContact() {
  if (contactCount >= 5) return showToast('Maximum of 5 contacts', 'warning');
  document.getElementById('contactsContainer').insertAdjacentHTML('beforeend', contactTemplate());
  contactCount++;
}

function removeContact(btn) {
  btn.closest('.contact-item')?.remove();
  contactCount = Math.max(0, contactCount - 1);
}

function collectAddresses() {
  return Array.from(document.querySelectorAll('.address-item')).map(item => ({
    address_type: item.querySelector('[data-field="address_type"]').value,
    street_address: item.querySelector('[data-field="street_address"]').value,
    barangay: item.querySelector('[data-field="barangay"]').value,
    city: item.querySelector('[data-field="city"]').value,
    province: item.querySelector('[data-field="province"]').value,
    postal_code: item.querySelector('[data-field="postal_code"]').value,
    country: item.querySelector('[data-field="country"]').value,
    is_primary: item.querySelector('[data-field="is_primary"]').checked ? 1 : 0,
  }));
}

function collectContacts() {
  return Array.from(document.querySelectorAll('.contact-item')).map(item => ({
    contact_type: item.querySelector('[data-field="contact_type"]').value,
    contact_number: item.querySelector('[data-field="contact_number"]').value,
    is_primary: item.querySelector('[data-field="is_primary"]').checked ? 1 : 0,
    is_verified: item.querySelector('[data-field="is_verified"]').checked ? 1 : 0,
  }));
}

async function saveClient() {
  const id  = document.getElementById('clientId').value;
  const btn = document.getElementById('saveClientBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving...';

  const payload = {
    first_name: document.getElementById('cFirst').value,
    middle_name: document.getElementById('cMiddle').value,
    last_name: document.getElementById('cLast').value,
    email: document.getElementById('cEmail').value,
    status: document.getElementById('cStatus').value,
  };

  if (!id) {
    payload.addresses = collectAddresses();
    payload.contacts = collectContacts();
  }

  const url = id ? '/api/v1/clients/' + id : '/api/v1/clients';
  const method = id ? 'PUT' : 'POST';

  try {
    const res  = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) { showToast(data.message || 'Saved', 'success'); closeModal('clientModal'); setTimeout(() => location.reload(), 800); }
    else showToast(data.message || 'Failed', 'error');
  } catch (e) {
    showToast('Network error', 'error');
  }

  btn.disabled = false; btn.innerHTML = 'Save Client';
}

function editClient(id) {
  fetch('/api/v1/clients/' + id).then(r => r.json()).then(res => {
    if (!res.success) return;
    const c = res.data;
    document.getElementById('clientId').value = c.id;
    document.getElementById('cFirst').value = c.first_name || '';
    document.getElementById('cMiddle').value = c.middle_name || '';
    document.getElementById('cLast').value = c.last_name || '';
    document.getElementById('cEmail').value = c.email || '';
    document.getElementById('cStatus').value = c.status || 'active';
    document.getElementById('clientModalTitle').textContent = 'Edit Client';
    document.getElementById('clientExtraSection').style.display = 'none';
    document.getElementById('clientEditNote').style.display = '';
    openModal('clientModal');
  });
}

function deleteClient(id, name) {
  document.getElementById('confirmMessage').textContent = `Delete client "${name}"? This cannot be undone.`;
  document.getElementById('confirmBtn').onclick = async () => {
    const res  = await fetch('/api/v1/clients/' + id, { method: 'DELETE', headers: { 'X-CSRF-Token': csrfToken } });
    const data = await res.json();
    if (data.success) { showToast('Client deleted', 'success'); closeModal('confirmModal'); setTimeout(() => location.reload(), 600); }
    else showToast(data.message || 'Failed', 'error');
  };
  openModal('confirmModal');
}
</script>

<?php
$content = ob_get_clean();
$title   = 'Clients | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
