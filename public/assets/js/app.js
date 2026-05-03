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

const originalFetch = window.fetch ? window.fetch.bind(window) : null;
if (originalFetch && !window.__appFetchPatched) {
  window.fetch = function(resource, init) {
    if (typeof resource === 'string' && resource.startsWith('/') && !resource.startsWith('//')) {
      resource = (window.APP_BASE_PATH || '') + resource;
    }
    return originalFetch(resource, init);
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

// Close modal on overlay click
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('show');
  }
});

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
      <div class="modal-content" style="max-width:520px">
        <div class="modal-header">
          <h2 class="modal-title">Notifications</h2>
          <button class="modal-close" onclick="closeNotifications()">✕</button>
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
