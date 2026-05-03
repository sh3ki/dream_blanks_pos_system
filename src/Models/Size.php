<?php

namespace App\Models;

class Size extends Model
{
    protected static string $table = 'sizes';
    protected static bool $softDelete = true;

    public static function allActive(): array
    {
        return static::db()->select(
            "SELECT * FROM sizes WHERE status = 'active' AND deleted_at IS NULL ORDER BY name"
        );
    }
}
