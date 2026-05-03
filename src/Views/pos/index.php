<?php ob_start(); ?>
<style>
.pos-layout { display:grid; grid-template-columns:1fr 400px; gap:16px; height:calc(100vh - var(--header-height) - 48px); }
.pos-left { display:flex; flex-direction:column; overflow:hidden; }
.pos-right { display:flex; flex-direction:column; background:white; border:1px solid var(--color-gray-100); border-radius:var(--border-radius); overflow:hidden; }
</style>

<div class="pos-layout">
  <!-- Left: Products -->
  <div class="pos-left">
    <!-- Search & Filter -->
    <div class="card" style="margin-bottom:12px;padding:12px">
      <div class="d-flex gap-12 align-center flex-wrap">
        <div class="search-bar" style="flex:1;min-width:200px">
          <?= icon('search', 16) ?> <input type="text" id="posSearch" placeholder="Search products..." oninput="searchProducts(this.value)">
        </div>
        <select id="categoryFilter" class="form-select" style="width:auto;height:38px" onchange="searchProducts()">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-secondary btn-sm" onclick="clearCart()"><?= icon('delete', 15) ?> Clear Cart</button>
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
    <div class="cart-header d-flex justify-between align-center">
      <span style="display:flex;align-items:center;gap:6px"><?= icon('pos', 18) ?> Cart (<span id="cartCount">0</span>)</span>
      <div>
        <select id="clientSelect" class="form-select" style="width:160px;height:32px;font-size:.8rem">
          <option value="">Walk-in Customer</option>
        </select>
      </div>
    </div>

    <div class="cart-items" id="cartItems">
      <div class="empty-state" id="emptyCart">
        <div class="empty-icon" style="color:var(--color-gray-300)"><?= icon('pos', 40) ?></div>
        <p>Cart is empty</p>
        <p style="font-size:.8rem">Click products to add</p>
      </div>
    </div>

    <div class="cart-summary">
      <!-- Order adjustments -->
      <div style="margin-bottom:12px">
        <div class="d-flex gap-8 mb-8">
          <input type="number" id="discountAmount" class="form-input" placeholder="Discount (₱)" min="0" style="height:34px;font-size:.8rem" oninput="recalculate()">
          <input type="number" id="taxAmount" class="form-input" placeholder="Tax (₱)" min="0" style="height:34px;font-size:.8rem" oninput="recalculate()">
        </div>
        <input type="number" id="additionalFee" class="form-input" placeholder="Additional fee (₱)" min="0" style="height:34px;font-size:.8rem;margin-bottom:8px" oninput="recalculate()">
        <textarea id="orderNotes" class="form-textarea" placeholder="Order notes..." style="min-height:60px;font-size:.8rem"></textarea>
      </div>

      <div class="summary-row"><span>Subtotal</span><span id="subtotal">₱0.00</span></div>
      <div class="summary-row"><span>Discount</span><span id="discountDisplay">-₱0.00</span></div>
      <div class="summary-row"><span>Tax</span><span id="taxDisplay">+₱0.00</span></div>
      <div class="summary-row"><span>Extra Fee</span><span id="feeDisplay">+₱0.00</span></div>
      <div class="summary-row total"><span>TOTAL</span><span id="totalAmount">₱0.00</span></div>

      <!-- Payment -->
      <div style="margin-top:12px">
        <select id="paymentMode" class="form-select" style="height:38px;margin-bottom:8px">
          <option value="cash">💵 Cash</option>
          <option value="bdo">🏦 BDO</option>
          <option value="gcash">📱 GCash</option>
        </select>
        <select id="paymentStatus" class="form-select" style="height:38px;margin-bottom:12px">
          <option value="fully_paid">Fully Paid</option>
          <option value="partially_paid">Partially Paid</option>
          <option value="unpaid">Unpaid (Create Invoice)</option>
        </select>
        <button class="btn btn-primary btn-block" id="checkoutBtn" onclick="checkout()" style="height:44px;font-size:1rem">
          ✅ Checkout
        </button>
      </div>
    </div>
  </div>
</div>

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

<script>
let cart = [];
let products = [];

// Load products on page load
loadProducts();
loadClients();

function loadProducts(search = '', categoryId = '') {
  let url = '/api/v1/pos/products?limit=100';
  if (search) url += '&search=' + encodeURIComponent(search);
  if (categoryId) url += '&category_id=' + categoryId;

  fetch(url).then(r => r.json()).then(res => {
    if (res.success) {
      products = res.data.products;
      renderProducts(products);
    }
  });
}

function renderProducts(list) {
  const grid = document.getElementById('productGrid');
  if (!list.length) {
    grid.innerHTML = '<div class="empty-state"><div class="empty-icon" style="color:var(--color-gray-300)"><?= icon("products", 40) ?></div><p>No products found</p></div>';
    return;
  }
  grid.innerHTML = list.map(p => `
    <div class="product-card ${p.current_stock <= 0 ? 'out-of-stock' : ''}" onclick="${p.current_stock > 0 ? 'addToCart(' + p.id + ')' : ''}" style="position:relative;padding-top:34px">
      <div style="position:absolute;top:8px;left:8px;background:rgba(0,0,0,0.72);color:white;padding:4px 8px;border-radius:4px;font-size:.68rem;font-weight:700;z-index:2;pointer-events:none">${p.sku}</div>
      <div style="padding:0 8px 8px">
            <img src="${p.image_path ? appPath(p.image_path) : appPath('/assets/images/no-image.png')}" alt="${p.name}" onerror="this.src='${appPath('/assets/images/no-image.png')}'" style="width:100%;aspect-ratio:1 / 1;height:auto;object-fit:cover;border-radius:10px;display:block">
      </div>
      <div class="product-name">${p.name}</div>
      ${[p.category_code, p.type_code, p.color_name, p.size_code].filter(Boolean).length ? `<div class="product-stock">${[p.category_code, p.type_code, p.color_name, p.size_code].filter(Boolean).join(' | ')}</div>` : ''}
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
        <div class="product-price">₱${parseFloat(p.selling_price).toFixed(2)}</div>
        <div style="font-weight:600;font-size:.9rem;${p.current_stock <= 0 ? 'color:var(--color-danger)' : ''}">${p.current_stock <= 0 ? '0' : p.current_stock}</div>
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
    cart.push({ product_id: product.id, name: product.name, unit_price: parseFloat(product.selling_price), quantity: 1, max_stock: product.current_stock });
  }
  renderCart();
}

function renderCart() {
  const cartItems = document.getElementById('cartItems');
  const emptyCart = document.getElementById('emptyCart');
  document.getElementById('cartCount').textContent = cart.length;

  if (!cart.length) {
    cartItems.innerHTML = '<div class="empty-state" id="emptyCart"><div class="empty-icon" style="color:var(--color-gray-300)"><?= icon("pos", 36) ?></div><p>Cart is empty</p><p style="font-size:.8rem">Click products to add</p></div>';
    recalculate();
    return;
  }

  cartItems.innerHTML = cart.map((item, idx) => `
    <div class="cart-item">
      <div class="cart-item-info">
        <div class="cart-item-name">${item.name}</div>
        <div class="cart-item-price">₱${item.unit_price.toFixed(2)} each</div>
      </div>
      <div class="cart-item-qty">
        <button class="qty-btn" onclick="changeQty(${idx}, -1)">-</button>
        <input type="number" class="qty-input" value="${item.quantity}" min="1" max="${item.max_stock}" onchange="setQty(${idx}, this.value)">
        <button class="qty-btn" onclick="changeQty(${idx}, 1)">+</button>
      </div>
      <div class="cart-item-total">₱${(item.unit_price * item.quantity).toFixed(2)}</div>
      <button class="cart-remove" onclick="removeItem(${idx})" title="Remove"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    </div>
  `).join('');
  recalculate();
}

function changeQty(idx, delta) {
  cart[idx].quantity = Math.max(1, Math.min(cart[idx].max_stock, cart[idx].quantity + delta));
  renderCart();
}
function setQty(idx, val) {
  cart[idx].quantity = Math.max(1, Math.min(cart[idx].max_stock, parseInt(val) || 1));
  renderCart();
}
function removeItem(idx) { cart.splice(idx, 1); renderCart(); }
function clearCart() { cart = []; renderCart(); }

function recalculate() {
  const subtotal  = cart.reduce((s, i) => s + i.unit_price * i.quantity, 0);
  const discount  = parseFloat(document.getElementById('discountAmount').value) || 0;
  const tax       = parseFloat(document.getElementById('taxAmount').value) || 0;
  const fee       = parseFloat(document.getElementById('additionalFee').value) || 0;
  const total     = Math.max(0, subtotal - discount + tax + fee);

  document.getElementById('subtotal').textContent       = '₱' + subtotal.toFixed(2);
  document.getElementById('discountDisplay').textContent = '-₱' + discount.toFixed(2);
  document.getElementById('taxDisplay').textContent      = '+₱' + tax.toFixed(2);
  document.getElementById('feeDisplay').textContent      = '+₱' + fee.toFixed(2);
  document.getElementById('totalAmount').textContent     = '₱' + total.toFixed(2);
}

function searchProducts(val) {
  const search   = document.getElementById('posSearch').value;
  const category = document.getElementById('categoryFilter').value;
  loadProducts(search, category);
}

function loadClients() {
  fetch('/api/v1/clients?per_page=100').then(r => r.json()).then(res => {
    if (res.success) {
      const sel = document.getElementById('clientSelect');
      res.data.clients.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.first_name + ' ' + c.last_name;
        sel.appendChild(opt);
      });
    }
  });
}

async function checkout() {
  if (!cart.length) { showToast('Cart is empty', 'error'); return; }
  const btn = document.getElementById('checkoutBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Processing...';

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
    payment_status: document.getElementById('paymentStatus').value,
    notes:          document.getElementById('orderNotes').value,
  };

  try {
    const res = await fetch('/api/v1/pos/checkout', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) {
      document.getElementById('receiptBody').innerHTML = `
        <p style="text-align:center;font-size:1.2rem;margin-bottom:16px">Invoice <strong>#${data.data.invoice_number}</strong></p>
        <p style="text-align:center;font-size:1.5rem;font-weight:700;color:var(--color-success)">₱${parseFloat(data.data.total_amount).toFixed(2)}</p>
        <p style="text-align:center;margin-top:8px;color:var(--color-gray-500)">Sale completed successfully</p>`;
      document.getElementById('printReceiptBtn').href = data.data.receipt_url;
      document.getElementById('receiptModal').classList.add('show');
      cart = [];
      renderCart();
    } else {
      showToast(data.message || 'Checkout failed', 'error');
    }
  } catch (e) {
    showToast('Network error', 'error');
  }

  btn.disabled = false;
  btn.innerHTML = '✅ Checkout';
}

function closeReceipt() {
  document.getElementById('receiptModal').classList.remove('show');
  loadProducts();
}

function showToast(msg, type = 'info') {
  const tc = document.getElementById('toastContainer');
  if (!tc) { alert(msg); return; }
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.innerHTML = `<span>${msg}</span><button class="toast-close" onclick="this.parentElement.remove()">×</button>`;
  tc.appendChild(t);
  setTimeout(() => t.remove(), 5000);
}
</script>
<?php
$content = ob_get_clean();
$title   = 'Point of Sale | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
