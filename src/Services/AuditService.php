<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    /**
     * Write an audit log entry.
     */
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

        // Resolve real client IP, checking proxy headers in priority order
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = trim($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = trim($_SERVER['HTTP_X_REAL_IP']);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        // Normalize IPv6 loopback and IPv4-mapped IPv6 addresses
        if ($ip === '::1') $ip = '127.0.0.1';
        if (str_starts_with($ip, '::ffff:')) $ip = substr($ip, 7);

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        AuditLog::log([
            'user_id'     => $userId,
            'action_type' => $actionType,
            'module_name' => $module,
            'record_id'   => $recordId,
            'old_value'   => $oldValue ? json_encode($oldValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'new_value'   => $newValue ? json_encode($newValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'ip_address'  => $ip,
            'user_agent'  => $ua,
            'status'      => $status,
            'description' => $description,
        ]);
    }

    /**
     * Compute field-level diff between old and new arrays.
     * Returns only fields that changed, with 'from' and 'to' values.
     * Skips internal/irrelevant fields.
     */
    public static function diff(array $old, array $new): array
    {
        $skip    = ['updated_at', 'deleted_at', 'password_hash', 'remember_token', 'otp', 'otp_expires_at', 'reset_token'];
        $changes = [];
        $keys    = array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($keys as $key) {
            if (in_array($key, $skip, true)) continue;
            $oldVal = $old[$key] ?? null;
            $newVal = $new[$key] ?? null;
            if ((string)$oldVal !== (string)$newVal) {
                $changes[$key] = ['from' => $oldVal, 'to' => $newVal];
            }
        }

        return $changes;
    }
}
