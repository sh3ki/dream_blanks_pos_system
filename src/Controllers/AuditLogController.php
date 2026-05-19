<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->requirePermission(MODULE_AUDIT_LOGS, ACTION_VIEW);
        [$page, $perPage] = $this->paginate($request);
        $filters = $request->only(['user_id', 'action_type', 'module', 'date_from', 'date_to', 'search']);
        $result  = AuditLog::search($filters, $page, $perPage);

        if ($request->isApi()) {
            return $this->success(['logs' => $result['data'], 'pagination' => $result['pagination']]);
        }

        $db    = Database::getInstance();
        $users = $db->select(
            "SELECT id, CONCAT(first_name,' ',last_name,' (',username,')') as display_name FROM users WHERE deleted_at IS NULL ORDER BY first_name",
            []
        );

        return $this->view('audit-logs/index', [
            'logs'       => $result['data'],
            'pagination' => $result['pagination'],
            'filters'    => $filters,
            'users'      => $users,
            'title'      => 'Audit Logs',
            'pageTitle'  => 'Audit Logs',
        ]);
    }

    public function export(Request $request): Response
    {
        $this->requirePermission(MODULE_AUDIT_LOGS, ACTION_VIEW);
        $filters = $request->only(['user_id', 'action_type', 'module', 'date_from', 'date_to', 'search']);
        $result  = AuditLog::search($filters, 1, 2000);
        $rows    = $result['data'];

        if (empty($rows)) return $this->error('No data');

        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['ID', 'Date/Time', 'User', 'IP Address', 'Action', 'Module', 'Record ID', 'Description', 'Status']);
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id'],
                $row['created_at'],
                $row['user_name'] ?? '—',
                $row['ip_address'] ?? '',
                $row['action_type'],
                $row['module_name'] ?? '',
                $row['record_id'] ?? '',
                $row['description'] ?? '',
                $row['status'],
            ]);
        }
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
