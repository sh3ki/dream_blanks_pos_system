<?php

namespace App\Models;

class AuditLog extends Model
{
    protected static string $table = 'audit_logs';
    protected static bool $softDelete = true;

    public static function log(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        return static::db()->insert('audit_logs', $data);
    }

    public static function search(array $filters, int $page, int $perPage): array
    {
        $where  = "al.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['user_id'])) {
            $where   .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action_type'])) {
            $where   .= " AND al.action_type = ?";
            $params[] = $filters['action_type'];
        }

        if (!empty($filters['module'])) {
            $where   .= " AND al.module_name = ?";
            $params[] = $filters['module'];
        }

        if (!empty($filters['date_from'])) {
            $where   .= " AND DATE(al.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where   .= " AND DATE(al.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $like     = '%' . $filters['search'] . '%';
            $where   .= " AND (al.description LIKE ? OR al.ip_address LIKE ? OR CONCAT(u.first_name,' ',u.last_name) LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $db    = static::db();
        $total = (int)($db->selectOne(
            "SELECT COUNT(*) as cnt FROM audit_logs al LEFT JOIN users u ON u.id = al.user_id WHERE {$where}",
            $params
        )['cnt'] ?? 0);
        $offset = ($page - 1) * $perPage;
        $sql   = "SELECT al.*, CONCAT(u.first_name,' ',u.last_name) as user_name, u.profile_image as user_profile_image
                  FROM audit_logs al
                  LEFT JOIN users u ON u.id = al.user_id
                  WHERE {$where}
                  ORDER BY al.created_at DESC
                  LIMIT {$perPage} OFFSET {$offset}";
        $items = $db->select($sql, $params);

        return ['data' => $items, 'pagination' => ['current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => (int)ceil($total / $perPage)]];
    }
}
