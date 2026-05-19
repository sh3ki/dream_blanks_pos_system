<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></title>
  <meta name="app-base-path" content="<?= htmlspecialchars(app_base_path()) ?>">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #f0f0f0; font-family: 'Segoe UI', Arial, sans-serif; color: #1a1a1a; font-size: 14px; }
    .invoice-page { background: white; max-width: 740px; margin: 0 auto; padding: 44px 48px; }
    @media print {
      body { background: white; }
      .no-print { display: none !important; }
      .invoice-page { padding: 24px; }
    }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 9px 12px; }
    th { background: #f9fafb; font-weight: 600; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: #374151; border-bottom: 2px solid #e5e7eb; }
    td { border-bottom: 1px solid #e5e7eb; font-size: .875rem; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: .75rem; font-weight: 700; text-transform: capitalize; }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-warning { background: #fef9c3; color: #854d0e; }
    .badge-danger  { background: #fee2e2; color: #991b1b; }
  </style>
</head>
<body>

<!-- Print/Download controls -->
<div class="no-print" id="printControls" style="text-align:center;padding:12px 16px;background:#1f2937;border-bottom:1px solid #374151;display:flex;align-items:center;justify-content:center;gap:10px">
  <button onclick="window.print()" style="padding:8px 18px;background:#4b5563;color:white;border:none;border-radius:6px;cursor:pointer;font-size:.875rem">
    🖨 Print
  </button>
  <button id="downloadPdfBtn" onclick="downloadInvoicePDF()" style="padding:8px 18px;background:#16a34a;color:white;border:none;border-radius:6px;cursor:pointer;font-size:.875rem">
    ↓ Download PDF
  </button>
  <button onclick="window.close()" style="padding:8px 18px;background:#6b7280;color:white;border:none;border-radius:6px;cursor:pointer;font-size:.875rem">
    ✕ Close
  </button>
</div>
<div class="no-print" id="downloadingMsg" style="display:none;text-align:center;padding:14px;background:#f0fdf4;border-bottom:1px solid #bbf7d0;font-size:.9rem;color:#14532d">
  Generating PDF, please wait…
</div>

<div class="invoice-page" id="invoice-page">

  <!-- HEADER -->
  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px">
    <div>
      <div style="font-size:1.6rem;font-weight:900;letter-spacing:1px;color:#111">DREAM BLANKS</div>
      <div style="font-size:.8rem;color:#6b7280;margin-top:2px">Customized Apparel &amp; Merchandise</div>
    </div>
    <div style="text-align:right;font-size:.8rem;color:#374151;line-height:1.6">
      <div style="font-weight:700;font-size:.9rem"><?= htmlspecialchars(\App\Models\Setting::get('business_name', 'Dream Blanks')) ?></div>
      <?php $phone = \App\Models\Setting::get('business_phone', ''); if ($phone): ?>
        <div><?= htmlspecialchars($phone) ?></div>
      <?php endif; ?>
      <?php $email = \App\Models\Setting::get('business_email', ''); if ($email): ?>
        <div><?= htmlspecialchars($email) ?></div>
        <?php $addr = \App\Models\Setting::get('business_address', ''); if ($addr): ?>
        <div><?= htmlspecialchars($addr) ?></div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- INVOICE TITLE -->
  <div style="text-align:center;border-top:2.5px solid #111;border-bottom:2.5px solid #111;padding:9px 0;margin-bottom:22px">
    <span style="font-size:1.3rem;font-weight:900;letter-spacing:3px">INVOICE</span>
  </div>

  <!-- BILL TO + INVOICE DETAILS -->
  <div style="display:flex;justify-content:space-between;margin-bottom:22px">
    <div>
      <div style="font-size:.7rem;font-weight:800;letter-spacing:.08em;color:#6b7280;margin-bottom:5px">BILL TO</div>
      <?php if (!empty($invoice['client_name'])): ?>
        <div style="font-weight:700;font-size:.95rem"><?= htmlspecialchars($invoice['client_name']) ?></div>
      <?php else: ?>
        <div style="color:#9ca3af;font-style:italic">Walk-in Customer</div>
      <?php endif; ?>
      <?php if (!empty($invoice['client_email'])): ?>
        <div style="font-size:.8rem;color:#374151;margin-top:2px"><?= htmlspecialchars($invoice['client_email']) ?></div>
      <?php endif; ?>
      <?php if (!empty($invoice['client_phone'])): ?>
        <div style="font-size:.8rem;color:#374151"><?= htmlspecialchars($invoice['client_phone']) ?></div>
      <?php endif; ?>
    </div>
    <div style="text-align:right">
      <div style="margin-bottom:6px">
        <span style="font-size:.7rem;font-weight:800;letter-spacing:.08em;color:#6b7280">INVOICE #</span><br>
        <strong style="font-size:.95rem"><?= htmlspecialchars($invoice['invoice_number']) ?></strong>
      </div>
      <div style="margin-bottom:6px">
        <span style="font-size:.7rem;font-weight:800;letter-spacing:.08em;color:#6b7280">DATE</span><br>
        <strong><?= date('F d, Y', strtotime($invoice['invoice_date'])) ?></strong>
      </div>
      <div>
        <?php
          $psCls = match($invoice['payment_status']) {
            'fully_paid'     => 'badge-success',
            'partially_paid' => 'badge-warning',
            default          => 'badge-danger',
          };
        ?>
        <span class="badge <?= $psCls ?>"><?= str_replace('_', ' ', $invoice['payment_status']) ?></span>
      </div>
    </div>
  </div>

  <div style="border-top:1px solid #e5e7eb;margin-bottom:18px"></div>

  <!-- ITEMS TABLE -->
  <?php
    $totalQty = 0;
    foreach ($invoice['items'] as $item) $totalQty += (int)$item['quantity'];
  ?>
  <table style="margin-bottom:20px">
    <thead>
      <tr>
        <th style="text-align:left">Description</th>
        <th style="text-align:center;width:60px">QTY</th>
        <th style="text-align:right;width:110px">Unit Price</th>
        <th style="text-align:right;width:110px">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($invoice['items'] as $item):
        $lineDiscount = (float)($item['discount'] ?? 0);
        $lineTotal    = (float)$item['unit_price'] * (int)$item['quantity'] - $lineDiscount;
      ?>
      <tr>
        <td>
          <strong><?= htmlspecialchars($item['product_name']) ?></strong>
          <?php if (!empty($item['variation_name'])): ?>
            <span style="color:#6b7280;font-size:.8rem"> (<?= htmlspecialchars($item['variation_name']) ?>)</span>
          <?php endif; ?>
          <?php if (!empty($item['sku'])): ?>
            <div style="font-size:.75rem;color:#9ca3af"><?= htmlspecialchars($item['sku']) ?></div>
          <?php endif; ?>
        </td>
        <td style="text-align:center"><?= (int)$item['quantity'] ?></td>
        <td style="text-align:right">
          <?= number_format((float)$item['unit_price'], 2) ?>
          <?php if ($lineDiscount > 0): ?>
            <div style="font-size:.75rem;color:#dc2626">-<?= number_format($lineDiscount, 2) ?></div>
          <?php endif; ?>
        </td>
        <td style="text-align:right"><strong><?= number_format($lineTotal, 2) ?></strong></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr style="background:#f9fafb">
        <td style="font-weight:700">Total QTY</td>
        <td style="text-align:center;font-weight:800;font-size:.95rem"><?= $totalQty ?></td>
        <td colspan="2"></td>
      </tr>
    </tfoot>
  </table>

  <!-- TOTALS -->
  <?php
    $discount   = (float)($invoice['discount_amount'] ?? 0);
    $tax        = (float)($invoice['tax_amount']      ?? 0);
    $fee        = (float)($invoice['additional_fee']  ?? 0);
    $totalPaid  = (float)($invoice['total_paid']      ?? 0);
    $totalAmt   = (float)($invoice['total_amount']    ?? 0);
    $balance    = $totalAmt - $totalPaid;
    $subtotal   = $totalAmt + $discount - $tax - $fee;
  ?>
  <div style="display:flex;justify-content:flex-end;margin-bottom:24px">
    <table style="width:260px;border-collapse:collapse">
      <tr>
        <td style="padding:5px 12px;color:#374151;border:none">Subtotal</td>
        <td style="padding:5px 12px;text-align:right;border:none">&#8369;<?= number_format($subtotal, 2) ?></td>
      </tr>
      <?php if ($discount > 0): ?>
      <tr>
        <td style="padding:5px 12px;color:#374151;border:none">Discount</td>
        <td style="padding:5px 12px;text-align:right;color:#dc2626;border:none">-&#8369;<?= number_format($discount, 2) ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($tax > 0): ?>
      <tr>
        <td style="padding:5px 12px;color:#374151;border:none">Tax</td>
        <td style="padding:5px 12px;text-align:right;border:none">&#8369;<?= number_format($tax, 2) ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($fee > 0): ?>
      <tr>
        <td style="padding:5px 12px;color:#374151;border:none">Additional Fee</td>
        <td style="padding:5px 12px;text-align:right;border:none">&#8369;<?= number_format($fee, 2) ?></td>
      </tr>
      <?php endif; ?>
      <tr style="border-top:2.5px solid #111">
        <td style="padding:10px 12px;font-weight:900;font-size:1rem;border:none">TOTAL</td>
        <td style="padding:10px 12px;text-align:right;font-weight:900;font-size:1rem;border:none">&#8369;<?= number_format($totalAmt, 2) ?></td>
      </tr>
      <tr>
        <td style="padding:5px 12px;font-weight:700;color:#166534;border:none">TOTAL PAID</td>
        <td style="padding:5px 12px;text-align:right;font-weight:700;color:#166534;border:none">&#8369;<?= number_format($totalPaid, 2) ?></td>
      </tr>
      <?php if ($balance > 0): ?>
      <tr>
        <td style="padding:5px 12px;font-weight:700;color:#dc2626;border:none">BALANCE DUE</td>
        <td style="padding:5px 12px;text-align:right;font-weight:700;color:#dc2626;border:none">&#8369;<?= number_format($balance, 2) ?></td>
      </tr>
      <?php endif; ?>
    </table>
  </div>

  <!-- PAYMENT HISTORY (no Reference column) -->
  <?php if (!empty($invoice['payments'])): ?>
  <div style="margin-bottom:24px">
    <div style="font-weight:700;margin-bottom:8px;font-size:.8rem;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">Payment History</div>
    <table>
      <thead>
        <tr>
          <th style="text-align:left">#</th>
          <th style="text-align:left">Date</th>
          <th style="text-align:left">Mode</th>
          <th style="text-align:right">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($invoice['payments'] as $i => $pay):
          $modeLabel = match($pay['payment_mode'] ?? '') { 'cash'=>'Cash','bdo'=>'Bank Transfer','gcash'=>'GCash', default=>strtoupper($pay['payment_mode']??'') };
        ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= date('M d, Y', strtotime($pay['payment_date'])) ?></td>
          <td><?= htmlspecialchars($modeLabel) ?></td>
          <td style="text-align:right">&#8369;<?= number_format((float)$pay['payment_amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <?php if (!empty($invoice['notes'])): ?>
  <div style="background:#f9fafb;padding:12px 16px;border-radius:6px;margin-bottom:24px;font-size:.875rem">
    <strong>Notes:</strong> <?= nl2br(htmlspecialchars($invoice['notes'])) ?>
  </div>
  <?php endif; ?>

  <!-- FOOTER -->
  <div style="border-top:1px solid #e5e7eb;padding-top:16px;display:flex;justify-content:space-between;font-size:.8rem;color:#374151;margin-top:8px">
    <div>Sales Staff: <strong><?= htmlspecialchars($invoice['created_by_name'] ?? '—') ?></strong></div>
    <div style="text-align:center">Authorized Signature: _______________</div>
    <div style="font-style:italic;color:#6b7280">Thank you for your Business!</div>
  </div>

</div><!-- /.invoice-page -->

<script>
const _invoiceNumber = '<?= htmlspecialchars($invoice['invoice_number']) ?>';

function downloadInvoicePDF() {
  const btn      = document.getElementById('downloadPdfBtn');
  const controls = document.getElementById('printControls');
  const msg      = document.getElementById('downloadingMsg');
  btn.disabled           = true;
  controls.style.display = 'none';
  msg.style.display      = 'block';

  const element = document.getElementById('invoice-page');
  const opt = {
    margin:      [8, 8, 8, 8],
    filename:    'Invoice-' + _invoiceNumber + '.pdf',
    image:       { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true, logging: false },
    jsPDF:       { unit: 'mm', format: 'a4', orientation: 'portrait' }
  };

  html2pdf().set(opt).from(element).save().then(function() {
    controls.style.display = '';
    msg.style.display      = 'none';
    btn.disabled = false;
  });
}

// Auto-download if ?download=1
(function() {
  if (new URLSearchParams(window.location.search).get('download') === '1') {
    document.getElementById('printControls').style.display = 'none';
    document.getElementById('downloadingMsg').style.display = 'block';
    window.addEventListener('load', function() {
      setTimeout(downloadInvoicePDF, 300);
    });
  }
})();
</script>
</body>
</html>

