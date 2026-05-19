<?php ob_start(); ?>
<?php
$actionBadge = [
    'create'  => ['label' => 'Add',      'class' => 'badge-success'],
    'update'  => ['label' => 'Edit',     'class' => 'badge-info'],
    'delete'  => ['label' => 'Delete',   'class' => 'badge-danger'],
    'login'   => ['label' => 'Login',    'class' => 'badge-teal'],
    'logout'  => ['label' => 'Logout',   'class' => 'badge-secondary'],
    'payment' => ['label' => 'Payment',  'class' => 'badge-purple'],
    'restock' => ['label' => 'Restock',  'class' => 'badge-orange'],
    'view'    => ['label' => 'View',     'class' => 'badge-gray'],
];

$moduleLabels = [
    'auth'           => 'Auth',
    'users'          => 'Users',
    'roles'          => 'Roles',
    'clients'        => 'Clients',
    'products'       => 'Products',
    'stock_products' => 'Stock Products',
    'inventory'      => 'Inventory',
    'pos'            => 'POS',
    'invoices'       => 'Invoices',
    'payments'       => 'Payments',
    'reports'        => 'Reports',
    'settings'       => 'Settings',
    'audit_logs'     => 'Audit Logs',
];

$f = $filters ?? [];
?>

<div class="page-header">
  <h1>Audit Logs</h1>
  <?php if (can('audit_logs', 'export')): ?>
  <div class="d-flex gap-8">
    <a href="<?= htmlspecialchars(app_url('/api/v1/audit-logs/export?' . http_build_query(array_filter($f)))) ?>"
       class="btn btn-secondary btn-sm" target="_blank" style="display:flex;align-items:center;gap:6px">
      <?= icon('download', 14) ?> Export CSV
    </a>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-body" style="padding:16px">
    <div class="filter-bar">
      <div class="search-bar" style="flex:1;max-width:280px">
        <?= icon('search', 16) ?>
        <input type="text" placeholder="Search description, IP, user..." name="search"
               value="<?= htmlspecialchars($f['search'] ?? '') ?>"
               onchange="this.form.submit()" form="auditFilterForm" style="width:100%">
      </div>
      <form id="auditFilterForm" method="GET"
            action="<?= htmlspecialchars(app_url('/audit-logs')) ?>"
            class="d-flex gap-8" style="flex-wrap:wrap;align-items:center">
        <select name="action_type" class="form-select" style="width:130px;height:38px" onchange="this.form.submit()">
          <option value="">All Actions</option>
          <?php foreach ($actionBadge as $val => $meta): ?>
            <option value="<?= $val ?>" <?= ($f['action_type'] ?? '') === $val ? 'selected' : '' ?>>
              <?= htmlspecialchars($meta['label']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <select name="module" class="form-select" style="width:150px;height:38px" onchange="this.form.submit()">
          <option value="">All Modules</option>
          <?php foreach ($moduleLabels as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= ($f['module'] ?? '') === $val ? 'selected' : '' ?>>
              <?= htmlspecialchars($lbl) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <select name="user_id" class="form-select" style="width:140px;height:38px" onchange="this.form.submit()">
          <option value="">All Users</option>
          <?php foreach ($users ?? [] as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= ((int)($f['user_id'] ?? 0)) === (int)$u['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($u['display_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" class="form-input" style="height:38px;width:140px"
               value="<?= htmlspecialchars($f['date_from'] ?? '') ?>" onchange="this.form.submit()">
        <input type="date" name="date_to" class="form-input" style="height:38px;width:140px"
               value="<?= htmlspecialchars($f['date_to'] ?? '') ?>" onchange="this.form.submit()">
        <?php if (!empty(array_filter($f))): ?>
          <a href="<?= htmlspecialchars(app_url('/audit-logs')) ?>" class="btn btn-secondary btn-sm"
             style="height:38px;display:flex;align-items:center">Clear</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:50px">#</th>
          <th style="min-width:120px">Date / Time</th>
          <th style="min-width:130px">User</th>
          <th style="min-width:110px">IP Address</th>
          <th style="width:90px">Action</th>
          <th style="min-width:110px">Module</th>
          <th>Description</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="8" class="text-center text-muted" style="padding:48px">No audit logs found.</td></tr>
        <?php else: ?>
          <?php
            $perPage = $pagination['per_page']     ?? 20;
            $curPage = $pagination['current_page'] ?? 1;
            foreach ($logs as $i => $log):
              $actionMeta = $actionBadge[$log['action_type']] ?? ['label' => ucfirst($log['action_type']), 'class' => 'badge-secondary'];
              $moduleLbl  = $moduleLabels[$log['module_name'] ?? ''] ?? ucfirst($log['module_name'] ?? '--');
              $dt         = new DateTime($log['created_at']);
              $hasDetails = !empty($log['old_value']) || !empty($log['new_value']);
              $rowNum     = (($curPage - 1) * $perPage) + $i + 1;
          ?>
          <tr<?= $hasDetails ? ' style="cursor:pointer" onclick="showAuditDetail(' . htmlspecialchars(json_encode($log), ENT_QUOTES) . ')"' : '' ?>>
            <td class="text-muted" style="font-size:.75rem"><?= $rowNum ?></td>
            <td style="white-space:nowrap">
              <div style="font-size:.78rem;font-weight:600"><?= $dt->format('h:i A') ?></div>
              <div style="font-size:.75rem;color:var(--color-gray-500)"><?= $dt->format('M d, Y') ?></div>
            </td>
            <td>
              <?php if (!empty($log['user_name'])): ?>
                <div style="display:flex;align-items:center;gap:8px">
                  <span style="display:inline-flex;width:28px;height:28px;border-radius:50%;overflow:hidden;flex-shrink:0;border:1.5px solid var(--color-gray-200)">
                    <img src="<?= htmlspecialchars(!empty($log['user_profile_image']) ? app_url($log['user_profile_image']) : asset_url('/assets/images/no-image.png')) ?>"
                         alt="" style="width:100%;height:100%;object-fit:cover;display:block"
                         onerror="this.src='<?= htmlspecialchars(asset_url('/assets/images/no-image.png')) ?>'">
                  </span>
                  <div style="font-size:.82rem"><?= htmlspecialchars($log['user_name']) ?></div>
                </div>
              <?php else: ?>
                <span class="text-muted" style="font-size:.78rem">System</span>
              <?php endif; ?>
            </td>
            <td>
              <code style="font-size:.75rem;background:none"><?= htmlspecialchars($log['ip_address'] ?? '--') ?></code>
            </td>
            <td>
              <span class="badge <?= $actionMeta['class'] ?>"><?= htmlspecialchars($actionMeta['label']) ?></span>
            </td>
            <td>
              <span style="font-size:.82rem"><?= htmlspecialchars($moduleLbl) ?></span>
              <?php if (!empty($log['record_id'])): ?>
                <div style="font-size:.72rem;color:var(--color-gray-500)">#<?= (int)$log['record_id'] ?></div>
              <?php endif; ?>
            </td>
            <td style="font-size:.82rem;max-width:280px;word-break:break-word">
              <?= htmlspecialchars($log['description'] ?? '') ?>
              <?php if (($log['status'] ?? 'success') === 'failed'): ?>
                <span class="badge badge-danger" style="margin-left:4px">Failed</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
    $auditFilters = array_filter($f, fn($v) => $v !== '');
    unset($auditFilters['page'], $auditFilters['per_page']);
    echo renderPagination($pagination ?? [], $auditFilters);
  ?>
</div>

<!-- ===== Audit Detail Modal ===== -->
<div class="modal-overlay" id="auditDetailModal">
  <div class="modal-content" style="max-width:720px;width:95%">
    <div class="modal-header">
      <h2 class="modal-title"><?= icon('audit', 16) ?> &nbsp;Audit Log Details</h2>
      <button class="modal-close" onclick="closeAuditDetail()"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body" id="auditDetailBody" style="max-height:72vh;overflow-y:auto">
      <!-- Filled by JS -->
    </div>
  </div>
</div>

<script>
const ACTION_LABELS = {
  create:  'Add',
  update:  'Edit',
  delete:  'Delete',
  login:   'Login',
  logout:  'Logout',
  payment: 'Payment',
  restock: 'Restock',
  view:    'View',
};

const ACTION_COLORS = {
  create:  '#22c55e',
  update:  '#3b82f6',
  delete:  '#ef4444',
  login:   '#14b8a6',
  logout:  '#6b7280',
  payment: '#a855f7',
  restock: '#f97316',
  view:    '#9ca3af',
};

const SKIP_FIELDS = ['updated_at','deleted_at','password_hash','remember_token','otp','otp_expires_at','reset_token','created_at'];

function showAuditDetail(log) {
  const action = log.action_type;
  const oldVal = log.old_value ? JSON.parse(log.old_value) : null;
  const newVal = log.new_value ? JSON.parse(log.new_value) : null;
  const label  = ACTION_LABELS[action] || action;
  const color  = ACTION_COLORS[action] || '#6b7280';
  const dt     = new Date(log.created_at);
  const dtStr  = dt.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'}) + '  ' + dt.toLocaleDateString('en-US',{month:'short',day:'2-digit',year:'numeric'});

  let html = `<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 24px;margin-bottom:16px;font-size:.83rem">
    <div><span class="text-muted">Action</span><br><strong style="color:${color}">${label}</strong></div>
    <div><span class="text-muted">Module</span><br><strong>${escHtml(log.module_name || '--')}</strong></div>
    <div><span class="text-muted">Date / Time</span><br><strong>${dtStr}</strong></div>
    <div><span class="text-muted">User</span><br><strong>${escHtml(log.user_name || 'System')}</strong></div>
    <div><span class="text-muted">IP Address</span><br><code>${escHtml(log.ip_address || '--')}</code></div>
    <div><span class="text-muted">Record ID</span><br><strong>${log.record_id || '--'}</strong></div>
  </div>`;

  if (log.description) {
    html += `<div style="background:var(--color-bg);border-radius:6px;padding:10px 14px;margin-bottom:16px;font-size:.83rem">
      <span class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em">Description</span><br>
      <span>${escHtml(log.description)}</span>
    </div>`;
  }

  if (action === 'update' && oldVal && newVal) {
    html += renderEditDiff(oldVal, newVal);
  } else if ((action === 'create' || action === 'payment' || action === 'restock') && newVal) {
    html += renderDataTable('Details', newVal, color);
  } else if (action === 'delete' && oldVal) {
    html += renderDataTable('Deleted Record Data', oldVal, '#ef4444');
  } else if ((action === 'login' || action === 'logout') && newVal) {
    html += renderDataTable('Session Info', newVal, color);
  } else {
    if (newVal) html += renderDataTable('New Value', newVal, color);
    if (oldVal) html += renderDataTable('Old Value', oldVal, '#ef4444');
  }

  if (log.user_agent) {
    html += `<details style="margin-top:12px;font-size:.75rem">
      <summary style="cursor:pointer;color:var(--color-muted)">User Agent</summary>
      <div style="margin-top:6px;word-break:break-all;color:var(--color-muted)">${escHtml(log.user_agent)}</div>
    </details>`;
  }

  document.getElementById('auditDetailBody').innerHTML = html;
  document.getElementById('auditDetailModal').classList.add('show');
}

function renderEditDiff(oldV, newV) {
  const allKeys = [...new Set([...Object.keys(oldV), ...Object.keys(newV)])].filter(k => !SKIP_FIELDS.includes(k));
  const changed = allKeys.filter(k => String(oldV[k] ?? '') !== String(newV[k] ?? ''));

  if (!changed.length) return `<div class="text-muted" style="font-size:.82rem">No field changes detected.</div>`;

  let rows = '';
  for (const k of changed) {
    rows += `<tr>
      <td style="font-weight:600;font-size:.8rem;white-space:nowrap;padding:6px 10px">${escHtml(k)}</td>
      <td style="font-size:.8rem;padding:6px 10px;color:#ef4444;word-break:break-word">${formatVal(oldV[k])}</td>
      <td style="font-size:.8rem;padding:6px 10px;color:#22c55e;word-break:break-word">${formatVal(newV[k])}</td>
    </tr>`;
  }
  return `<div style="margin-bottom:4px;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--color-muted)">
    Changes (${changed.length} field${changed.length !== 1 ? 's' : ''})
  </div>
  <div style="border:1px solid var(--color-border);border-radius:6px;overflow:hidden">
    <table style="width:100%;border-collapse:collapse">
      <thead style="background:var(--color-bg)">
        <tr>
          <th style="padding:6px 10px;font-size:.75rem;text-align:left;border-bottom:1px solid var(--color-border)">Field</th>
          <th style="padding:6px 10px;font-size:.75rem;text-align:left;border-bottom:1px solid var(--color-border)">From</th>
          <th style="padding:6px 10px;font-size:.75rem;text-align:left;border-bottom:1px solid var(--color-border)">To</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>
  </div>`;
}

function renderDataTable(title, obj, color) {
  if (Array.isArray(obj)) return renderArraySection(title, obj, color);

  let rows = '';
  for (const [k, v] of Object.entries(obj)) {
    if (SKIP_FIELDS.includes(k) || (k === 'items' && Array.isArray(v))) continue;
    rows += `<tr>
      <td style="font-weight:600;font-size:.8rem;white-space:nowrap;padding:6px 10px;width:40%">${escHtml(k)}</td>
      <td style="font-size:.8rem;padding:6px 10px;word-break:break-word">${formatVal(v)}</td>
    </tr>`;
  }

  let out = '';
  if (rows) {
    out += `<div style="margin-bottom:4px;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:${color}">
      ${escHtml(title)}
    </div>
    <div style="border:1px solid var(--color-border);border-radius:6px;overflow:hidden;margin-bottom:12px">
      <table style="width:100%;border-collapse:collapse"><tbody>${rows}</tbody></table>
    </div>`;
  }

  if (obj.items && Array.isArray(obj.items) && obj.items.length > 0) {
    out += renderArraySection('Items', obj.items, color);
  }

  return out;
}

function renderArraySection(title, arr, color) {
  if (!arr.length) return '';
  const cols = Object.keys(arr[0]).filter(k => !SKIP_FIELDS.includes(k));
  const head = cols.map(c => `<th style="padding:6px 10px;font-size:.75rem;white-space:nowrap;border-bottom:1px solid var(--color-border);text-align:left">${escHtml(c)}</th>`).join('');
  const body = arr.map(row => {
    const cells = cols.map(c => `<td style="padding:6px 10px;font-size:.78rem;word-break:break-word">${formatVal(row[c])}</td>`).join('');
    return `<tr>${cells}</tr>`;
  }).join('');

  return `<div style="margin-bottom:4px;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:${color}">
    ${escHtml(title)} (${arr.length})
  </div>
  <div style="border:1px solid var(--color-border);border-radius:6px;overflow:hidden;margin-bottom:12px">
    <table style="width:100%;border-collapse:collapse">
      <thead style="background:var(--color-bg)"><tr>${head}</tr></thead>
      <tbody>${body}</tbody>
    </table>
  </div>`;
}

function formatVal(v) {
  if (v === null || v === undefined || v === '') return '<span class="text-muted">--</span>';
  if (typeof v === 'object') return `<pre style="margin:0;font-size:.72rem;white-space:pre-wrap">${escHtml(JSON.stringify(v, null, 2))}</pre>`;
  return escHtml(String(v));
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function closeAuditDetail() {
  document.getElementById('auditDetailModal').classList.remove('show');
}

document.getElementById('auditDetailModal').addEventListener('click', function(e) {
  if (e.target === this) closeAuditDetail();
});
</script>

<?php
$content = ob_get_clean();
require VIEW_PATH . '/layouts/main.php';
