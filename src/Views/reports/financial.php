<?php ob_start(); ?>
<?php
$from = $from ?? date('Y-m-01');
$to   = $to ?? date('Y-m-d');
$exportUrl = '/api/v1/reports/export?type=financial&date_from=' . urlencode($from) . '&date_to=' . urlencode($to);
?>
<div class="page-header">
  <h1>Financial Report</h1>
  <a href="<?= $exportUrl ?>" class="btn btn-secondary">⬇ Export CSV</a>
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
    <div class="stats-header"><span class="stats-label">Total Revenue</span><span class="stats-icon">💵</span></div>
    <div class="stats-value">₱<?= number_format($report['total_revenue'] ?? 0, 2) ?></div>
    <div class="stats-change text-muted">Selected period</div>
  </div>
  <div class="stats-card">
    <div class="stats-header"><span class="stats-label">Collected</span><span class="stats-icon">✅</span></div>
    <div class="stats-value">₱<?= number_format($report['collected'] ?? 0, 2) ?></div>
    <div class="stats-change text-muted">Payments received</div>
  </div>
  <div class="stats-card">
    <div class="stats-header"><span class="stats-label">Outstanding</span><span class="stats-icon"><?= icon('alert', 20) ?></span></div>
    <div class="stats-value" style="color:var(--color-danger)">₱<?= number_format($report['outstanding_total'] ?? 0, 2) ?></div>
    <div class="stats-change text-muted">Receivables</div>
  </div>
</div>

<div class="card" style="margin-top:16px">
  <div class="card-header"><h3 class="card-title">Outstanding Receivables</h3></div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr><th>Invoice #</th><th>Client</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Date</th></tr>
      </thead>
      <tbody>
        <?php foreach (($report['receivables'] ?? []) as $row): ?>
        <?php
          $statusCls = match($row['payment_status']) {
            'fully_paid' => 'badge-success',
            'partially_paid' => 'badge-warning',
            default => 'badge-danger',
          };
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($row['invoice_number'] ?? '') ?></strong></td>
          <td><?= htmlspecialchars($row['client_name'] ?? 'Walk-in') ?></td>
          <td>₱<?= number_format($row['total_amount'] ?? 0, 2) ?></td>
          <td>₱<?= number_format($row['total_paid'] ?? 0, 2) ?></td>
          <td><span style="color:var(--color-danger)">₱<?= number_format($row['balance_due'] ?? 0, 2) ?></span></td>
          <td><span class="badge <?= $statusCls ?>"><?= str_replace('_', ' ', ucfirst($row['payment_status'] ?? '')) ?></span></td>
          <td><?= !empty($row['invoice_date']) ? date('M d, Y', strtotime($row['invoice_date'])) : '-' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($report['receivables'])): ?>
          <tr><td colspan="7" class="text-center text-muted" style="padding:36px">No outstanding invoices</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$content = ob_get_clean();
$title   = 'Financial Report | Dream Blanks POS';
require VIEW_PATH . '/layouts/main.php';
?>
