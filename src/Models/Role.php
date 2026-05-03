<?php

namespace App\Models;

class Role extends Model
{
    protected static string $table = 'roles';
    protected static bool $softDelete = true;

    public static function getPermissions(int $roleId): array
    {
        return static::db()->select(
            "SELECT p.* FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?",
            [$roleId]
        );
    }

    public static function syncPermissions(int $roleId, array $permissionIds): void
    {
        static::db()->delete('role_permissions', 'role_id = ?', [$roleId]);
        foreach ($permissionIds as $pid) {
            static::db()->query(
                "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                [$roleId, $pid]
            );
        }
    }

    public static function findByName(string $name): ?array
    {
        return static::whereOne('name', $name);
    }

    public static function allActive(): array
    {
        return static::db()->select(
            "SELECT * FROM roles WHERE status = 'active' AND deleted_at IS NULL ORDER BY name"
        );
    }
}
