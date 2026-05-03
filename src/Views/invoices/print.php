<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></title>
  <meta name="app-base-path" content="<?= htmlspecialchars(app_base_path()) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/assets/css/style.css')) ?>">
  <style>
    body { background:white; font-family:'Segoe UI',Arial,sans-serif; color:#2D2D2D; }
    .invoice-page { max-width:720px; margin:0 auto; padding:40px; }
    .invoice-header { display:flex; justify-content:space-between; margin-bottom:40px; }
    .business-info h2 { font-size:1.5rem; color:#0056B3; margin-bottom:4px; }
    .invoice-meta { text-align:right; }
    .invoice-meta h1 { font-size:2rem; color:#0056B3; }
    .invoice-meta .inv-num { font-size:.9rem; color:#808080; }
    .parties { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:32px; }
    .party-block { background:#F5F5F5; padding:16px; border-radius:6px; }
    .party-block h4 { font-size:.75rem; text-transform:uppercase; letter-spacing:.08em; color:#808080; margin-bottom:8px; }
    table { width:100%; border-collapse:collapse; margin-bottom:24px; }
    th { background:#F5F5F5; padding:10px 12px; text-align:left; font-size:.8rem; font-weight:600; text-transform:uppercase; border-bottom:2px solid #E8E8E8; }
    td { padding:10px 12px; border-bottom:1px solid #E8E8E8; }
    .totals-section { max-width:280px; margin-left:auto; }
    .totals-row { display:flex; justify-content:space-between; padding:6px 0; font-size:.9rem; }
    .totals-row.grand-total { font-size:1.1rem; font-weight:700; border-top:2px solid #2D2D2D; padding-top:10px; margin-top:4px; }
    .payment-history { margin-top:24px; }
    .footer-note { text-align:center; color:#808080; font-size:.85rem; margin-top:32px; padding-top:16px; border-top:1px solid #E8E8E8; }
    @media print { .no-print { display:none!important; } }
  </style>
</head>
<body>
<div class="no-print" style="text-align:center;padding:12px;background:#F5F5F5;border-bottom:1px solid #E8E8E8">
  <button onclick="window.print()" style="padding:8px 20px;background:#0056B3;color:white;border:none;border-radius:4px;cursor:pointer;font-size:.9rem;display:inline-flex;align-items:center;gap:6px"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6,9 6,2 18,2 18,9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg> Print</button>
  <button onclick="window.close()" style="padding:8px 20px;background:#808080;color:white;border:none;border-radius:4px;cursor:pointer;font-size:.9rem;margin-left:8px;display:inline-flex;align-items:center;gap:6px"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Close</button>
</div>

<div class="invoice-page">
  <div class="invoice-header">
    <div class="business-info">
      <h2>Dream Blanks</h2>
      <div style="font-size:.85rem;color:#505050">
        <div>📍 <?= htmlspecialchars(\App\Models\Setting::get('business_address', '')) ?></div>
        <div>📧 <?= htmlspecialchars(\App\Models\Setting::get('business_email', '')) ?></div>
        <div>📞 <?= htmlspecialchars(\App\Models\Setting::get('business_phone', '')) ?></div>
      </div>
    </div>
    <div class="invoice-meta">
      <h1>INVOICE</h1>
      <div class="inv-num">#<?= htmlspecialchars($invoice['invoice_number']) ?></div>
      <div style="margin-top:8px;font-size:.85rem">
        <div><strong>Date:</strong> <?= date('F d, Y', strtotime($invoice['invoice_date'])) ?></div>
        <div><strong>Time:</strong> <?= date('h:i A', strtotime($invoice['invoice_date'])) ?></div>
      </div>
      <div style="margin-top:8px">
        <?php
          $psCls = match($invoice['payment_status']) {
            'fully_paid' => '#28A745',
            'partially_paid' => '#FFC107',
            default => '#DC3545',
          };
        ?>
        <span style="background:<?= $psCls ?>;color:white;padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:700;text-transform:uppercase">
          <?= str_replace('_', ' ', $invoice['payment_status']) ?>
        </span>
      </div>
    </div>
  </div>

  <div class="parties">
    <div class="party-block">
      <h4>Billed To</h4>
      <?php if (!empty($invoice['client_name'])): ?>
        <strong><?= htmlspecialchars($invoice['client_name']) ?></strong>
      <?php else: ?>
        <em style="color:#808080">Walk-in Customer</em>
      <?php endif; ?>
    </div>
    <div class="party-block">
      <h4>Processed By</h4>
      <strong><?= htmlspecialchars($invoice['created_by_name']) ?></strong>
      <div style="font-size:.8rem;color:#808080;margin-top:4px">Payment: <?= strtoupper($invoice['primary_payment_mode'] ?? 'N/A') ?></div>
    </div>
  </div>

  <!-- Items -->
  <table>
    <thead>
      <tr>
        <th>#</th><th>Product</th><th>SKU</th><th style="text-align:right">Unit Price</th><th style="text-align:center">Qty</th><th style="text-align:right">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($invoice['items'] as $i => $item): ?>
      <tr>
        <td style="color:#808080"><?= $i + 1 ?></td>
        <td><strong><?= htmlspecialchars($item['product_name']) ?></strong></td>
        <td style="font-size:.8rem;color:#808080"><?= htmlspecialchars($item['sku']) ?></td>
        <td style="text-align:right">₱<?= number_format($item['unit_price'], 2) ?></td>
        <td style="text-align:center"><?= $item['quantity'] ?></td>
        <td style="text-align:right"><strong>₱<?= number_format($item['line_total'], 2) ?></strong></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Totals -->
  <div class="totals-section">
    <div class="totals-row"><span>Subtotal</span><span>₱<?= number_format($invoice['subtotal'], 2) ?></span></div>
    <?php if ((float)$invoice['discount_amount'] > 0): ?>
    <div class="totals-row" style="color:#DC3545"><span>Discount</span><span>-₱<?= number_format($invoice['discount_amount'], 2) ?></span></div>
    <?php endif; ?>
    <?php if ((float)$invoice['tax_amount'] > 0): ?>
    <div class="totals-row"><span>Tax</span><span>+₱<?= number_format($invoice['tax_amount'], 2) ?></span></div>
    <?php endif; ?>
    <?php if ((float)$invoice['additional_fee'] > 0): ?>
    <div class="totals-row"><span>Additional Fee</span><span>+₱<?= number_format($invoice['additional_fee'], 2) ?></span></div>
    <?php endif; ?>
    <div class="totals-row grand-total"><span>TOTAL</span><span>₱<?= number_format($invoice['total_amount'], 2) ?></span></div>
    <div class="totals-row" style="color:#28A745"><span>Total Paid</span><span>₱<?= number_format($invoice['total_paid'], 2) ?></span></div>
    <?php $balance = (float)$invoice['total_amount'] - (float)$invoice['total_paid']; ?>
    <?php if ($balance > 0): ?>
    <div class="totals-row" style="color:#DC3545;font-weight:700"><span>Balance Due</span><span>₱<?= number_format($balance, 2) ?></span></div>
    <?php endif; ?>
  </div>

  <!-- Payment History -->
  <?php if (!empty($invoice['payments'])): ?>
  <div class="payment-history">
    <h4 style="margin-bottom:12px;font-size:.875rem;text-transform:uppercase;letter-spacing:.05em;color:#808080">Payment History</h4>
    <table>
      <thead><tr><th>#</th><th>Date</th><th>Mode</th><th>Reference</th><th style="text-align:right">Amount</th></tr></thead>
      <tbody>
        <?php foreach ($invoice['payments'] as $pay): ?>
        <tr>
          <td><?= $pay['payment_number'] ?></td>
          <td><?= date('M d, Y', strtotime($pay['payment_date'])) ?></td>
          <td><?= strtoupper($pay['payment_mode']) ?></td>
          <td style="color:#808080"><?= htmlspecialchars($pay['reference_number'] ?? '-') ?></td>
          <td style="text-align:right">₱<?= number_format($pay['payment_amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <?php if (!empty($invoice['notes'])): ?>
  <div style="background:#F5F5F5;padding:12px 16px;border-radius:6px;margin-top:20px">
    <strong style="font-size:.8rem">Notes:</strong>
    <p style="margin-top:4px;font-size:.875rem"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
  </div>
  <?php endif; ?>

  <div class="footer-note">
    <p><?= htmlspecialchars(\App\Models\Setting::get('receipt_footer', 'Thank you for your business!')) ?></p>
    <p style="margin-top:4px;font-size:.75rem">Dream Blanks POS System</p>
  </div>
</div>
</body>
</html>
