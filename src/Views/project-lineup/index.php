<?php ob_start(); ?>
<?php
$canEdit          = can('project_lineup', 'edit');
$canDelete        = can('project_lineup', 'delete');
$canAdd           = can('project_lineup', 'add');
$canStatus        = can('project_lineup', 'status');
$canTshirt        = can('project_lineup', 'tshirt');
$canTags          = can('project_lineup', 'tags');
$canPrint         = can('project_lineup', 'print');
$canLabelAttached = can('project_lineup', 'label_attached');
$canQcPacking     = can('project_lineup', 'qc_packing');
$canAuthApproval  = can('project_lineup', 'auth_approval');

$projectStatusOptions = [
    'pending'       => 'Pending',
    'ongoing'       => 'Ongoing',
    'for_releasing' => 'For Releasing',
    'released'      => 'Released',
    'completed'     => 'Completed',
];
$triStatusOptions = [
    'pending'   => 'Pending',
    'ongoing'   => 'Ongoing',
    'completed' => 'Completed',
];
$approvalOptions = [
    'pending'  => 'Pending',
    'approved' => 'Approved',
];

function plStatusBadgeClass(string $val): string {
    return match($val) {
        'completed', 'approved', 'released' => 'badge-success',
        'ongoing', 'for_releasing'           => 'badge-warning',
        default                              => 'badge-secondary',
    };
}

function plSortLink(string $col, string $label, string $currentSort, string $currentOrder, array $filters): string {
    $nextOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $params    = array_filter(array_merge($filters, ['sort' => $col, 'order' => $nextOrder]), fn($v) => $v !== '');
    $arrow     = $currentSort === $col
        ? ($currentOrder === 'ASC' ? ' <span style="font-size:.8em">&#9650;</span>' : ' <span style="font-size:.8em">&#9660;</span>')
        : ' <span style="font-size:.8em;opacity:.4">&#8645;</span>';
    return '<a href="?' . http_build_query($params) . '" style="display:block;padding:12px 16px;color:inherit;text-decoration:none;white-space:nowrap">'
        . htmlspecialchars($label) . $arrow . '</a>';
}

$sort  = $filters['sort']  ?? 'pl.id';
$order = strtoupper($filters['order'] ?? 'ASC');
?>
<div class="page-header">
  <h1>Project Lineup</h1>
  <?php if ($canAdd): ?>
  <button class="btn btn-primary" onclick="openAddLineup()" style="display:flex;align-items:center;gap:6px">
    <?= icon('plus', 15) ?> Add Entry
  </button>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-body" style="padding:16px">
    <div class="filter-bar">
      <div class="search-bar" style="flex:1;max-width:280px">
        <?= icon('search', 16) ?>
        <input type="text" placeholder="Invoice, client, brand..." name="search"
          value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
          onchange="this.form.submit()" form="plFilterForm" style="width:100%">
      </div>
      <form id="plFilterForm" method="GET" class="d-flex gap-8" style="flex-wrap:wrap">
        <input type="date" name="date_from" class="form-input" style="height:38px;width:140px"
          value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" onchange="this.form.submit()">
        <input type="date" name="date_to" class="form-input" style="height:38px;width:140px"
          value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" onchange="this.form.submit()">
        <select name="client_id" class="form-select" style="width:180px;height:38px" onchange="this.form.submit()">
          <option value="">All Clients</option>
          <?php foreach ($clients as $cl): ?>
          <option value="<?= $cl['id'] ?>" <?= ($filters['client_id'] ?? '') == $cl['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($cl['full_name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <select name="category" class="form-select" style="width:160px;height:38px" onchange="this.form.submit()">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat['name']) ?>" <?= ($filters['category'] ?? '') === $cat['name'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <select name="type" class="form-select" style="width:150px;height:38px" onchange="this.form.submit()">
          <option value="">All Types</option>
          <?php foreach ($types as $tp): ?>
          <option value="<?= htmlspecialchars($tp['name']) ?>" <?= ($filters['type'] ?? '') === $tp['name'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($tp['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <select name="project_status" class="form-select" style="width:160px;height:38px" onchange="this.form.submit()">
          <option value="">All Status</option>
          <?php foreach ($projectStatusOptions as $val => $lbl): ?>
          <option value="<?= $val ?>" <?= ($filters['project_status'] ?? '') === $val ? 'selected' : '' ?>>
            <?= $lbl ?>
          </option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($filters['sort'])): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($filters['sort']) ?>"><?php endif; ?>
        <?php if (!empty($filters['order'])): ?><input type="hidden" name="order" value="<?= htmlspecialchars($filters['order']) ?>"><?php endif; ?>
      </form>
    </div>
  </div>

  <div class="table-wrapper" style="overflow-x:auto">
    <table class="data-table" style="min-width:1600px">
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th style="padding:0"><?= plSortLink('pl.date', 'Date', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= plSortLink('i.invoice_number', 'Invoice', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= plSortLink('c.full_name', 'Client', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= plSortLink('pl.brand_name', 'Brand Name', $sort, $order, $filters) ?></th>
          <th>Category</th>
          <th>Type</th>
          <th style="padding:0;width:60px"><?= plSortLink('pl.qty', 'Qty', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= plSortLink('pl.link', 'Link', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= plSortLink('pl.deadline', 'Deadline', $sort, $order, $filters) ?></th>
          <th style="padding:0;min-width:140px"><?= plSortLink('pl.project_status', 'Project Status', $sort, $order, $filters) ?></th>
          <th style="min-width:130px">T-Shirt</th>
          <th style="min-width:130px">Tags</th>
          <th style="min-width:130px">Print</th>
          <th style="min-width:130px">Label Attached</th>
          <th style="min-width:130px">QC/Packing</th>
          <th style="min-width:130px">Auth. Approval</th>
          <?php if ($canEdit || $canDelete): ?><th>Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lineups as $ln): ?>
        <tr>
          <td><?= (int)$ln['queue_number'] ?></td>
          <td style="white-space:nowrap">
            <div style="font-size:.85rem;font-weight:600"><?= date('M d, Y', strtotime($ln['date'])) ?></div>
          </td>
          <td><strong><?= htmlspecialchars($ln['invoice_number'] ?? '—') ?></strong></td>
          <td><?= htmlspecialchars($ln['client_name'] ?? 'Walk-in') ?></td>
          <td><?= htmlspecialchars($ln['brand_name'] ?? '') ?></td>
          <td style="font-size:.8rem"><?= htmlspecialchars($ln['categories'] ?? '') ?></td>
          <td style="font-size:.8rem"><?= htmlspecialchars($ln['types'] ?? '') ?></td>
          <td><?= (int)$ln['qty'] ?></td>
          <td><?php if (!empty($ln['link'])): ?>
            <?php $linkHref = preg_match('#^https?://#i', $ln['link']) ? $ln['link'] : 'https://' . $ln['link']; ?>
            <a href="<?= htmlspecialchars($linkHref) ?>" target="_blank" rel="noopener noreferrer" style="font-size:.8rem" title="<?= htmlspecialchars($ln['link']) ?>"><?= htmlspecialchars($ln['link']) ?></a>
          <?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
          <td style="white-space:nowrap"><?= $ln['deadline'] ? date('M d, Y', strtotime($ln['deadline'])) : '<span class="text-muted">—</span>' ?></td>
          <td onclick="event.stopPropagation()">
            <?php if ($canStatus): ?>
            <select class="form-select pl-status-select" style="padding:4px 6px;font-size:.78rem;height:30px"
              onchange="applyStatusColor(this); updateLineupStatus(<?= $ln['id'] ?>, 'project_status', this.value)">
              <?php foreach ($projectStatusOptions as $v => $l): ?>
              <option value="<?= $v ?>" <?= $ln['project_status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
            <?php else: ?>
            <span class="badge <?= plStatusBadgeClass($ln['project_status']) ?>">
              <?= $projectStatusOptions[$ln['project_status']] ?? ucfirst($ln['project_status']) ?>
            </span>
            <?php endif; ?>
          </td>
          <td onclick="event.stopPropagation()">
            <?php if ($canTshirt): ?>
            <select class="form-select pl-status-select" style="padding:4px 6px;font-size:.78rem;height:30px"
              onchange="applyStatusColor(this); updateLineupStatus(<?= $ln['id'] ?>, 'tshirt_status', this.value)">
              <?php foreach ($triStatusOptions as $v => $l): ?>
              <option value="<?= $v ?>" <?= $ln['tshirt_status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
            <?php else: ?>
            <span class="badge <?= plStatusBadgeClass($ln['tshirt_status']) ?>">
              <?= $triStatusOptions[$ln['tshirt_status']] ?? ucfirst($ln['tshirt_status']) ?>
            </span>
            <?php endif; ?>
          </td>
          <td onclick="event.stopPropagation()">
            <?php if ($canTags): ?>
            <select class="form-select pl-status-select" style="padding:4px 6px;font-size:.78rem;height:30px"
              onchange="applyStatusColor(this); updateLineupStatus(<?= $ln['id'] ?>, 'tags_status', this.value)">
              <?php foreach ($triStatusOptions as $v => $l): ?>
              <option value="<?= $v ?>" <?= $ln['tags_status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
            <?php else: ?>
            <span class="badge <?= plStatusBadgeClass($ln['tags_status']) ?>">
              <?= $triStatusOptions[$ln['tags_status']] ?? ucfirst($ln['tags_status']) ?>
            </span>
            <?php endif; ?>
          </td>
          <td onclick="event.stopPropagation()">
            <?php if ($canPrint): ?>
            <select class="form-select pl-status-select" style="padding:4px 6px;font-size:.78rem;height:30px"
              onchange="applyStatusColor(this); updateLineupStatus(<?= $ln['id'] ?>, 'print_status', this.value)">
              <?php foreach ($triStatusOptions as $v => $l): ?>
              <option value="<?= $v ?>" <?= $ln['print_status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
            <?php else: ?>
            <span class="badge <?= plStatusBadgeClass($ln['print_status']) ?>">
              <?= $triStatusOptions[$ln['print_status']] ?? ucfirst($ln['print_status']) ?>
            </span>
            <?php endif; ?>
          </td>
          <td onclick="event.stopPropagation()">
            <?php if ($canLabelAttached): ?>
            <select class="form-select pl-status-select" style="padding:4px 6px;font-size:.78rem;height:30px"
              onchange="applyStatusColor(this); updateLineupStatus(<?= $ln['id'] ?>, 'label_attached_status', this.value)">
              <?php foreach ($triStatusOptions as $v => $l): ?>
              <option value="<?= $v ?>" <?= $ln['label_attached_status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
            <?php else: ?>
            <span class="badge <?= plStatusBadgeClass($ln['label_attached_status']) ?>">
              <?= $triStatusOptions[$ln['label_attached_status']] ?? ucfirst($ln['label_attached_status']) ?>
            </span>
            <?php endif; ?>
          </td>
          <td onclick="event.stopPropagation()">
            <?php if ($canQcPacking): ?>
            <select class="form-select pl-status-select" style="padding:4px 6px;font-size:.78rem;height:30px"
              onchange="applyStatusColor(this); updateLineupStatus(<?= $ln['id'] ?>, 'qc_packing_status', this.value)">
              <?php foreach ($triStatusOptions as $v => $l): ?>
              <option value="<?= $v ?>" <?= $ln['qc_packing_status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
            <?php else: ?>
            <span class="badge <?= plStatusBadgeClass($ln['qc_packing_status']) ?>">
              <?= $triStatusOptions[$ln['qc_packing_status']] ?? ucfirst($ln['qc_packing_status']) ?>
            </span>
            <?php endif; ?>
          </td>
          <td onclick="event.stopPropagation()">
            <?php if ($canAuthApproval): ?>
            <select class="form-select pl-status-select" style="padding:4px 6px;font-size:.78rem;height:30px"
              onchange="applyStatusColor(this); updateLineupStatus(<?= $ln['id'] ?>, 'authorized_approval', this.value)">
              <?php foreach ($approvalOptions as $v => $l): ?>
              <option value="<?= $v ?>" <?= $ln['authorized_approval'] === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
            <?php else: ?>
            <span class="badge <?= plStatusBadgeClass($ln['authorized_approval']) ?>">
              <?= $approvalOptions[$ln['authorized_approval']] ?? ucfirst($ln['authorized_approval']) ?>
            </span>
            <?php endif; ?>
          </td>
          <?php if ($canEdit || $canDelete): ?>
          <td onclick="event.stopPropagation()">
            <?php if ($canEdit): ?>
            <button class="icon-btn" onclick="editLineup(<?= htmlspecialchars(json_encode($ln), ENT_QUOTES) ?>)" title="Edit"><?= icon('edit', 15) ?></button>
            <?php endif; ?>
            <?php if ($canDelete): ?>
            <button class="icon-btn danger" onclick="deleteLineup(<?= $ln['id'] ?>, '<?= htmlspecialchars($ln['invoice_number'] ?? 'entry', ENT_QUOTES) ?>')" title="Delete"><?= icon('delete', 15) ?></button>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($lineups)): ?>
          <tr><td colspan="18" class="text-center text-muted" style="padding:48px">No project lineup entries found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
    $plPqFilters = array_filter($filters ?? [], fn($v) => $v !== '');
    unset($plPqFilters['page'], $plPqFilters['per_page']);
    echo renderPagination($pagination, $plPqFilters);
  ?>
</div>

<!-- Add / Edit Modal -->
<div class="modal-overlay" id="lineupModal">
  <div class="modal-content" style="max-width:680px">
    <div class="modal-header">
      <h2 class="modal-title" id="lineupModalTitle">Add Project Lineup</h2>
      <button class="modal-close" onclick="closeModal('lineupModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="lineupId">
      <input type="hidden" id="lineupInvoiceId">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="form-group">
          <label class="form-label">Date <span class="required">*</span></label>
          <input type="date" id="lineupDate" class="form-input">
        </div>
        <div class="form-group">
          <label class="form-label">Invoice</label>
          <input type="text" id="lineupInvoiceNum" class="form-input" readonly placeholder="Auto-filled">
        </div>
        <div class="form-group">
          <label class="form-label">Client</label>
          <input type="text" id="lineupClientName" class="form-input" readonly placeholder="Auto-filled">
        </div>
        <div class="form-group">
          <label class="form-label">Brand Name</label>
          <input type="text" id="lineupBrandName" class="form-input" placeholder="Brand name">
        </div>
        <div class="form-group">
          <label class="form-label">Categories</label>
          <input type="text" id="lineupCategories" class="form-input" placeholder="e.g. Apparel, Accessories">
        </div>
        <div class="form-group">
          <label class="form-label">Types</label>
          <input type="text" id="lineupTypes" class="form-input" placeholder="e.g. T-Shirt, Polo">
        </div>
        <div class="form-group">
          <label class="form-label">Qty</label>
          <input type="number" id="lineupQty" class="form-input" min="0" value="0">
        </div>
        <div class="form-group">
          <label class="form-label">Deadline <span style="color:var(--color-gray-400);font-size:.8rem">(optional)</span></label>
          <input type="date" id="lineupDeadline" class="form-input">
        </div>
      </div>
      <!-- Optional: Link, Notes, Photo -->
      <input type="hidden" id="lineupExistingPhoto">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:4px">
        <div>
          <div class="form-group">
            <label class="form-label">Link <span style="color:var(--color-gray-400);font-size:.8rem">(optional)</span></label>
            <input type="url" id="lineupLink" class="form-input" placeholder="https://...">
          </div>
          <div class="form-group" style="margin-top:12px">
            <label class="form-label">Notes <span style="color:var(--color-gray-400);font-size:.8rem">(optional)</span></label>
            <textarea id="lineupNotes" class="form-input" rows="3" placeholder="Add notes..." style="resize:vertical"></textarea>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Photo <span style="color:var(--color-gray-400);font-size:.8rem">(optional)</span></label>
          <input type="file" id="lineupPhoto" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewLineupPhoto(this)" style="display:block;width:100%;padding:6px;border:1px solid var(--color-border);border-radius:6px;font-size:.85rem">
          <div id="lineupPhotoPreview" style="display:none;margin-top:8px;text-align:center">
            <img id="lineupPhotoImg" src="" alt="Photo preview" style="max-width:100%;max-height:160px;border-radius:6px;object-fit:contain;border:1px solid var(--color-border)">
            <button type="button" onclick="clearLineupPhoto()" style="display:block;margin:4px auto 0;font-size:.78rem;color:var(--color-danger,#dc3545);background:none;border:none;cursor:pointer;padding:0">Remove photo</button>
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:4px">
        <div class="form-group">
          <label class="form-label">Project Status</label>
          <select id="lineupProjectStatus" class="form-select">
            <option value="pending">Pending</option>
            <option value="ongoing">Ongoing</option>
            <option value="for_releasing">For Releasing</option>
            <option value="released">Released</option>
            <option value="completed">Completed</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">T-Shirt</label>
          <select id="lineupTshirtStatus" class="form-select">
            <option value="pending">Pending</option>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Tags</label>
          <select id="lineupTagsStatus" class="form-select">
            <option value="pending">Pending</option>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Print</label>
          <select id="lineupPrintStatus" class="form-select">
            <option value="pending">Pending</option>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Label Attached</label>
          <select id="lineupLabelStatus" class="form-select">
            <option value="pending">Pending</option>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">QC/Packing</label>
          <select id="lineupQcStatus" class="form-select">
            <option value="pending">Pending</option>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Auth. Approval</label>
          <select id="lineupApproval" class="form-select">
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
          </select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('lineupModal')">Cancel</button>
      <button type="button" class="btn btn-primary" id="lineupSaveBtn" onclick="saveLineup()">Save</button>
    </div>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="lineupDeleteModal">
  <div class="modal-content" style="max-width:400px">
    <div class="modal-header">
      <h2 class="modal-title">Delete Entry</h2>
      <button class="modal-close" onclick="closeModal('lineupDeleteModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <p>Are you sure you want to delete lineup entry for invoice <strong id="lineupDeleteInvoiceNum"></strong>? This cannot be undone.</p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('lineupDeleteModal')">Cancel</button>
      <button type="button" class="btn btn-danger" onclick="confirmDeleteLineup()">Delete</button>
    </div>
  </div>
</div>

<script>
var _lineupDeleteId = null;

// Color map for status dropdowns
function applyStatusColor(el) {
  var colors = {
    pending:       '#dc3545',
    ongoing:       '#0d6efd',
    completed:     '#198754',
    approved:      '#198754',
    for_releasing: '#6f42c1',
    released:      '#6f42c1',
  };
  el.style.color       = colors[el.value] || '#dc3545';
  el.style.fontWeight  = '600';
  el.style.background  = '';
  el.style.borderColor = '';
}

// Fields that are locked when forwarding from an invoice
var _lineupForwardLocked = ['lineupDate','lineupBrandName','lineupCategories','lineupTypes','lineupQty'];

function _setForwardLock(locked) {
  _lineupForwardLocked.forEach(function(id) {
    var el = document.getElementById(id);
    if (!el) return;
    if (locked) {
      el.setAttribute('readonly', 'readonly');
      el.style.background = 'var(--color-gray-50, #f9fafb)';
      el.style.cursor = 'not-allowed';
    } else {
      el.removeAttribute('readonly');
      el.style.background = '';
      el.style.cursor = '';
    }
  });
}

function openAddLineup(prefill) {
  document.getElementById('lineupModalTitle').textContent = 'Add Project Lineup';
  document.getElementById('lineupId').value = '';
  document.getElementById('lineupSaveBtn').textContent = 'Save';

  // Populate fields
  document.getElementById('lineupDate').value = prefill ? (prefill.date || '') : '';
  document.getElementById('lineupInvoiceId').value = prefill ? (prefill.id || '') : '';
  document.getElementById('lineupInvoiceNum').value = prefill ? (prefill.invoice_number || '') : '';
  document.getElementById('lineupClientName').value = prefill ? (prefill.client_name || '') : '';
  document.getElementById('lineupBrandName').value = prefill ? (prefill.brand_name || '') : '';
  document.getElementById('lineupCategories').value = prefill ? (prefill.categories || []).join(', ') : '';
  document.getElementById('lineupTypes').value = prefill ? (prefill.types || []).join(', ') : '';
  document.getElementById('lineupQty').value = prefill ? (prefill.total_qty || 0) : 0;
  document.getElementById('lineupDeadline').value = '';
  document.getElementById('lineupLink').value = '';
  document.getElementById('lineupNotes').value = '';
  document.getElementById('lineupPhoto').value = '';
  document.getElementById('lineupExistingPhoto').value = '';
  document.getElementById('lineupPhotoPreview').style.display = 'none';
  document.getElementById('lineupPhotoImg').src = '';
  document.getElementById('lineupProjectStatus').value = 'pending';
  document.getElementById('lineupTshirtStatus').value = 'pending';
  document.getElementById('lineupTagsStatus').value = 'pending';
  document.getElementById('lineupPrintStatus').value = 'pending';
  document.getElementById('lineupLabelStatus').value = 'pending';
  document.getElementById('lineupQcStatus').value = 'pending';
  document.getElementById('lineupApproval').value = 'pending';

  // Lock prefill-sourced fields when forwarding from invoice
  _setForwardLock(!!prefill);

  document.getElementById('lineupModal').classList.add('show');
}

function editLineup(data) {
  document.getElementById('lineupModalTitle').textContent = 'Edit Project Lineup';
  document.getElementById('lineupId').value = data.id;
  document.getElementById('lineupSaveBtn').textContent = 'Update';

  document.getElementById('lineupDate').value = data.date || '';
  document.getElementById('lineupInvoiceId').value = data.invoice_id || '';
  document.getElementById('lineupInvoiceNum').value = data.invoice_number || '';
  document.getElementById('lineupClientName').value = data.client_name || '';
  document.getElementById('lineupBrandName').value = data.brand_name || '';
  document.getElementById('lineupCategories').value = data.categories || '';
  document.getElementById('lineupTypes').value = data.types || '';
  document.getElementById('lineupQty').value = data.qty || 0;
  document.getElementById('lineupDeadline').value = data.deadline || '';
  document.getElementById('lineupLink').value = data.link || '';
  document.getElementById('lineupNotes').value = data.notes || '';
  document.getElementById('lineupPhoto').value = '';
  document.getElementById('lineupExistingPhoto').value = data.photo || '';
  if (data.photo) {
    document.getElementById('lineupPhotoImg').src = appPath(data.photo);
    document.getElementById('lineupPhotoPreview').style.display = 'block';
  } else {
    document.getElementById('lineupPhotoImg').src = '';
    document.getElementById('lineupPhotoPreview').style.display = 'none';
  }
  document.getElementById('lineupProjectStatus').value = data.project_status || 'pending';
  document.getElementById('lineupTshirtStatus').value = data.tshirt_status || 'pending';
  document.getElementById('lineupTagsStatus').value = data.tags_status || 'pending';
  document.getElementById('lineupPrintStatus').value = data.print_status || 'pending';
  document.getElementById('lineupLabelStatus').value = data.label_attached_status || 'pending';
  document.getElementById('lineupQcStatus').value = data.qc_packing_status || 'pending';
  document.getElementById('lineupApproval').value = data.authorized_approval || 'pending';

  // Always editable when opened via edit button
  _setForwardLock(false);

  document.getElementById('lineupModal').classList.add('show');
}

async function saveLineup() {
  const id = document.getElementById('lineupId').value;
  const invoiceId = document.getElementById('lineupInvoiceId').value;
  const date = document.getElementById('lineupDate').value;
  if (!date) { showToast('Date is required', 'error'); return; }
  if (!id && !invoiceId) { showToast('Invoice reference is missing', 'error'); return; }

  const fd = new FormData();
  if (id) fd.append('_method', 'PUT');
  fd.append('invoice_id',            invoiceId);
  fd.append('client_name',           document.getElementById('lineupClientName').value || 'Walk-in');
  fd.append('date',                  date);
  fd.append('brand_name',            document.getElementById('lineupBrandName').value);
  fd.append('categories',            document.getElementById('lineupCategories').value);
  fd.append('types',                 document.getElementById('lineupTypes').value);
  fd.append('qty',                   document.getElementById('lineupQty').value);
  fd.append('deadline',              document.getElementById('lineupDeadline').value);
  fd.append('project_status',        document.getElementById('lineupProjectStatus').value);
  fd.append('tshirt_status',         document.getElementById('lineupTshirtStatus').value);
  fd.append('tags_status',           document.getElementById('lineupTagsStatus').value);
  fd.append('print_status',          document.getElementById('lineupPrintStatus').value);
  fd.append('label_attached_status', document.getElementById('lineupLabelStatus').value);
  fd.append('qc_packing_status',     document.getElementById('lineupQcStatus').value);
  fd.append('authorized_approval',   document.getElementById('lineupApproval').value);
  fd.append('link',                  document.getElementById('lineupLink').value);
  fd.append('notes',                 document.getElementById('lineupNotes').value);
  fd.append('existing_photo',        document.getElementById('lineupExistingPhoto').value);
  const photoFile = document.getElementById('lineupPhoto').files[0];
  if (photoFile) fd.append('photo', photoFile);

  const url = id ? '/api/v1/project-lineup/' + id : '/api/v1/project-lineup';

  try {
    const res  = await fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: fd,
    });
    const json = await res.json();
    if (json.success) {
      showToast(id ? 'Entry updated' : 'Entry created', 'success');
      closeModal('lineupModal');
      setTimeout(() => location.reload(), 600);
    } else {
      showToast(json.message || 'Error saving entry', 'error');
    }
  } catch (e) {
    showToast('Network error', 'error');
  }
}

function previewLineupPhoto(input) {
  const file = input.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('lineupPhotoImg').src = e.target.result;
      document.getElementById('lineupPhotoPreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
  }
}

function clearLineupPhoto() {
  document.getElementById('lineupPhoto').value = '';
  document.getElementById('lineupPhotoImg').src = '';
  document.getElementById('lineupPhotoPreview').style.display = 'none';
  document.getElementById('lineupExistingPhoto').value = '';
}

async function updateLineupStatus(id, field, value) {
  try {
    const res  = await fetch('/api/v1/project-lineup/' + id + '/status', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: JSON.stringify({ field, value }),
    });
    const json = await res.json();
    if (json.success) {
      showToast('Status updated', 'success');
    } else {
      showToast(json.message || 'Failed to update status', 'error');
    }
  } catch (e) {
    showToast('Network error', 'error');
  }
}

function deleteLineup(id, invoiceNum) {
  _lineupDeleteId = id;
  document.getElementById('lineupDeleteInvoiceNum').textContent = invoiceNum;
  document.getElementById('lineupDeleteModal').classList.add('show');
}

async function confirmDeleteLineup() {
  if (!_lineupDeleteId) return;
  try {
    const res  = await fetch('/api/v1/project-lineup/' + _lineupDeleteId, {
      method: 'DELETE',
      headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
    });
    const json = await res.json();
    if (json.success) {
      showToast('Entry deleted', 'success');
      closeModal('lineupDeleteModal');
      setTimeout(() => location.reload(), 600);
    } else {
      showToast(json.message || 'Error deleting entry', 'error');
    }
  } catch (e) {
    showToast('Network error', 'error');
  }
}

<?php if (!empty($prefill_data)): ?>
// Auto-open add modal with prefill from invoice forward
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.pl-status-select').forEach(applyStatusColor);
  openAddLineup(<?= json_encode($prefill_data) ?>);
  // Remove prefill_invoice_id from URL without reload
  const url = new URL(window.location.href);
  url.searchParams.delete('prefill_invoice_id');
  history.replaceState(null, '', url.toString());
});
<?php else: ?>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.pl-status-select').forEach(applyStatusColor);
});
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();
require VIEW_PATH . '/layouts/main.php';
