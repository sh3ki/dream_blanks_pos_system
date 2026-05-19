<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ReportService;
use App\Models\Notification;

class DashboardController extends Controller
{
    private ReportService $reportService;

    public function __construct()
    {
        $this->reportService = new ReportService();
    }

    public function index(Request $request): Response
    {
        $this->requirePermission(MODULE_DASHBOARD, ACTION_VIEW);
        $metrics        = $this->reportService->dashboardMetrics();
        $charts         = $this->reportService->dashboardCharts('week');
        $recentInvoices = $this->reportService->recentInvoices(10);
        $lowStockItems  = $this->reportService->lowStockAlerts(8);
        $userId         = $this->currentUserId();
        $unread         = $userId ? Notification::unreadCount($userId) : 0;

        if ($request->isApi()) {
            return $this->success(['metrics' => $metrics, 'charts' => $charts]);
        }

        return $this->view('dashboard/index', [
            'metrics'               => $metrics,
            'charts'                => $charts,
            'recentInvoices'        => $recentInvoices,
            'lowStockItems'         => $lowStockItems,
            'unread_notifications'  => $unread,
            'title'                 => 'Dashboard',
            'pageTitle'             => 'Dashboard',
        ]);
    }

    public function metrics(Request $request): Response
    {
        return $this->success($this->reportService->dashboardMetrics());
    }

    public function charts(Request $request): Response
    {
        $period   = $request->query('period', 'week');
        $dateFrom = $request->query('date_from') ?: null;
        $dateTo   = $request->query('date_to')   ?: null;
        // Validate date format (YYYY-MM-DD) to prevent injection
        if ($dateFrom && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = null;
        if ($dateTo   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = null;
        return $this->success($this->reportService->dashboardCharts($period, $dateFrom, $dateTo));
    }
}
