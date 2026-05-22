<?php

namespace App\Models;

class Permission extends Model
{
    protected static string $table = 'permissions';
    protected static bool $timestamps = true;

    public static function allGrouped(): array
    {
        $rows   = static::db()->select("SELECT * FROM permissions ORDER BY module, action");
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['module']][] = $row;
        }

        // Return in a logical page order
        $order  = ['dashboard','users','roles','clients','products','stock_products','inventory','variations','pos','invoices','payments','project_lineup','transactions','reports_sales','reports_inventory','reports_financial','settings','audit_logs','notifications'];
        $sorted = [];
        foreach ($order as $mod) {
            if (isset($grouped[$mod])) {
                $sorted[$mod] = $grouped[$mod];
            }
        }
        // Append any modules not in the order list
        foreach ($grouped as $mod => $perms) {
            if (!isset($sorted[$mod])) {
                $sorted[$mod] = $perms;
            }
        }
        return $sorted;
    }

    public static function findByModuleAction(string $module, string $action): ?array
    {
        return static::db()->selectOne(
            "SELECT * FROM permissions WHERE module = ? AND action = ?",
            [$module, $action]
        );
    }
}
