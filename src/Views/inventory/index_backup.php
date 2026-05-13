<?php ob_start(); ?>
<?php
$search    = $filters['search']   ?? '';
$status    = $filters['status']   ?? '';
$typeId    = $filters['type_id']  ?? '';
$colorId   = $filters['color_id'] ?? '';
$sizeId    = $filters['size_id']  ?? '';
$sort      = $filters['sort']     ?? 'sp.stock_status';
$order     = strtoupper($filters['order'] ?? 'ASC');
$lowCount  = is_array($low_stock ?? null) ? count($low_stock) : 0;
$activeTab = $active_tab ?? 'inventory';
$restockStatus = $restock_filters['restock_status'] ?? '';

function invySortLink2(string $col, string $label, string $currentSort, string $currentOrder, array $filters): string {
    $nextOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $params    = array_filter(array_merge($filters, ['sort' => $col, 'order' => $nextOrder, 'tab' => 'inventory']), fn($v) => $v !== '');
    $arrow = '';
    if ($currentSort === $col) $arrow = $currentOrder === 'ASC' ? ' <span style="font-size:.8em">▲</span>' : ' <span style="font-size:.8em">▼</span>';
    else $arrow = ' <span style="font-size:.8em;opacity:.5">⇅</span>';
    return '<a href="?' . http_build_query($params) . '" style="display:block;padding:12px 16px;color:inherit;text-decoration:none;white-space:nowrap">' . htmlspecialchars($label) . $arrow . '</a>';
}
?>
<div class="page-header">
  <h1>Inventory</h1>
  <button class="btn btn-primary" onclick="openRestock()">+ Create Restock</button>
</div>

<div class="stats-grid">
  <?php
    $cardActiveStatus = $status;
    $cardActiveRestock = $restockStatus;
    function invCard(string $label, string $icon, string $value, string $valueColor, string $sub, string $href, bool $isActive): string {
        $border = $isActive ? 'border:2px solid var(--color-primary)' : 'border:2px solid transparent';
        $cursor = 'cursor:pointer';
        return '<a href="' . htmlspecialchars($href) . '" style="text-decoration:none;color:inherit">
          <div class="stats-card" style="' . $border . ';' . $cursor . ';transition:border .15s">
            <div class="stats-header"><span class="stats-label">' . $label . '</span><span class="stats-icon">' . icon($icon, 18) . '</span></div>
            <div class="stats-value" style="color:' . $valueColor . '">' . $value . '</div>
            <div class="stats-change text-muted">' . $sub . '</div>
          </div></a>';
    }
    $invUrl = fn(string $s) => '?tab=inventory&status=' . $s . ($sort !== 'sp.stock_status' ? '&sort=' . urlencode($sort) . '&order=' . $order : '');
    $roUrl  = fn(string $s) => '?tab=restock&restock_status=' . $s;
  ?>
  <?= invCard('Low Stock Items',   'alert',   (string)(int)($inv_stats['low_stock_count'] ?? $lowCount), 'var(--color-warning)', 'Needs attention',      $invUrl('low_stock'),   $cardActiveStatus === 'low_stock') ?>
  <?= invCard('Out of Stock',      'alert',   (string)(int)($inv_stats['out_of_stock_count'] ?? 0),      'var(--color-danger)',  'Zero quantity',         $invUrl('out_of_stock'),  $cardActiveStatus === 'out_of_stock') ?>
  <?= invCard('In Stock',          'package', (string)(int)($inv_stats['in_stock_count'] ?? 0),          'var(--color-success)', 'Sufficient stock',      $invUrl('in_stock'),      $cardActiveStatus === 'in_stock') ?>
  <?= invCard('Pending Restock',   'alert',   (string)(int)($restock_stats['pending_count'] ?? 0),       'var(--color-primary)', 'Awaiting delivery',     $roUrl('ordered'),        $cardActiveRestock === 'ordered') ?>
  <?= invCard('Delivered Orders',  'check',   (string)(int)($restock_stats['delivered_count'] ?? 0),     'var(--color-success)', 'Successfully received', $roUrl('delivered'),      $cardActiveRestock === 'delivered') ?>
</div>

<!-- Tabs -->
<div style="display:flex;gap:0;border-bottom:2px solid var(--color-gray-100);margin-bottom:16px">
  <button id="tab-inv-btn" onclick="switchTab('inventory')"
    style="padding:10px 20px;border:none;background:none;cursor:pointer;font-weight:600;font-size:.9rem;border-bottom:2px solid <?= $activeTab === 'inventory' ? 'var(--color-primary)' : 'transparent' ?>;color:<?= $activeTab === 'inventory' ? 'var(--color-primary)' : 'var(--color-gray-500)' ?>;margin-bottom:-2px">
    Inventory
  </button>
  <button id="tab-hist-btn" onclick="switchTab('history')"
    style="padding:10px 20px;border:none;background:none;cursor:pointer;font-weight:600;font-size:.9rem;border-bottom:2px solid <?= $activeTab === 'history' ? 'var(--color-primary)' : 'transparent' ?>;color:<?= $activeTab === 'history' ? 'var(--color-primary)' : 'var(--color-gray-500)' ?>;margin-bottom:-2px">
    Inventory History
  </button>
  <button id="tab-restock-btn" onclick="switchTab('restock')"
    style="padding:10px 20px;border:none;background:none;cursor:pointer;font-weight:600;font-size:.9rem;border-bottom:2px solid <?= $activeTab === 'restock' ? 'var(--color-primary)' : 'transparent' ?>;color:<?= $activeTab === 'restock' ? 'var(--color-primary)' : 'var(--color-gray-500)' ?>;margin-bottom:-2px">
    Restock Orders
  </button>
</div>

<!-- ===== INVENTORY TAB ===== -->
<div id="tab-inventory" style="display:<?= $activeTab === 'inventory' ? 'block' : 'none' ?>">
  <!-- Floating Restock Selected bar -->
  <div id="restockSelectionBar" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:var(--color-primary);color:#fff;padding:12px 24px;border-radius:30px;box-shadow:0 4px 16px rgba(0,0,0,.2);z-index:1000;display:none;align-items:center;gap:12px">
    <span id="restockSelectionCount">0 selected</span>
    <button class="btn btn-sm" style="background:#fff;color:var(--color-primary);font-weight:600" onclick="openRestockFromSelection()">Restock Selected</button>
    <button style="background:none;border:none;color:#fff;cursor:pointer;font-size:1.1rem" onclick="clearSelection()">✕</button>
  </div>

  <div class="card">
    <div class="card-body" style="padding:16px">
      <div class="filter-bar" style="flex-wrap:wrap;gap:8px">
        <div class="search-bar" style="flex:1;max-width:280px">
          <?= icon('search', 16) ?> <input type="text" id="invSearchInput" placeholder="Search stock products..." value="<?= htmlspecialchars($search) ?>" oninput="debouncedSearch()" style="width:100%">
        </div>
        <select id="invTypeFilter" class="form-select" style="width:130px;height:38px" onchange="applyInvFilters()">
          <option value="">All Types</option>
          <?php foreach ($types ?? [] as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $typeId == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="invColorFilter" class="form-select" style="width:120px;height:38px" onchange="applyInvFilters()">
          <option value="">All Colors</option>
          <?php foreach ($colors ?? [] as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $colorId == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="invSizeFilter" class="form-select" style="width:110px;height:38px" onchange="applyInvFilters()">
          <option value="">All Sizes</option>
          <?php foreach ($sizes ?? [] as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $sizeId == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="invStatusFilter" class="form-select" style="width:150px;height:38px" onchange="applyInvFilters()">
          <option value="">All Stock Status</option>
          <option value="in_stock"     <?= $status === 'in_stock'     ? 'selected' : '' ?>>In Stock</option>
          <option value="low_stock"    <?= $status === 'low_stock'    ? 'selected' : '' ?>>Low Stock</option>
          <option value="out_of_stock" <?= $status === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
        </select>
        <button class="btn btn-secondary btn-sm" onclick="resetInvFilters()" style="height:38px">Reset</button>
      </div>
    </div>

    <div id="inventoryResultsContainer">
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr style="cursor:pointer">
            <th style="width:38px"><input type="checkbox" id="selectAllInv" onchange="toggleSelectAll(this)" title="Select all"></th>
            <th style="padding:0"><?= invySortLink2('sp.code',  'Code',   $sort, $order, $filters) ?></th>
            <th style="padding:0"><?= invySortLink2('sp.name',  'Name',   $sort, $order, $filters) ?></th>
            <th style="padding:0"><?= invySortLink2('t.name',   'Type',   $sort, $order, $filters) ?></th>
            <th style="padding:0"><?= invySortLink2('c.name',   'Color',  $sort, $order, $filters) ?></th>
            <th style="padding:0"><?= invySortLink2('s.name',   'Size',   $sort, $order, $filters) ?></th>
            <th style="padding:0"><?= invySortLink2('sp.current_qty',  'Qty',    $sort, $order, $filters) ?></th>
            <th style="padding:0"><?= invySortLink2('sp.stock_status', 'Status', $sort, $order, $filters) ?></th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="invTableBody">
          <?php foreach ($inventory as $item): ?>
          <?php
            $statusCls = match($item['stock_status'] ?? '') {
              'in_stock'    => 'badge-success',
              'low_stock'   => 'badge-warning',
              default       => 'badge-danger',
            };
            $statusLbl = str_replace('_', ' ', ucfirst($item['stock_status'] ?? 'out of stock'));
            $spId = (int)($item['id'] ?? 0);
          ?>
          <tr>
            <td><input type="checkbox" class="inv-select" value="<?= $spId ?>"
              data-name="<?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES) ?>"
              onchange="onRowSelect()"></td>
            <td><code style="font-size:.8rem"><?= htmlspecialchars($item['code'] ?? '-') ?></code></td>
            <td><strong><?= htmlspecialchars($item['name'] ?? '') ?></strong></td>
            <td><?= htmlspecialchars($item['type_name']  ?? '-') ?></td>
            <td><?= htmlspecialchars($item['color_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($item['size_name']  ?? '-') ?></td>
            <td><span class="badge <?= $statusCls ?>"><?= (int)$item['current_qty'] ?></span></td>
            <td><span class="badge <?= $statusCls ?>"><?= $statusLbl ?></span></td>
            <td>
              <button class="icon-btn" onclick="openRestock(<?= $spId ?>, '<?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES) ?>')" title="Restock">🔄</button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($inventory)): ?>
            <tr><td colspan="9" class="text-center text-muted" style="padding:48px">No inventory records found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php
      $invPqFilters = array_filter(['search' => $search, 'status' => $status, 'type_id' => $typeId, 'color_id' => $colorId, 'size_id' => $sizeId, 'sort' => $sort, 'order' => $order, 'tab' => 'inventory'], fn($v) => $v !== '');
      echo renderPagination($pagination, $invPqFilters);
    ?>
    </div><!-- /inventoryResultsContainer -->
  </div>
</div><!-- /tab-inventory -->

<!-- View Restock Order Modal -->
<div class="modal-overlay" id="viewRestockModal">
  <div class="modal-content" style="max-width:680px;max-height:90vh;overflow-y:auto">
    <div class="modal-header" style="position:sticky;top:0;background:#fff;z-index:1">
      <h2 class="modal-title" id="viewRestockTitle">Restock Order</h2>
      <button class="modal-close" onclick="closeModal('viewRestockModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body" id="viewRestockBody">
      <div style="text-align:center;padding:48px"><span class="spinner"></span></div>
    </div>
    <div class="modal-footer" style="position:sticky;bottom:0;background:#fff;z-index:1">
      <button class="btn btn-secondary" onclick="closeModal('viewRestockModal')">Close</button>
    </div>
  </div>
</div>

<!-- ===== INVENTORY HISTORY TAB ===== -->
<div id="tab-history" style="display:<?= $activeTab === 'history' ? 'block' : 'none' ?>">
  <div class="card">
    <div class="card-body" style="padding:16px">
      <div class="filter-bar" style="flex-wrap:wrap;gap:8px">
        <div class="search-bar" style="flex:1;min-width:180px;max-width:260px">
          <?= icon('search', 16) ?> <input type="text" id="histSearchInput" placeholder="Search by code or name..." value="<?= htmlspecialchars($hist_filters['search'] ?? '') ?>" oninput="debouncedHistSearch()" style="width:100%">
        </div>
        <select id="histMovementFilter" class="form-select" style="width:150px;height:38px" onchange="applyHistFilters()">
          <option value="">All Movement Types</option>
          <option value="purchase"    <?= ($hist_filters['movement_type'] ?? '') === 'purchase'    ? 'selected' : '' ?>>Purchase / Restock</option>
          <option value="sale"        <?= ($hist_filters['movement_type'] ?? '') === 'sale'        ? 'selected' : '' ?>>Sale</option>
          <option value="adjustment"  <?= ($hist_filters['movement_type'] ?? '') === 'adjustment'  ? 'selected' : '' ?>>Adjustment</option>
          <option value="damage"      <?= ($hist_filters['movement_type'] ?? '') === 'damage'      ? 'selected' : '' ?>>Damage</option>
          <option value="loss"        <?= ($hist_filters['movement_type'] ?? '') === 'loss'        ? 'selected' : '' ?>>Loss</option>
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
            <th>Date</th>
            <th>Stock Product</th>
            <th>Type</th><th>Color</th><th>Size</th>
            <th>Movement Type</th>
            <th>Change</th>
            <th>Notes</th>
            <th>By</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history ?? [] as $h): ?>
          <?php
            $qty = (int)$h['quantity_change'];
            $mvCls = $qty > 0 ? 'badge-success' : 'badge-danger';
          ?>
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
            <tr><td colspan="9" class="text-center text-muted" style="padding:48px">No inventory history found</td></tr>
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

<!-- ===== TAB: RESTOCK ORDERS (read-only status) ===== -->
<div id="tab-restock" style="display:<?= $activeTab === 'restock' ? 'block' : 'none' ?>">
  <?php
    $rSort  = $restock_filters['restock_sort']  ?? 'ro.created_at';
    $rOrder = strtoupper($restock_filters['restock_order'] ?? 'DESC');
    function roSortLink(string $col, string $label, string $cs, string $co, array $rf): string {
        $next   = ($cs === $col && $co === 'ASC') ? 'DESC' : 'ASC';
        $params = array_filter(array_merge(['tab' => 'restock'], $rf, ['restock_sort' => $col, 'restock_order' => $next]), fn($v) => $v !== '');
        $arrow  = $cs === $col ? ($co === 'ASC' ? ' <span style="font-size:.8em">▲</span>' : ' <span style="font-size:.8em">▼</span>') : ' <span style="font-size:.8em;opacity:.5">⇅</span>';
        return '<a href="?' . http_build_query($params) . '" style="display:block;padding:10px 14px;color:inherit;text-decoration:none;white-space:nowrap">' . htmlspecialchars($label) . $arrow . '</a>';
    }
  ?>
  <div class="card">
    <div class="card-header"><h3 class="card-title">Restock Orders</h3></div>
    <?php if (!empty($restock_orders)): ?>
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr style="cursor:pointer">
            <th style="padding:0"><?= roSortLink('ro.order_number',    'Order #',         $rSort, $rOrder, $restock_filters) ?></th>
            <th style="padding:0"><?= roSortLink('ro.order_date',      'Date',            $rSort, $rOrder, $restock_filters) ?></th>
            <th style="padding:0"><?= roSortLink('ro.supplier_name',   'Supplier',        $rSort, $rOrder, $restock_filters) ?></th>
            <th>Items</th>
            <th style="padding:0"><?= roSortLink('ro.delivery_status', 'Delivery Status', $rSort, $rOrder, $restock_filters) ?></th>
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
            <td><code style="font-size:.8rem"><?= htmlspecialchars($ro['order_number'] ?? '-') ?></code></td>
            <td style="font-size:.85rem"><?= !empty($ro['order_date']) ? date('M d, Y', strtotime($ro['order_date'])) : '-' ?></td>
            <td><?= htmlspecialchars($ro['supplier_name'] ?? '-') ?></td>
            <td><span class="badge badge-gray"><?= (int)($ro['items_count'] ?? 0) ?></span></td>
            <td>
              <span style="display:inline-block;background-color:<?= $dsBg ?>;color:<?= $dsFg ?>;padding:3px 12px;border-radius:20px;font-size:.78rem;font-weight:600">
                <?= ucfirst($ds) ?>
              </span>
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

<!-- ===== RESTOCK MODAL (redesigned) ===== -->
<div class="modal-overlay" id="restockModal">
  <div class="modal-content" style="max-width:760px;max-height:90vh;overflow-y:auto">
    <div class="modal-header" style="position:sticky;top:0;background:#fff;z-index:1">
      <h2 class="modal-title">Create Restock Order</h2>
      <button class="modal-close" onclick="closeModal('restockModal')"><?= icon('close', 16) ?></button>
    </div>
    <div class="modal-body">

      <!-- SP Picker Section -->
      <div style="margin-bottom:16px">
        <div style="font-weight:600;font-size:.875rem;margin-bottom:8px">Select Stock Products to Restock</div>

        <!-- Search + Filters -->
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">
          <input type="text" id="restockSpSearch" class="form-input" placeholder="Search code or name..." style="flex:1;min-width:160px;height:34px" oninput="applyRestockFilter()">
          <select id="restockSpTypeFilter" class="form-select" style="width:130px;height:34px" onchange="applyRestockFilter()">
            <option value="">All Types</option>
            <?php foreach ($types ?? [] as $t): ?>
            <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <select id="restockSpColorFilter" class="form-select" style="width:120px;height:34px" onchange="applyRestockFilter()">
            <option value="">All Colors</option>
            <?php foreach ($colors ?? [] as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <select id="restockSpSizeFilter" class="form-select" style="width:110px;height:34px" onchange="applyRestockFilter()">
            <option value="">All Sizes</option>
            <?php foreach ($sizes ?? [] as $sz): ?>
            <option value="<?= (int)$sz['id'] ?>"><?= htmlspecialchars($sz['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- SP Picker Table -->
        <div style="max-height:280px;overflow-y:auto;border:1px solid var(--color-gray-100);border-radius:6px">
          <table class="data-table" style="margin:0;font-size:.82rem">
            <thead style="position:sticky;top:0;z-index:1">
              <tr>
                <th style="width:34px"><input type="checkbox" id="restockSelectAllSps" onchange="toggleRestockSelectAll(this)" title="Select all"></th>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th>Color</th>
                <th>Size</th>
                <th style="text-align:right">Stock Qty</th>
                <th style="width:90px">Restock Qty</th>
              </tr>
            </thead>
            <tbody id="restockSpPickerBody">
              <tr><td colspan="8" class="text-center text-muted" style="padding:24px"><span class="spinner"></span> Loading...</td></tr>
            </tbody>
          </table>
        </div>
        <div id="restockSelectedCount" style="margin-top:6px;font-size:.8rem;color:var(--color-gray-400)">0 item(s) selected</div>
      </div>

      <hr style="margin:14px 0">

      <!-- Order Details Section -->
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Supplier Name</label>
          <input type="text" id="restockSupplier" class="form-input" placeholder="Supplier name">
        </div>
        <div class="form-group">
          <label class="form-label">Delivery Date</label>
          <input type="date" id="restockDate" class="form-input">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Delivery Status</label>
          <select id="restockDeliveryStatus" class="form-select">
            <option value="ordered">Ordered</option>
            <option value="delivered">Delivered</option>
            <option value="incomplete">Incomplete</option>
            <option value="problematic">Problematic</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea id="restockNotes" class="form-textarea" style="min-height:38px"></textarea>
        </div>
      </div>
    </div>
    <div class="modal-footer" style="position:sticky;bottom:0;background:#fff;z-index:1">
      <button class="btn btn-secondary" onclick="closeModal('restockModal')">Cancel</button>
      <button class="btn btn-primary" onclick="confirmRestock()" id="restockBtn">Create Restock</button>
    </div>
  </div>
</div>

<!-- Action Confirm Modal -->
<div class="modal-overlay" id="invActionConfirmModal">
  <div class="modal-content" style="max-width:420px">
    <div class="modal-header">
      <h2 class="modal-title" id="invActionConfirmTitle">Confirm</h2>
      <button class="modal-close" onclick="closeModal('invActionConfirmModal')">✕</button>
    </div>
    <div class="modal-body"><p id="invActionConfirmMessage" style="margin:0"></p></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('invActionConfirmModal')">Cancel</button>
      <button class="btn btn-primary" id="invActionConfirmBtn">Confirm</button>
    </div>
  </div>
</div>

<script>
const csrfToken     = document.querySelector('meta[name="csrf-token"]')?.content || '';
const invBaseUrl    = '<?= htmlspecialchars(app_url('/inventory')) ?>';
let searchTimer     = null;
let histSearchTimer = null;

// All stock products loaded via AJAX for restock picker
let _allSPs       = [];
let _spSelections = new Map(); // id → qty
let _spLoaded     = false;

// ---- Tab switching ----
function switchTab(tab) {
  document.getElementById('tab-inventory').style.display = tab === 'inventory' ? 'block' : 'none';
  document.getElementById('tab-history').style.display   = tab === 'history'   ? 'block' : 'none';
  document.getElementById('tab-restock').style.display   = tab === 'restock'   ? 'block' : 'none';
  const tabs = { 'inventory': 'tab-inv-btn', 'history': 'tab-hist-btn', 'restock': 'tab-restock-btn' };
  const active  = 'var(--color-primary)';
  const inactive= 'var(--color-gray-500)';
  Object.entries(tabs).forEach(([t, btnId]) => {
    const b = document.getElementById(btnId);
    if (!b) return;
    b.style.borderBottomColor = t === tab ? active : 'transparent';
    b.style.color             = t === tab ? active : inactive;
  });
  history.replaceState({}, '', invBaseUrl + '?tab=' + tab);
}

// ---- Inventory filters ----
function debouncedSearch() { clearTimeout(searchTimer); searchTimer = setTimeout(applyInvFilters, 400); }

function applyInvFilters() {
  const search  = document.getElementById('invSearchInput').value;
  const status  = document.getElementById('invStatusFilter').value;
  const typeId  = document.getElementById('invTypeFilter').value;
  const colorId = document.getElementById('invColorFilter').value;
  const sizeId  = document.getElementById('invSizeFilter').value;
  const params  = new URLSearchParams({ tab: 'inventory' });
  if (search)  params.set('search',   search);
  if (status)  params.set('status',   status);
  if (typeId)  params.set('type_id',  typeId);
  if (colorId) params.set('color_id', colorId);
  if (sizeId)  params.set('size_id',  sizeId);
  const url = new URL(window.location.href);
  if (url.searchParams.get('sort'))  params.set('sort',  url.searchParams.get('sort'));
  if (url.searchParams.get('order')) params.set('order', url.searchParams.get('order'));
  const pageUrl = window.location.origin + invBaseUrl + '?' + params.toString();
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
  document.getElementById('invSearchInput').value  = '';
  document.getElementById('invStatusFilter').value = '';
  document.getElementById('invTypeFilter').value   = '';
  document.getElementById('invColorFilter').value  = '';
  document.getElementById('invSizeFilter').value   = '';
  applyInvFilters();
}

// ---- History filters ----
function debouncedHistSearch() { clearTimeout(histSearchTimer); histSearchTimer = setTimeout(applyHistFilters, 400); }

function applyHistFilters() {
  const search    = document.getElementById('histSearchInput').value;
  const movement  = document.getElementById('histMovementFilter').value;
  const typeId    = document.getElementById('histTypeIdFilter').value;
  const colorId   = document.getElementById('histColorIdFilter').value;
  const sizeId    = document.getElementById('histSizeIdFilter').value;
  const createdBy = document.getElementById('histByFilter').value;
  const dateFrom  = document.getElementById('histDateFrom').value;
  const dateTo    = document.getElementById('histDateTo').value;
  const params = new URLSearchParams({ tab: 'history' });
  if (search)    params.set('history_search',  search);
  if (movement)  params.set('movement_type',   movement);
  if (typeId)    params.set('hist_type_id',    typeId);
  if (colorId)   params.set('hist_color_id',   colorId);
  if (sizeId)    params.set('hist_size_id',    sizeId);
  if (createdBy) params.set('hist_created_by', createdBy);
  if (dateFrom)  params.set('hist_date_from',  dateFrom);
  if (dateTo)    params.set('hist_date_to',    dateTo);
  const pageUrl = window.location.origin + invBaseUrl + '?' + params.toString();
  history.pushState({}, '', pageUrl);
  const container = document.getElementById('historyResultsContainer');
  if (container) container.style.opacity = '0.5';
  fetch(pageUrl).then(r => r.text()).then(html => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const el  = doc.getElementById('historyResultsContainer');
    if (el && container) { container.innerHTML = el.innerHTML; container.style.opacity = '1'; }
  }).catch(() => { if (container) container.style.opacity = '1'; });
}

function resetHistFilters() {
  document.getElementById('histSearchInput').value   = '';
  document.getElementById('histMovementFilter').value = '';
  document.getElementById('histTypeIdFilter').value   = '';
  document.getElementById('histColorIdFilter').value  = '';
  document.getElementById('histSizeIdFilter').value   = '';
  document.getElementById('histByFilter').value       = '';
  document.getElementById('histDateFrom').value       = '';
  document.getElementById('histDateTo').value         = '';
  applyHistFilters();
}

// ---- Multi-select (inventory table) ----
function onRowSelect() {
  const checked = document.querySelectorAll('.inv-select:checked');
  const bar     = document.getElementById('restockSelectionBar');
  if (checked.length > 0) {
    document.getElementById('restockSelectionCount').textContent = checked.length + ' selected';
    bar.style.display = 'flex';
  } else {
    bar.style.display = 'none';
  }
  const all  = document.querySelectorAll('.inv-select');
  const sa   = document.getElementById('selectAllInv');
  if (sa) { sa.indeterminate = checked.length > 0 && checked.length < all.length; sa.checked = checked.length === all.length; }
}

function toggleSelectAll(cb) {
  document.querySelectorAll('.inv-select').forEach(c => c.checked = cb.checked);
  onRowSelect();
}

function clearSelection() {
  document.querySelectorAll('.inv-select').forEach(c => c.checked = false);
  const sa = document.getElementById('selectAllInv');
  if (sa) { sa.checked = false; sa.indeterminate = false; }
  document.getElementById('restockSelectionBar').style.display = 'none';
}

function openRestockFromSelection() {
  const checked = document.querySelectorAll('.inv-select:checked');
  const preIds  = Array.from(checked).map(cb => parseInt(cb.value, 10));
  openRestock(0, '', preIds);
}

// ---- Restock SP Picker ----
async function loadAllSPs() {
  if (_spLoaded) return;
  try {
    const res  = await fetch('/api/v1/stock-products/all');
    const data = await res.json();
    if (data.success) { _allSPs = data.data?.stock_products || []; _spLoaded = true; }
  } catch (e) { /* silently fail */ }
}

// ---- Restock delivery status view ----
// (Status is read-only on the inventory page — changes are made on the stock-products page)

async function viewRestockOrder(id) {
  document.getElementById('viewRestockTitle').textContent = 'Restock Order';
  document.getElementById('viewRestockBody').innerHTML = '<div style="text-align:center;padding:48px"><span class="spinner"></span></div>';
  openModal('viewRestockModal');
  try {
    const res  = await fetch('/api/v1/inventory/restock/' + id);
    const data = await res.json();
    if (!data.success) { document.getElementById('viewRestockBody').innerHTML = '<p class="text-danger">Failed to load order.</p>'; return; }
    const ro = data.data.restock;
    document.getElementById('viewRestockTitle').textContent = 'Restock Order ' + (ro.order_number || '');
    const dsColors = { ordered:'#dbeafe', delivered:'#d1fae5', incomplete:'#fef3c7', problematic:'#fee2e2' };
    const dsText   = { ordered:'#1e40af', delivered:'#065f46', incomplete:'#92400e', problematic:'#991b1b' };
    const dsBg = dsColors[ro.delivery_status] || '#f3f4f6';
    const dsFg = dsText[ro.delivery_status]   || '#374151';
    const statusPill = `<span style="display:inline-block;padding:2px 12px;border-radius:20px;font-size:.8rem;font-weight:600;background:${dsBg};color:${dsFg}">${ro.delivery_status || '-'}</span>`;

    let itemsHtml = '';
    if (ro.items && ro.items.length > 0) {
      itemsHtml = `<div style="margin-top:16px">
        <div style="font-size:.75rem;color:var(--color-gray-400);text-transform:uppercase;font-weight:600;margin-bottom:6px">Stock Products</div>
        <table style="width:100%;border-collapse:collapse;font-size:.83rem">
          <thead><tr style="background:var(--color-gray-50)">
            <th style="padding:7px 10px;text-align:left;border-bottom:1px solid var(--color-gray-100)">Code</th>
            <th style="padding:7px 10px;text-align:left;border-bottom:1px solid var(--color-gray-100)">Name</th>
            <th style="padding:7px 10px;text-align:left;border-bottom:1px solid var(--color-gray-100)">Type / Color / Size</th>
            <th style="padding:7px 10px;text-align:right;border-bottom:1px solid var(--color-gray-100)">Requested</th>
            <th style="padding:7px 10px;text-align:right;border-bottom:1px solid var(--color-gray-100)">Received</th>
          </tr></thead><tbody>
          ${ro.items.map(it => `<tr>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50)"><code style="font-size:.78rem">${esc(it.code)}</code></td>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50)">${esc(it.name)}</td>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50);font-size:.8rem;color:var(--color-gray-500)">${[it.type_name, it.color_name, it.size_name].filter(Boolean).join(' / ') || '-'}</td>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50);text-align:right"><strong>${parseInt(it.quantity_requested || 0)}</strong></td>
            <td style="padding:6px 10px;border-bottom:1px solid var(--color-gray-50);text-align:right">${parseInt(it.quantity_received || 0)}</td>
          </tr>`).join('')}
          </tbody>
        </table>
      </div>`;
    } else {
      itemsHtml = `<div style="margin-top:12px;padding:10px;background:var(--color-gray-50);border-radius:6px;font-size:.82rem;color:var(--color-gray-400)">No items in this order.</div>`;
    }

    document.getElementById('viewRestockBody').innerHTML = `
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 20px;margin-bottom:12px;font-size:.87rem">
        <div><span style="color:var(--color-gray-400);font-size:.75rem;text-transform:uppercase;font-weight:600">Order #</span><div style="margin-top:2px;font-weight:600">${esc(ro.order_number)}</div></div>
        <div><span style="color:var(--color-gray-400);font-size:.75rem;text-transform:uppercase;font-weight:600">Date</span><div style="margin-top:2px">${ro.order_date ? new Date(ro.order_date).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}) : '-'}</div></div>
        <div><span style="color:var(--color-gray-400);font-size:.75rem;text-transform:uppercase;font-weight:600">Supplier</span><div style="margin-top:2px">${esc(ro.supplier_name || '-')}</div></div>
        <div><span style="color:var(--color-gray-400);font-size:.75rem;text-transform:uppercase;font-weight:600">Delivery Status</span><div style="margin-top:4px">${statusPill}</div></div>
        <div><span style="color:var(--color-gray-400);font-size:.75rem;text-transform:uppercase;font-weight:600">Created By</span><div style="margin-top:2px">${esc(ro.created_by_name || '-')}</div></div>
        ${ro.notes ? `<div style="grid-column:1/-1"><span style="color:var(--color-gray-400);font-size:.75rem;text-transform:uppercase;font-weight:600">Notes</span><div style="margin-top:2px">${esc(ro.notes)}</div></div>` : ''}
      </div>
      ${itemsHtml}`;
  } catch (e) {
    document.getElementById('viewRestockBody').innerHTML = '<p class="text-danger">Network error.</p>';
  }
}

function filterRestockPicker() {
  const search = (document.getElementById('restockSpSearch')?.value || '').toLowerCase();
  const typeId = parseInt(document.getElementById('restockSpTypeFilter')?.value || '0', 10) || 0;
  const colId  = parseInt(document.getElementById('restockSpColorFilter')?.value || '0', 10) || 0;
  const sizId  = parseInt(document.getElementById('restockSpSizeFilter')?.value || '0', 10) || 0;

  return _allSPs.filter(sp => {
    if (typeId && parseInt(sp.type_id)  !== typeId) return false;
    if (colId  && parseInt(sp.color_id) !== colId)  return false;
    if (sizId  && parseInt(sp.size_id)  !== sizId)  return false;
    if (search && !sp.code.toLowerCase().includes(search) && !sp.name.toLowerCase().includes(search)) return false;
    return true;
  });
}

function applyRestockFilter() {
  renderRestockPicker(filterRestockPicker());
}

function esc(str) {
  const d = document.createElement('span');
  d.textContent = str || '';
  return d.innerHTML;
}

function renderRestockPicker(filtered) {
  const tbody = document.getElementById('restockSpPickerBody');
  if (!tbody) return;
  if (!filtered.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted" style="padding:24px">No stock products found</td></tr>';
    updateRestockSelectedCount();
    return;
  }
  tbody.innerHTML = filtered.map(sp => {
    const sel = _spSelections.has(sp.id);
    const qty = sel ? _spSelections.get(sp.id) : 1;
    const qtyLow = sp.current_qty <= (sp.low_stock_alert || 10);
    return `<tr>
      <td><input type="checkbox" class="restock-sp-cb" data-id="${sp.id}" ${sel ? 'checked' : ''} onchange="onRestockSpCheck(this, ${sp.id})"></td>
      <td><code style="font-size:.78rem">${esc(sp.code)}</code></td>
      <td>${esc(sp.name)}</td>
      <td style="font-size:.8rem">${esc(sp.type_name || '-')}</td>
      <td style="font-size:.8rem">${esc(sp.color_name || '-')}</td>
      <td style="font-size:.8rem">${esc(sp.size_name || '-')}</td>
      <td style="text-align:right"><span class="badge ${qtyLow ? 'badge-warning' : 'badge-gray'}">${sp.current_qty}</span></td>
      <td><input type="number" class="form-input restock-sp-qty" data-id="${sp.id}" min="1" value="${qty}" style="height:30px;width:72px" oninput="onRestockQtyChange(this, ${sp.id})" ${!sel ? 'disabled' : ''}></td>
    </tr>`;
  }).join('');
  updateRestockSelectedCount();
}

function onRestockSpCheck(cb, id) {
  if (cb.checked) {
    const qtyInput = cb.closest('tr')?.querySelector('.restock-sp-qty');
    const qty = qtyInput ? parseInt(qtyInput.value, 10) : 1;
    _spSelections.set(id, qty > 0 ? qty : 1);
    if (qtyInput) qtyInput.disabled = false;
  } else {
    _spSelections.delete(id);
    const qtyInput = cb.closest('tr')?.querySelector('.restock-sp-qty');
    if (qtyInput) qtyInput.disabled = true;
  }
  updateRestockSelectedCount();
  updateRestockSelectAllState();
}

function onRestockQtyChange(input, id) {
  const qty = parseInt(input.value, 10);
  if (_spSelections.has(id) && qty > 0) _spSelections.set(id, qty);
}

function toggleRestockSelectAll(cb) {
  const filtered = filterRestockPicker();
  if (cb.checked) {
    filtered.forEach(sp => { if (!_spSelections.has(sp.id)) _spSelections.set(sp.id, 1); });
  } else {
    filtered.forEach(sp => _spSelections.delete(sp.id));
  }
  renderRestockPicker(filtered);
}

function updateRestockSelectAllState() {
  const filtered = filterRestockPicker();
  const allChecked  = filtered.length > 0 && filtered.every(sp => _spSelections.has(sp.id));
  const someChecked = filtered.some(sp => _spSelections.has(sp.id));
  const cb = document.getElementById('restockSelectAllSps');
  if (cb) { cb.checked = allChecked; cb.indeterminate = !allChecked && someChecked; }
}

function updateRestockSelectedCount() {
  const el = document.getElementById('restockSelectedCount');
  if (el) el.textContent = `${_spSelections.size} item(s) selected`;
  updateRestockSelectAllState();
}

async function openRestock(stockProductId = 0, name = '', preIds = []) {
  _spSelections = new Map();
  preIds.forEach(id => _spSelections.set(id, 1));
  if (stockProductId > 0) _spSelections.set(stockProductId, 1);

  document.getElementById('restockSupplier').value = '';
  document.getElementById('restockDate').value     = '';
  document.getElementById('restockDeliveryStatus').value = 'ordered';
  document.getElementById('restockNotes').value    = '';
  document.getElementById('restockSpSearch').value  = '';
  document.getElementById('restockSpTypeFilter').value  = '';
  document.getElementById('restockSpColorFilter').value = '';
  document.getElementById('restockSpSizeFilter').value  = '';

  openModal('restockModal');

  if (!_spLoaded) {
    document.getElementById('restockSpPickerBody').innerHTML =
      '<tr><td colspan="8" class="text-center text-muted" style="padding:24px"><span class="spinner"></span> Loading...</td></tr>';
    await loadAllSPs();
  }
  renderRestockPicker(filterRestockPicker());
}

// ---- Confirm action helper ----
function invConfirmAction(title, message, onConfirm) {
  document.getElementById('invActionConfirmTitle').textContent   = title;
  document.getElementById('invActionConfirmMessage').textContent = message;
  document.getElementById('invActionConfirmBtn').onclick = () => { closeModal('invActionConfirmModal'); onConfirm(); };
  openModal('invActionConfirmModal');
}

function confirmRestock() {
  const count = _spSelections.size;
  if (count === 0) return showToast('Select at least one stock product', 'error');
  invConfirmAction('Create Restock Order', `Create a restock order with ${count} item(s)?`, submitRestock);
}

async function submitRestock() {
  if (_spSelections.size === 0) return showToast('Select at least one stock product', 'error');

  const items = [];
  _spSelections.forEach((qty, id) => {
    if (qty > 0) items.push({ stock_product_id: id, quantity_requested: qty });
  });
  if (items.length === 0) return showToast('Add at least one item', 'error');

  const btn = document.getElementById('restockBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving...';

  const payload = {
    supplier_name:    document.getElementById('restockSupplier').value,
    delivery_date:    document.getElementById('restockDate').value,
    delivery_status:  document.getElementById('restockDeliveryStatus').value,
    notes:            document.getElementById('restockNotes').value,
    items,
  };

  try {
    const res  = await fetch('/api/v1/inventory/restock', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body:    JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) {
      showToast('Restock order created', 'success');
      closeModal('restockModal');
      clearSelection();
      setTimeout(() => location.reload(), 800);
    } else showToast(data.message || 'Failed', 'error');
  } catch (e) { showToast('Network error', 'error'); }

  btn.disabled = false; btn.innerHTML = 'Create Restock';
}
</script>

<?php
$content = ob_get_clean();
$title   = 'Inventory | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
