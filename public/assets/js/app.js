// Toast notifications
function showToast(message, type = 'info') {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<span>${message}</span><button class="toast-close" onclick="this.parentElement.remove()">×</button>`;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 5000);
}

window.APP_BASE_PATH = window.APP_BASE_PATH || (document.querySelector('meta[name="app-base-path"]')?.content || '');

if (!window.__appFetchPatched && window.fetch) {
  const _origFetch = window.fetch.bind(window);
  window.fetch = function(resource, init) {
    if (typeof resource === 'string' && resource.startsWith('/') && !resource.startsWith('//')) {
      resource = (window.APP_BASE_PATH || '') + resource;
    }
    return _origFetch(resource, init);
  };
  window.__appFetchPatched = true;
}

function appPath(path) {
  if (!path) return window.APP_BASE_PATH || '';
  if (!path.startsWith('/')) path = '/' + path;
  return (window.APP_BASE_PATH || '') + path;
}

// Modal helpers
function openModal(id) { document.getElementById(id)?.classList.add('show'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('show'); }

// Modal overlays do NOT close on backdrop click — only the X button closes them.

// Sidebar toggle
function applySidebarState(collapsed) {
  const sidebar = document.getElementById('sidebar');
  const topbar = document.getElementById('topbar');
  const mainContent = document.getElementById('mainContent');
  if (!sidebar || !topbar || !mainContent) return;

  sidebar.classList.toggle('collapsed', collapsed);
  topbar.classList.toggle('sidebar-collapsed', collapsed);
  mainContent.classList.toggle('sidebar-collapsed', collapsed);
}

function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  if (!sidebar) return;
  const collapsed = !sidebar.classList.contains('collapsed');
  applySidebarState(collapsed);
  try { localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0'); } catch (e) { /* ignore */ }
}

document.addEventListener('DOMContentLoaded', function() {
  try {
    const saved = localStorage.getItem('sidebarCollapsed') === '1';
    applySidebarState(saved);
  } catch (e) { /* ignore */ }
});

// Flash messages auto-dismiss
document.querySelectorAll('.alert').forEach(alert => {
  setTimeout(() => alert.remove(), 5000);
});

// Format currency
function formatCurrency(amount) {
  return '₱' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Confirm dialog helper
function confirmAction(message, callback) {
  if (confirm(message)) callback();
}

// Notifications panel
function openNotifications() {
  let modal = document.getElementById('notificationsModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'notificationsModal';
    modal.className = 'modal-overlay';
    modal.innerHTML = `
      <div class="modal-content" style="max-width:700px">
        <div class="modal-header">
          <h2 class="modal-title">Notifications</h2>
          <div style="display:flex;align-items:center;gap:8px">
            <button class="btn btn-secondary btn-sm" onclick="markAllNotificationsRead()" id="markAllReadBtn">Mark all as read</button>
            <button class="modal-close" onclick="closeNotifications()">✕</button>
          </div>
        </div>
        <div class="modal-body">
          <div id="notificationsList" class="stack"></div>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
  }
  modal.classList.add('show');
  loadNotifications();
}

function closeNotifications() {
  document.getElementById('notificationsModal')?.classList.remove('show');
}

async function loadNotifications() {
  const list = document.getElementById('notificationsList');
  if (!list) return;
  list.innerHTML = '<div class="text-muted">Loading...</div>';

  try {
    const res = await fetch('/api/v1/notifications?per_page=20');
    const data = await res.json();
    if (!data.success) {
      list.innerHTML = '<div class="text-muted">Unable to load notifications.</div>';
      return;
    }

    updateNotificationBadge(data.data?.unread_count ?? 0);
    const items = data.data?.notifications || [];
    if (!items.length) {
      list.innerHTML = '<div class="text-muted">No notifications.</div>';
      return;
    }

    list.innerHTML = items.map(n => {
      const title = escapeHtml(n.title || 'Notification');
      const message = escapeHtml(n.message || '');
      const date = n.created_at ? new Date(n.created_at).toLocaleString() : '';
      const actions = n.is_read ? '' : `<button class="btn btn-secondary btn-sm" onclick="markNotificationRead(${n.id})">Mark read</button>`;
      return `
        <div class="card" style="padding:12px;margin-bottom:10px">
          <div style="display:flex;justify-content:space-between;gap:12px">
            <div style="flex:1">
              <div style="font-weight:600">${title}</div>
              <div style="font-size:.85rem;color:var(--color-gray-700)">${message}</div>
              <div style="font-size:.75rem;color:var(--color-gray-500);margin-top:6px">${date}</div>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end">
              ${actions}
              <button class="btn btn-danger btn-sm" onclick="deleteNotification(${n.id})">Delete</button>
            </div>
          </div>
        </div>
      `;
    }).join('');
  } catch (e) {
    list.innerHTML = '<div class="text-muted">Network error.</div>';
  }
}

async function markNotificationRead(id) {
  try {
    const res = await fetch('/api/v1/notifications/' + id + '/read', {
      method: 'PUT',
      headers: { 'X-CSRF-Token': getCsrfToken() },
    });
    const data = await res.json();
    if (data.success) loadNotifications();
  } catch (e) {
    showToast('Failed to mark as read', 'error');
  }
}

async function deleteNotification(id) {
  try {
    const res = await fetch('/api/v1/notifications/' + id, {
      method: 'DELETE',
      headers: { 'X-CSRF-Token': getCsrfToken() },
    });
    const data = await res.json();
    if (data.success) loadNotifications();
  } catch (e) {
    showToast('Failed to delete notification', 'error');
  }
}

async function markAllNotificationsRead() {
  try {
    const res = await fetch('/api/v1/notifications/read-all', {
      method: 'PUT',
      headers: { 'X-CSRF-Token': getCsrfToken() },
    });
    const data = await res.json();
    if (data.success) loadNotifications();
    else showToast('Failed to mark all as read', 'error');
  } catch (e) {
    showToast('Failed to mark all as read', 'error');
  }
}

function updateNotificationBadge(count) {
  const btn = document.querySelector('.notification-btn');
  if (!btn) return;
  let badge = btn.querySelector('.badge');
  if (count > 0) {
    if (!badge) {
      badge = document.createElement('span');
      badge.className = 'badge';
      btn.appendChild(badge);
    }
    badge.textContent = count > 99 ? '99+' : String(count);
  } else if (badge) {
    badge.remove();
  }
}

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// User dropdown (click-based)
function toggleUserDropdown() {
  const dropdown = document.getElementById('userDropdown');
  if (dropdown) dropdown.classList.toggle('show');
}

document.addEventListener('click', function(e) {
  const wrapper = document.getElementById('userMenuWrapper');
  const dropdown = document.getElementById('userDropdown');
  if (dropdown && wrapper && !wrapper.contains(e.target)) {
    dropdown.classList.remove('show');
  }
});

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/**
 * Renders invoice HTML matching the /print page layout.
 * @param {object} inv  - Invoice data from API
 * @param {object} biz  - Business settings: {name, phone, email, address}
 * @returns {string} HTML string
 */
function renderInvoiceHtml(inv, biz) {
  biz = biz || {};
  const methodLabel = {cash: 'Cash', bdo: 'Bank Transfer', gcash: 'GCash'};
  const statusCls   = {fully_paid: 'badge-success', partially_paid: 'badge-warning', unpaid: 'badge-danger'};
  const balance  = parseFloat(inv.total_amount||0) - parseFloat(inv.total_paid||0);
  const discount = parseFloat(inv.discount_amount||0);
  const tax      = parseFloat(inv.tax_amount||0);
  const fee      = parseFloat(inv.additional_fee||0);
  const subtotal = parseFloat(inv.total_amount||0) + discount - tax - fee;

  function e(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }
  function f(n) { return parseFloat(n||0).toFixed(2); }

  let itemRows = '', totalQty = 0;
  (inv.items||[]).forEach(function(it) {
    const qty = parseInt(it.quantity||0), up = parseFloat(it.unit_price||0);
    const disc = parseFloat(it.discount||0), net = up * qty - disc;
    totalQty += qty;
    itemRows += '<tr>'
      + '<td style="padding:9px 12px;border-bottom:1px solid #e5e7eb"><strong>' + e(it.product_name||'') + '</strong>'
        + (it.variation_name ? ' <span style="color:#6b7280;font-size:.8rem">(' + e(it.variation_name) + ')</span>' : '')
        + (it.sku ? '<div style="font-size:.75rem;color:#9ca3af">' + e(it.sku) + '</div>' : '') + '</td>'
      + '<td style="padding:9px 12px;text-align:center;border-bottom:1px solid #e5e7eb">' + qty + '</td>'
      + '<td style="padding:9px 12px;text-align:right;border-bottom:1px solid #e5e7eb">' + f(up) + (disc > 0 ? '<div style="font-size:.75rem;color:#dc2626">-' + f(disc) + '</div>' : '') + '</td>'
      + '<td style="padding:9px 12px;text-align:right;border-bottom:1px solid #e5e7eb"><strong>' + f(net) + '</strong></td>'
      + '</tr>';
  });

  let payRows = '';
  (inv.payments||[]).forEach(function(p, i) {
    payRows += '<tr>'
      + '<td style="padding:9px 12px;border-bottom:1px solid #e5e7eb">' + (i+1) + '</td>'
      + '<td style="padding:9px 12px;border-bottom:1px solid #e5e7eb">' + (p.payment_date ? new Date(p.payment_date).toLocaleDateString('en-PH', {month:'short',day:'numeric',year:'numeric'}) : '—') + '</td>'
      + '<td style="padding:9px 12px;border-bottom:1px solid #e5e7eb">' + e(methodLabel[p.payment_mode]||p.payment_mode||'—') + '</td>'
      + '<td style="padding:9px 12px;text-align:right;border-bottom:1px solid #e5e7eb">&#8369;' + f(p.payment_amount) + '</td>'
      + '</tr>';
  });

  const invDate     = inv.invoice_date ? new Date(inv.invoice_date + 'T00:00:00').toLocaleDateString('en-PH', {month:'long', day:'numeric', year:'numeric'}) : '—';
  const statusLabel = (inv.payment_status||'').replace(/_/g,' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
  const thStyle     = 'padding:9px 12px;background:#f9fafb;font-weight:600;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;color:#374151;border-bottom:2px solid #e5e7eb';

  return '<div style="font-family:\'Segoe UI\',Arial,sans-serif;color:#1a1a1a;font-size:14px">'

    /* Header */
    + '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px">'
      + '<div><div style="font-size:1.6rem;font-weight:900;letter-spacing:1px;color:#111">DREAM BLANKS</div>'
      + '<div style="font-size:.8rem;color:#6b7280;margin-top:2px">Customized Apparel &amp; Merchandise</div></div>'
      + '<div style="text-align:right;font-size:.8rem;color:#374151;line-height:1.6">'
        + (biz.name    ? '<div style="font-weight:700;font-size:.9rem">' + e(biz.name) + '</div>' : '')
        + (biz.phone   ? '<div>' + e(biz.phone) + '</div>' : '')
        + (biz.email   ? '<div>' + e(biz.email) + '</div>' : '')
        + (biz.address ? '<div>' + e(biz.address) + '</div>' : '')
      + '</div>'
    + '</div>'

    /* INVOICE rule */
    + '<div style="text-align:center;border-top:2.5px solid #111;border-bottom:2.5px solid #111;padding:9px 0;margin-bottom:22px">'
      + '<span style="font-size:1.3rem;font-weight:900;letter-spacing:3px">INVOICE</span>'
    + '</div>'

    /* Bill To + Details */
    + '<div style="display:flex;justify-content:space-between;margin-bottom:22px">'
      + '<div>'
        + '<div style="font-size:.7rem;font-weight:800;letter-spacing:.08em;color:#6b7280;margin-bottom:5px">BILL TO</div>'
        + (inv.client_name ? '<div style="font-weight:700;font-size:.95rem">' + e(inv.client_name) + '</div>' : '<div style="color:#9ca3af;font-style:italic">Walk-in Customer</div>')
        + (inv.client_email ? '<div style="font-size:.8rem;color:#374151;margin-top:2px">' + e(inv.client_email) + '</div>' : '')
        + (inv.client_phone ? '<div style="font-size:.8rem;color:#374151">' + e(inv.client_phone) + '</div>' : '')
        + (inv.client_address ? '<div style="font-size:.8rem;color:#374151;margin-top:2px">' + e(inv.client_address) + '</div>' : '')
      + '</div>'
      + '<div style="text-align:right">'
        + '<div style="margin-bottom:6px"><span style="font-size:.7rem;font-weight:800;letter-spacing:.08em;color:#6b7280">INVOICE #</span><br><strong style="font-size:.95rem">' + e(inv.invoice_number) + '</strong></div>'
        + '<div style="margin-bottom:6px"><span style="font-size:.7rem;font-weight:800;letter-spacing:.08em;color:#6b7280">DATE</span><br><strong>' + invDate + '</strong></div>'
        + '<span class="badge ' + (statusCls[inv.payment_status]||'badge-secondary') + '">' + statusLabel + '</span>'
      + '</div>'
    + '</div>'

    /* Divider */
    + '<div style="border-top:1px solid #e5e7eb;margin-bottom:18px"></div>'

    /* Items table */
    + '<table style="width:100%;border-collapse:collapse;margin-bottom:20px">'
      + '<thead><tr>'
        + '<th style="' + thStyle + ';text-align:left">Description</th>'
        + '<th style="' + thStyle + ';text-align:center;width:60px">QTY</th>'
        + '<th style="' + thStyle + ';text-align:right;width:110px">Unit Price</th>'
        + '<th style="' + thStyle + ';text-align:right;width:110px">Total</th>'
      + '</tr></thead>'
      + '<tbody>' + itemRows + '</tbody>'
      + '<tfoot><tr style="background:#f9fafb">'
        + '<td style="padding:9px 12px;font-weight:700;border-top:1px solid #e5e7eb">Total QTY</td>'
        + '<td style="padding:9px 12px;text-align:center;font-weight:800;font-size:.95rem;border-top:1px solid #e5e7eb">' + totalQty + '</td>'
        + '<td colspan="2" style="border-top:1px solid #e5e7eb"></td>'
      + '</tr></tfoot>'
    + '</table>'

    /* Totals */
    + '<div style="display:flex;justify-content:flex-end;margin-bottom:24px">'
      + '<table style="width:260px;border-collapse:collapse">'
        + '<tr><td style="padding:5px 12px;color:#374151;border:none">Subtotal</td><td style="padding:5px 12px;text-align:right;border:none">&#8369;' + f(subtotal) + '</td></tr>'
        + (discount > 0 ? '<tr><td style="padding:5px 12px;color:#374151;border:none">Discount</td><td style="padding:5px 12px;text-align:right;color:#dc2626;border:none">-&#8369;' + f(discount) + '</td></tr>' : '')
        + (tax > 0 ? '<tr><td style="padding:5px 12px;color:#374151;border:none">Tax</td><td style="padding:5px 12px;text-align:right;border:none">&#8369;' + f(tax) + '</td></tr>' : '')
        + (fee > 0 ? '<tr><td style="padding:5px 12px;color:#374151;border:none">Additional Fee</td><td style="padding:5px 12px;text-align:right;border:none">&#8369;' + f(fee) + '</td></tr>' : '')
        + '<tr style="border-top:2.5px solid #111"><td style="padding:10px 12px;font-weight:900;font-size:1rem;border:none">TOTAL</td><td style="padding:10px 12px;text-align:right;font-weight:900;font-size:1rem;border:none">&#8369;' + f(inv.total_amount) + '</td></tr>'
        + '<tr><td style="padding:5px 12px;font-weight:700;color:#166534;border:none">TOTAL PAID</td><td style="padding:5px 12px;text-align:right;font-weight:700;color:#166534;border:none">&#8369;' + f(inv.total_paid) + '</td></tr>'
        + (balance > 0 ? '<tr><td style="padding:5px 12px;font-weight:700;color:#dc2626;border:none">BALANCE DUE</td><td style="padding:5px 12px;text-align:right;font-weight:700;color:#dc2626;border:none">&#8369;' + f(balance) + '</td></tr>' : '')
      + '</table>'
    + '</div>'

    /* Payment History */
    + (payRows ? '<div style="margin-bottom:24px">'
        + '<div style="font-weight:700;margin-bottom:8px;font-size:.8rem;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">Payment History</div>'
        + '<table style="width:100%;border-collapse:collapse">'
          + '<thead><tr>'
            + '<th style="' + thStyle + ';text-align:left">#</th>'
            + '<th style="' + thStyle + ';text-align:left">Date</th>'
            + '<th style="' + thStyle + ';text-align:left">Mode</th>'
            + '<th style="' + thStyle + ';text-align:right">Amount</th>'
          + '</tr></thead>'
          + '<tbody>' + payRows + '</tbody>'
        + '</table>'
      + '</div>' : '')

    /* Notes */
    + (inv.notes ? '<div style="background:#f9fafb;padding:12px 16px;border-radius:6px;margin-bottom:24px;font-size:.875rem"><strong>Notes:</strong> ' + e(inv.notes) + '</div>' : '')

    /* Footer */
    + '<div style="border-top:1px solid #e5e7eb;padding-top:16px;display:flex;justify-content:space-between;font-size:.8rem;color:#374151;margin-top:8px">'
      + '<div>Sales Staff: <strong>' + e(inv.created_by_name||'—') + '</strong></div>'
      + '<div style="text-align:center">Authorized Signature: _______________</div>'
      + '<div style="font-style:italic;color:#6b7280">Thank you for your Business!</div>'
    + '</div>'

  + '</div>';
}
