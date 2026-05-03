<?php

namespace App\Models;

class Notification extends Model
{
    protected static string $table = 'notifications';
    protected static bool $timestamps = false;

    public static function forUser(int $userId, bool $unreadOnly, int $page, int $perPage): array
    {
        $where  = "user_id = ? AND is_deleted = 0";
        $params = [$userId];

        if ($unreadOnly) {
            $where .= " AND is_read = 0";
        }

        return static::paginate($page, $perPage, $where, $params, 'created_at', 'DESC');
    }

    public static function unreadCount(int $userId): int
    {
        return static::count("user_id = ? AND is_read = 0 AND is_deleted = 0", [$userId]);
    }

    public static function markRead(int $id, int $userId): void
    {
        static::db()->update(
            'notifications',
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            'id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public static function markDeleted(int $id, int $userId): void
    {
        static::db()->update('notifications', ['is_deleted' => 1], 'id = ? AND user_id = ?', [$id, $userId]);
    }

    public static function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        return static::db()->insert('notifications', $data);
    }
}
