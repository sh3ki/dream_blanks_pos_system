<?php ob_start(); ?>

<div class="page-header">
  <h1>Dashboard</h1>
  <div>
    <span class="text-muted" style="font-size:.875rem;display:flex;align-items:center;gap:6px"><?= icon('calendar', 15) ?> <?= date('F d, Y') ?></span>
  </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
  <div class="stats-card">
    <div class="stats-header">
      <span class="stats-label">Sales Today</span>
      <span class="stats-icon"><?= icon('money', 20) ?></span>
    </div>
    <div class="stats-value">₱<?= number_format($metrics['total_sales_today'] ?? 0, 2) ?></div>
    <div class="stats-change text-muted">Today</div>
  </div>
  <div class="stats-card">
    <div class="stats-header">
      <span class="stats-label">Sales This Week</span>
      <span class="stats-icon"><?= icon('chart-line', 20) ?></span>
    </div>
    <div class="stats-value">₱<?= number_format($metrics['total_sales_week'] ?? 0, 2) ?></div>
    <div class="stats-change text-muted">This Week</div>
  </div>
  <div class="stats-card">
    <div class="stats-header">
      <span class="stats-label">Sales This Month</span>
      <span class="stats-icon"><?= icon('chart-bar', 20) ?></span>
    </div>
    <div class="stats-value">₱<?= number_format($metrics['total_sales_month'] ?? 0, 2) ?></div>
    <div class="stats-change text-muted">This Month</div>
  </div>
  <div class="stats-card">
    <div class="stats-header">
      <span class="stats-label">Outstanding</span>
      <span class="stats-icon"><?= icon('alert', 20) ?></span>
    </div>
    <div class="stats-value" style="color:var(--color-danger)">₱<?= number_format($metrics['outstanding_receivables'] ?? 0, 2) ?></div>
    <div class="stats-change text-muted">Receivables</div>
  </div>
  <div class="stats-card">
    <div class="stats-header">
      <span class="stats-label">Low Stock Items</span>
      <span class="stats-icon"><?= icon('package', 20) ?></span>
    </div>
    <div class="stats-value" style="color:var(--color-warning)"><?= $metrics['low_stock_items'] ?? 0 ?></div>
    <div class="stats-change"><a href="/inventory?status=low_stock">View items →</a></div>
  </div>
  <div class="stats-card">
    <div class="stats-header">
      <span class="stats-label">Pending Restocks</span>
      <span class="stats-icon"><?= icon('refresh', 20) ?></span>
    </div>
    <div class="stats-value"><?= $metrics['pending_restocks'] ?? 0 ?></div>
    <div class="stats-change"><a href="/inventory">View orders →</a></div>
  </div>
</div>

<!-- Charts Row -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Sales Trend</h3>
      <select id="periodSelect" class="form-select" style="width:auto;height:32px;padding:4px 10px;font-size:.8rem" onchange="loadChart(this.value)">
        <option value="week">Last 7 Days</option>
        <option value="month">Last 30 Days</option>
      </select>
    </div>
    <div class="card-body">
      <canvas id="salesChart" height="80"></canvas>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3 class="card-title">Payment Modes</h3></div>
    <div class="card-body">
      <canvas id="paymentChart" height="160"></canvas>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:24px">
  <a href="/pos" class="card quick-action-card" style="padding:20px;text-align:center;display:block;text-decoration:none;transition:all .25s ease">
    <div class="quick-action-icon" style="color:var(--color-primary)"><?= icon('pos', 32) ?></div>
    <div style="font-weight:600">New Sale</div>
    <div style="font-size:.8rem;color:var(--color-gray-500)">Open POS</div>
  </a>
  <a href="/clients" class="card quick-action-card" style="padding:20px;text-align:center;display:block;text-decoration:none;transition:all .25s ease">
    <div class="quick-action-icon" style="color:var(--color-success)"><?= icon('clients', 32) ?></div>
    <div style="font-weight:600">Clients</div>
    <div style="font-size:.8rem;color:var(--color-gray-500)">Manage clients</div>
  </a>
  <a href="/products" class="card quick-action-card" style="padding:20px;text-align:center;display:block;text-decoration:none;transition:all .25s ease">
    <div class="quick-action-icon" style="color:var(--color-warning)"><?= icon('products', 32) ?></div>
    <div style="font-weight:600">Products</div>
    <div style="font-size:.8rem;color:var(--color-gray-500)">Manage products</div>
  </a>
  <a href="/reports/sales" class="card quick-action-card" style="padding:20px;text-align:center;display:block;text-decoration:none;transition:all .25s ease">
    <div class="quick-action-icon" style="color:var(--color-info)"><?= icon('chart-bar', 32) ?></div>
    <div style="font-weight:600">Reports</div>
    <div style="font-size:.8rem;color:var(--color-gray-500)">View analytics</div>
  </a>
</div>

<script>
const chartData = <?= json_encode($charts ?? []) ?>;

function loadChart(period) {
  fetch('/api/v1/dashboard/charts?period=' + period)
    .then(r => r.json())
    .then(res => {
      if (res.success) renderSalesChart(res.data.sales_trend);
    });
}

function renderSalesChart(trend) {
  const ctx = document.getElementById('salesChart');
  if (window.salesChartInst) window.salesChartInst.destroy();
  window.salesChartInst = new Chart(ctx, {
    type: 'line',
    data: {
      labels: trend.labels,
      datasets: [{
        label: 'Sales (₱)',
        data: trend.data,
        borderColor: '#0056B3',
        backgroundColor: 'rgba(0,86,179,0.08)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#0056B3',
        pointRadius: 4,
      }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });
}

function renderPaymentChart(modes) {
  const ctx = document.getElementById('paymentChart');
  const labels = modes.map(m => (m.mode || 'N/A').toUpperCase());
  const data   = modes.map(m => m.cnt);
  if (window.payChartInst) window.payChartInst.destroy();
  window.payChartInst = new Chart(ctx, {
    type: 'doughnut',
    data: { labels, datasets: [{ data, backgroundColor: ['#0056B3','#28A745','#00C9FF'] }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
  });
}

// Initial render
if (chartData.sales_trend) renderSalesChart(chartData.sales_trend);
if (chartData.payment_modes) renderPaymentChart(chartData.payment_modes);
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

<?php
$content = ob_get_clean();
$title   = 'Dashboard | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
