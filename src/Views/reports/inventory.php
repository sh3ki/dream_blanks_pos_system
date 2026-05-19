<?php ob_start(); ?>
<?php
$r             = $report ?? [];
$exportUrl     = '/api/v1/reports/export?type=inventory';
$summary       = $r['summary']        ?? [];
$jsStockStatus = json_encode($r['stock_status']  ?? ['labels'=>[],'data'=>[]]);
$jsHighest     = json_encode($r['highest_stock'] ?? []);
$jsLowest      = json_encode($r['lowest_stock']  ?? []);
$rs            = $r['restock_stats'] ?? ['total_orders'=>0,'pending'=>0,'received'=>0];
$jsRestock     = json_encode([
    'labels' => ['Pending Delivery', 'Received'],
    'data'   => [(int)($rs['pending']??0), (int)($rs['received']??0)],
]);
?>
<style>
.rpt-kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.rpt-kpi-card{background:#fff;border-radius:10px;padding:14px 16px 12px;box-shadow:var(--shadow-sm);border-left:4px solid var(--rpt-accent,#0056B3);display:flex;flex-direction:column;gap:4px;transition:box-shadow .2s,transform .2s}
.rpt-kpi-card:hover{box-shadow:var(--shadow);transform:translateY(-2px)}
.rpt-kpi-label{font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-gray-500)}
.rpt-kpi-value{font-size:1.3rem;font-weight:800;color:var(--color-dark-gray);line-height:1.1}
.rpt-kpi-sub{font-size:.72rem;color:var(--color-gray-500)}
.rpt-row{display:grid;gap:16px;margin-bottom:16px}
.rpt-row.c12{grid-template-columns:1fr 2fr}
.rpt-row.c11{grid-template-columns:1fr 1fr}
.rpt-card{background:#fff;border-radius:10px;box-shadow:var(--shadow-sm);overflow:hidden;display:flex;flex-direction:column}
.rpt-card-head{display:flex;align-items:center;justify-content:space-between;padding:12px 16px 0;flex-shrink:0}
.rpt-card-title{font-size:.88rem;font-weight:700;color:var(--color-dark-gray)}
.rpt-card-body{padding:10px 14px 14px;flex:1}
.rpt-card-body.center{display:flex;align-items:center;justify-content:center}
.rpt-chart-wrap{max-width:260px;width:100%;margin:auto}
.rpt-sl{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--color-gray-500);margin:4px 0 12px;display:flex;align-items:center;gap:8px}
.rpt-sl::after{content:'';flex:1;height:1px;background:var(--color-gray-100)}
.rpt-tbl{width:100%;border-collapse:collapse;font-size:.81rem}
.rpt-tbl th{background:#f8f9fa;font-size:.69rem;text-transform:uppercase;letter-spacing:.05em;padding:7px 12px;color:var(--color-gray-500);font-weight:600;border-bottom:1px solid var(--color-gray-100);text-align:left}
.rpt-tbl td{padding:7px 12px;border-bottom:1px solid var(--color-gray-100);color:var(--color-dark-gray);vertical-align:middle}
.rpt-tbl tr:last-child td{border-bottom:none}
.rpt-tbl tr:hover td{background:#f8f9fa}
.rpt-badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:.69rem;font-weight:700;white-space:nowrap}
.rpt-b-in{background:var(--color-success-light);color:var(--color-success)}
.rpt-b-low{background:var(--color-warning-light);color:#856404}
.rpt-b-out{background:var(--color-danger-light);color:var(--color-danger)}
.rpt-val-row{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid var(--color-gray-100);font-size:.9rem}
.rpt-val-row:last-child{border-bottom:none}
@media(max-width:1200px){.rpt-kpi-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:800px){.rpt-kpi-grid{grid-template-columns:repeat(2,1fr)}.rpt-row.c12,.rpt-row.c11{grid-template-columns:1fr}}
@media print{@page{margin:0}body{padding:12mm!important}.btn{display:none!important}.rpt-card{box-shadow:none!important;border:1px solid #ddd!important;break-inside:avoid;page-break-inside:avoid}.rpt-row{break-inside:avoid;page-break-inside:avoid}.rpt-kpi-card{break-inside:avoid;page-break-inside:avoid}.rpt-kpi-grid{break-inside:avoid;page-break-inside:avoid}}
</style>

<div class="page-header" style="margin-bottom:14px">
  <div>
    <h1 style="margin:0 0 2px">Inventory Report</h1>
    <span style="color:var(--color-gray-500);font-size:.82rem">Current stock status as of <?php echo date('l, F d, Y'); ?></span>
  </div>
  <div style="display:flex;gap:6px">
    <?php if(can('reports_inventory','export')): ?>
    <button type="button" class="btn btn-secondary" onclick="exportPDF(this,'inventory-report-<?php echo date('Y-m-d'); ?>.pdf')">&#128196; Export PDF</button>
    <?php endif; ?>
    <?php if(can('reports_inventory','view')): ?>
    <button type="button" class="btn btn-secondary" onclick="window.print()">&#128438; Print</button>
    <?php endif; ?>
  </div>
</div>
<div id="rpt-content">
<div class="rpt-sl">Stock Overview</div>
<div class="rpt-kpi-grid">
  <div class="rpt-kpi-card" style="--rpt-accent:#0056B3">
    <div class="rpt-kpi-label">Total Products</div>
    <div class="rpt-kpi-value"><?php echo number_format($summary['total_products']??0); ?></div>
    <div class="rpt-kpi-sub"><?php echo number_format($summary['total_units']??0); ?> total units</div>
  </div>
  <div class="rpt-kpi-card" style="--rpt-accent:#28A745">
    <div class="rpt-kpi-label">In Stock</div>
    <div class="rpt-kpi-value" style="color:var(--color-success)"><?php echo number_format($summary['in_stock']??0); ?></div>
    <div class="rpt-kpi-sub">Items available</div>
  </div>
  <div class="rpt-kpi-card" style="--rpt-accent:#FFC107">
    <div class="rpt-kpi-label">Low Stock</div>
    <div class="rpt-kpi-value" style="color:var(--color-warning)"><?php echo number_format($summary['low_stock']??0); ?></div>
    <div class="rpt-kpi-sub">Needs restocking</div>
  </div>
  <div class="rpt-kpi-card" style="--rpt-accent:#DC3545">
    <div class="rpt-kpi-label">Out of Stock</div>
    <div class="rpt-kpi-value" style="color:var(--color-danger)"><?php echo number_format($summary['out_of_stock']??0); ?></div>
    <div class="rpt-kpi-sub">Unavailable items</div>
  </div>
</div>

<div class="rpt-sl">Stock Distribution &amp; Analysis</div>
<div class="rpt-row c12">
  <div class="rpt-card">
    <div class="rpt-card-head"><span class="rpt-card-title">Stock Status Distribution</span></div>
    <div class="rpt-card-body center"><div class="rpt-chart-wrap"><canvas id="statusChart" height="220"></canvas></div></div>
  </div>
  <div class="rpt-card">
    <div class="rpt-card-head"><span class="rpt-card-title">Top 10 Highest Stock Items</span></div>
    <div class="rpt-card-body"><canvas id="highChart" height="200"></canvas></div>
  </div>
</div>

<div class="rpt-row c11">
  <div class="rpt-card">
    <div class="rpt-card-head"><span class="rpt-card-title">Bottom 10 &mdash; Lowest Stock (At Risk)</span></div>
    <div class="rpt-card-body"><canvas id="lowChart" height="200"></canvas></div>
  </div>
  <div class="rpt-card">
    <div class="rpt-card-head">
      <span class="rpt-card-title">Restock Order Status</span>
      <span style="font-size:.72rem;color:var(--color-gray-500)"><?php echo (int)($rs['total_orders']??0); ?> total order<?php echo ($rs['total_orders']??0)!=1?'s':''; ?></span>
    </div>
    <div class="rpt-card-body center"><div class="rpt-chart-wrap"><canvas id="restockChart" height="220"></canvas></div></div>
  </div>
</div>

<div class="rpt-sl">Low &amp; Out of Stock Items</div>
<div class="rpt-card" style="margin-bottom:16px">
  <div class="rpt-card-head">
    <span class="rpt-card-title">Stock Alert List</span>
    <span style="font-size:.72rem;color:var(--color-danger)"><?php echo count($r['low_stock_items']??[]); ?> item(s) need attention</span>
  </div>
  <div style="overflow-x:auto">
    <table class="rpt-tbl">
      <thead><tr><th>Product Name</th><th>SKU / Code</th><th style="text-align:right">Qty on Hand</th><th style="text-align:right">Min Level</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach(($r['low_stock_items']??[]) as $item): ?>
        <tr>
          <td><?php echo htmlspecialchars($item['name']??''); ?></td>
          <td style="font-family:monospace;font-size:.75rem;color:var(--color-gray-500)"><?php echo htmlspecialchars($item['code']??'-'); ?></td>
          <td style="text-align:right;font-weight:800;color:<?php echo $item['stock_status']==='out_of_stock'?'var(--color-danger)':'var(--color-warning)'; ?>"><?php echo (int)($item['quantity_on_hand']??0); ?></td>
          <td style="text-align:right"><?php echo (int)($item['low_stock_alert']??0); ?></td>
          <td><?php if($item['stock_status']==='out_of_stock'): ?><span class="rpt-badge rpt-b-out">Out of Stock</span><?php else: ?><span class="rpt-badge rpt-b-low">Low Stock</span><?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($r['low_stock_items'])): ?><tr><td colspan="5" style="text-align:center;color:var(--color-success);padding:24px">&#10003; All items are at healthy stock levels</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</div><!-- /rpt-content -->

<script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
Chart.register(ChartDataLabels);
Chart.defaults.font.family="'Inter','Segoe UI','Helvetica Neue',sans-serif";
Chart.defaults.font.size=11;Chart.defaults.color='#808080';
const P={blue:'#0056B3',green:'#28A745',red:'#DC3545',yellow:'#FFC107',teal:'#17A2B8',purple:'#6F42C1',orange:'#FD7E14',mint:'#20C997'};
const PALETTE=[P.blue,P.green,P.teal,P.purple,P.orange,P.mint,P.red,P.yellow];
const statusData=<?php echo $jsStockStatus; ?>;
const highData=<?php echo $jsHighest; ?>;
const lowData=<?php echo $jsLowest; ?>;
const restockData=<?php echo $jsRestock; ?>;
// Stock status doughnut
(function(){
  new Chart(document.getElementById('statusChart'),{
    type:'doughnut',
    data:{labels:statusData.labels,datasets:[{data:statusData.data,backgroundColor:[P.green,P.yellow,P.red],borderWidth:2,hoverOffset:10}]},
    options:{responsive:true,cutout:'58%',plugins:{
      legend:{position:'bottom',labels:{padding:14,boxWidth:13}},
      tooltip:{callbacks:{label:ctx=>' '+ctx.label+': '+ctx.parsed}},
      datalabels:{display:(ctx)=>ctx.dataset.data[ctx.dataIndex]>0,anchor:'center',align:'center',
        formatter:(v,ctx)=>{const t=ctx.dataset.data.reduce((a,b)=>a+b,0);return t>0?v+'\n'+Math.round(v/t*100)+'%':'';},
        font:{size:10,weight:'700',lineHeight:1.4},
        color:(ctx)=>ctx.dataset.backgroundColor[ctx.dataIndex]===P.yellow?'#333':'#fff',textAlign:'center'}
    }}
  });
})();
// Highest stock
(function(){
  if(!highData.length)return;
  const labels=highData.map(i=>i.name.length>18?i.name.substring(0,18)+'\u2026':i.name);
  const vals=highData.map(i=>parseInt(i.current_qty||0));
  new Chart(document.getElementById('highChart'),{
    type:'bar',
    data:{labels,datasets:[{label:'Qty',data:vals,backgroundColor:PALETTE,borderRadius:5,borderSkipped:false}]},
    options:{indexAxis:'y',responsive:true,layout:{padding:{right:10}},plugins:{legend:{display:false},
      tooltip:{callbacks:{label:ctx=>' '+ctx.parsed.x+' units'}},
      datalabels:{display:(ctx)=>ctx.dataset.data[ctx.dataIndex]>0,anchor:'end',align:'right',offset:4,
        formatter:v=>v+' pcs',font:{size:8,weight:'700'},color:'#444',clip:false}},
      scales:{x:{beginAtZero:true,grid:{color:'#f0f0f0'}},y:{grid:{display:false}}}
    }
  });
})();
// Lowest stock (colored by status)
(function(){
  if(!lowData.length)return;
  const labels=lowData.map(i=>i.name.length>18?i.name.substring(0,18)+'\u2026':i.name);
  const vals=lowData.map(i=>parseInt(i.current_qty||0));
  const colors=lowData.map(i=>i.stock_status==='out_of_stock'?P.red:i.stock_status==='low_stock'?P.yellow:P.green);
  new Chart(document.getElementById('lowChart'),{
    type:'bar',
    data:{labels,datasets:[{label:'Qty',data:vals,backgroundColor:colors,borderRadius:5,borderSkipped:false}]},
    options:{indexAxis:'y',responsive:true,layout:{padding:{right:10}},plugins:{legend:{display:false},
      tooltip:{callbacks:{label:ctx=>' '+ctx.parsed.x+' units'}},
      datalabels:{display:true,anchor:'end',align:'right',offset:4,
        formatter:v=>v+' pcs',font:{size:8,weight:'700'},color:'#444',clip:false}},
      scales:{x:{beginAtZero:true,grid:{color:'#f0f0f0'}},y:{grid:{display:false}}}
    }
  });
})();
// Restock order doughnut
(function(){
  const total=restockData.data.reduce((a,b)=>a+b,0);
  new Chart(document.getElementById('restockChart'),{
    type:'doughnut',
    data:{labels:restockData.labels,datasets:[{data:restockData.data,backgroundColor:[P.yellow,P.green],borderWidth:2,hoverOffset:10}]},
    options:{responsive:true,cutout:'60%',plugins:{
      legend:{position:'bottom',labels:{padding:14,boxWidth:13}},
      tooltip:{callbacks:{label:ctx=>' '+ctx.label+': '+ctx.parsed}},
      datalabels:{display:(ctx)=>ctx.dataset.data[ctx.dataIndex]>0,anchor:'center',align:'center',
        formatter:(v,ctx)=>{return total>0?v+'\n'+Math.round(v/total*100)+'%':v;},
        font:{size:11,weight:'700',lineHeight:1.4},
        color:(ctx)=>ctx.dataset.backgroundColor[ctx.dataIndex]===P.yellow?'#333':'#fff',
        textAlign:'center'}
    }}
  });
})();
function exportPDF(btn,filename){
  const el=document.getElementById('rpt-content');
  const orig=btn.innerHTML;
  btn.disabled=true;btn.innerHTML='\u231B Generating…';
  html2pdf().set({margin:[10,8],filename:filename,image:{type:'jpeg',quality:.98},html2canvas:{scale:2,useCORS:true,logging:false},jsPDF:{unit:'mm',format:'a4',orientation:'landscape'}}).from(el).save().then(()=>{btn.disabled=false;btn.innerHTML=orig;});}
</script>
<?php
$content = ob_get_clean();
$title   = 'Inventory Report | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
