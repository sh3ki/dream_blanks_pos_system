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
        return $grouped;
    }

    public static function findByModuleAction(string $module, string $action): ?array
    {
        return static::db()->selectOne(
            "SELECT * FROM permissions WHERE module = ? AND action = ?",
            [$module, $action]
        );
    }
}
