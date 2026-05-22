<?php

namespace App\Services;

use App\Core\Database;

class ReportService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function salesReport(string $dateFrom, string $dateTo): array
    {
        $params = [$dateFrom, $dateTo];

        $summary = $this->db->selectOne(
            "SELECT
               COALESCE(SUM(total_amount),0)   as total_sales,
               COUNT(*)                        as transaction_count,
               COALESCE(AVG(total_amount),0)   as avg_transaction,
               COALESCE(SUM(total_paid),0)     as total_collected,
               COUNT(DISTINCT client_id)       as unique_clients
             FROM invoices
             WHERE DATE(invoice_date) BETWEEN ? AND ? AND deleted_at IS NULL",
            $params
        );

        $highestDay = $this->db->selectOne(
            "SELECT DATE(invoice_date) as day, COALESCE(SUM(total_amount),0) as amount
             FROM invoices WHERE DATE(invoice_date) BETWEEN ? AND ? AND deleted_at IS NULL
             GROUP BY DATE(invoice_date) ORDER BY amount DESC LIMIT 1",
            $params
        );

        $byMode = $this->db->select(
            "SELECT primary_payment_mode as mode, COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as amount
             FROM invoices WHERE DATE(invoice_date) BETWEEN ? AND ? AND deleted_at IS NULL
             GROUP BY primary_payment_mode ORDER BY amount DESC",
            $params
        );

        $topProductsByRevenue = $this->db->select(
            "SELECT p.name, SUM(ii.quantity) as total_qty, COALESCE(SUM(ii.line_total),0) as total_revenue
             FROM invoice_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN invoices i ON i.id = ii.invoice_id
             WHERE DATE(i.invoice_date) BETWEEN ? AND ? AND i.deleted_at IS NULL
             GROUP BY p.id, p.name ORDER BY total_revenue DESC LIMIT 10",
            $params
        );

        $topProductsByQty = $this->db->select(
            "SELECT p.name, SUM(ii.quantity) as total_qty, COALESCE(SUM(ii.line_total),0) as total_revenue
             FROM invoice_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN invoices i ON i.id = ii.invoice_id
             WHERE DATE(i.invoice_date) BETWEEN ? AND ? AND i.deleted_at IS NULL
             GROUP BY p.id, p.name ORDER BY total_qty DESC LIMIT 10",
            $params
        );

        // Daily trend with gap-filling
        $dailyRows = $this->db->select(
            "SELECT DATE(invoice_date) as day, COUNT(*) as invoice_count,
                    COALESCE(SUM(total_amount),0) as revenue, COALESCE(SUM(total_paid),0) as collected
             FROM invoices WHERE DATE(invoice_date) BETWEEN ? AND ? AND deleted_at IS NULL
             GROUP BY DATE(invoice_date) ORDER BY day ASC",
            $params
        );
        $dailyMap = [];
        foreach ($dailyRows as $dr) { $dailyMap[$dr['day']] = $dr; }

        $start        = new \DateTime($dateFrom);
        $end          = new \DateTime($dateTo);
        $totalDays    = (int)$start->diff($end)->days + 1;
        $trend        = ['labels' => [], 'revenue' => [], 'collected' => [], 'count' => []];
        $dailySummary = [];
        $cur          = clone $start;
        while ($cur <= $end) {
            $ds = $cur->format('Y-m-d');
            $trend['labels'][]    = $cur->format('M d');
            $trend['revenue'][]   = (float)($dailyMap[$ds]['revenue']       ?? 0);
            $trend['collected'][] = (float)($dailyMap[$ds]['collected']     ?? 0);
            $trend['count'][]     = (int)($dailyMap[$ds]['invoice_count']   ?? 0);
            $dailySummary[] = [
                'date'          => $ds,
                'invoice_count' => (int)($dailyMap[$ds]['invoice_count'] ?? 0),
                'revenue'       => (float)($dailyMap[$ds]['revenue']     ?? 0),
                'collected'     => (float)($dailyMap[$ds]['collected']   ?? 0),
            ];
            $cur->modify('+1 day');
        }

        // Sales by day of week
        $dowRows = $this->db->select(
            "SELECT DAYOFWEEK(invoice_date) as dow, COALESCE(SUM(total_amount),0) as amount, COUNT(*) as cnt
             FROM invoices WHERE DATE(invoice_date) BETWEEN ? AND ? AND deleted_at IS NULL
             GROUP BY DAYOFWEEK(invoice_date) ORDER BY dow",
            $params
        );
        $dowMap = [];
        foreach ($dowRows as $dwr) { $dowMap[(int)$dwr['dow']] = ['amount' => (float)$dwr['amount'], 'cnt' => (int)$dwr['cnt']]; }
        $dowLabels  = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $dowAmounts = [];
        for ($d = 1; $d <= 7; $d++) { $dowAmounts[] = $dowMap[$d]['amount'] ?? 0; }

        $totalRevenue   = (float)($summary['total_sales']     ?? 0);
        $totalCollected = (float)($summary['total_collected'] ?? 0);
        $collectionRate = $totalRevenue > 0 ? round($totalCollected / $totalRevenue * 100, 1) : 0;

        foreach ($topProductsByRevenue as &$p) {
            $p['pct'] = $totalRevenue > 0 ? round((float)$p['total_revenue'] / $totalRevenue * 100, 1) : 0;
        }
        unset($p);

        $salesByMode = [];
        foreach ($byMode as $row) { $salesByMode[$row['mode'] ?? 'other'] = (float)$row['amount']; }

        return [
            'total_sales'         => $totalRevenue,
            'transaction_count'   => (int)($summary['transaction_count']  ?? 0),
            'average_transaction' => (float)($summary['avg_transaction']  ?? 0),
            'total_collected'     => $totalCollected,
            'collection_rate'     => $collectionRate,
            'unique_clients'      => (int)($summary['unique_clients']     ?? 0),
            'highest_day'         => $highestDay,
            'sales_by_mode'       => $salesByMode,
            'payment_modes_raw'   => $byMode,
            'top_products'        => $topProductsByRevenue,
            'top_products_by_qty' => $topProductsByQty,
            'trend'               => $trend,
            'daily_summary'       => $dailySummary,
            'day_of_week'         => ['labels' => $dowLabels, 'data' => $dowAmounts],
        ];
    }

    public function inventoryReport(): array
    {
        $summary = $this->db->selectOne(
            "SELECT
               COUNT(*) as total_products,
               COALESCE(SUM(current_qty),0) as total_units,
               SUM(CASE WHEN stock_status='in_stock'     THEN 1 ELSE 0 END) as in_stock,
               SUM(CASE WHEN stock_status='low_stock'    THEN 1 ELSE 0 END) as low_stock,
               SUM(CASE WHEN stock_status='out_of_stock' THEN 1 ELSE 0 END) as out_of_stock
             FROM stock_products WHERE deleted_at IS NULL"
        );

        $valuation = $this->db->selectOne(
            "SELECT COALESCE(SUM(p.cost_price * p.current_stock),0)    as cost_value,
                    COALESCE(SUM(p.selling_price * p.current_stock),0) as selling_value
             FROM products p WHERE p.deleted_at IS NULL AND p.status='active'"
        );

        $highestStock = $this->db->select(
            "SELECT name, code, current_qty, low_stock_alert, stock_status
             FROM stock_products WHERE deleted_at IS NULL ORDER BY current_qty DESC LIMIT 10"
        );

        $lowestStock = $this->db->select(
            "SELECT name, code, current_qty, low_stock_alert, stock_status
             FROM stock_products WHERE deleted_at IS NULL ORDER BY current_qty ASC LIMIT 10"
        );

        $lowStockItems = $this->db->select(
            "SELECT sp.name, sp.code, sp.current_qty as quantity_on_hand, sp.low_stock_alert, sp.stock_status
             FROM stock_products sp
             WHERE sp.stock_status IN ('low_stock','out_of_stock') AND sp.deleted_at IS NULL
             ORDER BY sp.current_qty ASC"
        );

        $allStock = $this->db->select(
            "SELECT name, code, current_qty, low_stock_alert, stock_status
             FROM stock_products WHERE deleted_at IS NULL ORDER BY name ASC LIMIT 100"
        );

        $potentialProfit = (float)($valuation['selling_value'] ?? 0) - (float)($valuation['cost_value'] ?? 0);

        $stockStatus = [
            'labels' => ['In Stock', 'Low Stock', 'Out of Stock'],
            'data'   => [
                (int)($summary['in_stock']     ?? 0),
                (int)($summary['low_stock']    ?? 0),
                (int)($summary['out_of_stock'] ?? 0),
            ],
        ];

        $restockStats = $this->db->selectOne(
            "SELECT
               COUNT(*) as total_orders,
               SUM(CASE WHEN delivery_status='ordered'  THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN delivery_status='received' THEN 1 ELSE 0 END) as received
             FROM restock_orders"
        );

        return [
            'summary'          => $summary,
            'valuation'        => $valuation,
            'potential_profit' => $potentialProfit,
            'low_stock_items'  => $lowStockItems,
            'all_stock'        => $allStock,
            'highest_stock'    => $highestStock,
            'lowest_stock'     => $lowestStock,
            'stock_status'     => $stockStatus,
            'restock_stats'    => $restockStats ?? ['total_orders' => 0, 'pending' => 0, 'received' => 0],
        ];
    }

    public function financialReport(string $dateFrom, string $dateTo): array
    {
        $params = [$dateFrom, $dateTo];

        $revenue = $this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount),0) as total, COALESCE(SUM(total_paid),0) as collected,
                    COUNT(*) as invoice_count
             FROM invoices
             WHERE DATE(invoice_date) BETWEEN ? AND ? AND deleted_at IS NULL",
            $params
        );

        $outstanding = $this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount - total_paid),0) as balance
             FROM invoices
             WHERE payment_status != 'fully_paid' AND deleted_at IS NULL"
        );

        $statusRows = $this->db->select(
            "SELECT payment_status, COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as amount
             FROM invoices WHERE DATE(invoice_date) BETWEEN ? AND ? AND deleted_at IS NULL
             GROUP BY payment_status",
            $params
        );
        $statusMap = [
            'fully_paid'     => ['cnt' => 0, 'amount' => 0.0],
            'partially_paid' => ['cnt' => 0, 'amount' => 0.0],
            'unpaid'         => ['cnt' => 0, 'amount' => 0.0],
        ];
        foreach ($statusRows as $sr) {
            $statusMap[$sr['payment_status']] = ['cnt' => (int)$sr['cnt'], 'amount' => (float)$sr['amount']];
        }

        // Daily revenue trend with gap-filling
        $dailyRows = $this->db->select(
            "SELECT DATE(invoice_date) as day, COALESCE(SUM(total_amount),0) as revenue, COALESCE(SUM(total_paid),0) as collected
             FROM invoices WHERE DATE(invoice_date) BETWEEN ? AND ? AND deleted_at IS NULL
             GROUP BY DATE(invoice_date) ORDER BY day ASC",
            $params
        );
        $dailyMap = [];
        foreach ($dailyRows as $dr) { $dailyMap[$dr['day']] = $dr; }
        $start = new \DateTime($dateFrom);
        $end   = new \DateTime($dateTo);
        $trend = ['labels' => [], 'revenue' => [], 'collected' => []];
        $cur   = clone $start;
        while ($cur <= $end) {
            $ds = $cur->format('Y-m-d');
            $trend['labels'][]    = $cur->format('M d');
            $trend['revenue'][]   = (float)($dailyMap[$ds]['revenue']   ?? 0);
            $trend['collected'][] = (float)($dailyMap[$ds]['collected'] ?? 0);
            $cur->modify('+1 day');
        }

        // Receivables aging (all outstanding, not period-filtered)
        $agingRow = $this->db->selectOne(
            "SELECT
               COALESCE(SUM(CASE WHEN DATEDIFF(NOW(), invoice_date) <= 30 THEN (total_amount-total_paid) ELSE 0 END),0)               as age_0_30,
               COALESCE(SUM(CASE WHEN DATEDIFF(NOW(), invoice_date) BETWEEN 31 AND 60  THEN (total_amount-total_paid) ELSE 0 END),0)  as age_31_60,
               COALESCE(SUM(CASE WHEN DATEDIFF(NOW(), invoice_date) BETWEEN 61 AND 90  THEN (total_amount-total_paid) ELSE 0 END),0)  as age_61_90,
               COALESCE(SUM(CASE WHEN DATEDIFF(NOW(), invoice_date) > 90               THEN (total_amount-total_paid) ELSE 0 END),0)  as age_90plus
             FROM invoices WHERE payment_status != 'fully_paid' AND deleted_at IS NULL"
        );
        $aging = [
            'labels' => ['0-30 days', '31-60 days', '61-90 days', '90+ days'],
            'data'   => [
                (float)($agingRow['age_0_30']   ?? 0),
                (float)($agingRow['age_31_60']  ?? 0),
                (float)($agingRow['age_61_90']  ?? 0),
                (float)($agingRow['age_90plus'] ?? 0),
            ],
        ];

        $topClients = $this->db->select(
            "SELECT COALESCE(c.full_name,'Walk-in') as client_name, COUNT(*) as invoice_count,
                    COALESCE(SUM(i.total_amount),0) as total_billed, COALESCE(SUM(i.total_paid),0) as total_paid_amount
             FROM invoices i
             LEFT JOIN clients c ON c.id = i.client_id
             WHERE DATE(i.invoice_date) BETWEEN ? AND ? AND i.deleted_at IS NULL
             GROUP BY i.client_id, c.full_name ORDER BY total_billed DESC LIMIT 10",
            $params
        );

        $receivables = $this->db->select(
            "SELECT i.invoice_number, COALESCE(c.full_name,'Walk-in') as client_name,
                    i.total_amount, i.total_paid, (i.total_amount - i.total_paid) as balance_due,
                    i.invoice_date, i.payment_status,
                    DATEDIFF(NOW(), i.invoice_date) as days_outstanding
             FROM invoices i
             LEFT JOIN clients c ON c.id = i.client_id
             WHERE i.payment_status != 'fully_paid' AND i.deleted_at IS NULL
             ORDER BY balance_due DESC"
        );

        $unconfirmedPayments = $this->db->select(
            "SELECT pay.id, pay.payment_date, pay.payment_amount, pay.payment_mode,
                    pay.reference_number, pay.payment_photo_path, pay.payment_number,
                    i.invoice_number, COALESCE(c.full_name,'Walk-in') as client_name,
                    CONCAT(u.first_name,' ',u.last_name) as recorded_by_name
             FROM payments pay
             INNER JOIN invoices i ON i.id = pay.invoice_id AND i.deleted_at IS NULL
             LEFT JOIN clients c ON c.id = i.client_id
             INNER JOIN users u ON u.id = pay.recorded_by
             WHERE pay.is_confirmed = 0
             ORDER BY pay.payment_date DESC"
        );

        $totalRevenue   = (float)($revenue['total']     ?? 0);
        $totalCollected = (float)($revenue['collected'] ?? 0);
        $collectionRate = $totalRevenue > 0 ? round($totalCollected / $totalRevenue * 100, 1) : 0;

        return [
            'total_revenue'        => $totalRevenue,
            'collected'            => $totalCollected,
            'outstanding_total'    => (float)($outstanding['balance'] ?? 0),
            'invoice_count'        => (int)($revenue['invoice_count'] ?? 0),
            'collection_rate'      => $collectionRate,
            'status_map'           => $statusMap,
            'trend'                => $trend,
            'aging'                => $aging,
            'top_clients'          => $topClients,
            'receivables'          => $receivables,
            'unconfirmed_payments' => $unconfirmedPayments,
            'status_chart'       => [
                'labels' => ['Fully Paid', 'Partially Paid', 'Unpaid'],
                'data'   => [$statusMap['fully_paid']['cnt'], $statusMap['partially_paid']['cnt'], $statusMap['unpaid']['cnt']],
            ],
        ];
    }

    public function dashboardMetrics(): array
    {
        $today          = date('Y-m-d');
        $yesterday      = date('Y-m-d', strtotime('-1 day'));
        $weekStart      = date('Y-m-d', strtotime('monday this week'));
        $monthStart     = date('Y-m-01');
        $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
        $lastMonthEnd   = date('Y-m-t',  strtotime('-1 month'));

        $salesToday = (float)($this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount),0) as t FROM invoices WHERE DATE(invoice_date)=? AND deleted_at IS NULL", [$today]
        )['t'] ?? 0);

        $salesYesterday = (float)($this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount),0) as t FROM invoices WHERE DATE(invoice_date)=? AND deleted_at IS NULL", [$yesterday]
        )['t'] ?? 0);

        $salesWeek = (float)($this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount),0) as t FROM invoices WHERE DATE(invoice_date)>=? AND deleted_at IS NULL", [$weekStart]
        )['t'] ?? 0);

        $salesMonth = (float)($this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount),0) as t FROM invoices WHERE DATE(invoice_date)>=? AND deleted_at IS NULL", [$monthStart]
        )['t'] ?? 0);

        $salesLastMonth = (float)($this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount),0) as t FROM invoices WHERE DATE(invoice_date) BETWEEN ? AND ? AND deleted_at IS NULL",
            [$lastMonthStart, $lastMonthEnd]
        )['t'] ?? 0);

        $outstanding = (float)($this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount-total_paid),0) as t FROM invoices WHERE payment_status!='fully_paid' AND deleted_at IS NULL"
        )['t'] ?? 0);

        $invoicesToday = (int)($this->db->selectOne(
            "SELECT COUNT(*) as c FROM invoices WHERE DATE(invoice_date)=? AND deleted_at IS NULL", [$today]
        )['c'] ?? 0);

        $invoicesMonth = (int)($this->db->selectOne(
            "SELECT COUNT(*) as c FROM invoices WHERE DATE(invoice_date)>=? AND deleted_at IS NULL", [$monthStart]
        )['c'] ?? 0);

        $avgInvoiceMonth = (float)($this->db->selectOne(
            "SELECT COALESCE(AVG(total_amount),0) as t FROM invoices WHERE DATE(invoice_date)>=? AND deleted_at IS NULL", [$monthStart]
        )['t'] ?? 0);

        $collectedMonth = (float)($this->db->selectOne(
            "SELECT COALESCE(SUM(total_paid),0) as t FROM invoices WHERE DATE(invoice_date)>=? AND deleted_at IS NULL", [$monthStart]
        )['t'] ?? 0);

        $totalClients = (int)($this->db->selectOne(
            "SELECT COUNT(*) as c FROM clients WHERE deleted_at IS NULL AND status='active'"
        )['c'] ?? 0);

        $newClientsMonth = (int)($this->db->selectOne(
            "SELECT COUNT(*) as c FROM clients WHERE DATE(created_at)>=? AND deleted_at IS NULL", [$monthStart]
        )['c'] ?? 0);

        $activeProducts = (int)($this->db->selectOne(
            "SELECT COUNT(*) as c FROM products WHERE deleted_at IS NULL AND status='active'"
        )['c'] ?? 0);

        $lowStock = (int)($this->db->selectOne(
            "SELECT COUNT(*) as c FROM stock_products WHERE stock_status='low_stock' AND deleted_at IS NULL"
        )['c'] ?? 0);

        $outOfStock = (int)($this->db->selectOne(
            "SELECT COUNT(*) as c FROM stock_products WHERE stock_status='out_of_stock' AND deleted_at IS NULL"
        )['c'] ?? 0);

        $pendingRestocks = (int)($this->db->selectOne(
            "SELECT COUNT(*) as c FROM restock_orders WHERE delivery_status='ordered'"
        )['c'] ?? 0);

        $invStatus = $this->db->select(
            "SELECT payment_status, COUNT(*) as cnt FROM invoices WHERE deleted_at IS NULL GROUP BY payment_status"
        );
        $statusMap = ['fully_paid' => 0, 'partially_paid' => 0, 'unpaid' => 0];
        foreach ($invStatus as $row) {
            $statusMap[$row['payment_status']] = (int)$row['cnt'];
        }

        return [
            'total_sales_today'       => $salesToday,
            'total_sales_yesterday'   => $salesYesterday,
            'total_sales_week'        => $salesWeek,
            'total_sales_month'       => $salesMonth,
            'total_sales_last_month'  => $salesLastMonth,
            'outstanding_receivables' => $outstanding,
            'invoices_today'          => $invoicesToday,
            'invoices_month'          => $invoicesMonth,
            'avg_invoice_month'       => $avgInvoiceMonth,
            'collected_month'         => $collectedMonth,
            'total_clients'           => $totalClients,
            'new_clients_month'       => $newClientsMonth,
            'active_products'         => $activeProducts,
            'low_stock_items'         => $lowStock,
            'out_of_stock_items'      => $outOfStock,
            'pending_restocks'        => $pendingRestocks,
            'unpaid_invoices'         => $statusMap['unpaid'] + $statusMap['partially_paid'],
            'invoice_status'          => $statusMap,
        ];
    }

    public function dashboardCharts(string $period = 'week', ?string $dateFrom = null, ?string $dateTo = null): array
    {
        return [
            'sales_trend'          => $this->getSalesTrend($period, $dateFrom, $dateTo),
            'payment_modes'        => $this->getPaymentModes($dateFrom, $dateTo),
            'monthly_revenue'      => $this->getMonthlyRevenue(),
            'hourly_sales'         => $this->getHourlySales(),
            'revenue_vs_collected' => $this->getRevenueVsCollected(),
            'invoice_status'       => $this->getInvoiceStatusChart($dateFrom, $dateTo),
            'stock_status'         => $this->getStockStatusChart(),
            'top_products'         => $this->getTopProductsByRevenue($dateFrom, $dateTo),
        ];
    }

    public function recentInvoices(int $limit = 10): array
    {
        return $this->db->select(
            "SELECT i.id, i.invoice_number, c.full_name as client_name, i.total_amount,
                    i.total_paid, i.payment_status, i.invoice_date, i.primary_payment_mode
             FROM invoices i
             LEFT JOIN clients c ON c.id = i.client_id
             WHERE i.deleted_at IS NULL
             ORDER BY i.invoice_date DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function lowStockAlerts(int $limit = 8): array
    {
        return $this->db->select(
            "SELECT name, code, current_qty, low_stock_alert, stock_status
             FROM stock_products
             WHERE stock_status IN ('low_stock','out_of_stock') AND deleted_at IS NULL
             ORDER BY current_qty ASC
             LIMIT ?",
            [$limit]
        );
    }

    private function getSalesTrend(string $period, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $labels = [];
        $data   = [];

        if ($dateFrom && $dateTo) {
            $start    = new \DateTime($dateFrom);
            $end      = new \DateTime($dateTo);
            $diffDays = (int)$start->diff($end)->days + 1;
            $fmt      = $diffDays <= 62 ? 'M d' : 'M d';
            $cur      = clone $start;
            while ($cur <= $end) {
                $date     = $cur->format('Y-m-d');
                $labels[] = $cur->format($fmt);
                $sales    = $this->db->selectOne(
                    "SELECT COALESCE(SUM(total_amount),0) as t FROM invoices WHERE DATE(invoice_date)=? AND deleted_at IS NULL",
                    [$date]
                );
                $data[] = (float)($sales['t'] ?? 0);
                $cur->modify('+1 day');
            }
        } else {
            $days = $period === 'month' ? 30 : ($period === 'year' ? 365 : 7);
            for ($i = $days - 1; $i >= 0; $i--) {
                $date     = date('Y-m-d', strtotime("-{$i} days"));
                $labels[] = $days <= 7 ? date('D M d', strtotime($date)) : date('M d', strtotime($date));
                $sales    = $this->db->selectOne(
                    "SELECT COALESCE(SUM(total_amount),0) as t FROM invoices WHERE DATE(invoice_date)=? AND deleted_at IS NULL",
                    [$date]
                );
                $data[] = (float)($sales['t'] ?? 0);
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function getPaymentModes(?string $from = null, ?string $to = null): array
    {
        if ($from && $to) {
            return $this->db->select(
                "SELECT primary_payment_mode as mode, COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as amount
                 FROM invoices WHERE deleted_at IS NULL AND DATE(invoice_date) BETWEEN ? AND ?
                 GROUP BY primary_payment_mode",
                [$from, $to]
            );
        }
        return $this->db->select(
            "SELECT primary_payment_mode as mode, COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as amount
             FROM invoices WHERE deleted_at IS NULL GROUP BY primary_payment_mode"
        );
    }

    private function getMonthlyRevenue(): array
    {
        $labels    = [];
        $revenue   = [];
        $collected = [];

        for ($i = 11; $i >= 0; $i--) {
            $month    = date('Y-m', strtotime("-{$i} months"));
            $labels[] = date('M y', strtotime("-{$i} months"));
            $row      = $this->db->selectOne(
                "SELECT COALESCE(SUM(total_amount),0) as rev, COALESCE(SUM(total_paid),0) as col
                 FROM invoices WHERE DATE_FORMAT(invoice_date,'%Y-%m')=? AND deleted_at IS NULL",
                [$month]
            );
            $revenue[]   = (float)($row['rev'] ?? 0);
            $collected[] = (float)($row['col'] ?? 0);
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'collected' => $collected];
    }

    private function getHourlySales(): array
    {
        $today = date('Y-m-d');
        $rows  = $this->db->select(
            "SELECT HOUR(invoice_date) as hr, COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as amount
             FROM invoices WHERE DATE(invoice_date)=? AND deleted_at IS NULL
             GROUP BY HOUR(invoice_date) ORDER BY hr",
            [$today]
        );

        $labels = [];
        $data   = [];
        for ($h = 6; $h <= 22; $h++) {
            $labels[] = date('g A', mktime($h, 0, 0));
            $found    = 0.0;
            foreach ($rows as $r) {
                if ((int)$r['hr'] === $h) { $found = (float)$r['amount']; break; }
            }
            $data[] = $found;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function getRevenueVsCollected(): array
    {
        $labels    = [];
        $revenue   = [];
        $collected = [];

        for ($i = 5; $i >= 0; $i--) {
            $month    = date('Y-m', strtotime("-{$i} months"));
            $labels[] = date('M', strtotime("-{$i} months"));
            $row      = $this->db->selectOne(
                "SELECT COALESCE(SUM(total_amount),0) as rev, COALESCE(SUM(total_paid),0) as col
                 FROM invoices WHERE DATE_FORMAT(invoice_date,'%Y-%m')=? AND deleted_at IS NULL",
                [$month]
            );
            $revenue[]   = (float)($row['rev'] ?? 0);
            $collected[] = (float)($row['col'] ?? 0);
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'collected' => $collected];
    }

    private function getInvoiceStatusChart(?string $from = null, ?string $to = null): array
    {
        if ($from && $to) {
            $rows = $this->db->select(
                "SELECT payment_status, COUNT(*) as cnt FROM invoices
                 WHERE deleted_at IS NULL AND DATE(invoice_date) BETWEEN ? AND ?
                 GROUP BY payment_status",
                [$from, $to]
            );
        } else {
            $rows = $this->db->select(
                "SELECT payment_status, COUNT(*) as cnt FROM invoices WHERE deleted_at IS NULL GROUP BY payment_status"
            );
        }
        $map = ['fully_paid' => 0, 'partially_paid' => 0, 'unpaid' => 0];
        foreach ($rows as $r) {
            $map[$r['payment_status']] = (int)$r['cnt'];
        }

        return [
            'labels' => ['Fully Paid', 'Partially Paid', 'Unpaid'],
            'data'   => [$map['fully_paid'], $map['partially_paid'], $map['unpaid']],
        ];
    }

    private function getStockStatusChart(): array
    {
        $row = $this->db->selectOne(
            "SELECT
               SUM(CASE WHEN stock_status='in_stock'     THEN 1 ELSE 0 END) as in_stock,
               SUM(CASE WHEN stock_status='low_stock'    THEN 1 ELSE 0 END) as low_stock,
               SUM(CASE WHEN stock_status='out_of_stock' THEN 1 ELSE 0 END) as out_of_stock
             FROM stock_products WHERE deleted_at IS NULL"
        );

        return [
            'labels' => ['In Stock', 'Low Stock', 'Out of Stock'],
            'data'   => [
                (int)($row['in_stock']    ?? 0),
                (int)($row['low_stock']   ?? 0),
                (int)($row['out_of_stock']?? 0),
            ],
        ];
    }

    private function getTopProductsByRevenue(?string $from = null, ?string $to = null): array
    {
        if ($from && $to) {
            return $this->db->select(
                "SELECT p.name, SUM(ii.quantity) as total_qty, COALESCE(SUM(ii.line_total),0) as revenue
                 FROM invoice_items ii
                 INNER JOIN products p ON p.id = ii.product_id
                 INNER JOIN invoices i ON i.id = ii.invoice_id
                 WHERE i.deleted_at IS NULL AND DATE(i.invoice_date) BETWEEN ? AND ?
                 GROUP BY p.id, p.name
                 ORDER BY revenue DESC
                 LIMIT 8",
                [$from, $to]
            );
        }
        return $this->db->select(
            "SELECT p.name, SUM(ii.quantity) as total_qty, COALESCE(SUM(ii.line_total),0) as revenue
             FROM invoice_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN invoices i ON i.id = ii.invoice_id
             WHERE i.deleted_at IS NULL
             GROUP BY p.id, p.name
             ORDER BY revenue DESC
             LIMIT 8"
        );
    }
}
