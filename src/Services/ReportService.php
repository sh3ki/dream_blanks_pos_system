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
               COALESCE(SUM(total_amount),0) as total_sales,
               COUNT(*) as transaction_count,
               COALESCE(AVG(total_amount),0) as avg_transaction
             FROM invoices
             WHERE DATE(invoice_date) BETWEEN ? AND ? AND deleted_at IS NULL",
            $params
        );

        $byMode = $this->db->select(
            "SELECT primary_payment_mode as mode, COALESCE(SUM(total_amount),0) as amount
             FROM invoices
             WHERE DATE(invoice_date) BETWEEN ? AND ? AND deleted_at IS NULL
             GROUP BY primary_payment_mode",
            $params
        );

        $topProducts = $this->db->select(
            "SELECT p.name, SUM(ii.quantity) as total_qty, SUM(ii.line_total) as total_revenue
             FROM invoice_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN invoices i ON i.id = ii.invoice_id
             WHERE DATE(i.invoice_date) BETWEEN ? AND ? AND i.deleted_at IS NULL
             GROUP BY p.id, p.name
             ORDER BY total_revenue DESC
             LIMIT 10",
            $params
        );

        $salesByMode = [];
        foreach ($byMode as $row) {
            $salesByMode[$row['mode'] ?? 'other'] = (float)$row['amount'];
        }

        return [
            'total_sales'        => (float)($summary['total_sales'] ?? 0),
            'transaction_count'  => (int)($summary['transaction_count'] ?? 0),
            'average_transaction'=> (float)($summary['avg_transaction'] ?? 0),
            'sales_by_mode'      => $salesByMode,
            'top_products'       => $topProducts,
        ];
    }

    public function inventoryReport(): array
    {
        $summary = $this->db->selectOne(
            "SELECT
               COUNT(*) as total_products,
               SUM(CASE WHEN stock_status='in_stock' THEN 1 ELSE 0 END) as in_stock,
               SUM(CASE WHEN stock_status='low_stock' THEN 1 ELSE 0 END) as low_stock,
               SUM(CASE WHEN stock_status='out_of_stock' THEN 1 ELSE 0 END) as out_of_stock
             FROM stock_products WHERE deleted_at IS NULL"
        );

        $lowStockItems = $this->db->select(
            "SELECT sp.name, sp.code, sp.current_qty as quantity_on_hand, sp.low_stock_alert
             FROM stock_products sp
             WHERE sp.stock_status IN ('low_stock','out_of_stock') AND sp.deleted_at IS NULL
             ORDER BY sp.current_qty ASC"
        );

        $valuation = $this->db->selectOne(
            "SELECT COALESCE(SUM(p.cost_price * p.current_stock),0) as cost_value,
                    COALESCE(SUM(p.selling_price * p.current_stock),0) as selling_value
             FROM products p WHERE p.deleted_at IS NULL AND p.status='active'"
        );

        return [
            'summary'         => $summary,
            'low_stock_items' => $lowStockItems,
            'valuation'       => $valuation,
        ];
    }

    public function financialReport(string $dateFrom, string $dateTo): array
    {
        $params = [$dateFrom, $dateTo];

        $revenue = $this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount),0) as total, COALESCE(SUM(total_paid),0) as collected
             FROM invoices
             WHERE DATE(invoice_date) BETWEEN ? AND ? AND deleted_at IS NULL",
            $params
        );

        $outstanding = $this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount - total_paid),0) as balance
             FROM invoices
             WHERE payment_status != 'fully_paid' AND deleted_at IS NULL"
        );

        $receivables = $this->db->select(
            "SELECT i.invoice_number, c.full_name as client_name,
                    i.total_amount, i.total_paid, (i.total_amount - i.total_paid) as balance_due,
                    i.invoice_date, i.payment_status
             FROM invoices i
             LEFT JOIN clients c ON c.id = i.client_id
             WHERE i.payment_status != 'fully_paid' AND i.deleted_at IS NULL
             ORDER BY i.invoice_date ASC"
        );

        return [
            'total_revenue'      => (float)($revenue['total'] ?? 0),
            'collected'          => (float)($revenue['collected'] ?? 0),
            'outstanding_total'  => (float)($outstanding['balance'] ?? 0),
            'receivables'        => $receivables,
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

    public function dashboardCharts(string $period = 'week'): array
    {
        return [
            'sales_trend'          => $this->getSalesTrend($period),
            'payment_modes'        => $this->db->select(
                "SELECT primary_payment_mode as mode, COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as amount
                 FROM invoices WHERE deleted_at IS NULL GROUP BY primary_payment_mode"
            ),
            'monthly_revenue'      => $this->getMonthlyRevenue(),
            'hourly_sales'         => $this->getHourlySales(),
            'revenue_vs_collected' => $this->getRevenueVsCollected(),
            'invoice_status'       => $this->getInvoiceStatusChart(),
            'stock_status'         => $this->getStockStatusChart(),
            'top_products'         => $this->getTopProductsByRevenue(),
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

    private function getSalesTrend(string $period): array
    {
        $days   = $period === 'month' ? 30 : ($period === 'year' ? 365 : 7);
        $labels = [];
        $data   = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = $days <= 7 ? date('D M d', strtotime($date)) : date('M d', strtotime($date));
            $sales    = $this->db->selectOne(
                "SELECT COALESCE(SUM(total_amount),0) as t FROM invoices WHERE DATE(invoice_date)=? AND deleted_at IS NULL",
                [$date]
            );
            $data[] = (float)($sales['t'] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
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

    private function getInvoiceStatusChart(): array
    {
        $rows = $this->db->select(
            "SELECT payment_status, COUNT(*) as cnt FROM invoices WHERE deleted_at IS NULL GROUP BY payment_status"
        );
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

    private function getTopProductsByRevenue(): array
    {
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
