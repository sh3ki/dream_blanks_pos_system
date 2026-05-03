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
        $metrics = $this->reportService->dashboardMetrics();
        $charts  = $this->reportService->dashboardCharts('week');
        $userId  = $this->currentUserId();
        $unread  = $userId ? Notification::unreadCount($userId) : 0;

        if ($request->isApi()) {
            return $this->success(['metrics' => $metrics, 'charts' => $charts]);
        }

        return $this->view('dashboard/index', [
            'metrics'        => $metrics,
            'charts'         => $charts,
            'unread_notifications' => $unread,
            'title'          => 'Dashboard',
            'pageTitle' => 'Dashboard',
        ]);
    }

    public function metrics(Request $request): Response
    {
        return $this->success($this->reportService->dashboardMetrics());
    }

    public function charts(Request $request): Response
    {
        $period = $request->query('period', 'week');
        return $this->success($this->reportService->dashboardCharts($period));
    }
}
