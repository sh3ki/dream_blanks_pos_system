<?php ob_start(); ?>
<?php
$sort          = $filters['sort']   ?? 'sp.stock_status';
$order         = strtoupper($filters['order'] ?? 'ASC');
$activeTab     = $active_tab ?? 'inventory';
$stockStatus   = $filters['status'] ?? '';
$restockStatus = $restock_filters['restock_status'] ?? '';

function invSortLink(string $col, string $label, string $currentSort, string $currentOrder, array $filters): string {
    $nextOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $arrow = $currentSort === $col
        ? ($currentOrder === 'ASC' ? ' <span style="font-size:.8em">▲</span>' : ' <span style="font-size:.8em">▼</span>')
        : ' <span style="font-size:.8em;opacity:.5">⇅</span>';
    $q = http_build_query(array_merge($filters, ['tab' => 'inventory', 'sort' => $col, 'order' => $nextOrder]));
    return '<a href="?' . $q . '" style="display:block;padding:12px 16px;color:inherit;text-decoration:none;white-space:nowrap">' . htmlspecialchars($label) . $arrow . '</a>';
}

function roSortLinkInv(string $col, string $label, string $cs, string $co, array $rf): string {
    $next   = ($cs === $col && $co === 'ASC') ? 'DESC' : 'ASC';
    $params = array_filter(array_merge(['tab' => 'restock'], $rf, ['restock_sort' => $col, 'restock_order' => $next]), fn($v) => $v !== '');
    $arrow  = $cs === $col ? ($co === 'ASC' ? ' <span style="font-size:.8em">▲</span>' : ' <span style="font-size:.8em">▼</span>') : ' <span style="font-size:.8em;opacity:.5">⇅</span>';
    return '<a href="?' . http_build_query($params) . '" style="display:block;padding:10px 14px;color:inherit;text-decoration:none;white-space:nowrap">' . htmlspecialchars($label) . $arrow . '</a>';
}
?>
<div class="page-header">
  <h1>Inventory</h1>
  <button class="btn btn-primary" onclick="openRestock()">+ Create Restock</button>
</div>

<!-- Stats cards -->
<div class="stats-grid">
  <?php
    function invCard(string $label, string $icon, string $value, string $valueColor, string $sub, string $href, bool $isActive): string {
        $border = $isActive ? 'border:2px solid var(--color-primary)' : 'border:2px solid transparent';
        return '<a href="' . htmlspecialchars($href) . '" style="text-decoration:none;color:inherit">
          <div class="stats-card" style="' . $border . ';cursor:pointer;transition:border .15s">
            <div class="stats-header"><span class="stats-label">' . $label . '</span><span class="stats-icon">' . icon($icon, 18) . '</span></div>
            <div class="stats-value" style="color:' . $valueColor . '">' . $value . '</div>
            <div class="stats-change text-muted">' . $sub . '</div>
          </div></a>';
    }
    $invUrl = fn(string $s) => '?tab=inventory&status=' . $s;
    $roUrl  = fn(string $s) => '?tab=restock&restock_status=' . $s;
  ?>
  <?= invCard('Low Stock Items',  'alert',   (string)(int)($inv_stats['low_stock_count']    ?? 0), 'var(--color-warning)', 'Needs attention',       $invUrl('low_stock'),    $activeTab === 'inventory' && $stockStatus === 'low_stock') ?>
  <?= invCard('Out of Stock',     'alert',   (string)(int)($inv_stats['out_of_stock_count'] ?? 0), 'var(--color-danger)',  'Zero quantity',          $invUrl('out_of_stock'),  $activeTab === 'inventory' && $stockStatus === 'out_of_stock') ?>
  <?= invCard('In Stock',         'package', (string)(int)($inv_stats['in_stock_count']     ?? 0), 'var(--color-success)', 'Sufficient stock',       $invUrl('in_stock'),      $activeTab === 'inventory' && $stockStatus === 'in_stock') ?>
  <?= invCard('Pending Restock',  'alert',   (string)(int)($restock_stats['pending_count']   ?? 0), 'var(--color-primary)', 'Awaiting delivery',   $roUrl('ordered'),    $activeTab === 'restock' && $restockStatus === 'ordered') ?>
  <?= invCard('Delivered Orders', 'check',   (string)(int)($restock_stats['delivered_count'] ?? 0), 'var(--color-success)', 'Successfully received', $roUrl('delivered'), $activeTab === 'restock' && $restockStatus === 'delivered') ?>
</div>

<!-- Tabs -->
<div style="display:flex;gap:0;border-bottom:2px solid var(--color-gray-100);margin-bottom:16px">
  <button id="tab-inv-btn" onclick="switchInvTab('inventory')"
    style="padding:10px 20px;border:none;background:none;cursor:pointer;font-weight:600;font-size:.9rem;border-bottom:2px solid <?= $activeTab === 'inventory' ? 'var(--color-primary)' : 'transparent' ?>;color:<?= $activeTab === 'inventory' ? 'var(--color-primary)' : 'var(--color-gray-500)' ?>;margin-bottom:-2px">
    Inventory
  </button>
  <button id="tab-hist-btn" onclick="switchInvTab('history')"
    style="padding:10px 20px;border:none;background:none;cursor:pointer;font-weight:600;font-size:.9rem;border-bottom:2px solid <?= $activeTab === 'history' ? 'var(--color-primary)' : 'transparent' ?>;color:<?= $activeTab === 'history' ? 'var(--color-primary)' : 'var(--color-gray-500)' ?>;margin-bottom:-2px">
    History
  </button>
  <button id="tab-restock-btn" onclick="switchInvTab('restock')"
    style="padding:10px 20px;border:none;background:none;cursor:pointer;font-weight:600;font-size:.9rem;border-bottom:2px solid <?= $activeTab === 'restock' ? 'var(--color-primary)' : 'transparent' ?>;color:<?= $activeTab === 'restock' ? 'var(--color-primary)' : 'var(--color-gray-500)' ?>;margin-bottom:-2px">
    Restock Orders
  </button>
</div>

<!-- ===== TAB: INVENTORY ===== -->
<div id="tab-inventory" style="display:<?= $activeTab === 'inventory' ? 'block' : 'none' ?>">

<!-- Floating Restock Selected bar -->
<div id="invRestockBar" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:var(--color-primary);color:#fff;padding:12px 24px;border-radius:30px;box-shadow:0 4px 16px rgba(0,0,0,.2);z-index:1000;align-items:center;gap:12px">
  <span id="invRestockBarCount">0 selected</span>
  <button class="btn btn-sm" style="background:#fff;color:var(--color-primary);font-weight:600" onclick="openRestockFromSelection()">Restock Selected</button>
  <button style="background:none;border:none;color:#fff;cursor:pointer;font-size:1.1rem" onclick="clearInvSelection()">✕</button>
</div>

<div class="card">
  <div class="card-body" style="padding:16px">
    <div class="filter-bar" style="flex-wrap:wrap;gap:8px">
      <div class="search-bar" style="flex:1;min-width:200px;max-width:280px">
        <?= icon('search', 16) ?> <input type="text" id="invSearchInput" placeholder="Search by name or code..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" oninput="debouncedInvSearch()" style="width:100%">
      </div>
      <select id="invTypeFilter" class="form-select" style="width:130px;height:38px" onchange="applyInvFilters()">
        <option value="">All Types</option>
        <?php foreach ($types ?? [] as $t): ?>
          <option value="<?= $t['id'] ?>" <?= ($filters['type_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="invColorFilter" class="form-select" style="width:120px;height:38px" onchange="applyInvFilters()">
        <option value="">All Colors</option>
        <?php foreach ($colors ?? [] as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($filters['color_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="invSizeFilter" class="form-select" style="width:110px;height:38px" onchange="applyInvFilters()">
        <option value="">All Sizes</option>
        <?php foreach ($sizes ?? [] as $s): ?>
          <option value="<?= $s['id'] ?>" <?= ($filters['size_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="invStatusFilter" class="form-select" style="width:120px;height:38px" onchange="applyInvFilters()">
        <option value="">All Status</option>
        <option value="active"   <?= ($filters['status'] ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
      <select id="invStockStatusFilter" class="form-select" style="width:150px;height:38px" onchange="applyInvFilters()">
        <option value="">All Stock Status</option>
        <option value="in_stock"     <?= ($filters['stock_status'] ?? '') === 'in_stock'     ? 'selected' : '' ?>>In Stock</option>
        <option value="low_stock"    <?= ($filters['stock_status'] ?? '') === 'low_stock'    ? 'selected' : '' ?>>Low Stock</option>
        <option value="out_of_stock" <?= ($filters['stock_status'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
      </select>
      <button class="btn btn-secondary btn-sm" onclick="resetInvFilters()" style="height:38px">Reset</button>
    </div>
  </div>

  <div id="inventoryResultsContainer">
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr style="cursor:pointer">
          <th style="width:38px"><input type="checkbox" id="invSelectAll" onchange="toggleInvSelectAll(this)" title="Select all"></th>
          <th style="width:52px">Image</th>
          <th style="padding:0"><?= invSortLink('sp.code',        'Code',         $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= invSortLink('sp.name',        'Name',         $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= invSortLink('t.name',         'Type',         $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= invSortLink('c.name',         'Color',        $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= invSortLink('s.name',         'Size',         $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= invSortLink('sp.current_qty', 'Qty',          $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= invSortLink('stock_status',   'Stock Status', $sort, $order, $filters) ?></th>
          <th style="padding:0"><?= invSortLink('sp.status',      'Status',       $sort, $order, $filters) ?></th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="invBody">
        <?php foreach ($inventory as $sp): ?>
        <?php
          $qty   = (int)$sp['current_qty'];
          $alert = (int)($sp['low_stock_alert'] ?? 10);
          $sCls  = $qty <= 0 ? 'badge-danger' : ($qty <= $alert ? 'badge-warning' : 'badge-success');
          $sLbl  = $qty <= 0 ? 'Out of Stock' : ($qty <= $alert ? 'Low Stock' : 'In Stock');
        ?>
        <tr style="cursor:pointer" onclick="viewSp(<?= $sp['id'] ?>)">
          <td onclick="event.stopPropagation()">
            <input type="checkbox" class="inv-sp-select" value="<?= $sp['id'] ?>"
              data-name="<?= htmlspecialchars($sp['name'], ENT_QUOTES) ?>"
              onchange="onInvRowSelect()">
          </td>
          <td onclick="event.stopPropagation()">
            <img
              src="<?= htmlspecialchars(!empty($sp['image_path']) ? app_url($sp['image_path']) : asset_url('/assets/images/no-image.png')) ?>"
              alt="<?= htmlspecialchars($sp['name']) ?>"
              style="width:42px;height:42px;object-fit:cover;border-radius:8px;border:1px solid var(--color-gray-100)"
              onerror="this.src='<?= htmlspecialchars(asset_url('/assets/images/no-image.png')) ?>'">
          </td>
          <td onclick="event.stopPropagation()"><code style="font-size:.8rem"><?= htmlspecialchars($sp['code']) ?></code></td>
          <td><?= htmlspecialchars($sp['name']) ?></td>
          <td><?= htmlspecialchars($sp['type_name']  ?? '-') ?></td>
          <td><?= htmlspecialchars($sp['color_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($sp['size_name']  ?? '-') ?></td>
          <td><span class="badge <?= $sCls ?>"><?= $qty ?></span></td>
          <td><span class="badge <?= $sCls ?>"><?= $sLbl ?></span></td>
          <td><span class="badge <?= $sp['status'] === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= ucfirst($sp['status']) ?></span></td>
          <td onclick="event.stopPropagation()">
            <button class="icon-btn" onclick="openRestock([<?= $sp['id'] ?>])" title="Create Restock">🔄</button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($inventory)): ?>
          <tr><td colspan="11" class="text-center text-muted" style="padding:48px">No inventory records found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
    $invPqFilters = array_filter(array_merge($filters, ['tab' => 'inventory', 'page' => null, 'per_page' => null]), fn($v) => $v !== null && $v !== '');
    echo renderPagination($pagination, $invPqFilters);
  ?>
  </div><!-- /inventoryResultsContainer -->
</div>
</div><!-- /tab-inventory -->

<!-- ===== TAB: HISTORY ===== -->
<div id="tab-history" style="display:<?= $activeTab === 'history' ? 'block' : 'none' ?>">
  <div class="card">
    <div class="card-body" style="padding:16px">
      <div class="filter-bar" style="flex-wrap:wrap;gap:8px">
        <div class="search-bar" style="flex:1;min-width:180px;max-width:260px">
          <?= icon('search', 16) ?> <input type="text" id="histSearchInput" placeholder="Search by code or name..." value="<?= htmlspecialchars($hist_filters['search'] ?? '') ?>" oninput="debouncedHistSearch()" style="width:100%">
        </div>
        <select id="histMovementFilter" class="form-select" style="width:150px;height:38px" onchange="applyHistFilters()">
          <option value="">All Movement Types</option>
          <option value="purchase"    <?= ($hist_filters['movement_type'] ?? '') === 'purchase'   ? 'selected' : '' ?>>Purchase / Restock</option>
          <option value="sale"        <?= ($hist_filters['movement_type'] ?? '') === 'sale'       ? 'selected' : '' ?>>Sale</option>
          <option value="adjustment"  <?= ($hist_filters['movement_type'] ?? '') === 'adjustment' ? 'selected' : '' ?>>Adjustment</option>
          <option value="damage"      <?= ($hist_filters['movement_type'] ?? '') === 'damage'     ? 'selected' : '' ?>>Damage</option>
          <option value="loss"        <?= ($hist_filters['movement_type'] ?? '') === 'loss'       ? 'selected' : '' ?>>Loss</option>
        </select>
        <select id="histTypeIdFilter" class="form-select" style="width:130px;height:38px" onchange="applyHistFilters()">
          <option value="">All Types</option>
          <?php foreach ($types ?? [] as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($hist_filters['type_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="histColorIdFilter" class="form-select" style="width:120px;height:38px" onchange="applyHistFilters()">
          <option value="">All Colors</option>
          <?php foreach ($colors ?? [] as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($hist_filters['color_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="histSizeIdFilter" class="form-select" style="width:110px;height:38px" onchange="applyHistFilters()">
          <option value="">All Sizes</option>
          <?php foreach ($sizes ?? [] as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($hist_filters['size_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="histByFilter" class="form-select" style="width:150px;height:38px" onchange="applyHistFilters()">
          <option value="">All Users</option>
          <?php foreach ($all_users ?? [] as $u): ?>
            <option value="<?= $u['id'] ?>" <?= ($hist_filters['created_by'] ?? '') == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="date" id="histDateFrom" class="form-input" style="width:140px;height:38px" value="<?= htmlspecialchars($hist_filters['date_from'] ?? '') ?>" onchange="applyHistFilters()" title="Date from">
        <input type="date" id="histDateTo"   class="form-input" style="width:140px;height:38px" value="<?= htmlspecialchars($hist_filters['date_to']   ?? '') ?>" onchange="applyHistFilters()" title="Date to">
        <button class="btn btn-secondary btn-sm" onclick="resetHistFilters()" style="height:38px">Reset</button>
      </div>
    </div>
    <div id="historyResultsContainer">
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Date</th><th>Stock Product</th><th>Type</th><th>Color</th><th>Size</th>
            <th>Movement Type</th><th>Change</th><th>Notes</th><th>By</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history ?? [] as $h): ?>
          <?php $qty = (int)$h['quantity_change']; $mvCls = $qty > 0 ? 'badge-success' : 'badge-danger'; ?>
          <tr>
            <td style="white-space:nowrap;font-size:.82rem"><?= !empty($h['created_at']) ? date('M d, Y H:i', strtotime($h['created_at'])) : '-' ?></td>
            <td>
              <code style="font-size:.78rem"><?= htmlspecialchars($h['sp_code'] ?? '-') ?></code>
              <?php if (!empty($h['sp_name'])): ?>
                <br><span style="font-size:.8rem"><?= htmlspecialchars($h['sp_name']) ?></span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($h['type_name']  ?? '-') ?></td>
            <td><?= htmlspecialchars($h['color_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($h['size_name']  ?? '-') ?></td>
            <td><span class="badge badge-gray" style="font-size:.75rem"><?= ucfirst($h['movement_type'] ?? '-') ?></span></td>
            <td><span class="badge <?= $mvCls ?>"><?= $qty > 0 ? '+' . $qty : $qty ?></span></td>
            <td style="font-size:.82rem;max-width:180px;word-break:break-word"><?= htmlspecialchars($h['reason'] ?? '-') ?></td>
            <td style="font-size:.82rem"><?= htmlspecialchars($h['created_by_name'] ?? '-') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($history)): ?>
            <tr><td colspan="9" class="text-center text-muted" style="padding:48px">No history records found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
      $histPqFilters = array_filter([
        'history_search'  => $hist_filters['search']        ?? '',
        'movement_type'   => $hist_filters['movement_type'] ?? '',
        'hist_type_id'    => $hist_filters['type_id']       ?? '',
        'hist_color_id'   => $hist_filters['color_id']      ?? '',
        'hist_size_id'    => $hist_filters['size_id']       ?? '',
        'hist_created_by' => $hist_filters['created_by']    ?? '',
        'hist_date_from'  => $hist_filters['date_from']     ?? '',
        'hist_date_to'    => $hist_filters['date_to']       ?? '',
        'tab'             => 'history',
      ], fn($v) => $v !== '');
      echo renderPagination($hist_pagination, $histPqFilters, 'history_page');
    ?>
    </div><!-- /historyResultsContainer -->
  </div>
</div><!-- /tab-history -->

<!-- ===== TAB: RESTOCK ORDERS ===== -->
<div id="tab-restock" style="display:<?= $activeTab === 'restock' ? 'block' : 'none' ?>">
  <?php
    $rSort  = $restock_filters['restock_sort']  ?? 'ro.created_at';
    $rOrder = strtoupper($restock_filters['restock_order'] ?? 'DESC');
  ?>
  <div class="card" id="restockOrdersContainer">
    <div class="card-header"><h3 class="card-title">Restock Orders</h3></div>
    <?php if (!empty($restock_orders)): ?>
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr style="cursor:pointer">
            <th style="padding:0"><?= roSortLinkInv('ro.order_number',   'Order #',         $rSort, $rOrder, $restock_filters) ?></th>
            <th style="padding:0"><?= roSortLinkInv('ro.order_date',     'Date',            $rSort, $rOrder, $restock_filters) ?></th>
            <th style="padding:0"><?= roSortLinkInv('ro.supplier_name',  'Supplier',        $rSort, $rOrder, $restock_filters) ?></th>
            <th>Items</th>
            <th style="padding:0"><?= roSortLinkInv('ro.delivery_status','Delivery Status', $rSort, $rOrder, $restock_filters) ?></th>
            <th>Created By</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($restock_orders as $ro): ?>
          <?php
            $ds   = $ro['delivery_status'] ?? 'ordered';
            $dsBg = match($ds) { 'delivered'=>'#d1fae5','ordered'=>'#dbeafe','incomplete'=>'#fef3c7','problematic'=>'#fee2e2',default=>'#f3f4f6' };
            $dsFg = match($ds) { 'delivered'=>'#065f46','ordered'=>'#1e40af','incomplete'=>'#92400e','problematic'=>'#991b1b',default=>'#374151' };
          ?>
          <tr style="cursor:pointer" onclick="viewRestockOrder(<?= (int)$ro['id'] ?>)">
            <td onclick="event.stopPropagation()"><code style="font-size:.8rem"><?= htmlspecialchars($ro['order_number'] ?? '-') ?></code></td>
            <td style="font-size:.85rem"><?= !empty($ro['order_date']) ? date('M d, Y', strtotime($ro['order_date'])) : '-' ?></td>
            <td><?= htmlspecialchars($ro['supplier_name'] ?? '-') ?></td>
            <td><span class="badge badge-gray"><?= (int)($ro['items_count'] ?? 0) ?></span></td>
            <td onclick="event.stopPropagation()">
              <select class="restock-status-select" data-id="<?= (int)$ro['id'] ?>" data-prev="<?= htmlspecialchars($ds) ?>"
                onchange="updateRestockDeliveryStatus(<?= (int)$ro['id'] ?>, this.value, this)"
                style="border:none;border-radius:20px;padding:3px 12px;font-size:.78rem;font-weight:600;cursor:pointer;outline:none;background-color:<?= $dsBg ?>;color:<?= $dsFg ?>">
                <option value="ordered"     <?= $ds === 'ordered'     ? 'selected' : '' ?>>Ordered</option>
                <option value="delivered"   <?= $ds === 'delivered'   ? 'selected' : '' ?>>Delivered</option>
                <option value="incomplete"  <?= $ds === 'incomplete'  ? 'selected' : '' ?>>Incomplete</option>
                <option value="problematic" <?= $ds === 'problematic' ? 'selected' : '' ?>>Problematic</option>
              </select>
            </td>
            <td style="font-size:.85rem"><?= htmlspecialchars($ro['created_by_name'] ?? '-') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
      $rPqFilters = array_filter(array_merge(['tab' => 'restock'], $restock_filters), fn($v) => $v !== '');
      echo renderPagination($restock_pagination, $rPqFilters, 'restock_page', 'restock_per_page');
    ?>
    <?php else: ?>
    <div class="card-body"><p class="text-muted text-center" style="padding:24px 0">No restock orders yet.</p></div>
    <?php endif; ?>
  </div>
</div><!-- /tab-restock -->

<!-- ===== View Stock Product Modal (read-only) ===== -->
<div class="modal-overlay" id="viewSpModal">
  <div class="modal-content" style="max-width:620px">
    <div class="modal-header">
      <h2 class="modal-title">Stock Product Details</h2>
      <button class="modal-close" onclick="closeModal('viewSpModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body" id="viewSpBody"><div style="text-align:center;padding:48px"><span class="spinner"></span></div></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('viewSpModal')">Close</button>
    </div>
  </div>
</div>

<!-- ===== Create Restock Modal ===== -->
<div class="modal-overlay" id="restockModal">
  <div class="modal-content" style="max-width:780px;max-height:90vh;display:flex;flex-direction:column">
    <div class="modal-header">
      <h2 class="modal-title">Create Restock Order</h2>
      <button class="modal-close" onclick="closeModal('restockModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body" style="overflow-y:auto;flex:1">
      <!-- Order details -->
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Supplier Name</label>
          <input type="text" id="roSupplier" class="form-input" placeholder="Optional">
        </div>
        <div class="form-group">
          <label class="form-label">Expected Delivery Date</label>
          <input type="date" id="roDeliveryDate" class="form-input">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Delivery Status</label>
          <select id="roDeliveryStatus" class="form-select">
            <option value="ordered">Ordered</option>
            <option value="delivered">Delivered</option>
            <option value="incomplete">Incomplete</option>
            <option value="problematic">Problematic</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <input type="text" id="roNotes" class="form-input" placeholder="Optional notes">
        </div>
      </div>

      <!-- SP Picker -->
      <hr style="margin:12px 0">
      <label class="form-label" style="font-weight:600">Select Stock Products &amp; Quantities</label>
      <div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap">
        <input type="text" id="roSpSearch" class="form-input" style="flex:1;min-width:160px;height:34px" placeholder="Search..." oninput="applyRestockFilter()">
        <select id="roSpTypeFilter" class="form-select" style="width:120px;height:34px" onchange="applyRestockFilter()">
          <option value="">All Types</option>
          <?php foreach ($types ?? [] as $t): ?>
            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="roSpColorFilter" class="form-select" style="width:110px;height:34px" onchange="applyRestockFilter()">
          <option value="">All Colors</option>
          <?php foreach ($colors ?? [] as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="roSpSizeFilter" class="form-select" style="width:100px;height:34px" onchange="applyRestockFilter()">
          <option value="">All Sizes</option>
          <?php foreach ($sizes ?? [] as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="max-height:280px;overflow-y:auto;border:1px solid var(--color-gray-100);border-radius:6px">
        <table class="data-table" style="margin:0">
          <thead>
            <tr>
              <th style="width:34px"><input type="checkbox" id="roSelectAll" onchange="toggleRestockSelectAll(this)"></th>
              <th>Code</th><th>Name</th><th>Type</th><th>Color</th><th>Size</th><th>Current Qty</th><th style="width:100px">Order Qty</th>
            </tr>
          </thead>
          <tbody id="roSpPickerBody"></tbody>
        </table>
      </div>
      <div id="roSelectedSummary" style="font-size:.82rem;color:var(--color-gray-500);margin-top:6px">No items selected</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('restockModal')">Cancel</button>
      <button class="btn btn-primary" onclick="submitRestock()" id="roSubmitBtn">Create Restock Order</button>
    </div>
  </div>
</div>

<!-- ===== View Restock Order Modal ===== -->
<div class="modal-overlay" id="viewRestockModal">
  <div class="modal-content" style="max-width:680px;max-height:90vh;display:flex;flex-direction:column">
    <div class="modal-header">
      <h2 class="modal-title" id="vrmoTitle">Restock Order</h2>
      <button class="modal-close" onclick="closeModal('viewRestockModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body" style="overflow-y:auto;flex:1" id="vrmoBody">
      <p class="text-muted">Loading...</p>
    </div>
  </div>
</div>

<!-- ===== Action Confirm Modal ===== -->
<div class="modal-overlay" id="invActionConfirmModal">
  <div class="modal-content" style="max-width:420px">
    <div class="modal-header">
      <h2 class="modal-title" id="invActionConfirmTitle">Confirm Action</h2>
      <button class="modal-close" onclick="closeModal('invActionConfirmModal')">✕</button>
    </div>
    <div class="modal-body">
      <p id="invActionConfirmMsg" style="margin:0"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('invActionConfirmModal')">Cancel</button>
      <button class="btn btn-primary" id="invActionConfirmBtn">Confirm</button>
    </div>
  </div>
</div>

<script>
const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content || '';
const invBaseUrl = '<?= htmlspecialchars(app_url('/inventory')) ?>';

const _dsColors = {
  ordered:     { bg: '#dbeafe', fg: '#1e40af' },
  delivered:   { bg: '#d1fae5', fg: '#065f46' },
  incomplete:  { bg: '#fef3c7', fg: '#92400e' },
  problematic: { bg: '#fee2e2', fg: '#991b1b' },
};

let _allSPs   = [];
let _spSels   = {};   // { id: qty }
let _spLoaded = false;

function openModal(id)  { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

function esc(s) {
  const d = document.createElement('span');
  d.textContent = s || '';
  return d.innerHTML;
}

// ===== TAB SWITCHING =====
function switchInvTab(tab) {
  document.getElementById('tab-inventory').style.display = tab === 'inventory' ? 'block' : 'none';
  document.getElementById('tab-history').style.display   = tab === 'history'   ? 'block' : 'none';
  document.getElementById('tab-restock').style.display   = tab === 'restock'   ? 'block' : 'none';
  const tabs = { 'inventory': 'tab-inv-btn', 'history': 'tab-hist-btn', 'restock': 'tab-restock-btn' };
  Object.entries(tabs).forEach(([t, btnId]) => {
    const b = document.getElementById(btnId);
    if (!b) return;
    const active = t === tab;
    b.style.borderBottom = active ? '2px solid var(--color-primary)' : '2px solid transparent';
    b.style.color        = active ? 'var(--color-primary)'           : 'var(--color-gray-500)';
  });
  const qs = new URLSearchParams(window.location.search);
  qs.set('tab', tab);
  if (tab !== 'inventory') qs.delete('status');
  if (tab !== 'restock')   qs.delete('restock_status');
  history.pushState({}, '', '?' + qs.toString());
}

// ===== VIEW STOCK PRODUCT (read-only) =====
async function viewSp(id) {
  document.getElementById('viewSpBody').innerHTML = '<div style="text-align:center;padding:48px"><span class="spinner"></span></div>';
  openModal('viewSpModal');
  try {
    const res  = await fetch((window.APP_BASE_PATH || '') + '/api/v1/stock-products/' + id);
    const data = await res.json();
    if (!data.success) { document.getElementById('viewSpBody').innerHTML = '<p class="text-danger">Failed to load</p>'; return; }
    const s    = data.data;
    const qty  = parseInt(s.current_qty);
    const alrt = parseInt(s.low_stock_alert || 10);
    const cls  = qty <= 0 ? 'badge-danger' : (qty <= alrt ? 'badge-warning' : 'badge-success');
    const used = s.used_by_products ?? [];

    let usedHtml = '';
    if (used.length > 0) {
      usedHtml = `<div style="margin-top:16px">
        <div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600;margin-bottom:6px">Used by Sellable Products</div>
        <table style="width:100%;border-collapse:collapse;font-size:.82rem">
          <thead><tr style="background:var(--color-gray-50)">
            <th style="padding:6px 10px;text-align:left;border-bottom:1px solid var(--color-gray-100)">SKU</th>
            <th style="padding:6px 10px;text-align:left;border-bottom:1px solid var(--color-gray-100)">Name</th>
            <th style="padding:6px 10px;text-align:right;border-bottom:1px solid var(--color-gray-100)">Qty/unit</th>
            <th style="padding:6px 10px;text-align:right;border-bottom:1px solid var(--color-gray-100)">Waste %</th>
          </tr></thead><tbody>
          ${used.map(p => `<tr>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50)"><code style="font-size:.78rem">${esc(p.sku)}</code></td>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50)">${esc(p.name)}</td>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50);text-align:right">${parseFloat(p.qty_required_per_unit)}</td>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50);text-align:right">${parseFloat(p.waste_percent || 0)}%</td>
          </tr>`).join('')}
          </tbody>
        </table>
      </div>`;
    }

    document.getElementById('viewSpBody').innerHTML = `
      ${s.image_path ? `<div style="text-align:center;margin-bottom:16px"><img src="${(window.APP_BASE_PATH||'')+s.image_path}" alt="${esc(s.name)}" style="max-height:160px;border-radius:10px;border:1px solid var(--color-gray-100);object-fit:contain"></div>` : ''}
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Code</div><code>${esc(s.code)}</code></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Name</div><strong>${esc(s.name)}</strong></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Status</div><span class="badge ${s.status === 'active' ? 'badge-success' : 'badge-gray'}">${esc(s.status)}</span></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Type</div>${esc(s.type_name  || '-')}</div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Color</div>${esc(s.color_name || '-')}</div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Size</div>${esc(s.size_name  || '-')}</div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Current Qty</div><span class="badge ${cls}">${qty}</span></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Low Stock Alert</div>${alrt}</div>
      </div>
      ${s.description ? `<p style="margin:12px 0 0;font-size:.875rem;color:var(--color-gray-600)">${esc(s.description)}</p>` : ''}
      ${usedHtml}`;
  } catch (e) { document.getElementById('viewSpBody').innerHTML = '<p class="text-danger">Network error</p>'; }
}

// ===== INVENTORY TAB FILTERS =====
let _invSearchTimer = null;
function debouncedInvSearch() {
  clearTimeout(_invSearchTimer);
  _invSearchTimer = setTimeout(applyInvFilters, 400);
}

function applyInvFilters() {
  const params = new URLSearchParams();
  const search      = document.getElementById('invSearchInput').value.trim();
  const type        = document.getElementById('invTypeFilter').value;
  const color       = document.getElementById('invColorFilter').value;
  const size        = document.getElementById('invSizeFilter').value;
  const status      = document.getElementById('invStatusFilter').value;
  const stockStatus = document.getElementById('invStockStatusFilter').value;
  params.set('tab', 'inventory');
  if (search)      params.set('search',       search);
  if (type)        params.set('type_id',      type);
  if (color)       params.set('color_id',     color);
  if (size)        params.set('size_id',      size);
  if (status)      params.set('status',       status);
  if (stockStatus) params.set('stock_status', stockStatus);
  const cur = new URLSearchParams(window.location.search);
  if (cur.get('sort'))  params.set('sort',  cur.get('sort'));
  if (cur.get('order')) params.set('order', cur.get('order'));
  const pageUrl = window.location.origin + invBaseUrl + (params.toString() ? '?' + params.toString() : '');
  history.pushState({}, '', pageUrl);
  const container = document.getElementById('inventoryResultsContainer');
  if (container) container.style.opacity = '0.5';
  fetch(pageUrl).then(r => r.text()).then(html => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const el  = doc.getElementById('inventoryResultsContainer');
    if (el && container) { container.innerHTML = el.innerHTML; container.style.opacity = '1'; }
  }).catch(() => { if (container) container.style.opacity = '1'; });
}

function resetInvFilters() {
  ['invSearchInput','invTypeFilter','invColorFilter','invSizeFilter','invStatusFilter','invStockStatusFilter']
    .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
  applyInvFilters();
}

// ===== INVENTORY ROW SELECTION =====
function onInvRowSelect() {
  const checked = document.querySelectorAll('.inv-sp-select:checked');
  const bar = document.getElementById('invRestockBar');
  if (checked.length > 0) {
    document.getElementById('invRestockBarCount').textContent = checked.length + ' selected';
    bar.style.display = 'flex';
  } else {
    bar.style.display = 'none';
  }
  const all = document.querySelectorAll('.inv-sp-select');
  const sa  = document.getElementById('invSelectAll');
  if (sa) { sa.indeterminate = checked.length > 0 && checked.length < all.length; sa.checked = checked.length === all.length; }
}

function toggleInvSelectAll(cb) {
  document.querySelectorAll('.inv-sp-select').forEach(c => c.checked = cb.checked);
  onInvRowSelect();
}

function clearInvSelection() {
  document.querySelectorAll('.inv-sp-select').forEach(c => c.checked = false);
  const sa = document.getElementById('invSelectAll');
  if (sa) { sa.checked = false; sa.indeterminate = false; }
  document.getElementById('invRestockBar').style.display = 'none';
}

function openRestockFromSelection() {
  const checked = document.querySelectorAll('.inv-sp-select:checked');
  const preIds  = Array.from(checked).map(cb => parseInt(cb.value, 10));
  openRestock(preIds);
}

// ===== HISTORY FILTERS =====
let _histTimer = null;
function debouncedHistSearch() {
  clearTimeout(_histTimer);
  _histTimer = setTimeout(applyHistFilters, 400);
}

function applyHistFilters() {
  const params = new URLSearchParams(window.location.search);
  params.set('tab',            'history');
  params.set('history_search',  document.getElementById('histSearchInput')?.value.trim() || '');
  params.set('movement_type',   document.getElementById('histMovementFilter')?.value || '');
  params.set('hist_type_id',    document.getElementById('histTypeIdFilter')?.value || '');
  params.set('hist_color_id',   document.getElementById('histColorIdFilter')?.value || '');
  params.set('hist_size_id',    document.getElementById('histSizeIdFilter')?.value || '');
  params.set('hist_created_by', document.getElementById('histByFilter')?.value || '');
  params.set('hist_date_from',  document.getElementById('histDateFrom')?.value || '');
  params.set('hist_date_to',    document.getElementById('histDateTo')?.value || '');
  params.set('history_page', '1');
  for (const [k, v] of [...params.entries()]) { if (!v) params.delete(k); }
  params.set('tab', 'history');
  const url = window.location.origin + invBaseUrl + '?' + params.toString();
  history.pushState({}, '', url);
  const container = document.getElementById('historyResultsContainer');
  if (container) container.style.opacity = '0.5';
  fetch(url).then(r => r.text()).then(html => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const el  = doc.getElementById('historyResultsContainer');
    if (el && container) { container.innerHTML = el.innerHTML; container.style.opacity = '1'; }
  }).catch(() => { if (container) container.style.opacity = '1'; });
}

function resetHistFilters() {
  ['histSearchInput','histMovementFilter','histTypeIdFilter','histColorIdFilter','histSizeIdFilter','histByFilter','histDateFrom','histDateTo']
    .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
  applyHistFilters();
}

// ===== RESTOCK PICKER =====
async function loadAllSPs() {
  if (_spLoaded) return;
  const res  = await fetch((window.APP_BASE_PATH || '') + '/api/v1/stock-products?per_page=999&status=active');
  const data = await res.json();
  _allSPs   = data.data?.data || data.data || [];
  _spLoaded = true;
}

function applyRestockFilter() {
  const search  = (document.getElementById('roSpSearch')?.value || '').toLowerCase();
  const typeId  = document.getElementById('roSpTypeFilter')?.value  || '';
  const colorId = document.getElementById('roSpColorFilter')?.value || '';
  const sizeId  = document.getElementById('roSpSizeFilter')?.value  || '';
  const filtered = _allSPs.filter(sp => {
    if (search  && !sp.code.toLowerCase().includes(search) && !sp.name.toLowerCase().includes(search)) return false;
    if (typeId  && String(sp.type_id)  !== typeId)  return false;
    if (colorId && String(sp.color_id) !== colorId) return false;
    if (sizeId  && String(sp.size_id)  !== sizeId)  return false;
    return true;
  });
  renderRestockPickerBody(filtered);
}

function renderRestockPickerBody(list) {
  const tbody = document.getElementById('roSpPickerBody');
  if (!tbody) return;
  if (!list.length) { tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted" style="padding:24px">No stock products found</td></tr>'; return; }
  tbody.innerHTML = list.map(sp => {
    const checked = _spSels[sp.id] !== undefined;
    const qty     = _spSels[sp.id] || 1;
    return `<tr>
      <td><input type="checkbox" class="ro-sp-chk" value="${sp.id}" ${checked ? 'checked' : ''} onchange="toggleRoSp(${sp.id},this)"></td>
      <td><code style="font-size:.78rem">${esc(sp.code)}</code></td>
      <td>${esc(sp.name)}</td>
      <td>${esc(sp.type_name  || '-')}</td>
      <td>${esc(sp.color_name || '-')}</td>
      <td>${esc(sp.size_name  || '-')}</td>
      <td>${parseInt(sp.current_qty || 0)}</td>
      <td><input type="number" min="1" value="${qty}" style="width:72px;padding:3px 6px;border:1px solid var(--color-gray-200);border-radius:4px"
        id="roQty_${sp.id}" onchange="updateRoQty(${sp.id},this.value)" onclick="event.stopPropagation()"></td>
    </tr>`;
  }).join('');
  updateRoSummary();
}

function toggleRoSp(id, cb) {
  if (cb.checked) { _spSels[id] = parseInt(document.getElementById('roQty_' + id)?.value || 1); }
  else            { delete _spSels[id]; }
  updateRoSummary();
}

function updateRoQty(id, val) {
  if (_spSels[id] !== undefined) _spSels[id] = Math.max(1, parseInt(val) || 1);
  updateRoSummary();
}

function toggleRestockSelectAll(cb) {
  const chks = document.querySelectorAll('.ro-sp-chk');
  chks.forEach(c => {
    c.checked = cb.checked;
    const id  = parseInt(c.value);
    if (cb.checked) { _spSels[id] = parseInt(document.getElementById('roQty_' + id)?.value || 1); }
    else            { delete _spSels[id]; }
  });
  updateRoSummary();
}

function updateRoSummary() {
  const count = Object.keys(_spSels).length;
  const el = document.getElementById('roSelectedSummary');
  if (el) el.textContent = count ? `${count} item(s) selected` : 'No items selected';
}

async function openRestock(preIds = []) {
  _spSels   = {};
  _spLoaded = false;
  preIds.forEach(id => { _spSels[id] = 1; });
  ['roSupplier','roDeliveryDate','roNotes'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
  const ds = document.getElementById('roDeliveryStatus');
  if (ds) ds.value = 'ordered';
  document.getElementById('roSpSearch').value = '';
  ['roSpTypeFilter','roSpColorFilter','roSpSizeFilter'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
  document.getElementById('roSpPickerBody').innerHTML = '<tr><td colspan="8" class="text-center text-muted" style="padding:24px"><span class="spinner"></span> Loading...</td></tr>';
  openModal('restockModal');
  await loadAllSPs();
  renderRestockPickerBody(_allSPs);
}

async function submitRestock() {
  const items = Object.entries(_spSels).map(([id, qty]) => ({
    stock_product_id:  parseInt(id),
    quantity_requested: parseInt(qty),
  }));
  if (!items.length) return showToast('Select at least one stock product', 'error');
  const btn = document.getElementById('roSubmitBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Creating...';
  try {
    const res  = await fetch((window.APP_BASE_PATH || '') + '/api/v1/inventory/restock', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body:    JSON.stringify({
        supplier_name:   document.getElementById('roSupplier').value.trim(),
        delivery_date:   document.getElementById('roDeliveryDate').value,
        delivery_status: document.getElementById('roDeliveryStatus').value,
        notes:           document.getElementById('roNotes').value.trim(),
        items,
      }),
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message || 'Restock order created', 'success');
      closeModal('restockModal');
      setTimeout(() => { window.location.href = invBaseUrl + '?tab=restock'; }, 600);
    } else showToast(data.message || 'Failed', 'error');
  } catch (e) { showToast('Network error', 'error'); }
  btn.disabled = false; btn.innerHTML = 'Create Restock Order';
}

// ===== RESTOCK DELIVERY STATUS =====
function updateRestockDeliveryStatus(id, newStatus, selectEl) {
  const prevStatus = selectEl.getAttribute('data-prev');
  if (prevStatus === newStatus) return;
  const msg = newStatus === 'delivered'
    ? 'Marking as Delivered will update all stock quantities. This cannot easily be undone. Continue?'
    : (prevStatus === 'delivered' ? 'Changing from Delivered will reverse the stock quantity changes. Continue?' : `Change status to "${newStatus}"?`);
  document.getElementById('invActionConfirmTitle').textContent = 'Confirm Status Change';
  document.getElementById('invActionConfirmMsg').textContent   = msg;
  document.getElementById('invActionConfirmBtn').onclick = async () => {
    closeModal('invActionConfirmModal');
    selectEl.disabled = true;
    try {
      const res  = await fetch((window.APP_BASE_PATH || '') + '/api/v1/inventory/restock/' + id, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body:    JSON.stringify({ _method: 'PUT', delivery_status: newStatus }),
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message || 'Status updated', 'success');
        selectEl.setAttribute('data-prev', newStatus);
        const c = _dsColors[newStatus] || _dsColors['ordered'];
        selectEl.style.backgroundColor = c.bg;
        selectEl.style.color           = c.fg;
      } else {
        showToast(data.message || 'Failed', 'error');
        selectEl.value = prevStatus;
      }
    } catch (e) {
      showToast('Network error', 'error');
      selectEl.value = prevStatus;
    }
    selectEl.disabled = false;
  };
  document.getElementById('invActionConfirmModal').querySelector('.btn-secondary').onclick = () => {
    selectEl.value = prevStatus;
    closeModal('invActionConfirmModal');
  };
  openModal('invActionConfirmModal');
}

// ===== VIEW RESTOCK ORDER =====
async function viewRestockOrder(id) {
  document.getElementById('vrmoTitle').textContent = 'Restock Order';
  document.getElementById('vrmoBody').innerHTML    = '<div style="text-align:center;padding:48px"><span class="spinner"></span></div>';
  openModal('viewRestockModal');
  try {
    const res  = await fetch((window.APP_BASE_PATH || '') + '/api/v1/inventory/restock/' + id);
    const data = await res.json();
    if (!data.success) { document.getElementById('vrmoBody').innerHTML = '<p class="text-danger">Failed to load</p>'; return; }
    const o  = data.data?.restock ?? data.data;
    const ds = o.delivery_status || 'ordered';
    const c  = _dsColors[ds] || _dsColors['ordered'];
    const items = (o.items || []).map(i => `<tr>
      <td><code style="font-size:.78rem">${esc(i.sp_code || i.code || '-')}</code></td>
      <td>${esc(i.sp_name  || i.name  || '-')}</td>
      <td>${esc(i.type_name  || '-')}</td>
      <td>${esc(i.color_name || '-')}</td>
      <td>${esc(i.size_name  || '-')}</td>
      <td style="text-align:right">${parseInt(i.quantity_requested || i.quantity_ordered || 0)}</td>
      <td style="text-align:right">${parseInt(i.quantity_received  || 0)}</td>
    </tr>`).join('') || '<tr><td colspan="7" class="text-center text-muted" style="padding:16px">No items</td></tr>';
    document.getElementById('vrmoTitle').textContent = 'Restock #' + (o.order_number || id);
    document.getElementById('vrmoBody').innerHTML = `
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Order #</div><code>${esc(o.order_number || '-')}</code></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Date</div>${o.order_date ? new Date(o.order_date).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '-'}</div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Supplier</div>${esc(o.supplier_name || '-')}</div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Delivery Status</div>
          <span style="background-color:${c.bg};color:${c.fg};padding:2px 12px;border-radius:20px;font-size:.8rem;font-weight:600">${ds.charAt(0).toUpperCase()+ds.slice(1)}</span></div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Expected Delivery</div>${o.delivery_date || o.expected_delivery_date ? new Date(o.delivery_date || o.expected_delivery_date).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '-'}</div>
        <div><div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600">Created By</div>${esc(o.created_by_name || '-')}</div>
      </div>
      ${o.notes ? `<p style="font-size:.875rem;color:var(--color-gray-600);margin-bottom:16px"><strong>Notes:</strong> ${esc(o.notes)}</p>` : ''}
      <div style="font-weight:600;margin-bottom:8px">Items (${(o.items||[]).length})</div>
      <div class="table-wrapper"><table class="data-table">
        <thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Color</th><th>Size</th><th style="text-align:right">Requested</th><th style="text-align:right">Received</th></tr></thead>
        <tbody>${items}</tbody>
      </table></div>`;
  } catch (e) { document.getElementById('vrmoBody').innerHTML = '<p class="text-danger">Network error</p>'; }
}
</script>
<?php
$content = ob_get_clean();
$title   = 'Inventory | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
