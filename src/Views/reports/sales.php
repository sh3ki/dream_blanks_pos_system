<?php ob_start(); ?>
<?php
$from = $from ?? date('Y-m-01');
$to   = $to ?? date('Y-m-d');
$exportUrl = '/api/v1/reports/export?type=sales&date_from=' . urlencode($from) . '&date_to=' . urlencode($to);
?>
<div class="page-header">
  <h1>Sales Report</h1>
  <?php if (can('reports_sales', 'export')): ?>
  <a href="<?= $exportUrl ?>" class="btn btn-secondary">⬇ Export CSV</a>
  <?php endif; ?>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-body" style="padding:16px">
    <form method="GET" class="d-flex gap-8" style="flex-wrap:wrap">
      <div class="form-group">
        <label class="form-label">Date From</label>
        <input type="date" name="date_from" class="form-input" value="<?= htmlspecialchars($from) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Date To</label>
        <input type="date" name="date_to" class="form-input" value="<?= htmlspecialchars($to) ?>">
      </div>
      <div class="form-group" style="align-self:end">
        <button class="btn btn-primary">Apply</button>
      </div>
    </form>
  </div>
</div>

<div class="stats-grid">
  <div class="stats-card">
    <div class="stats-header"><span class="stats-label">Total Sales</span><span class="stats-icon"><?= icon('money', 20) ?></span></div>
    <div class="stats-value">₱<?= number_format($report['total_sales'] ?? 0, 2) ?></div>
    <div class="stats-change text-muted">Selected period</div>
  </div>
  <div class="stats-card">
    <div class="stats-header"><span class="stats-label">Transactions</span><span class="stats-icon"><?= icon('clipboard', 20) ?></span></div>
    <div class="stats-value"><?= $report['transaction_count'] ?? 0 ?></div>
    <div class="stats-change text-muted">Total invoices</div>
  </div>
  <div class="stats-card">
    <div class="stats-header"><span class="stats-label">Avg. Transaction</span><span class="stats-icon"><?= icon('chart-line', 20) ?></span></div>
    <div class="stats-value">₱<?= number_format($report['average_transaction'] ?? 0, 2) ?></div>
    <div class="stats-change text-muted">Per invoice</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
  <div class="card">
    <div class="card-header"><h3 class="card-title">Sales by Payment Mode</h3></div>
    <div class="card-body">
      <?php if (!empty($report['sales_by_mode'])): ?>
        <div class="table-wrapper">
          <table class="data-table">
            <thead><tr><th>Mode</th><th>Amount</th></tr></thead>
            <tbody>
              <?php foreach ($report['sales_by_mode'] as $mode => $amount): ?>
                <tr><td><?= strtoupper(htmlspecialchars($mode)) ?></td><td>₱<?= number_format($amount, 2) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-muted">No data available.</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3 class="card-title">Top Products</h3></div>
    <div class="card-body">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Product</th><th>Qty</th><th>Revenue</th></tr></thead>
          <tbody>
            <?php foreach (($report['top_products'] ?? []) as $p): ?>
              <tr>
                <td><?= htmlspecialchars($p['name'] ?? '') ?></td>
                <td><?= (int)($p['total_qty'] ?? 0) ?></td>
                <td>₱<?= number_format($p['total_revenue'] ?? 0, 2) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($report['top_products'])): ?>
              <tr><td colspan="3" class="text-center text-muted" style="padding:24px">No products found</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
$title   = 'Sales Report | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
