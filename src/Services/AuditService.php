<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    public static function log(
        string $actionType,
        string $module,
        ?int $recordId = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        string $description = '',
        string $status = 'success'
    ): void {
        $userId = $_SESSION['user']['id'] ?? null;
        $ip     = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua     = $_SERVER['HTTP_USER_AGENT'] ?? '';

        AuditLog::log([
            'user_id'     => $userId,
            'action_type' => $actionType,
            'module_name' => $module,
            'record_id'   => $recordId,
            'old_value'   => $oldValue ? json_encode($oldValue) : null,
            'new_value'   => $newValue ? json_encode($newValue) : null,
            'ip_address'  => $ip,
            'user_agent'  => $ua,
            'status'      => $status,
            'description' => $description,
        ]);
    }
}
