<?php

namespace App\Models;

class Setting extends Model
{
    protected static string $table = 'settings';
    protected static bool $timestamps = false;
    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $row = static::db()->selectOne("SELECT * FROM settings WHERE setting_key = ?", [$key]);
        if (!$row) return $default;

        $value = match ($row['setting_type']) {
            'integer' => (int)$row['setting_value'],
            'boolean' => (bool)$row['setting_value'],
            'json'    => json_decode($row['setting_value'], true),
            default   => $row['setting_value'],
        };

        self::$cache[$key] = $value;
        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $stringValue = is_array($value) ? json_encode($value) : (string)$value;

        static::db()->query(
            "INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()",
            [$key, $stringValue]
        );

        self::$cache[$key] = $value;
    }

    public static function allAsArray(): array
    {
        $rows   = static::db()->select("SELECT * FROM settings ORDER BY setting_key");
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }
}
