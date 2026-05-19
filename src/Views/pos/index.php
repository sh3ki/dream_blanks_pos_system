<?php ob_start(); ?>
<style>
.pos-layout { display:grid; grid-template-columns:1fr 420px; gap:16px; height:calc(100vh - var(--header-height) - 48px); }
.pos-left { display:flex; flex-direction:column; overflow:hidden; }
.pos-right { display:flex; flex-direction:column; background:white; border:1px solid var(--color-gray-100); border-radius:var(--border-radius); overflow:hidden; }
.cart-item { display:flex; align-items:center; gap:8px; padding:8px 12px; border-bottom:1px solid var(--color-gray-50); }
.cart-item-img { width:40px; height:40px; object-fit:cover; border-radius:6px; border:1px solid var(--color-gray-100); flex-shrink:0; }
.cart-item-info { flex:1; min-width:0; }
.cart-item-name { font-size:.85rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cart-item-price { font-size:.75rem; color:var(--color-gray-500); }
.cart-item-qty { display:flex; align-items:center; gap:4px; }
.qty-btn { width:26px; height:26px; border:1px solid var(--color-gray-200); background:#fff; border-radius:4px; cursor:pointer; font-size:1rem; display:flex; align-items:center; justify-content:center; }
.qty-btn:hover { background:var(--color-gray-50); }
.qty-input { width:36px; height:26px; text-align:center; border:1px solid var(--color-gray-200); border-radius:4px; font-size:.8rem; }
.cart-item-total { font-size:.85rem; font-weight:700; min-width:60px; text-align:right; }
.cart-remove { background:none; border:none; cursor:pointer; color:var(--color-gray-400); padding:2px; }
.cart-remove:hover { color:var(--color-danger); }

/* POS filter tabs */
.pos-filter-wrap { background:white; border:1px solid var(--color-gray-100); border-radius:var(--border-radius); padding:10px 12px; margin-bottom:12px; display:flex; flex-direction:column; gap:6px; }
.pos-tab-row { display:flex; align-items:center; gap:8px; }
.pos-tab-label { font-size:.7rem; font-weight:700; text-transform:uppercase; color:var(--color-gray-400); white-space:nowrap; width:62px; flex-shrink:0; }
.pos-tab-scroll { display:flex; gap:4px; overflow-x:auto; scrollbar-width:none; flex:1; padding-bottom:2px; }
.pos-tab-scroll::-webkit-scrollbar { display:none; }
.pos-tab { border:1px solid var(--color-gray-200); background:transparent; border-radius:20px; padding:3px 11px; font-size:.78rem; cursor:pointer; white-space:nowrap; color:var(--color-gray-600); transition:all .15s; line-height:1.6; }
.pos-tab:hover { border-color:var(--color-primary); color:var(--color-primary); background:#f0f4ff; }
.pos-tab.active { background:var(--color-primary); border-color:var(--color-primary); color:#fff; font-weight:600; }
</style>

<div class="pos-layout">
  <!-- Left: Products -->
  <div class="pos-left">
    <!-- Search bar -->
    <div class="card" style="margin-bottom:8px;padding:10px 12px">
      <div class="d-flex gap-8 align-center">
        <div class="search-bar" style="flex:1">
          <?= icon('search', 16) ?> <input type="text" id="posSearch" placeholder="Search products..." oninput="debouncedSearch()">
        </div>
        <button class="btn btn-secondary btn-sm" onclick="resetFilters()" style="height:36px;white-space:nowrap">Reset</button>
      </div>
    </div>

    <!-- Filter tabs -->
    <div class="pos-filter-wrap">
      <div class="pos-tab-row">
        <span class="pos-tab-label">Category</span>
        <div class="pos-tab-scroll">
          <button class="pos-tab active" data-filter="category" data-value="" onclick="setFilter('category','')">All</button>
          <?php foreach ($categories as $cat): ?>
            <button class="pos-tab" data-filter="category" data-value="<?= $cat['id'] ?>" onclick="setFilter('category','<?= $cat['id'] ?>')"><?= htmlspecialchars($cat['name']) ?></button>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="pos-tab-row">
        <span class="pos-tab-label">Type</span>
        <div class="pos-tab-scroll">
          <button class="pos-tab active" data-filter="type" data-value="" onclick="setFilter('type','')">All</button>
          <?php foreach ($types as $t): ?>
            <button class="pos-tab" data-filter="type" data-value="<?= $t['id'] ?>" onclick="setFilter('type','<?= $t['id'] ?>')"><?= htmlspecialchars($t['name']) ?></button>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="pos-tab-row">
        <span class="pos-tab-label">Color</span>
        <div class="pos-tab-scroll">
          <button class="pos-tab active" data-filter="color" data-value="" onclick="setFilter('color','')">All</button>
          <?php foreach ($colors as $c): ?>
            <button class="pos-tab" data-filter="color" data-value="<?= $c['id'] ?>" onclick="setFilter('color','<?= $c['id'] ?>')"><?= htmlspecialchars($c['name']) ?></button>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="pos-tab-row">
        <span class="pos-tab-label">Size</span>
        <div class="pos-tab-scroll">
          <button class="pos-tab active" data-filter="size" data-value="" onclick="setFilter('size','')">All</button>
          <?php foreach ($sizes as $s): ?>
            <button class="pos-tab" data-filter="size" data-value="<?= $s['id'] ?>" onclick="setFilter('size','<?= $s['id'] ?>')"><?= htmlspecialchars($s['name']) ?></button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Product Grid -->
    <div style="flex:1;overflow-y:auto">
      <div class="product-grid" id="productGrid">
        <div class="text-center text-muted p-24">Loading products...</div>
      </div>
    </div>
  </div>

  <!-- Right: Cart -->
  <div class="pos-right">
    <!-- Cart Header: title + clear + customer -->
    <div class="cart-header" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border-bottom:1px solid var(--color-gray-100);flex-wrap:wrap">
      <span style="display:flex;align-items:center;gap:5px;font-weight:600;font-size:.9rem;flex:0 0 auto"><?= icon('pos', 16) ?> Cart (<span id="cartCount">0</span>)</span>
      <button class="btn btn-secondary btn-sm" onclick="clearCart()" style="flex:0 0 auto"><?= icon('delete', 13) ?> Clear</button>
      <select id="clientSelect" class="form-select" style="flex:1;min-width:120px;height:32px;font-size:.8rem">
        <option value="">Walk-in Customer</option>
      </select>
    </div>

    <!-- Cart Items -->
    <div class="cart-items" id="cartItems" style="flex:1;overflow-y:auto">
      <div class="empty-state" id="emptyCart" style="padding:40px 0;text-align:center">
        <div style="color:var(--color-gray-300);margin-bottom:8px"><?= icon('pos', 40) ?></div>
        <p style="color:var(--color-gray-400);margin:0">Cart is empty</p>
        <p style="font-size:.8rem;color:var(--color-gray-300);margin:4px 0 0">Click products to add</p>
      </div>
    </div>

    <!-- Cart Summary -->
    <div class="cart-summary" style="padding:12px;border-top:1px solid var(--color-gray-100)">
      <div class="summary-row"><span>Subtotal</span><span id="subtotal">₱0.00</span></div>
      <div class="summary-row" id="discountRow" style="display:none"><span>Discount</span><span id="discountDisplay" style="color:var(--color-success)">-₱0.00</span></div>
      <div class="summary-row" id="taxRow" style="display:none"><span>Tax</span><span id="taxDisplay">+₱0.00</span></div>
      <div class="summary-row" id="feeRow" style="display:none"><span>Extra Fee</span><span id="feeDisplay">+₱0.00</span></div>
      <div class="summary-row total"><span>TOTAL</span><span id="totalAmount">₱0.00</span></div>

      <button class="btn btn-secondary btn-sm" onclick="openAdjustmentsModal()" style="width:100%;margin:8px 0;height:32px;font-size:.82rem">
        <?= icon('settings', 13) ?> Discount / Tax / Fee / Notes
      </button>

      <!-- Payment: full-width method + cash received -->
      <div style="margin-bottom:10px">
        <label style="font-size:.72rem;color:var(--color-gray-500);text-transform:uppercase;font-weight:600;display:block;margin-bottom:4px">Payment Method</label>
        <select id="paymentMode" class="form-select" style="width:100%;height:36px;font-size:.82rem" onchange="onPaymentModeChange()">
          <option value="cash">💵 Cash</option>
          <option value="bdo">🏦 BDO</option>
          <option value="gcash">📱 GCash</option>
        </select>
      </div>
      <div id="cashReceivedRow" style="margin-bottom:10px">
        <label style="font-size:.72rem;color:var(--color-gray-500);text-transform:uppercase;font-weight:600;display:block;margin-bottom:4px">Cash Received (₱)</label>
        <input type="number" id="cashReceived" class="form-input" placeholder="0.00" min="0" step="0.01" oninput="calcPaymentStatus()" style="height:36px;font-size:.82rem">
      </div>

      <button class="btn btn-primary btn-block" id="checkoutBtn" onclick="openCheckoutConfirm()" style="height:44px;font-size:1rem">
        ✅ Checkout
      </button>
    </div>
  </div>
</div>

<!-- Adjustments Modal -->
<div class="modal-overlay" id="adjustmentsModal">
  <div class="modal-content" style="max-width:400px">
    <div class="modal-header">
      <h2 class="modal-title"><?= icon('settings', 16) ?> Order Adjustments</h2>
      <button class="modal-close" onclick="closeModal('adjustmentsModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Discount (₱)</label>
        <input type="number" id="discountAmount" class="form-input" placeholder="0.00" min="0" step="0.01" value="0" oninput="recalculate()">
      </div>
      <div class="form-group">
        <label class="form-label">Tax (₱)</label>
        <input type="number" id="taxAmount" class="form-input" placeholder="0.00" min="0" step="0.01" value="0" oninput="recalculate()">
      </div>
      <div class="form-group">
        <label class="form-label">Additional Fee (₱)</label>
        <input type="number" id="additionalFee" class="form-input" placeholder="0.00" min="0" step="0.01" value="0" oninput="recalculate()">
      </div>
      <div class="form-group">
        <label class="form-label">Order Notes</label>
        <textarea id="orderNotes" class="form-textarea" placeholder="Special instructions, remarks..." style="min-height:80px"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('adjustmentsModal')">Cancel</button>
      <button class="btn btn-primary" onclick="applyAdjustments()">Apply</button>
    </div>
  </div>
</div>

<!-- Checkout Confirmation Modal -->
<div class="modal-overlay" id="checkoutConfirmModal">
  <div class="modal-content" style="max-width:480px">
    <div class="modal-header">
      <h2 class="modal-title">Confirm Checkout</h2>
      <button class="modal-close" onclick="closeModal('checkoutConfirmModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body" id="checkoutConfirmBody"></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('checkoutConfirmModal')">Cancel</button>
      <button class="btn btn-primary" id="confirmCheckoutBtn" onclick="checkout()">✅ Confirm & Checkout</button>
    </div>
  </div>
</div>

<!-- Receipt Modal -->
<div class="modal-overlay" id="receiptModal">
  <div class="modal-content" style="max-width:420px">
    <div class="modal-header"><h2 class="modal-title">✅ Sale Complete!</h2></div>
    <div class="modal-body" id="receiptBody"></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeReceipt()">New Sale</button>
      <a id="printReceiptBtn" href="#" target="_blank" class="btn btn-primary">🖨 Print Receipt</a>
    </div>
  </div>
</div>

<!-- POS Invoice Preview Modal -->
<div class="modal-overlay" id="posInvoiceModal">
  <div class="modal-content" style="max-width:780px;max-height:90vh;overflow-y:auto">
    <div class="modal-header">
      <h2 class="modal-title">✅ Sale Complete — Invoice</h2>
      <button class="modal-close" onclick="closePosInvoiceModal()"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body" id="posInvoiceBody" style="padding:24px"></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closePosInvoiceModal()">New Sale</button>
      <a id="posInvoicePrintBtn" href="#" target="_blank" class="btn btn-outline">🖨 Print</a>
      <a id="posInvoiceDownloadBtn" href="#" target="_blank" class="btn btn-primary">↓ Download PDF</a>
    </div>
  </div>
</div>

<script>
let cart = [];
let products = [];
const noImg = appPath('/assets/images/no-image.png');
let searchTimer;

loadProducts();
loadClients();

function debouncedSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(applyFilters, 350);
}

const activeFilters = { category: '', type: '', color: '', size: '' };

function setFilter(name, value) {
  activeFilters[name] = value;
  document.querySelectorAll(`.pos-tab[data-filter="${name}"]`).forEach(btn => {
    btn.classList.toggle('active', btn.dataset.value === String(value));
  });
  applyFilters();
}

function applyFilters() {
  const search = document.getElementById('posSearch').value.trim();
  loadProducts(search, activeFilters.category, activeFilters.type, activeFilters.color, activeFilters.size);
}

function resetFilters() {
  document.getElementById('posSearch').value = '';
  ['category','type','color','size'].forEach(k => {
    activeFilters[k] = '';
    document.querySelectorAll(`.pos-tab[data-filter="${k}"]`).forEach(btn => {
      btn.classList.toggle('active', btn.dataset.value === '');
    });
  });
  loadProducts();
}

function loadProducts(search = '', categoryId = '', typeId = '', colorId = '', sizeId = '') {
  let url = '/api/v1/pos/products?limit=200';
  if (search)     url += '&search='      + encodeURIComponent(search);
  if (categoryId) url += '&category_id=' + categoryId;
  if (typeId)     url += '&type_id='     + typeId;
  if (colorId)    url += '&color_id='    + colorId;
  if (sizeId)     url += '&size_id='     + sizeId;

  document.getElementById('productGrid').innerHTML = '<div class="text-center text-muted p-24">Loading...</div>';
  fetch(url).then(r => r.json()).then(res => {
    if (res.success) { products = res.data.products; renderProducts(products); }
  }).catch(() => { document.getElementById('productGrid').innerHTML = '<div class="text-center text-muted p-24">Error loading products</div>'; });
}

function renderProducts(list) {
  const grid = document.getElementById('productGrid');
  if (!list.length) {
    grid.innerHTML = '<div class="empty-state" style="padding:60px 0;text-align:center"><div style="color:var(--color-gray-300);margin-bottom:8px"><?= icon('products', 40) ?></div><p style="color:var(--color-gray-400)">No products found</p></div>';
    return;
  }
  grid.innerHTML = list.map(p => `
    <div class="product-card ${p.current_stock <= 0 ? 'out-of-stock' : ''}" onclick="${p.current_stock > 0 ? 'addToCart(' + p.id + ')' : ''}" style="position:relative;padding-top:34px">
      <div style="position:absolute;top:8px;left:8px;background:rgba(0,0,0,0.72);color:white;padding:3px 7px;border-radius:4px;font-size:.65rem;font-weight:700;z-index:2;pointer-events:none">${p.sku}</div>
      <div style="padding:0 8px 8px">
        <img src="${p.image_path ? appPath(p.image_path) : noImg}" alt="${p.name}" onerror="this.src='${noImg}'" style="width:100%;aspect-ratio:1/1;height:auto;object-fit:cover;border-radius:10px;display:block">
      </div>
      <div class="product-name">${p.name}</div>
      ${[p.category_code, p.type_code, p.color_name, p.size_code].filter(Boolean).length ? `<div class="product-stock">${[p.category_code, p.type_code, p.color_name, p.size_code].filter(Boolean).join(' | ')}</div>` : ''}
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
        <div class="product-price">₱${parseFloat(p.selling_price).toFixed(2)}</div>
        <div style="font-weight:600;font-size:.85rem;${p.current_stock <= 0 ? 'color:var(--color-danger)' : ''}">${p.current_stock}</div>
      </div>
    </div>
  `).join('');
}

function addToCart(productId) {
  const product = products.find(p => p.id == productId);
  if (!product) return;
  const existing = cart.find(i => i.product_id == productId);
  if (existing) {
    if (existing.quantity >= product.current_stock) { showToast('Not enough stock!', 'error'); return; }
    existing.quantity++;
  } else {
    cart.push({
      product_id: product.id,
      name: product.name,
      image_path: product.image_path || '',
      unit_price: parseFloat(product.selling_price),
      quantity: 1,
      max_stock: product.current_stock,
    });
  }
  renderCart();
}

function renderCart() {
  document.getElementById('cartCount').textContent = cart.reduce((s, i) => s + i.quantity, 0);
  const container = document.getElementById('cartItems');
  if (!cart.length) {
    container.innerHTML = `<div class="empty-state" style="padding:40px 0;text-align:center"><div style="color:var(--color-gray-300);margin-bottom:8px"><?= icon('pos', 36) ?></div><p style="color:var(--color-gray-400);margin:0">Cart is empty</p><p style="font-size:.8rem;color:var(--color-gray-300);margin:4px 0 0">Click products to add</p></div>`;
    recalculate(); return;
  }
  container.innerHTML = cart.map((item, idx) => `
    <div class="cart-item">
      <img class="cart-item-img" src="${item.image_path ? appPath(item.image_path) : noImg}" onerror="this.src='${noImg}'" alt="">
      <div class="cart-item-info">
        <div class="cart-item-name">${item.name}</div>
        <div class="cart-item-price">₱${item.unit_price.toFixed(2)} each</div>
      </div>
      <div class="cart-item-qty">
        <button class="qty-btn" onclick="changeQty(${idx}, -1)">−</button>
        <input type="number" class="qty-input" value="${item.quantity}" min="1" max="${item.max_stock}" onchange="setQty(${idx}, this.value)">
        <button class="qty-btn" onclick="changeQty(${idx}, 1)">+</button>
      </div>
      <div class="cart-item-total">₱${(item.unit_price * item.quantity).toFixed(2)}</div>
      <button class="cart-remove" onclick="removeItem(${idx})" title="Remove"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    </div>
  `).join('');
  recalculate();
}

function changeQty(idx, delta) { cart[idx].quantity = Math.max(1, Math.min(cart[idx].max_stock, cart[idx].quantity + delta)); renderCart(); }
function setQty(idx, val) { cart[idx].quantity = Math.max(1, Math.min(cart[idx].max_stock, parseInt(val) || 1)); renderCart(); }
function removeItem(idx) { cart.splice(idx, 1); renderCart(); }
function clearCart() { cart = []; renderCart(); }

function recalculate() {
  const subtotal = cart.reduce((s, i) => s + i.unit_price * i.quantity, 0);
  const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
  const tax      = parseFloat(document.getElementById('taxAmount').value) || 0;
  const fee      = parseFloat(document.getElementById('additionalFee').value) || 0;
  const total    = Math.max(0, subtotal - discount + tax + fee);

  document.getElementById('subtotal').textContent    = '₱' + subtotal.toFixed(2);
  document.getElementById('discountDisplay').textContent = '-₱' + discount.toFixed(2);
  document.getElementById('taxDisplay').textContent      = '+₱' + tax.toFixed(2);
  document.getElementById('feeDisplay').textContent      = '+₱' + fee.toFixed(2);
  document.getElementById('totalAmount').textContent     = '₱' + total.toFixed(2);
  document.getElementById('discountRow').style.display = discount > 0 ? '' : 'none';
  document.getElementById('taxRow').style.display      = tax      > 0 ? '' : 'none';
  document.getElementById('feeRow').style.display      = fee      > 0 ? '' : 'none';
}

function openAdjustmentsModal() { openModal('adjustmentsModal'); }

function onPaymentModeChange() {
  // Only show cash received for cash payments
  const mode = document.getElementById('paymentMode').value;
  document.getElementById('cashReceivedRow').style.display = mode === 'cash' ? '' : 'none';
  calcPaymentStatus();
}

function calcPaymentStatus() {
  const totalEl = document.getElementById('totalAmount');
  const total = parseFloat(totalEl ? totalEl.textContent.replace(/[^0-9.]/g,'') : '0') || 0;
  const cashEl = document.getElementById('cashReceived');
  const cash   = parseFloat(cashEl ? cashEl.value : '0') || 0;
  const mode   = document.getElementById('paymentMode').value;
  if (mode !== 'cash') return 'fully_paid'; // non-cash assumed fully paid at POS
  if (cash <= 0) return 'unpaid';
  if (cash < total) return 'partially_paid';
  return 'fully_paid';
}

function applyAdjustments() {
  recalculate();
  closeModal('adjustmentsModal');
}

function openCheckoutConfirm() {
  if (!cart.length) { showToast('Cart is empty', 'error'); return; }
  const subtotal = cart.reduce((s, i) => s + i.unit_price * i.quantity, 0);
  const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
  const tax      = parseFloat(document.getElementById('taxAmount').value) || 0;
  const fee      = parseFloat(document.getElementById('additionalFee').value) || 0;
  const total    = Math.max(0, subtotal - discount + tax + fee);
  const notes    = document.getElementById('orderNotes').value;
  const payMode  = document.getElementById('paymentMode').options[document.getElementById('paymentMode').selectedIndex].text;
  const payStatus = calcPaymentStatus();
  const payStatusLabel = payStatus.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
  const clientText = document.getElementById('clientSelect').options[document.getElementById('clientSelect').selectedIndex].text;

  let rows = cart.map(i => `<tr><td style="padding:4px 8px">${i.name}</td><td style="padding:4px 8px;text-align:center">${i.quantity}</td><td style="padding:4px 8px;text-align:right">₱${(i.unit_price*i.quantity).toFixed(2)}</td></tr>`).join('');

  document.getElementById('checkoutConfirmBody').innerHTML = `
    <table style="width:100%;border-collapse:collapse;margin-bottom:12px;font-size:.875rem">
      <thead><tr style="border-bottom:2px solid var(--color-gray-100)"><th style="padding:4px 8px;text-align:left">Item</th><th style="padding:4px 8px;text-align:center">Qty</th><th style="padding:4px 8px;text-align:right">Total</th></tr></thead>
      <tbody>${rows}</tbody>
    </table>
    <div style="font-size:.85rem;border-top:1px solid var(--color-gray-100);padding-top:10px">
      <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>Subtotal</span><span>₱${subtotal.toFixed(2)}</span></div>
      ${discount > 0 ? `<div style="display:flex;justify-content:space-between;margin-bottom:4px;color:var(--color-success)"><span>Discount</span><span>-₱${discount.toFixed(2)}</span></div>` : ''}
      ${tax > 0      ? `<div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>Tax</span><span>+₱${tax.toFixed(2)}</span></div>` : ''}
      ${fee > 0      ? `<div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>Extra Fee</span><span>+₱${fee.toFixed(2)}</span></div>` : ''}
      <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1rem;margin-top:8px;padding-top:8px;border-top:2px solid var(--color-gray-100)"><span>TOTAL</span><span>₱${total.toFixed(2)}</span></div>
    </div>
    <div style="font-size:.82rem;color:var(--color-gray-500);margin-top:10px;display:grid;grid-template-columns:1fr 1fr;gap:6px">
      <div><strong>Customer:</strong> ${clientText}</div>
      <div><strong>Payment:</strong> ${payMode}</div>
      <div><strong>Status:</strong> ${payStatusLabel}</div>
      ${notes ? `<div style="grid-column:1/-1"><strong>Notes:</strong> ${notes}</div>` : ''}
    </div>`;
  openModal('checkoutConfirmModal');
}

async function checkout() {
  closeModal('checkoutConfirmModal');
  const btn = document.getElementById('checkoutBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Processing...';

  const subtotal = cart.reduce((s, i) => s + i.unit_price * i.quantity, 0);
  const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
  const tax      = parseFloat(document.getElementById('taxAmount').value) || 0;
  const fee      = parseFloat(document.getElementById('additionalFee').value) || 0;
  const total    = Math.max(0, subtotal - discount + tax + fee);

  const payload = {
    client_id:      document.getElementById('clientSelect').value || null,
    items:          cart.map(i => ({ product_id: i.product_id, quantity: i.quantity, unit_price: i.unit_price })),
    subtotal, discount_amount: discount, tax_amount: tax, additional_fee: fee, total_amount: total,
    payment_mode:   document.getElementById('paymentMode').value,
    payment_status: calcPaymentStatus(),
    cash_received:  parseFloat(document.getElementById('cashReceived').value) || 0,
    notes:          document.getElementById('orderNotes').value,
  };

  try {
    const res  = await fetch('/api/v1/pos/checkout', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) {
      cart = []; renderCart();
      document.getElementById('discountAmount').value = '0';
      document.getElementById('taxAmount').value = '0';
      document.getElementById('additionalFee').value = '0';
      document.getElementById('orderNotes').value = '';
      document.getElementById('cashReceived').value = '';
      recalculate();
      // Open invoice modal
      openPosInvoiceModal(data.data.invoice_id || data.data.id);
    } else {
      showToast(data.message || 'Checkout failed', 'error');
    }
  } catch (e) { showToast('Network error', 'error'); }
  btn.disabled = false; btn.innerHTML = '✅ Checkout';
}

function closeReceipt() { closeModal('receiptModal'); loadProducts(); }

function closePosInvoiceModal() { closeModal('posInvoiceModal'); loadProducts(); }

function posInvEsc(s) { const d=document.createElement('div');d.textContent=s||'';return d.innerHTML; }

async function openPosInvoiceModal(invoiceId) {
  document.getElementById('posInvoiceBody').innerHTML = '<div style="padding:40px;text-align:center"><span class="spinner"></span></div>';
  openModal('posInvoiceModal');
  const printUrl    = appPath('/api/v1/invoices/' + invoiceId + '/print');
  const downloadUrl = appPath('/api/v1/invoices/' + invoiceId + '/print') + '?download=1';
  document.getElementById('posInvoicePrintBtn').href    = printUrl;
  document.getElementById('posInvoiceDownloadBtn').href = downloadUrl;
  try {
    const res  = await fetch(appPath('/api/v1/invoices/' + invoiceId));
    const data = await res.json();
    if (!data.success) { document.getElementById('posInvoiceBody').innerHTML = '<p class="text-danger">Failed to load invoice.</p>'; return; }
    renderPosInvoice(data.data);
  } catch (e) { document.getElementById('posInvoiceBody').innerHTML = '<p class="text-danger">Network error</p>'; }
}

function renderPosInvoice(inv) {
  const methodLabel = {cash:'Cash', bdo:'Bank Transfer', gcash:'GCash'};
  const statusCls   = {fully_paid:'badge-success', partially_paid:'badge-warning', unpaid:'badge-danger'};
  const balance     = (parseFloat(inv.total_amount||0) - parseFloat(inv.total_paid||0));
  let itemRows = '', totalQty = 0;
  (inv.items||[]).forEach(it => {
    const qty = parseInt(it.quantity||0), up = parseFloat(it.unit_price||0);
    const disc = parseFloat(it.discount||0), net = up*qty - disc;
    totalQty += qty;
    itemRows += `<tr>
      <td style="padding:8px 12px">${posInvEsc(it.product_name||'')}${it.variation_name?` <span style="color:#6b7280;font-size:.8rem">(${posInvEsc(it.variation_name)})</span>`:''}${it.sku?`<div style="font-size:.75rem;color:#9ca3af">${posInvEsc(it.sku)}</div>`:''}</td>
      <td style="padding:8px 12px;text-align:center">${qty}</td>
      <td style="padding:8px 12px;text-align:right">₱${up.toFixed(2)}${disc>0?`<div style="font-size:.75rem;color:#e74c3c">-₱${disc.toFixed(2)}</div>`:''}</td>
      <td style="padding:8px 12px;text-align:right">₱${net.toFixed(2)}</td>
    </tr>`;
  });
  const discount = parseFloat(inv.discount_amount||0), tax = parseFloat(inv.tax_amount||0), fee = parseFloat(inv.additional_fee||0);
  const subtotal = parseFloat(inv.total_amount||0) + discount - tax - fee;
  let payRows = '';
  (inv.payments||[]).forEach((p,i) => {
    payRows += `<tr>
      <td style="padding:6px 10px">${i+1}</td>
      <td style="padding:6px 10px">${p.payment_date ? new Date(p.payment_date).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'}) : '—'}</td>
      <td style="padding:6px 10px">${posInvEsc(methodLabel[p.payment_mode]||p.payment_mode||'—')}</td>
      <td style="padding:6px 10px;text-align:right">₱${parseFloat(p.payment_amount||0).toFixed(2)}</td>
    </tr>`;
  });
  document.getElementById('posInvoiceBody').innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
      <div><div style="font-size:1.5rem;font-weight:900;letter-spacing:.5px">DREAM BLANKS</div><div style="font-size:.8rem;color:#6b7280">Customized Apparel &amp; Merchandise</div></div>
      <div style="text-align:right;font-size:.8rem;color:#374151"><div style="font-weight:700">Dream Blanks</div><div>Philippines</div></div>
    </div>
    <div style="text-align:center;border-top:2px solid #111;border-bottom:2px solid #111;padding:8px 0;margin-bottom:16px">
      <span style="font-size:1.25rem;font-weight:900;letter-spacing:2px">INVOICE</span>
    </div>
    <div style="display:flex;justify-content:space-between;margin-bottom:16px">
      <div>
        <div style="font-size:.7rem;font-weight:800;letter-spacing:.08em;color:#6b7280;margin-bottom:4px">BILL TO</div>
        <div style="font-weight:700">${posInvEsc(inv.client_name||'Walk-in Customer')}</div>
        ${inv.client_email?`<div style="font-size:.85rem">${posInvEsc(inv.client_email)}</div>`:''}
      </div>
      <div style="text-align:right">
        <div style="margin-bottom:4px"><span style="font-size:.7rem;color:#6b7280;font-weight:800">INVOICE #</span><br><strong>${posInvEsc(inv.invoice_number)}</strong></div>
        <div><span style="font-size:.7rem;color:#6b7280;font-weight:800">DATE</span><br><strong>${inv.invoice_date ? new Date(inv.invoice_date+'T00:00:00').toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'}) : '—'}</strong></div>
        <div style="margin-top:6px"><span class="badge ${statusCls[inv.payment_status]||'badge-secondary'}">${(inv.payment_status||'').replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</span></div>
      </div>
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;font-size:.875rem">
      <thead><tr style="background:#f9fafb;border-bottom:2px solid #e5e7eb">
        <th style="padding:8px 12px;text-align:left">Description</th><th style="padding:8px 12px;text-align:center">QTY</th>
        <th style="padding:8px 12px;text-align:right">Unit Price</th><th style="padding:8px 12px;text-align:right">Total</th>
      </tr></thead>
      <tbody style="border-bottom:1px solid #e5e7eb">${itemRows}</tbody>
      <tfoot><tr style="background:#f9fafb"><td style="padding:8px 12px;font-weight:700">Total QTY</td><td style="padding:8px 12px;text-align:center;font-weight:800">${totalQty}</td><td colspan="2"></td></tr></tfoot>
    </table>
    <div style="display:flex;justify-content:flex-end;margin-bottom:16px">
      <table style="min-width:250px;font-size:.875rem">
        <tr><td style="padding:4px 12px;color:#6b7280">Subtotal</td><td style="padding:4px 12px;text-align:right">₱${subtotal.toFixed(2)}</td></tr>
        ${discount>0?`<tr><td style="padding:4px 12px;color:#6b7280">Discount</td><td style="padding:4px 12px;text-align:right;color:#e74c3c">-₱${discount.toFixed(2)}</td></tr>`:''}
        ${tax>0?`<tr><td style="padding:4px 12px;color:#6b7280">Tax</td><td style="padding:4px 12px;text-align:right">₱${tax.toFixed(2)}</td></tr>`:''}
        ${fee>0?`<tr><td style="padding:4px 12px;color:#6b7280">Additional Fee</td><td style="padding:4px 12px;text-align:right">₱${fee.toFixed(2)}</td></tr>`:''}
        <tr style="border-top:2px solid #111;font-weight:900;font-size:1rem"><td style="padding:8px 12px">TOTAL</td><td style="padding:8px 12px;text-align:right">₱${parseFloat(inv.total_amount||0).toFixed(2)}</td></tr>
        <tr style="font-weight:700;color:#166534"><td style="padding:4px 12px">TOTAL PAID</td><td style="padding:4px 12px;text-align:right">₱${parseFloat(inv.total_paid||0).toFixed(2)}</td></tr>
        ${balance>0?`<tr style="font-weight:700;color:#dc2626"><td style="padding:4px 12px">BALANCE</td><td style="padding:4px 12px;text-align:right">₱${balance.toFixed(2)}</td></tr>`:''}
      </table>
    </div>
    ${payRows?`<div style="margin-bottom:16px"><div style="font-weight:700;margin-bottom:8px;font-size:.875rem">Payment History</div>
    <table style="width:100%;border-collapse:collapse;font-size:.8rem">
      <thead><tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb"><th style="padding:6px 10px;text-align:left">#</th><th style="padding:6px 10px;text-align:left">Date</th><th style="padding:6px 10px;text-align:left">Mode</th><th style="padding:6px 10px;text-align:right">Amount</th></tr></thead>
      <tbody>${payRows}</tbody></table></div>`:''}
    <div style="border-top:1px solid #e5e7eb;padding-top:16px;display:flex;justify-content:space-between;font-size:.8rem;color:#374151">
      <div>Sales Staff: <strong>${posInvEsc(inv.created_by_name||'—')}</strong></div>
      <div>Authorized Signature: _______________</div>
      <div style="font-style:italic;color:#6b7280">Thank you for your Business!</div>
    </div>`;
}

function loadClients() {
  fetch('/api/v1/clients?per_page=200').then(r => r.json()).then(res => {
    if (res.success) {
      const sel = document.getElementById('clientSelect');
      res.data.clients.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.full_name;
        sel.appendChild(opt);
      });
    }
  });
}
</script>
<?php
$content = ob_get_clean();
$title   = 'Point of Sale | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
