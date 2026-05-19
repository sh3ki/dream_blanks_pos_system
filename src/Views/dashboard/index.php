<?php ob_start(); ?>

<style>
/* ── Dashboard-specific styles ── */
.dash-kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}
.dash-kpi-card {
  background: #fff;
  border-radius: 10px;
  padding: 18px 20px 14px;
  box-shadow: var(--shadow-sm);
  border-left: 4px solid var(--kpi-accent, #0056B3);
  display: flex;
  flex-direction: column;
  gap: 5px;
  transition: box-shadow .2s, transform .2s;
}
.dash-kpi-card:hover { box-shadow: var(--shadow); transform: translateY(-2px); }
.dash-kpi-label {
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--color-gray-500);
  display: flex;
  align-items: center;
  gap: 4px;
}
.dash-kpi-value {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--color-dark-gray);
  line-height: 1.1;
}
.dash-kpi-sub {
  font-size: .76rem;
  color: var(--color-gray-500);
  display: flex;
  align-items: center;
  gap: 4px;
}
.dash-kpi-sub .pos { color: var(--color-success); font-weight: 700; }
.dash-kpi-sub .neg { color: var(--color-danger);  font-weight: 700; }
.dash-row { display: grid; gap: 18px; margin-bottom: 18px; }
.dash-row.c21  { grid-template-columns: 2fr 1fr; }
.dash-row.c12  { grid-template-columns: 1fr 2fr; }
.dash-row.c11  { grid-template-columns: 1fr 1fr; }
.dash-row.c111 { grid-template-columns: 1fr 1fr 1fr; }
.dash-card {
  background: #fff;
  border-radius: 10px;
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.dash-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px 0;
  flex-shrink: 0;
}
.dash-card-title { font-size: .88rem; font-weight: 700; color: var(--color-dark-gray); }
.dash-card-body { padding: 10px 16px 16px; flex: 1; }
.dash-card-body.center { display: flex; align-items: center; justify-content: center; }
.dash-chart-wrap { max-width: 280px; width: 100%; margin: auto; }
.quick-links { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
.quick-link {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 14px; background: #fff;
  border: 1px solid var(--color-gray-100); border-radius: 8px;
  font-size: .8rem; font-weight: 600; color: var(--color-dark-gray);
  text-decoration: none; transition: all .18s;
}
.quick-link:hover {
  background: var(--color-primary-light); color: var(--color-primary);
  border-color: var(--color-primary); text-decoration: none;
}
.dash-tbl { width: 100%; border-collapse: collapse; font-size: .81rem; }
.dash-tbl th {
  background: #f8f9fa; font-size: .69rem; text-transform: uppercase;
  letter-spacing: .05em; padding: 7px 12px; color: var(--color-gray-500);
  font-weight: 600; border-bottom: 1px solid var(--color-gray-100); text-align: left;
}
.dash-tbl td {
  padding: 8px 12px; border-bottom: 1px solid var(--color-gray-100);
  color: var(--color-dark-gray); vertical-align: middle;
}
.dash-tbl tr:last-child td { border-bottom: none; }
.dash-tbl tr:hover td { background: #f8f9fa; }
.ds-badge {
  display: inline-flex; align-items: center;
  padding: 2px 8px; border-radius: 20px;
  font-size: .69rem; font-weight: 700; white-space: nowrap;
}
.ds-b-paid    { background: var(--color-success-light); color: var(--color-success); }
.ds-b-partial { background: var(--color-warning-light); color: #856404; }
.ds-b-unpaid  { background: var(--color-danger-light);  color: var(--color-danger); }
.ds-b-low     { background: var(--color-warning-light); color: #856404; }
.ds-b-out     { background: var(--color-danger-light);  color: var(--color-danger); }
.dash-section-label {
  font-size: .72rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .07em; color: var(--color-gray-500);
  margin: 6px 0 10px; display: flex; align-items: center; gap: 8px;
}
.dash-section-label::after { content: ''; flex: 1; height: 1px; background: var(--color-gray-100); }
.dash-filter-bar { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.dash-fb-btn {
  padding:5px 14px; border:1px solid var(--color-gray-100); border-radius:6px;
  background:#fff; font-size:.78rem; font-weight:600; cursor:pointer;
  color:var(--color-dark-gray); transition:all .15s; height:32px; line-height:1;
}
.dash-fb-btn:hover { border-color:var(--color-primary); color:var(--color-primary); }
.dash-fb-btn.active { background:var(--color-primary); color:#fff; border-color:var(--color-primary); }
.dash-fb-sep { width:1px; height:20px; background:var(--color-gray-200); margin:0 2px; flex-shrink:0; }
.dash-fb-label { font-size:.73rem; font-weight:600; color:var(--color-gray-500); white-space:nowrap; }
.dash-fb-date {
  height:32px; padding:4px 8px; font-size:.78rem;
  border:1px solid var(--color-gray-200); border-radius:6px;
  background:#fff; color:var(--color-dark-gray);
}
.dash-fb-apply { height:32px; padding:0 16px; font-size:.78rem; font-weight:600; }
.dash-fb-range-label { font-size:.73rem; color:var(--color-gray-500); font-style:italic; }
@media (max-width: 1150px) {
  .dash-kpi-grid { grid-template-columns: repeat(2, 1fr); }
  .dash-row.c21,.dash-row.c12,.dash-row.c11,.dash-row.c111 { grid-template-columns: 1fr; }
}
@media (max-width: 600px) { .dash-kpi-grid { grid-template-columns: 1fr; } }
</style>

<?php
$todayDelta = ''; $todayClass = '';
$yesterday  = $metrics['total_sales_yesterday'] ?? 0;
if ($yesterday > 0) {
    $pct = (($metrics['total_sales_today'] - $yesterday) / $yesterday) * 100;
    $todayClass = $pct >= 0 ? 'pos' : 'neg';
    $todayDelta = ($pct >= 0 ? '▲ ' : '▼ ') . abs(round($pct, 1)) . '% vs yesterday';
} else {
    $todayDelta = $metrics['total_sales_today'] > 0 ? 'No sales yesterday to compare' : 'No sales yet today';
}
$monthDelta = ''; $monthClass = '';
$lastMonth  = $metrics['total_sales_last_month'] ?? 0;
if ($lastMonth > 0) {
    $pct = (($metrics['total_sales_month'] - $lastMonth) / $lastMonth) * 100;
    $monthClass = $pct >= 0 ? 'pos' : 'neg';
    $monthDelta = ($pct >= 0 ? '▲ ' : '▼ ') . abs(round($pct, 1)) . '% vs last month';
} else {
    $monthDelta = 'No prior month data';
}
$collectionRate = ($metrics['total_sales_month'] ?? 0) > 0
    ? round(($metrics['collected_month'] / $metrics['total_sales_month']) * 100, 1) : 0;
?>

<div class="page-header" style="margin-bottom:16px">
  <div>
    <h1 style="margin:0 0 2px">Dashboard</h1>
    <span style="color:var(--color-gray-500);font-size:.82rem"><?php echo date('l, F d, Y'); ?></span>
  </div>
  <div class="dash-filter-bar">
    <button class="dash-fb-btn active" id="fbWeek" onclick="applyPreset('week')">Last 7 Days</button>
    <button class="dash-fb-btn" id="fbMonth" onclick="applyPreset('month')">Last 30 Days</button>
    <span class="dash-fb-sep"></span>
    <label class="dash-fb-label">From</label>
    <input type="date" id="filterFrom" class="dash-fb-date" max="<?php echo date('Y-m-d'); ?>">
    <label class="dash-fb-label">To</label>
    <input type="date" id="filterTo" class="dash-fb-date" max="<?php echo date('Y-m-d'); ?>">
    <button class="btn btn-primary dash-fb-apply" onclick="applyCustomRange()">Apply</button>
    <span id="filterRangeLabel" class="dash-fb-range-label"></span>
  </div>
</div>

<div class="quick-links">
  <?php if (can('pos',       'view')): ?><a href="/pos"       class="quick-link"><?php echo icon('pos',       15); ?> POS</a><?php endif; ?>
  <?php if (can('invoices',  'view')): ?><a href="/invoices"  class="quick-link"><?php echo icon('invoice',   15); ?> Invoices</a><?php endif; ?>
  <?php if (can('clients',   'view')): ?><a href="/clients"   class="quick-link"><?php echo icon('clients',   15); ?> Clients</a><?php endif; ?>
  <?php if (can('products',  'view')): ?><a href="/products"  class="quick-link"><?php echo icon('products',  15); ?> Products</a><?php endif; ?>
  <?php if (can('inventory', 'view')): ?><a href="/inventory" class="quick-link"><?php echo icon('inventory', 15); ?> Inventory</a><?php endif; ?>
  <?php if (can('reports_sales', 'view')): ?><a href="/reports/sales" class="quick-link"><?php echo icon('chart-bar', 15); ?> Reports</a><?php endif; ?>
</div>

<div class="dash-section-label">Key Performance Indicators</div>
<div class="dash-kpi-grid">
  <div class="dash-kpi-card" style="--kpi-accent:#0056B3">
    <div class="dash-kpi-label"><?php echo icon('money', 12); ?> Today's Revenue</div>
    <div class="dash-kpi-value">&#8369;<?php echo number_format($metrics['total_sales_today'] ?? 0, 2); ?></div>
    <div class="dash-kpi-sub"><span class="<?php echo $todayClass; ?>"><?php echo htmlspecialchars($todayDelta); ?></span></div>
    <div class="dash-kpi-sub"><?php echo $metrics['invoices_today'] ?? 0; ?> invoice<?php echo ($metrics['invoices_today'] ?? 0) != 1 ? 's' : ''; ?> today</div>
  </div>
  <div class="dash-kpi-card" style="--kpi-accent:#28A745">
    <div class="dash-kpi-label"><?php echo icon('chart-line', 12); ?> Monthly Revenue</div>
    <div class="dash-kpi-value">&#8369;<?php echo number_format($metrics['total_sales_month'] ?? 0, 2); ?></div>
    <div class="dash-kpi-sub"><span class="<?php echo $monthClass; ?>"><?php echo htmlspecialchars($monthDelta); ?></span></div>
    <div class="dash-kpi-sub"><?php echo $metrics['invoices_month'] ?? 0; ?> invoices &bull; Collected <?php echo $collectionRate; ?>%</div>
  </div>
  <div class="dash-kpi-card" style="--kpi-accent:#DC3545">
    <div class="dash-kpi-label"><?php echo icon('alert', 12); ?> Outstanding</div>
    <div class="dash-kpi-value" style="color:var(--color-danger)">&#8369;<?php echo number_format($metrics['outstanding_receivables'] ?? 0, 2); ?></div>
    <div class="dash-kpi-sub"><?php echo $metrics['unpaid_invoices'] ?? 0; ?> unpaid / partial invoices</div>
    <div class="dash-kpi-sub"><a href="/invoices" style="font-size:.73rem">View unpaid &#8594;</a></div>
  </div>
  <div class="dash-kpi-card" style="--kpi-accent:#17A2B8">
    <div class="dash-kpi-label"><?php echo icon('chart-bar', 12); ?> Avg. Transaction</div>
    <div class="dash-kpi-value">&#8369;<?php echo number_format($metrics['avg_invoice_month'] ?? 0, 2); ?></div>
    <div class="dash-kpi-sub">Per invoice this month</div>
    <div class="dash-kpi-sub">&#8369;<?php echo number_format($metrics['collected_month'] ?? 0, 2); ?> collected</div>
  </div>
  <div class="dash-kpi-card" style="--kpi-accent:#6F42C1">
    <div class="dash-kpi-label"><?php echo icon('clients', 12); ?> Active Clients</div>
    <div class="dash-kpi-value"><?php echo number_format($metrics['total_clients'] ?? 0); ?></div>
    <?php if (($metrics['new_clients_month'] ?? 0) > 0): ?>
    <div class="dash-kpi-sub"><span class="pos">+<?php echo $metrics['new_clients_month']; ?> new this month</span></div>
    <?php else: ?>
    <div class="dash-kpi-sub">No new clients this month</div>
    <?php endif; ?>
    <div class="dash-kpi-sub"><a href="/clients" style="font-size:.73rem">Manage &#8594;</a></div>
  </div>
  <div class="dash-kpi-card" style="--kpi-accent:#FD7E14">
    <div class="dash-kpi-label"><?php echo icon('products', 12); ?> Active Products</div>
    <div class="dash-kpi-value"><?php echo number_format($metrics['active_products'] ?? 0); ?></div>
    <div class="dash-kpi-sub">In product catalog</div>
    <div class="dash-kpi-sub"><a href="/products" style="font-size:.73rem">Manage &#8594;</a></div>
  </div>
  <?php
  $totalAlerts = ($metrics['low_stock_items'] ?? 0) + ($metrics['out_of_stock_items'] ?? 0);
  $alertColor  = ($metrics['out_of_stock_items'] ?? 0) > 0 ? 'var(--color-danger)' : 'var(--color-warning)';
  ?>
  <div class="dash-kpi-card" style="--kpi-accent:#FFC107">
    <div class="dash-kpi-label"><?php echo icon('package', 12); ?> Stock Alerts</div>
    <div class="dash-kpi-value" style="color:<?php echo $totalAlerts > 0 ? $alertColor : 'var(--color-success)'; ?>">
      <?php echo $totalAlerts > 0 ? $totalAlerts : '&#10003;'; ?>
    </div>
    <div class="dash-kpi-sub"><?php echo $metrics['low_stock_items'] ?? 0; ?> low &bull; <?php echo $metrics['out_of_stock_items'] ?? 0; ?> out of stock</div>
    <div class="dash-kpi-sub"><a href="/inventory" style="font-size:.73rem">View inventory &#8594;</a></div>
  </div>
  <div class="dash-kpi-card" style="--kpi-accent:#20C997">
    <div class="dash-kpi-label"><?php echo icon('refresh', 12); ?> Pending Restocks</div>
    <div class="dash-kpi-value"><?php echo $metrics['pending_restocks'] ?? 0; ?></div>
    <div class="dash-kpi-sub">Restock orders placed</div>
    <div class="dash-kpi-sub"><a href="/inventory" style="font-size:.73rem">View orders &#8594;</a></div>
  </div>
</div>

<div class="dash-section-label">Sales &amp; Revenue</div>
<div class="dash-row c21">
  <div class="dash-card">
    <div class="dash-card-head">
      <span class="dash-card-title">Sales Trend</span>
      <span id="trendPeriodLabel" style="font-size:.73rem;color:var(--color-gray-500)">Last 7 Days</span>
    </div>
    <div class="dash-card-body"><canvas id="salesTrendChart" height="88"></canvas></div>
  </div>
  <div class="dash-card">
    <div class="dash-card-head"><span class="dash-card-title">Payment Modes</span></div>
    <div class="dash-card-body center">
      <div class="dash-chart-wrap"><canvas id="paymentModeChart" height="195"></canvas></div>
    </div>
  </div>
</div>

<div class="dash-row c11">
  <div class="dash-card">
    <div class="dash-card-head"><span class="dash-card-title">12-Month Revenue vs Collected</span></div>
    <div class="dash-card-body"><canvas id="monthlyRevenueChart" height="115"></canvas></div>
  </div>
  <div class="dash-card">
    <div class="dash-card-head"><span class="dash-card-title">Invoice Status Breakdown</span></div>
    <div class="dash-card-body center" style="flex-direction:column;gap:8px">
      <div class="dash-chart-wrap"><canvas id="invoiceStatusChart" height="195"></canvas></div>
    </div>
  </div>
</div>

<div class="dash-section-label">Products &amp; Inventory</div>
<div class="dash-row c11">
  <div class="dash-card">
    <div class="dash-card-head"><span class="dash-card-title">Top Products by Revenue (All Time)</span></div>
    <div class="dash-card-body"><canvas id="topProductsChart" height="165"></canvas></div>
  </div>
  <div class="dash-card">
    <div class="dash-card-head"><span class="dash-card-title">Stock Health</span></div>
    <div class="dash-card-body center" style="flex-direction:column;gap:10px">
      <div class="dash-chart-wrap"><canvas id="stockHealthChart" height="195"></canvas></div>
    </div>
  </div>
</div>

<div class="dash-section-label">Today &amp; Collection Analysis</div>
<div class="dash-row c11">
  <div class="dash-card">
    <div class="dash-card-head">
      <span class="dash-card-title">Sales by Hour &mdash; Today</span>
      <span style="font-size:.73rem;color:var(--color-gray-500)"><?php echo date('M d, Y'); ?></span>
    </div>
    <div class="dash-card-body"><canvas id="hourlyChart" height="115"></canvas></div>
  </div>
  <div class="dash-card">
    <div class="dash-card-head"><span class="dash-card-title">Revenue vs Collected &mdash; Last 6 Months</span></div>
    <div class="dash-card-body"><canvas id="revVsColChart" height="115"></canvas></div>
  </div>
</div>

<div class="dash-section-label">Recent Activity</div>
<div class="dash-row c11" style="margin-bottom:32px">
  <div class="dash-card">
    <div class="dash-card-head" style="padding-bottom:10px">
      <span class="dash-card-title">Recent Invoices</span>
      <a href="/invoices" style="font-size:.75rem">View all &#8594;</a>
    </div>
    <div style="overflow-x:auto">
      <table class="dash-tbl">
        <thead>
          <tr><th>#</th><th>Client</th><th>Amount</th><th>Mode</th><th>Status</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php foreach (($recentInvoices ?? []) as $inv): ?>
          <tr>
            <td style="font-weight:700;font-family:monospace"><?php echo htmlspecialchars($inv['invoice_number']); ?></td>
            <td><?php echo htmlspecialchars($inv['client_name'] ?? '&#8212;'); ?></td>
            <td style="font-weight:600">&#8369;<?php echo number_format($inv['total_amount'], 2); ?></td>
            <td style="text-transform:uppercase;font-size:.72rem;color:var(--color-gray-500)"><?php echo htmlspecialchars($inv['primary_payment_mode'] ?? '&#8212;'); ?></td>
            <td>
              <?php if ($inv['payment_status'] === 'fully_paid'): ?>
                <span class="ds-badge ds-b-paid">Paid</span>
              <?php elseif ($inv['payment_status'] === 'partially_paid'): ?>
                <span class="ds-badge ds-b-partial">Partial</span>
              <?php else: ?>
                <span class="ds-badge ds-b-unpaid">Unpaid</span>
              <?php endif; ?>
            </td>
            <td style="color:var(--color-gray-500)"><?php echo date('M d', strtotime($inv['invoice_date'])); ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentInvoices ?? [])): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--color-gray-500);padding:24px">No invoices yet</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="dash-card">
    <div class="dash-card-head" style="padding-bottom:10px">
      <span class="dash-card-title">Low Stock Alerts</span>
      <a href="/inventory" style="font-size:.75rem">View all &#8594;</a>
    </div>
    <div style="overflow-x:auto">
      <table class="dash-tbl">
        <thead>
          <tr><th>Product</th><th>Code</th><th>Qty</th><th>Min</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach (($lowStockItems ?? []) as $item): ?>
          <tr>
            <td><?php echo htmlspecialchars($item['name']); ?></td>
            <td style="font-family:monospace;font-size:.75rem;color:var(--color-gray-500)"><?php echo htmlspecialchars($item['code']); ?></td>
            <td style="font-weight:800;color:<?php echo $item['stock_status'] === 'out_of_stock' ? 'var(--color-danger)' : 'var(--color-warning)'; ?>"><?php echo (int)$item['current_qty']; ?></td>
            <td><?php echo (int)$item['low_stock_alert']; ?></td>
            <td>
              <?php if ($item['stock_status'] === 'out_of_stock'): ?>
                <span class="ds-badge ds-b-out">Out</span>
              <?php else: ?>
                <span class="ds-badge ds-b-low">Low</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($lowStockItems ?? [])): ?>
          <tr><td colspan="5" style="text-align:center;color:var(--color-success);padding:24px">&#10003; All stock levels are healthy</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
// Register datalabels plugin globally
Chart.register(ChartDataLabels);

const DASH = <?php echo json_encode($charts ?? []); ?>;
Chart.defaults.font.family = "'Inter','Segoe UI','Helvetica Neue',sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#808080';
const P = {
  blue:'#0056B3', green:'#28A745', red:'#DC3545', yellow:'#FFC107',
  teal:'#17A2B8', purple:'#6F42C1', orange:'#FD7E14', mint:'#20C997',
  blueA12:'rgba(0,86,179,0.12)', blueA75:'rgba(0,86,179,0.78)', greenA75:'rgba(40,167,69,0.78)'
};
const PALETTE=[P.blue,P.green,P.teal,P.purple,P.orange,P.mint,P.red,P.yellow];
function fmtCurrency(v){return'\u20b1'+Number(v).toLocaleString('en-PH',{minimumFractionDigits:2});}
function fmtAxis(v){if(v>=1000000)return'\u20b1'+(v/1000000).toFixed(1)+'M';if(v>=1000)return'\u20b1'+(v/1000).toFixed(0)+'k';return'\u20b1'+v;}
const yScale={beginAtZero:true,grid:{color:'#f0f0f0'},ticks:{callback:fmtAxis}};
const xScale={grid:{display:false}};

// Doughnut label color: white on dark backgrounds, dark on yellow
function dlColorDonut(ctx){
  const bg=ctx.dataset.backgroundColor[ctx.dataIndex];
  return bg===P.yellow?'#333':'#fff';
}
// Doughnut formatter: value + percentage
function dlFmtDonutCount(v,ctx){
  if(!v)return'';
  const total=ctx.dataset.data.reduce((a,b)=>a+b,0);
  if(!total)return'';
  return v+'\n'+Math.round(v/total*100)+'%';
}
function dlFmtDonutAmt(v,ctx){
  if(!v)return'';
  const total=ctx.dataset.data.reduce((a,b)=>a+b,0);
  if(!total)return'';
  return fmtCurrency(v)+'\n'+Math.round(v/total*100)+'%';
}

// ── 1. SALES TREND (line) ────────────────────────────────────────────────────
let trendInst;
function renderSalesTrend(trend){
  const ctx=document.getElementById('salesTrendChart');
  if(trendInst)trendInst.destroy();
  trendInst=new Chart(ctx,{
    type:'line',
    data:{labels:trend.labels,datasets:[{
      label:'Revenue',data:trend.data,
      borderColor:P.blue,backgroundColor:P.blueA12,
      tension:0.42,fill:true,
      pointBackgroundColor:P.blue,pointRadius:4,pointHoverRadius:7,borderWidth:2
    }]},
    options:{
      responsive:true,
      layout:{padding:{top:22}},
      interaction:{mode:'index',intersect:false},
      plugins:{
        legend:{display:false},
        tooltip:{callbacks:{label:ctx=>' '+fmtCurrency(ctx.parsed.y)}},
        datalabels:{
          display:(ctx)=>ctx.dataset.data[ctx.dataIndex]>0,
          anchor:'end',align:'top',offset:4,
          formatter:(v)=>fmtCurrency(v),
          font:{size:9,weight:'700'},
          color:P.blue,
          clip:false,
        }
      },
      scales:{y:yScale,x:xScale}
    }
  });
}

// ── 2. PAYMENT MODES (doughnut) ──────────────────────────────────────────────
let paymentInst;
function renderPaymentModes(modes){
  const labels=(modes||[]).map(m=>(m.mode||'N/A').toUpperCase());
  const amounts=(modes||[]).map(m=>parseFloat(m.amount||0));
  if(paymentInst)paymentInst.destroy();
  paymentInst=new Chart(document.getElementById('paymentModeChart'),{
    type:'doughnut',
    data:{labels,datasets:[{data:amounts,backgroundColor:[P.blue,P.green,P.teal,P.purple],borderWidth:2,hoverOffset:10}]},
    options:{responsive:true,cutout:'62%',plugins:{
      legend:{position:'bottom',labels:{padding:14,boxWidth:13}},
      tooltip:{callbacks:{label:ctx=>' '+ctx.label+': '+fmtCurrency(ctx.parsed)}},
      datalabels:{
        display:(ctx)=>ctx.dataset.data[ctx.dataIndex]>0,
        anchor:'center',align:'center',
        formatter:dlFmtDonutAmt,
        font:{size:9,weight:'700',lineHeight:1.4},
        color:dlColorDonut,
        textAlign:'center',
      }
    }}
  });
}

// ── 3. 12-MONTH REVENUE vs COLLECTED (grouped bar) ───────────────────────────
(function(){
  const d=DASH.monthly_revenue||{labels:[],revenue:[],collected:[]};
  new Chart(document.getElementById('monthlyRevenueChart'),{
    type:'bar',
    data:{labels:d.labels,datasets:[
      {label:'Billed',   data:d.revenue,   backgroundColor:P.blueA75, borderRadius:4,borderSkipped:false},
      {label:'Collected',data:d.collected, backgroundColor:P.greenA75,borderRadius:4,borderSkipped:false}
    ]},
    options:{responsive:true,
      layout:{padding:{top:4}},
      interaction:{mode:'index',intersect:false},
      plugins:{
        legend:{position:'top',labels:{boxWidth:12,padding:12}},
        tooltip:{callbacks:{label:ctx=>' '+ctx.dataset.label+': '+fmtCurrency(ctx.parsed.y)}},
        datalabels:{
          display:(ctx)=>ctx.dataset.data[ctx.dataIndex]>0,
          anchor:'center',align:'center',
          rotation:-90,
          formatter:(v)=>fmtCurrency(v),
          font:{size:8,weight:'700'},
          color:'#fff',
        }
      },
      scales:{y:yScale,x:xScale}
    }
  });
})();

// ── 4. INVOICE STATUS (doughnut) ─────────────────────────────────────────────
let invoiceStatusInst;
function renderInvoiceStatus(d){
  if(invoiceStatusInst)invoiceStatusInst.destroy();
  invoiceStatusInst=new Chart(document.getElementById('invoiceStatusChart'),{
    type:'doughnut',
    data:{labels:d.labels,datasets:[{data:d.data,backgroundColor:[P.green,P.yellow,P.red],borderWidth:2,hoverOffset:10}]},
    options:{responsive:true,cutout:'58%',plugins:{
      legend:{position:'bottom',labels:{padding:14,boxWidth:13}},
      tooltip:{callbacks:{label:ctx=>' '+ctx.label+': '+ctx.parsed+' invoices'}},
      datalabels:{
        display:(ctx)=>ctx.dataset.data[ctx.dataIndex]>0,
        anchor:'center',align:'center',
        formatter:dlFmtDonutCount,
        font:{size:10,weight:'700',lineHeight:1.4},
        color:dlColorDonut,
        textAlign:'center',
      }
    }}
  });
}

// ── 5. TOP PRODUCTS (horizontal bar) ────────────────────────────────────────
let topProductsInst;
function renderTopProducts(products){
  if(!products||!products.length){
    if(topProductsInst){topProductsInst.destroy();topProductsInst=null;}
    return;
  }
  const labels=products.map(p=>p.name.length>22?p.name.substring(0,22)+'…':p.name);
  const revenues=products.map(p=>parseFloat(p.revenue||0));
  if(topProductsInst)topProductsInst.destroy();
  topProductsInst=new Chart(document.getElementById('topProductsChart'),{
    type:'bar',
    data:{labels,datasets:[{label:'Revenue',data:revenues,backgroundColor:PALETTE,borderRadius:5,borderSkipped:false}]},
    options:{indexAxis:'y',responsive:true,
      layout:{padding:{right:8}},
      plugins:{
        legend:{display:false},
        tooltip:{callbacks:{label:ctx=>' '+fmtCurrency(ctx.parsed.x)}},
        datalabels:{
          display:(ctx)=>ctx.dataset.data[ctx.dataIndex]>0,
          anchor:'end',align:'right',offset:4,
          formatter:(v)=>fmtCurrency(v),
          font:{size:9,weight:'700'},
          color:'#444',
          clip:false,
        }
      },
      scales:{x:{beginAtZero:true,grid:{color:'#f0f0f0'},ticks:{callback:fmtAxis}},y:{grid:{display:false}}}
    }
  });
}

// ── 6. STOCK HEALTH (doughnut) ───────────────────────────────────────────────
(function(){
  const d=DASH.stock_status||{labels:[],data:[]};
  new Chart(document.getElementById('stockHealthChart'),{
    type:'doughnut',
    data:{labels:d.labels,datasets:[{data:d.data,backgroundColor:[P.green,P.yellow,P.red],borderWidth:2,hoverOffset:10}]},
    options:{responsive:true,cutout:'58%',plugins:{
      legend:{position:'bottom',labels:{padding:14,boxWidth:13}},
      tooltip:{callbacks:{label:ctx=>' '+ctx.label+': '+ctx.parsed+' items'}},
      datalabels:{
        display:(ctx)=>ctx.dataset.data[ctx.dataIndex]>0,
        anchor:'center',align:'center',
        formatter:dlFmtDonutCount,
        font:{size:10,weight:'700',lineHeight:1.4},
        color:dlColorDonut,
        textAlign:'center',
      }
    }}
  });
})();

// ── 7. SALES BY HOUR — TODAY (bar) ──────────────────────────────────────────
(function(){
  const d=DASH.hourly_sales||{labels:[],data:[]};
  const maxVal=Math.max(...d.data,1);
  new Chart(document.getElementById('hourlyChart'),{
    type:'bar',
    data:{labels:d.labels,datasets:[{
      label:'Revenue',data:d.data,
      backgroundColor:d.data.map(v=>v>=maxVal*0.8?P.blue:v>0?'rgba(0,86,179,0.5)':'rgba(0,86,179,0.12)'),
      borderRadius:4,borderSkipped:false
    }]},
    options:{responsive:true,
      layout:{padding:{top:22}},
      plugins:{
        legend:{display:false},
        tooltip:{callbacks:{label:ctx=>' '+fmtCurrency(ctx.parsed.y)}},
        datalabels:{
          display:(ctx)=>ctx.dataset.data[ctx.dataIndex]>0,
          anchor:'end',align:'top',offset:2,
          formatter:(v)=>fmtCurrency(v),
          font:{size:9,weight:'700'},
          color:P.blue,
          clip:false,
        }
      },
      scales:{y:yScale,x:xScale}
    }
  });
})();

// ── 8. REVENUE vs COLLECTED — LAST 6 MONTHS (grouped bar) ───────────────────
(function(){
  const d=DASH.revenue_vs_collected||{labels:[],revenue:[],collected:[]};
  new Chart(document.getElementById('revVsColChart'),{
    type:'bar',
    data:{labels:d.labels,datasets:[
      {label:'Billed',   data:d.revenue,   backgroundColor:P.blueA75, borderRadius:4,borderSkipped:false},
      {label:'Collected',data:d.collected, backgroundColor:P.greenA75,borderRadius:4,borderSkipped:false}
    ]},
    options:{responsive:true,
      layout:{padding:{top:22}},
      interaction:{mode:'index',intersect:false},
      plugins:{
        legend:{position:'top',labels:{boxWidth:12,padding:12}},
        tooltip:{callbacks:{label:ctx=>' '+ctx.dataset.label+': '+fmtCurrency(ctx.parsed.y)}},
        datalabels:{
          display:(ctx)=>ctx.dataset.data[ctx.dataIndex]>0,
          anchor:'end',align:'top',offset:2,
          formatter:(v)=>fmtCurrency(v),
          font:{size:9,weight:'700'},
          color:(ctx)=>ctx.datasetIndex===0?P.blue:P.green,
          clip:false,
        }
      },
      scales:{y:yScale,x:xScale}
    }
  });
})();

// ── DATE FILTER LOGIC ────────────────────────────────────────────────────────
let _filter={period:'week',dateFrom:null,dateTo:null};

function applyPreset(period){
  _filter={period,dateFrom:null,dateTo:null};
  document.querySelectorAll('.dash-fb-btn').forEach(b=>b.classList.remove('active'));
  const btn=document.getElementById(period==='month'?'fbMonth':'fbWeek');
  if(btn)btn.classList.add('active');
  document.getElementById('filterRangeLabel').textContent='';
  const lbl=document.getElementById('trendPeriodLabel');
  if(lbl)lbl.textContent=period==='month'?'Last 30 Days':'Last 7 Days';
  reloadFilteredCharts();
}

function applyCustomRange(){
  const from=document.getElementById('filterFrom').value;
  const to=document.getElementById('filterTo').value;
  if(!from||!to){alert('Please select both a From and To date.');return;}
  if(from>to){alert('From date must be on or before To date.');return;}
  _filter={period:null,dateFrom:from,dateTo:to};
  document.querySelectorAll('.dash-fb-btn').forEach(b=>b.classList.remove('active'));
  const fD=s=>new Date(s+'T00:00:00').toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'});
  const rangeText=fD(from)+' \u2013 '+fD(to);
  document.getElementById('filterRangeLabel').textContent=rangeText;
  const lbl=document.getElementById('trendPeriodLabel');
  if(lbl)lbl.textContent=rangeText;
  reloadFilteredCharts();
}

function reloadFilteredCharts(){
  let url='/api/v1/dashboard/charts?';
  if(_filter.dateFrom&&_filter.dateTo){
    url+='date_from='+encodeURIComponent(_filter.dateFrom)+'&date_to='+encodeURIComponent(_filter.dateTo);
  }else{
    url+='period='+encodeURIComponent(_filter.period||'week');
  }
  fetch(url)
    .then(r=>r.json())
    .then(res=>{
      if(!res.success||!res.data)return;
      const d=res.data;
      if(d.sales_trend)renderSalesTrend(d.sales_trend);
      if(d.payment_modes)renderPaymentModes(d.payment_modes);
      if(d.invoice_status)renderInvoiceStatus(d.invoice_status||{labels:[],data:[]});
      renderTopProducts(d.top_products||[]);
    })
    .catch(()=>{});
}

// ── INITIAL RENDER ────────────────────────────────────────────────────────────
if(DASH.sales_trend)renderSalesTrend(DASH.sales_trend);
renderPaymentModes(DASH.payment_modes||[]);
renderInvoiceStatus(DASH.invoice_status||{labels:[],data:[]});
renderTopProducts(DASH.top_products||[]);
</script>

<?php
$content = ob_get_clean();
$title   = 'Dashboard | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
