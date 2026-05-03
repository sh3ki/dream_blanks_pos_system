<?php

namespace App\Models;

class User extends Model
{
    protected static string $table = 'users';
    protected static bool $softDelete = true;

    public static function findByEmail(string $email): ?array
    {
        return static::db()->selectOne(
            "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL",
            [$email]
        );
    }

    public static function findByUsername(string $username): ?array
    {
        return static::db()->selectOne(
            "SELECT * FROM users WHERE username = ? AND deleted_at IS NULL",
            [$username]
        );
    }

    public static function findByEmailOrUsername(string $value): ?array
    {
        return static::db()->selectOne(
            "SELECT * FROM users WHERE (email = ? OR username = ?) AND deleted_at IS NULL",
            [$value, $value]
        );
    }

    public static function getRoles(int $userId): array
    {
        return static::db()->select(
            "SELECT r.* FROM roles r
             INNER JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = ? AND r.status = 'active' AND r.deleted_at IS NULL",
            [$userId]
        );
    }

    public static function getPermissions(int $userId): array
    {
        return static::db()->select(
            "SELECT DISTINCT p.module, p.action FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             INNER JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = ?",
            [$userId]
        );
    }

    public static function search(string $term, int $page, int $perPage, string $status = '', string $sort = 'created_at', string $order = 'DESC'): array
    {
        $allowed = ['first_name','last_name','username','email','status','last_login','created_at'];
        $sort    = in_array($sort, $allowed) ? $sort : 'created_at';
        $order   = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $where  = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?) AND deleted_at IS NULL";
        $params = ["%{$term}%", "%{$term}%", "%{$term}%", "%{$term}%"];

        if ($status) {
            $where   .= " AND status = ?";
            $params[] = $status;
        }

        return static::paginate($page, $perPage, $where, $params, $sort, $order);
    }

    public static function assignRole(int $userId, int $roleId): void
    {
        static::db()->query(
            "INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)",
            [$userId, $roleId]
        );
    }

    public static function removeAllRoles(int $userId): void
    {
        static::db()->delete('user_roles', 'user_id = ?', [$userId]);
    }

    public static function updateLastLogin(int $userId): void
    {
        static::db()->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);
    }

    public static function storeOtp(string $email, string $otp): void
    {
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        static::db()->query(
            "INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE otp = VALUES(otp), expires_at = VALUES(expires_at), created_at = NOW()",
            [$email, password_hash($otp, PASSWORD_BCRYPT), $expires]
        );
    }

    public static function verifyOtp(string $email, string $otp): bool
    {
        $row = static::db()->selectOne(
            "SELECT * FROM password_resets WHERE email = ? AND expires_at > NOW()",
            [$email]
        );
        return $row && password_verify($otp, $row['otp']);
    }

    public static function clearOtp(string $email): void
    {
        static::db()->delete('password_resets', 'email = ?', [$email]);
    }
}
