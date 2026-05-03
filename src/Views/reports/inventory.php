<?php ob_start(); ?>
<?php
$summary = $report['summary'] ?? [];
$valuation = $report['valuation'] ?? [];
$exportUrl = '/api/v1/reports/export?type=inventory';
?>
<div class="page-header">
  <h1>Inventory Report</h1>
  <a href="<?= $exportUrl ?>" class="btn btn-secondary">⬇ Export CSV</a>
</div>

<div class="stats-grid">
  <div class="stats-card">
    <div class="stats-header"><span class="stats-label">Total Products</span><span class="stats-icon"><?= icon('package', 20) ?></span></div>
    <div class="stats-value"><?= (int)($summary['total_products'] ?? 0) ?></div>
    <div class="stats-change text-muted">All items</div>
  </div>
  <div class="stats-card">
    <div class="stats-header"><span class="stats-label">In Stock</span><span class="stats-icon"><?= icon('check', 20) ?></span></div>
    <div class="stats-value"><?= (int)($summary['in_stock'] ?? 0) ?></div>
    <div class="stats-change text-muted">Available</div>
  </div>
  <div class="stats-card">
    <div class="stats-header"><span class="stats-label">Low Stock</span><span class="stats-icon"><?= icon('alert', 20) ?></span></div>
    <div class="stats-value" style="color:var(--color-warning)"><?= (int)($summary['low_stock'] ?? 0) ?></div>
    <div class="stats-change text-muted">Needs restock</div>
  </div>
  <div class="stats-card">
    <div class="stats-header"><span class="stats-label">Out of Stock</span><span class="stats-icon"><?= icon('close', 20) ?></span></div>
    <div class="stats-value" style="color:var(--color-danger)"><?= (int)($summary['out_of_stock'] ?? 0) ?></div>
    <div class="stats-change text-muted">Unavailable</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
  <div class="card">
    <div class="card-header"><h3 class="card-title">Inventory Valuation</h3></div>
    <div class="card-body">
      <div class="summary-row"><span>Cost Value</span><span>₱<?= number_format($valuation['cost_value'] ?? 0, 2) ?></span></div>
      <div class="summary-row"><span>Selling Value</span><span>₱<?= number_format($valuation['selling_value'] ?? 0, 2) ?></span></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3 class="card-title">Low Stock Items</h3></div>
    <div class="card-body">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Alert</th></tr></thead>
          <tbody>
            <?php foreach (($report['low_stock_items'] ?? []) as $item): ?>
              <tr>
                <td><?= htmlspecialchars($item['name'] ?? '') ?></td>
                <td><?= htmlspecialchars($item['sku'] ?? '') ?></td>
                <td><span class="badge badge-danger"><?= (int)($item['quantity_on_hand'] ?? 0) ?></span></td>
                <td><?= (int)($item['low_stock_alert'] ?? 0) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($report['low_stock_items'])): ?>
              <tr><td colspan="4" class="text-center text-muted" style="padding:24px">No low stock items</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
$title   = 'Inventory Report | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
