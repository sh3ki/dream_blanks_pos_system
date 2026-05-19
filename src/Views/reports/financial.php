<?php ob_start(); ?>
<?php
$from       = $from ?? date('Y-m-01');
$to         = $to   ?? date('Y-m-d');
$r          = $report ?? [];
$exportUrl  = '/api/v1/reports/export?type=financial&date_from=' . urlencode($from) . '&date_to=' . urlencode($to);
$dateRange  = date('M d, Y', strtotime($from)) . ' – ' . date('M d, Y', strtotime($to));
$rangeDays  = max(1, (int)(strtotime($to) - strtotime($from)) / 86400 + 1);
$statusMap  = $r['status_map'] ?? ['fully_paid'=>['cnt'=>0,'amount'=>0],'partially_paid'=>['cnt'=>0,'amount'=>0],'unpaid'=>['cnt'=>0,'amount'=>0]];
$jsTrend    = json_encode($r['trend']        ?? ['labels'=>[],'revenue'=>[],'collected'=>[]]);
$jsAging    = json_encode($r['aging']        ?? ['labels'=>[],'data'=>[]]);
$jsStatus   = json_encode($r['status_chart'] ?? ['labels'=>[],'data'=>[]]);
$jsClients  = json_encode($r['top_clients']  ?? []);
?>
<style>
.rpt-filter-bar{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.rpt-fb-btn{padding:5px 14px;border:1px solid var(--color-gray-100);border-radius:6px;background:#fff;font-size:.78rem;font-weight:600;cursor:pointer;color:var(--color-dark-gray);transition:all .15s;height:32px;line-height:1}
.rpt-fb-btn:hover{border-color:var(--color-primary);color:var(--color-primary)}
.rpt-fb-btn.active{background:var(--color-primary);color:#fff;border-color:var(--color-primary)}
.rpt-fb-sep{width:1px;height:20px;background:var(--color-gray-200);margin:0 2px;flex-shrink:0}
.rpt-fb-label{font-size:.73rem;font-weight:600;color:var(--color-gray-500);white-space:nowrap}
.rpt-fb-date{height:32px;padding:4px 8px;font-size:.78rem;border:1px solid var(--color-gray-200);border-radius:6px;background:#fff;color:var(--color-dark-gray)}
.rpt-kpi-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:20px}
.rpt-kpi-card{background:#fff;border-radius:10px;padding:14px 16px 12px;box-shadow:var(--shadow-sm);border-left:4px solid var(--rpt-accent,#0056B3);display:flex;flex-direction:column;gap:4px;transition:box-shadow .2s,transform .2s}
.rpt-kpi-card:hover{box-shadow:var(--shadow);transform:translateY(-2px)}
.rpt-kpi-label{font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-gray-500)}
.rpt-kpi-value{font-size:1.3rem;font-weight:800;color:var(--color-dark-gray);line-height:1.1}
.rpt-kpi-sub{font-size:.72rem;color:var(--color-gray-500)}
.rpt-row{display:grid;gap:16px;margin-bottom:16px}
.rpt-row.c21{grid-template-columns:2fr 1fr}
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
.rpt-b-paid{background:var(--color-success-light);color:var(--color-success)}
.rpt-b-partial{background:var(--color-warning-light);color:#856404}
.rpt-b-unpaid{background:var(--color-danger-light);color:var(--color-danger)}
@media(max-width:1200px){.rpt-kpi-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:800px){.rpt-kpi-grid{grid-template-columns:repeat(2,1fr)}.rpt-row.c21,.rpt-row.c11{grid-template-columns:1fr}}
@media print{@page{margin:0}body{padding:12mm!important}.rpt-filter-bar,.btn,form{display:none!important}.rpt-card{box-shadow:none!important;border:1px solid #ddd!important;break-inside:avoid;page-break-inside:avoid}.rpt-row{break-inside:avoid;page-break-inside:avoid}.rpt-kpi-card{break-inside:avoid;page-break-inside:avoid}.rpt-kpi-grid{break-inside:avoid;page-break-inside:avoid}}
</style>

<form id="filterForm" method="GET">
<div class="page-header" style="margin-bottom:14px;flex-wrap:wrap;gap:10px">
  <div>
    <h1 style="margin:0 0 2px">Financial Report</h1>
    <span style="color:var(--color-gray-500);font-size:.82rem"><?php echo htmlspecialchars($dateRange); ?> &bull; <?php echo $rangeDays; ?> day<?php echo $rangeDays!=1?'s':''; ?></span>
  </div>
  <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <div class="rpt-filter-bar">
      <button type="button" class="rpt-fb-btn<?php echo $rangeDays==7?' active':''; ?>" onclick="setPreset(7)">Last 7 Days</button>
      <button type="button" class="rpt-fb-btn<?php echo $rangeDays==30?' active':''; ?>" onclick="setPreset(30)">Last 30 Days</button>
      <span class="rpt-fb-sep"></span>
      <label class="rpt-fb-label">From</label>
      <input type="date" name="date_from" id="dateFrom" class="rpt-fb-date" value="<?php echo htmlspecialchars($from); ?>" max="<?php echo date('Y-m-d'); ?>">
      <label class="rpt-fb-label">To</label>
      <input type="date" name="date_to" id="dateTo" class="rpt-fb-date" value="<?php echo htmlspecialchars($to); ?>" max="<?php echo date('Y-m-d'); ?>">
      <button type="submit" class="btn btn-primary" style="height:32px;padding:0 16px;font-size:.78rem">Apply</button>
    </div>
    <div style="display:flex;gap:6px">
      <?php if(can('reports_financial','export')): ?>
      <button type="button" class="btn btn-secondary" onclick="exportPDF(this,'financial-report-<?php echo $from; ?>_to_<?php echo $to; ?>.pdf')" style="height:32px;padding:0 14px;font-size:.78rem">&#128196; Export PDF</button>
      <?php endif; ?>
      <?php if(can('reports_financial','view')): ?>
      <button type="button" class="btn btn-secondary" onclick="window.print()" style="height:32px;padding:0 14px;font-size:.78rem">&#128438; Print</button>
      <?php endif; ?>
    </div>
  </div>
</div>
</form>
<div id="rpt-content">
<div class="rpt-sl">Financial Overview &mdash; <?php echo htmlspecialchars($dateRange); ?></div>
<div class="rpt-kpi-grid">
  <div class="rpt-kpi-card" style="--rpt-accent:#0056B3">
    <div class="rpt-kpi-label">Total Revenue</div>
    <div class="rpt-kpi-value">&#8369;<?php echo number_format($r['total_revenue']??0,2); ?></div>
    <div class="rpt-kpi-sub"><?php echo number_format($r['invoice_count']??0); ?> invoices</div>
  </div>
  <div class="rpt-kpi-card" style="--rpt-accent:#28A745">
    <div class="rpt-kpi-label">Collected</div>
    <div class="rpt-kpi-value" style="color:var(--color-success)">&#8369;<?php echo number_format($r['collected']??0,2); ?></div>
    <div class="rpt-kpi-sub"><?php echo $r['collection_rate']??0; ?>% of billed</div>
  </div>
  <div class="rpt-kpi-card" style="--rpt-accent:#DC3545">
    <div class="rpt-kpi-label">Outstanding</div>
    <div class="rpt-kpi-value" style="color:var(--color-danger)">&#8369;<?php echo number_format($r['outstanding_total']??0,2); ?></div>
    <div class="rpt-kpi-sub">All unpaid / partial</div>
  </div>
  <div class="rpt-kpi-card" style="--rpt-accent:#28A745">
    <div class="rpt-kpi-label">Fully Paid</div>
    <div class="rpt-kpi-value" style="color:var(--color-success)"><?php echo $statusMap['fully_paid']['cnt']; ?></div>
    <div class="rpt-kpi-sub">&#8369;<?php echo number_format($statusMap['fully_paid']['amount'],2); ?></div>
  </div>
  <div class="rpt-kpi-card" style="--rpt-accent:#FFC107">
    <div class="rpt-kpi-label">Partial Payments</div>
    <div class="rpt-kpi-value" style="color:var(--color-warning)"><?php echo $statusMap['partially_paid']['cnt']; ?></div>
    <div class="rpt-kpi-sub">&#8369;<?php echo number_format($statusMap['partially_paid']['amount'],2); ?></div>
  </div>
  <div class="rpt-kpi-card" style="--rpt-accent:#DC3545">
    <div class="rpt-kpi-label">Unpaid Invoices</div>
    <div class="rpt-kpi-value" style="color:var(--color-danger)"><?php echo $statusMap['unpaid']['cnt']; ?></div>
    <div class="rpt-kpi-sub">&#8369;<?php echo number_format($statusMap['unpaid']['amount'],2); ?></div>
  </div>
</div>

<div class="rpt-sl">Revenue Trend &amp; Payment Status</div>
<div class="rpt-row c21">
  <div class="rpt-card">
    <div class="rpt-card-head">
      <span class="rpt-card-title">Daily Revenue vs Collected</span>
      <span style="font-size:.72rem;color:var(--color-gray-500)"><?php echo $rangeDays; ?> days</span>
    </div>
    <div class="rpt-card-body"><canvas id="trendChart" height="90"></canvas></div>
  </div>
  <div class="rpt-card">
    <div class="rpt-card-head"><span class="rpt-card-title">Invoice Status Breakdown</span></div>
    <div class="rpt-card-body center"><div class="rpt-chart-wrap"><canvas id="statusChart" height="200"></canvas></div></div>
  </div>
</div>

<div class="rpt-sl">Receivables Analysis</div>
<div class="rpt-row c11">
  <div class="rpt-card">
    <div class="rpt-card-head">
      <span class="rpt-card-title">Receivables Aging (All Time)</span>
      <span style="font-size:.72rem;color:var(--color-gray-500)">Amount outstanding by age</span>
    </div>
    <div class="rpt-card-body"><canvas id="agingChart" height="140"></canvas></div>
  </div>
  <div class="rpt-card">
    <div class="rpt-card-head">
      <span class="rpt-card-title">Top Clients by Revenue</span>
      <span style="font-size:.72rem;color:var(--color-gray-500)">Billed vs Paid</span>
    </div>
    <div class="rpt-card-body"><canvas id="clientChart" height="140"></canvas></div>
  </div>
</div>

<div class="rpt-sl">Top Clients &mdash; <?php echo htmlspecialchars($dateRange); ?></div>
<div class="rpt-card" style="margin-bottom:16px">
  <div style="overflow-x:auto">
    <table class="rpt-tbl">
      <thead><tr><th>#</th><th>Client</th><th style="text-align:center">Invoices</th><th style="text-align:right">Total Billed</th><th style="text-align:right">Total Paid</th><th style="text-align:right">Balance</th></tr></thead>
      <tbody>
        <?php foreach(($r['top_clients']??[]) as $i=>$client): ?>
        <tr>
          <td style="color:var(--color-gray-500);font-size:.75rem"><?php echo $i+1; ?></td>
          <td><?php echo htmlspecialchars($client['client_name']??'Walk-in'); ?></td>
          <td style="text-align:center"><?php echo (int)($client['invoice_count']??0); ?></td>
          <td style="text-align:right;font-weight:700">&#8369;<?php echo number_format($client['total_billed']??0,2); ?></td>
          <td style="text-align:right;color:var(--color-success)">&#8369;<?php echo number_format($client['total_paid_amount']??0,2); ?></td>
          <td style="text-align:right;color:var(--color-danger)">&#8369;<?php echo number_format(($client['total_billed']??0)-($client['total_paid_amount']??0),2); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($r['top_clients'])): ?><tr><td colspan="6" style="text-align:center;color:var(--color-gray-500);padding:24px">No data for this period</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="rpt-sl">Outstanding Receivables (All Time)</div>
<div class="rpt-card">
  <div class="rpt-card-head">
    <span class="rpt-card-title">Unpaid &amp; Partially Paid Invoices</span>
    <span style="font-size:.72rem;color:var(--color-danger)"><?php echo count($r['receivables']??[]); ?> invoice(s) outstanding</span>
  </div>
  <div style="overflow-x:auto">
    <table class="rpt-tbl">
      <thead><tr><th>Invoice #</th><th>Client</th><th style="text-align:right">Total</th><th style="text-align:right">Paid</th><th style="text-align:right">Balance</th><th>Status</th><th>Date</th><th style="text-align:right">Age</th></tr></thead>
      <tbody>
        <?php foreach(($r['receivables']??[]) as $row): ?>
        <?php
          $ageDays=(int)($row['days_outstanding']??0);
          $ageStyle=$ageDays>90?'color:var(--color-danger);font-weight:700':($ageDays>30?'color:var(--color-warning)':'');
          $stCls=match($row['payment_status']??''){
            'fully_paid'=>'rpt-b-paid','partially_paid'=>'rpt-b-partial',default=>'rpt-b-unpaid'
          };
          $stLabel=match($row['payment_status']??''){
            'fully_paid'=>'Fully Paid','partially_paid'=>'Partial',default=>'Unpaid'
          };
        ?>
        <tr>
          <td><strong><?php echo htmlspecialchars($row['invoice_number']??''); ?></strong></td>
          <td><?php echo htmlspecialchars($row['client_name']??'Walk-in'); ?></td>
          <td style="text-align:right">&#8369;<?php echo number_format($row['total_amount']??0,2); ?></td>
          <td style="text-align:right;color:var(--color-success)">&#8369;<?php echo number_format($row['total_paid']??0,2); ?></td>
          <td style="text-align:right;color:var(--color-danger);font-weight:700">&#8369;<?php echo number_format($row['balance_due']??0,2); ?></td>
          <td><span class="rpt-badge <?php echo $stCls; ?>"><?php echo $stLabel; ?></span></td>
          <td><?php echo !empty($row['invoice_date'])?date('M d, Y',strtotime($row['invoice_date'])):'-'; ?></td>
          <td style="text-align:right;<?php echo $ageStyle; ?>"><?php echo $ageDays; ?>d</td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($r['receivables'])): ?><tr><td colspan="8" style="text-align:center;color:var(--color-success);padding:36px">&#10003; No outstanding receivables</td></tr><?php endif; ?>
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
function fmtC(v){return'\u20b1'+Number(v).toLocaleString('en-PH',{minimumFractionDigits:2});}
function fmtA(v){if(v>=1e6)return'\u20b1'+(v/1e6).toFixed(1)+'M';if(v>=1000)return'\u20b1'+(v/1000).toFixed(0)+'k';return'\u20b1'+v;}
const yScl={beginAtZero:true,grid:{color:'#f0f0f0'},ticks:{callback:fmtA}};
const trend=<?php echo $jsTrend; ?>;
const aging=<?php echo $jsAging; ?>;
const status=<?php echo $jsStatus; ?>;
const clients=<?php echo $jsClients; ?>;
// 1. Trend
(function(){
  new Chart(document.getElementById('trendChart'),{
    type:'line',
    data:{labels:trend.labels,datasets:[
      {label:'Revenue',data:trend.revenue,borderColor:P.blue,backgroundColor:'rgba(0,86,179,0.1)',tension:0.4,fill:true,pointRadius:3,pointHoverRadius:6,borderWidth:2},
      {label:'Collected',data:trend.collected,borderColor:P.green,backgroundColor:'rgba(40,167,69,0.08)',tension:0.4,fill:true,pointRadius:3,pointHoverRadius:6,borderWidth:2}
    ]},
    options:{responsive:true,layout:{padding:{top:6}},interaction:{mode:'index',intersect:false},
      plugins:{legend:{position:'top',labels:{boxWidth:12,padding:12}},
        tooltip:{callbacks:{label:ctx=>' '+ctx.dataset.label+': '+fmtC(ctx.parsed.y)}},
        datalabels:{display:false}},
      scales:{y:yScl,x:{grid:{display:false}}}
    }
  });
})();
// 2. Status doughnut
(function(){
  new Chart(document.getElementById('statusChart'),{
    type:'doughnut',
    data:{labels:status.labels,datasets:[{data:status.data,backgroundColor:[P.green,P.yellow,P.red],borderWidth:2,hoverOffset:10}]},
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
// 3. Aging bar
(function(){
  new Chart(document.getElementById('agingChart'),{
    type:'bar',
    data:{labels:aging.labels,datasets:[{label:'Outstanding',data:aging.data,
      backgroundColor:[P.green,P.yellow,P.orange||'#FD7E14',P.red],borderRadius:6,borderSkipped:false}]},
    options:{responsive:true,layout:{padding:{top:22}},plugins:{legend:{display:false},
      tooltip:{callbacks:{label:ctx=>' '+fmtC(ctx.parsed.y)}},
      datalabels:{display:(ctx)=>ctx.dataset.data[ctx.dataIndex]>0,anchor:'end',align:'top',offset:2,
        formatter:v=>fmtC(v),font:{size:8,weight:'700'},color:'#444',clip:false}},
      scales:{y:yScl,x:{grid:{display:false}}}
    }
  });
})();
// 4. Top clients grouped bar
(function(){
  if(!clients.length)return;
  const labels=clients.map(c=>(c.client_name||'Walk-in').substring(0,16));
  const billed=clients.map(c=>parseFloat(c.total_billed||0));
  const paid=clients.map(c=>parseFloat(c.total_paid_amount||0));
  new Chart(document.getElementById('clientChart'),{
    type:'bar',
    data:{labels,datasets:[
      {label:'Billed',data:billed,backgroundColor:'rgba(0,86,179,0.8)',borderRadius:4,borderSkipped:false},
      {label:'Paid',data:paid,backgroundColor:'rgba(40,167,69,0.8)',borderRadius:4,borderSkipped:false}
    ]},
    options:{indexAxis:'y',responsive:true,layout:{padding:{right:10}},interaction:{mode:'index',intersect:false},
      plugins:{legend:{position:'top',labels:{boxWidth:12,padding:10}},
        tooltip:{callbacks:{label:ctx=>' '+ctx.dataset.label+': '+fmtC(ctx.parsed.x)}},
        datalabels:{display:false}},
      scales:{x:{beginAtZero:true,grid:{color:'#f0f0f0'},ticks:{callback:fmtA}},y:{grid:{display:false}}}
    }
  });
})();
function exportPDF(btn,filename){
  const el=document.getElementById('rpt-content');
  const orig=btn.innerHTML;
  btn.disabled=true;btn.innerHTML='\u231B Generating…';
  html2pdf().set({margin:[10,8],filename:filename,image:{type:'jpeg',quality:.98},html2canvas:{scale:2,useCORS:true,logging:false},jsPDF:{unit:'mm',format:'a4',orientation:'landscape'}}).from(el).save().then(()=>{btn.disabled=false;btn.innerHTML=orig;});}
function setPreset(days){
  const to=new Date(),from=new Date();
  from.setDate(from.getDate()-(days-1));
  document.getElementById('dateFrom').value=from.toISOString().slice(0,10);
  document.getElementById('dateTo').value=to.toISOString().slice(0,10);
  document.getElementById('filterForm').submit();
}
</script>
<?php
$content = ob_get_clean();
$title   = 'Financial Report | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
