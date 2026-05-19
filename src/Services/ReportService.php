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
        $today  = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $monthStart = date('Y-m-01');

        $salesToday = (float)($this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount),0) as t FROM invoices WHERE DATE(invoice_date)=? AND deleted_at IS NULL", [$today]
        )['t'] ?? 0);

        $salesWeek = (float)($this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount),0) as t FROM invoices WHERE DATE(invoice_date)>=? AND deleted_at IS NULL", [$weekStart]
        )['t'] ?? 0);

        $salesMonth = (float)($this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount),0) as t FROM invoices WHERE DATE(invoice_date)>=? AND deleted_at IS NULL", [$monthStart]
        )['t'] ?? 0);

        $outstanding = (float)($this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount-total_paid),0) as t FROM invoices WHERE payment_status!='fully_paid' AND deleted_at IS NULL"
        )['t'] ?? 0);

        $lowStock = (int)($this->db->selectOne(
            "SELECT COUNT(*) as c FROM stock_products WHERE stock_status IN ('low_stock','out_of_stock') AND deleted_at IS NULL"
        )['c'] ?? 0);

        $pendingRestocks = (int)($this->db->selectOne(
            "SELECT COUNT(*) as c FROM restock_orders WHERE delivery_status='ordered'"
        )['c'] ?? 0);

        return [
            'total_sales_today'      => $salesToday,
            'total_sales_week'       => $salesWeek,
            'total_sales_month'      => $salesMonth,
            'outstanding_receivables'=> $outstanding,
            'low_stock_items'        => $lowStock,
            'pending_restocks'       => $pendingRestocks,
        ];
    }

    public function dashboardCharts(string $period = 'week'): array
    {
        $salesTrend  = $this->getSalesTrend($period);
        $topProducts = $this->db->select(
            "SELECT p.name, SUM(ii.quantity) as qty
             FROM invoice_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN invoices i ON i.id = ii.invoice_id
             WHERE i.deleted_at IS NULL
             GROUP BY p.id ORDER BY qty DESC LIMIT 5"
        );

        $payModes = $this->db->select(
            "SELECT primary_payment_mode as mode, COUNT(*) as cnt
             FROM invoices WHERE deleted_at IS NULL GROUP BY primary_payment_mode"
        );

        return [
            'sales_trend' => $salesTrend,
            'top_products'=> $topProducts,
            'payment_modes'=> $payModes,
        ];
    }

    private function getSalesTrend(string $period): array
    {
        $days   = $period === 'month' ? 30 : ($period === 'year' ? 365 : 7);
        $labels = [];
        $data   = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('M d', strtotime($date));
            $sales    = $this->db->selectOne(
                "SELECT COALESCE(SUM(total_amount),0) as t FROM invoices WHERE DATE(invoice_date)=? AND deleted_at IS NULL",
                [$date]
            );
            $data[] = (float)($sales['t'] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
