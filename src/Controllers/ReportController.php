<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ReportService;
use App\Services\AuditService;

class ReportController extends Controller
{
    private ReportService $reportService;

    public function __construct()
    {
        $this->reportService = new ReportService();
    }

    public function sales(Request $request): Response
    {
        $this->requirePermission(MODULE_REPORTS_SALES, ACTION_VIEW);
        $from   = $request->query('date_from', date('Y-m-01'));
        $to     = $request->query('date_to', date('Y-m-d'));
        $format = $request->query('format', 'json');

        $data = $this->reportService->salesReport($from, $to);

        if ($format === 'csv') {
            return $this->exportCsv('sales_report', $data['top_products'] ?? []);
        }

        if ($request->isApi()) {
            return $this->success($data);
        }

        return $this->view('reports/sales', ['report' => $data, 'from' => $from, 'to' => $to, 'title' => 'Sales Report', 'pageTitle' => 'Sales Report']);
    }

    public function inventory(Request $request): Response
    {
        $this->requirePermission(MODULE_REPORTS_INVENTORY, ACTION_VIEW);
        $data = $this->reportService->inventoryReport();

        if ($request->isApi()) return $this->success($data);
        return $this->view('reports/inventory', ['report' => $data, 'title' => 'Inventory Report', 'pageTitle' => 'Inventory Report']);
    }

    public function financial(Request $request): Response
    {
        $this->requirePermission(MODULE_REPORTS_FINANCIAL, ACTION_VIEW);
        $from = $request->query('date_from', date('Y-m-01'));
        $to   = $request->query('date_to', date('Y-m-d'));
        $data = $this->reportService->financialReport($from, $to);

        if ($request->isApi()) return $this->success($data);
        return $this->view('reports/financial', ['report' => $data, 'from' => $from, 'to' => $to, 'title' => 'Financial Report' , 'pageTitle' => 'Financial Report']);
    }

    public function export(Request $request): Response
    {
        $type   = $request->query('type', 'sales');
        $moduleMap = ['sales' => MODULE_REPORTS_SALES, 'inventory' => MODULE_REPORTS_INVENTORY, 'financial' => MODULE_REPORTS_FINANCIAL];
        $this->requirePermission($moduleMap[$type] ?? MODULE_REPORTS_SALES, 'export');
        $from   = $request->query('date_from', date('Y-m-01'));
        $to     = $request->query('date_to', date('Y-m-d'));

        switch ($type) {
            case 'sales':
                $data  = $this->reportService->salesReport($from, $to);
                $rows  = $data['daily_summary'] ?? [];
                // Add headers matching the array keys
                if (!empty($rows)) {
                    $rows = array_map(fn($r) => [
                        'date'          => $r['date'],
                        'invoice_count' => $r['invoice_count'],
                        'revenue'       => number_format($r['revenue'], 2, '.', ''),
                        'collected'     => number_format($r['collected'], 2, '.', ''),
                    ], $rows);
                }
                $fname = 'sales_report';
                break;
            case 'inventory':
                $data  = $this->reportService->inventoryReport();
                $rows  = array_map(fn($r) => [
                    'name'            => $r['name'],
                    'code'            => $r['code'],
                    'current_qty'     => $r['current_qty'],
                    'low_stock_alert' => $r['low_stock_alert'],
                    'stock_status'    => $r['stock_status'],
                ], $data['all_stock'] ?? []);
                $fname = 'inventory_report';
                break;
            case 'financial':
                $data  = $this->reportService->financialReport($from, $to);
                $rows  = array_map(fn($r) => [
                    'invoice_number'   => $r['invoice_number'],
                    'client_name'      => $r['client_name'],
                    'total_amount'     => number_format($r['total_amount'], 2, '.', ''),
                    'total_paid'       => number_format($r['total_paid'], 2, '.', ''),
                    'balance_due'      => number_format($r['balance_due'], 2, '.', ''),
                    'payment_status'   => $r['payment_status'],
                    'invoice_date'     => $r['invoice_date'],
                    'days_outstanding' => $r['days_outstanding'],
                ], $data['receivables'] ?? []);
                $fname = 'financial_report';
                break;
            default:
                return $this->error('Invalid report type');
        }

        AuditService::log(AUDIT_VIEW, $moduleMap[$type] ?? MODULE_REPORTS_SALES, null, null, null, "Exported {$type} report");
        return $this->exportCsv($fname . '_' . date('Ymd'), $rows);
    }

    private function exportCsv(string $filename, array $rows): Response
    {
        if (empty($rows)) {
            return $this->error('No data to export');
        }

        $output   = fopen('php://temp', 'r+');
        $headers  = array_keys($rows[0]);
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        $response = new \App\Core\Response();
        $response->setHeader('Content-Type', 'text/csv');
        $response->setHeader('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
        $response->setBody($csv);
        return $response;
    }
}
