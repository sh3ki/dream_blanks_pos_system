<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->requirePermission(MODULE_AUDIT_LOGS, ACTION_VIEW);
        [$page, $perPage] = $this->paginate($request);
        $filters = $request->only(['user_id', 'action_type', 'module', 'date_from', 'date_to']);
        $result  = AuditLog::search($filters, $page, $perPage);
        return $this->success(['logs' => $result['data'], 'pagination' => $result['pagination']]);
    }

    public function export(Request $request): Response
    {
        $this->requirePermission(MODULE_AUDIT_LOGS, ACTION_VIEW);
        $filters = $request->only(['user_id', 'action_type', 'module', 'date_from', 'date_to']);
        $result  = AuditLog::search($filters, 1, 1000);
        $rows    = $result['data'];

        if (empty($rows)) return $this->error('No data');

        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys($rows[0]));
        foreach ($rows as $row) { fputcsv($output, $row); }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        $response = new \App\Core\Response();
        $response->setHeader('Content-Type', 'text/csv');
        $response->setHeader('Content-Disposition', 'attachment; filename="audit_logs_' . date('Ymd') . '.csv"');
        $response->setBody($csv);
        return $response;
    }
}
